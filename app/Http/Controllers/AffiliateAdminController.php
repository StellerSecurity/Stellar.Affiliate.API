<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\AffiliateCampaign;
use App\Models\AffiliateClick;
use App\Models\AffiliateCommission;
use App\Models\AffiliateCommissionRule;
use App\Models\AffiliateCommissionStatusLog;
use App\Models\AffiliateSession;
use App\Models\Payout;
use App\Models\User;
use App\Services\AffiliateCommissionPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AffiliateAdminController extends Controller
{
    private function adminContext(Request $request): array
    {
        return [
            'currentAffiliate' => null,
            'isAdmin' => true,
            'adminRole' => $request->user()?->affiliateAdminRole(),
        ];
    }

    private function exportDateRange(Request $request): array
    {
        $fromInput = trim((string) $request->query('from'));
        $toInput = trim((string) $request->query('to'));
        $from = null;
        $to = null;

        if ($fromInput !== '') {
            try { $from = Carbon::parse($fromInput); } catch (\Throwable) { $fromInput = ''; }
        }
        if ($toInput !== '') {
            try { $to = Carbon::parse($toInput); } catch (\Throwable) { $toInput = ''; }
        }
        if ($from && $to && $to->lt($from)) {
            [$from, $to] = [$to, $from];
            [$fromInput, $toInput] = [$toInput, $fromInput];
        }

        return [$from, $to, $fromInput, $toInput];
    }

    private function applyCreatedAtRange($query, ?Carbon $from, ?Carbon $to)
    {
        if ($from) { $query->where('created_at', '>=', $from); }
        if ($to) { $query->where('created_at', '<=', $to); }
        return $query;
    }

    private function csvCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $text = (string) $value;
        $formulaCandidate = ltrim($text);

        if ($formulaCandidate !== '' && preg_match('/^[=+\-@]/', $formulaCandidate) === 1) {
            return "'".$text;
        }

        return $text;
    }

    private function streamCsv(string $filename, array $headers, callable $writeRows)
    {
        return response()->streamDownload(function () use ($headers, $writeRows) {
            echo "\xEF\xBB\xBF";
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, $headers, ',', '"', '');
            $writeRows($handle);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function affiliateExportFilename(Affiliate $affiliate, string $report): string
    {
        $code = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) $affiliate->public_code) ?: 'affiliate');

        return 'stellar-affiliate-'.$code.'-'.$report.'-'.now()->format('Y-m-d').'.csv';
    }

    private function campaignDestination(AffiliateCampaign $campaign): string
    {
        $stored = trim((string) $campaign->redirect_url);
        $product = (string) ($campaign->product ?: 'esim');

        return $stored !== ''
            ? $stored
            : trim((string) config('affiliate.products.'.$product.'.default_redirect_url', ''));
    }

    private function campaignTrackingUrl(Affiliate $affiliate, AffiliateCampaign $campaign): string
    {
        $params = array_filter([
            'src' => $campaign->source,
            'campaign' => $campaign->name,
            'sub1' => $campaign->sub_id1,
            'sub2' => $campaign->sub_id2,
            'product' => $campaign->product ?: 'esim',
            'redirect' => $this->campaignDestination($campaign),
        ], static fn ($value) => $value !== null && $value !== '');

        $url = route('affiliate.track.public', ['code' => $affiliate->public_code]);
        $query = http_build_query($params);

        return $query === '' ? $url : $url.'?'.$query;
    }

    private function requireProgramManager(Request $request): void
    {
        if (! $request->user()?->canManageAffiliateProgram()) {
            abort(403, 'You do not have permission to change program settings.');
        }
    }

    private function affiliatePortalUser(Affiliate $affiliate): ?User
    {
        if ($affiliate->external_user_id) {
            $linkedUser = User::query()->find((int) $affiliate->external_user_id);
            if ($linkedUser) {
                return $linkedUser;
            }
        }

        $email = mb_strtolower(trim((string) $affiliate->email));
        if ($email === '') {
            return null;
        }

        return User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
    }

    private function requireCommissionManager(Request $request): void
    {
        if (! $request->user()?->canManageAffiliateCommissions()) {
            abort(403, 'You do not have permission to change financial status.');
        }
    }

    private function requireRoleManager(Request $request): void
    {
        if (! $request->user()?->canManageAffiliateRoles()) {
            abort(403, 'Only super admins can manage admin roles.');
        }
    }

    public function startAffiliateView(Request $request, Affiliate $affiliate)
    {
        $this->requireRoleManager($request);

        if ($request->session()->has('affiliate_impersonation')) {
            return redirect()
                ->route('affiliate.dashboard')
                ->with('status', 'You are already viewing an affiliate workspace. Exit that view first.');
        }

        $adminUser = $request->user();
        $returnUrl = route('affiliate.admin.affiliates.show', $affiliate);

        $request->session()->put('affiliate_impersonation', [
            'affiliate_id' => (int) $affiliate->id,
            'started_by_user_id' => (int) $adminUser->id,
            'started_at' => now()->toIso8601String(),
            'return_url' => $returnUrl,
        ]);
        $request->session()->regenerate();

        Log::notice('Affiliate workspace view started.', [
            'admin_user_id' => (int) $adminUser->id,
            'affiliate_id' => (int) $affiliate->id,
            'affiliate_code' => (string) $affiliate->public_code,
        ]);

        return redirect()
            ->route('affiliate.dashboard')
            ->with('status', 'You are now viewing '.($affiliate->name ?: $affiliate->public_code).' as an affiliate.');
    }

    public function stopAffiliateView(Request $request)
    {
        $impersonation = $request->session()->get('affiliate_impersonation');
        $user = $request->user();

        if (! is_array($impersonation)) {
            return redirect()->route($user?->hasAffiliateAdminAccess()
                ? 'affiliate.admin.dashboard'
                : 'affiliate.dashboard');
        }

        $startedByUserId = (int) ($impersonation['started_by_user_id'] ?? 0);
        if (! $user || $startedByUserId !== (int) $user->id || $user->affiliateAdminRole() !== 'super_admin') {
            $request->session()->forget('affiliate_impersonation');
            abort(403, 'Invalid affiliate view session.');
        }

        $affiliateId = (int) ($impersonation['affiliate_id'] ?? 0);
        $affiliate = $affiliateId > 0 ? Affiliate::find($affiliateId) : null;
        $returnUrl = (string) ($impersonation['return_url'] ?? '');

        $request->session()->forget('affiliate_impersonation');
        $request->session()->regenerate();

        Log::notice('Affiliate workspace view ended.', [
            'admin_user_id' => (int) $user->id,
            'affiliate_id' => $affiliateId,
            'affiliate_code' => $affiliate?->public_code,
        ]);

        if ($returnUrl !== '' && str_starts_with($returnUrl, url('/'))) {
            return redirect()->to($returnUrl)->with('status', 'Returned to the admin center.');
        }

        if ($affiliate) {
            return redirect()
                ->route('affiliate.admin.affiliates.show', $affiliate)
                ->with('status', 'Returned to the admin center.');
        }

        return redirect()
            ->route('affiliate.admin.affiliates.index')
            ->with('status', 'Returned to the admin center.');
    }

    public function dashboard(Request $request, AffiliateCommissionPolicy $policy)
    {
        $from = now()->subDays(30);

        $totalAffiliates = Affiliate::count();
        $activeAffiliates = Affiliate::where('status', 'active')->count();
        $clicksLast30 = AffiliateClick::where('created_at', '>=', $from)->count();
        $validCommissionStatuses = ['pending', 'approved', 'paid_out'];
        $conversionsLast30 = AffiliateCommission::whereIn('status', $validCommissionStatuses)
            ->where('created_at', '>=', $from)
            ->count();
        $conversionRate = $clicksLast30 > 0 ? round(($conversionsLast30 / $clicksLast30) * 100, 2) : 0;

        $pendingCommission = AffiliateCommission::where('status', 'pending')->sum('amount');
        $approvedCommission = AffiliateCommission::where('status', 'approved')->sum('amount');
        $paidCommission = AffiliateCommission::where('status', 'paid_out')->sum('amount');

        $recentCommissions = AffiliateCommission::with('affiliate')->latest()->take(12)->get();

        $topAffiliates = AffiliateCommission::query()
            ->select('affiliate_id', DB::raw('COUNT(*) as conversions_count'), DB::raw('SUM(amount) as commission_total'))
            ->with('affiliate')
            ->whereIn('status', $validCommissionStatuses)
            ->where('created_at', '>=', $from)
            ->groupBy('affiliate_id')
            ->orderByDesc('commission_total')
            ->limit(8)
            ->get();

        $productPerformance = AffiliateCommission::query()
            ->select('product', DB::raw('COUNT(*) as conversions_count'), DB::raw('SUM(amount) as commission_total'))
            ->whereIn('status', $validCommissionStatuses)
            ->where('created_at', '>=', $from)
            ->groupBy('product')
            ->get()
            ->groupBy(fn ($row) => $policy->normalizeProduct($row->product))
            ->map(function ($rows, string $product) {
                return (object) [
                    'product' => $product,
                    'conversions_count' => $rows->sum('conversions_count'),
                    'commission_total' => $rows->sum('commission_total'),
                ];
            })
            ->sortByDesc('conversions_count')
            ->values();

        $vpnInitialRate = (float) $policy->globalRate('vpn', 'initial')['rate'];
        $vpnRecurringRate = (float) $policy->globalRate('vpn', 'recurring')['rate'];

        return view('admin.affiliate-dashboard', array_merge($this->adminContext($request), compact(
            'totalAffiliates',
            'activeAffiliates',
            'clicksLast30',
            'conversionsLast30',
            'conversionRate',
            'pendingCommission',
            'approvedCommission',
            'paidCommission',
            'recentCommissions',
            'topAffiliates',
            'productPerformance',
            'vpnInitialRate',
            'vpnRecurringRate'
        )));
    }

    public function affiliatesIndex(Request $request, AffiliateCommissionPolicy $policy)
    {
        $search = trim((string) $request->query('q'));
        $status = trim((string) $request->query('status'));

        $query = Affiliate::query()
            ->with(['commissionRules' => fn ($q) => $q
                ->where('product', 'esim')
                ->where('is_active', true)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')])
            ->withCount(['campaigns', 'clicks'])
            ->withCount(['commissions as conversions_count' => fn ($q) => $q->whereIn('status', ['pending', 'approved', 'paid_out'])])
            ->withSum(['commissions as earned_commission_total' => fn ($q) => $q->whereIn('status', ['pending', 'approved', 'paid_out'])], 'amount')
            ->orderByDesc('earned_commission_total')
            ->orderByDesc('conversions_count')
            ->orderByDesc('created_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('public_code', 'like', "%{$search}%");
            });
        }

        if (in_array($status, ['pending', 'active', 'banned'], true)) {
            $query->where('status', $status);
        }

        $affiliates = $query->paginate(30)->withQueryString();
        $globalEsimRate = (float) $policy->globalRate('esim', 'initial')['rate'];

        foreach ($affiliates as $affiliateRow) {
            $rule = $affiliateRow->commissionRules->first();
            $affiliateRow->setAttribute('esim_rate', $rule ? (float) $rule->rate : $globalEsimRate);
            $affiliateRow->setAttribute('esim_rate_source', $rule ? 'affiliate' : 'program_default');
        }

        return view('admin.affiliates', array_merge($this->adminContext($request), [
            'affiliates' => $affiliates,
            'search' => $search,
            'statusFilter' => $status,
        ]));
    }

    public function affiliateStore(Request $request)
    {
        $this->requireProgramManager($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'public_code' => ['nullable', 'string', 'max:50', 'unique:affiliates,public_code'],
            'status' => ['required', Rule::in(['pending', 'active', 'banned'])],
        ]);

        $email = isset($data['email']) ? strtolower(trim((string) $data['email'])) : null;
        $linkedUserId = $email ? User::where('email', $email)->value('id') : null;

        if ($linkedUserId && Affiliate::where('external_user_id', $linkedUserId)->exists()) {
            return back()->withErrors(['email' => 'That portal user is already linked to an affiliate.'])->withInput();
        }

        $publicCode = strtoupper(trim((string) ($data['public_code'] ?? '')));
        if ($publicCode === '') {
            do {
                $publicCode = strtoupper(Str::random(8));
            } while (Affiliate::where('public_code', $publicCode)->exists());
        }

        $affiliate = Affiliate::create([
            'external_user_id' => $linkedUserId ? (int) $linkedUserId : null,
            'name' => trim($data['name']),
            'email' => $email,
            'public_code' => $publicCode,
            'status' => $data['status'],
            'payout_currency' => 'EUR',
        ]);

        return redirect()->route('affiliate.admin.affiliates.show', $affiliate)->with('status', 'Affiliate created.');
    }

    public function affiliateShow(Request $request, Affiliate $affiliate, AffiliateCommissionPolicy $policy)
    {
        $affiliate->loadCount(['campaigns', 'clicks', 'sessions']);
        $affiliate->loadCount([
            'commissions as conversions_count' => fn ($q) => $q->whereIn('status', ['pending', 'approved', 'paid_out']),
        ]);

        $recentCommissions = $affiliate->commissions()->with('campaign')->latest()->take(25)->get();
        $campaigns = $affiliate->campaigns()
            ->withCount(['clicks', 'sessions'])
            ->withCount([
                'commissions as conversions_count' => fn ($q) => $q->whereIn('status', ['pending', 'approved', 'paid_out']),
            ])
            ->withSum([
                'commissions as commission_total' => fn ($q) => $q->whereIn('status', ['pending', 'approved', 'paid_out']),
            ], 'amount')
            ->latest()
            ->take(12)
            ->get();
        $recentClicks = $affiliate->clicks()->latest()->take(10)->get();
        $payouts = $affiliate->payouts()->latest()->take(10)->get();
        $rateMatrix = $policy->matrix((int) $affiliate->id);
        $portalUser = $this->affiliatePortalUser($affiliate);
        $portalUserProtected = (bool) $portalUser?->hasAffiliateAdminAccess();

        $totals = [
            'commission' => (float) $affiliate->commissions()->whereIn('status', ['pending', 'approved', 'paid_out'])->sum('amount'),
            'pending' => (float) $affiliate->commissions()->where('status', 'pending')->sum('amount'),
            'approved' => (float) $affiliate->commissions()->where('status', 'approved')->sum('amount'),
            'paid' => (float) $affiliate->commissions()->where('status', 'paid_out')->sum('amount'),
        ];

        return view('admin.affiliate-show', array_merge($this->adminContext($request), compact(
            'affiliate',
            'recentCommissions',
            'campaigns',
            'recentClicks',
            'payouts',
            'rateMatrix',
            'totals',
            'portalUser',
            'portalUserProtected'
        )));
    }

    public function affiliateConversionsExport(Request $request, Affiliate $affiliate)
    {
        $status = trim((string) $request->query('status'));
        $type = trim((string) $request->query('type'));
        $product = trim((string) $request->query('product'));
        $search = trim((string) $request->query('q'));
        $from = trim((string) $request->query('from'));
        $to = trim((string) $request->query('to'));

        $query = AffiliateCommission::query()
            ->with('campaign:id,name,source')
            ->where('affiliate_id', (int) $affiliate->id);

        if (in_array($status, ['pending', 'approved', 'paid_out', 'rejected'], true)) {
            $query->where('status', $status);
        }
        if (in_array($type, ['initial', 'recurring'], true)) {
            $query->where('type', $type);
        }
        if (in_array($product, ['esim', 'vpn', 'antivirus'], true)) {
            $query->where('product', $product);
        }
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('external_payment_id', 'like', "%{$search}%")
                    ->orWhereHas('campaign', function ($campaignQuery) use ($search) {
                        $campaignQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('source', 'like', "%{$search}%");
                    });
            });
        }
        if ($from !== '') {
            try {
                $query->where('created_at', '>=', Carbon::parse($from));
            } catch (\Throwable) {
            }
        }
        if ($to !== '') {
            try {
                $query->where('created_at', '<=', Carbon::parse($to));
            } catch (\Throwable) {
            }
        }

        $campaignCandidates = AffiliateCampaign::query()
            ->where('affiliate_id', (int) $affiliate->id)
            ->orderBy('id')
            ->limit(2)
            ->get(['id', 'name', 'source']);
        $singleCampaignFallback = $campaignCandidates->count() === 1
            ? $campaignCandidates->first()
            : null;

        return $this->streamCsv(
            $this->affiliateExportFilename($affiliate, 'conversions'),
            [
                'Date', 'Order ID', 'Campaign', 'Source', 'Product', 'Commission Type', 'Order Value', 'Currency',
                'Rate Decimal', 'Rate %', 'Commission', 'Status', 'Eligible Payout At', 'Payout ID',
            ],
            function ($handle) use ($query, $singleCampaignFallback) {
                $query->orderBy('id')->chunkById(500, function ($rows) use ($handle, $singleCampaignFallback) {
                    foreach ($rows as $commission) {
                        $campaign = $commission->campaign ?: $singleCampaignFallback;
                        $campaignName = $campaign?->name ?: 'Legacy / unattributed';
                        $campaignSource = $campaign?->source;

                        fputcsv($handle, array_map([$this, 'csvCell'], [
                            $commission->created_at?->format('Y-m-d H:i:s'),
                            $commission->getRawOriginal('order_id'),
                            $campaignName,
                            $campaignSource,
                            $commission->product,
                            $commission->type,
                            $commission->order_amount !== null ? number_format((float) $commission->order_amount, 2, '.', '') : null,
                            $commission->currency ?: 'EUR',
                            number_format((float) $commission->rate, 4, '.', ''),
                            number_format((float) $commission->rate * 100, 4, '.', ''),
                            number_format((float) $commission->amount, 6, '.', ''),
                            $commission->status,
                            $commission->eligible_payout_at?->format('Y-m-d H:i:s'),
                            $commission->payout_id,
                        ]), ',', '"', '');
                    }
                });
            }
        );
    }

    public function affiliateCampaignsExport(Request $request, Affiliate $affiliate)
    {
        $validCommissionStatuses = ['pending', 'approved', 'paid_out'];
        [$from, $to] = $this->exportDateRange($request);

        $range = function ($q) use ($from, $to) {
            $this->applyCreatedAtRange($q, $from, $to);
        };
        $commissionRange = function ($q) use ($validCommissionStatuses, $from, $to) {
            $q->whereIn('status', $validCommissionStatuses);
            $this->applyCreatedAtRange($q, $from, $to);
        };

        $query = AffiliateCampaign::query()
            ->where('affiliate_id', (int) $affiliate->id)
            ->withCount(['clicks' => $range])
            ->withCount(['sessions' => $range])
            ->withCount(['commissions as conversions_count' => $commissionRange])
            ->withSum(['commissions as commission_total' => $commissionRange], 'amount')
            ->withSum(['commissions as order_value_total' => $commissionRange], 'order_amount');

        return $this->streamCsv(
            $this->affiliateExportFilename($affiliate, 'campaigns'),
            [
                'Campaign ID', 'Campaign', 'Product', 'Source', 'Sub ID 1', 'Sub ID 2', 'Destination URL',
                'Clicks', 'Sessions', 'Conversions', 'Conversion Rate %', 'Order Value', 'Commission', 'Tracking URL', 'Created At',
            ],
            function ($handle) use ($query, $affiliate) {
                $query->orderBy('id')->chunkById(250, function ($rows) use ($handle, $affiliate) {
                    foreach ($rows as $campaign) {
                        $clicks = (int) ($campaign->clicks_count ?? 0);
                        $conversions = (int) ($campaign->conversions_count ?? 0);
                        $conversionRate = $clicks > 0 ? ($conversions / $clicks) * 100 : 0;

                        fputcsv($handle, array_map([$this, 'csvCell'], [
                            $campaign->id,
                            $campaign->name,
                            $campaign->product ?: 'esim',
                            $campaign->source,
                            $campaign->sub_id1,
                            $campaign->sub_id2,
                            $this->campaignDestination($campaign),
                            $clicks,
                            (int) ($campaign->sessions_count ?? 0),
                            $conversions,
                            number_format($conversionRate, 4, '.', ''),
                            number_format((float) ($campaign->order_value_total ?? 0), 2, '.', ''),
                            number_format((float) ($campaign->commission_total ?? 0), 6, '.', ''),
                            $this->campaignTrackingUrl($affiliate, $campaign),
                            $campaign->created_at?->format('Y-m-d H:i:s'),
                        ]), ',', '"', '');
                    }
                });
            }
        );
    }

    public function affiliateTrackingExport(Request $request, Affiliate $affiliate)
    {
        $from = trim((string) $request->query('from'));
        $to = trim((string) $request->query('to'));
        $query = AffiliateClick::query()
            ->with('campaign:id,name')
            ->where('affiliate_id', (int) $affiliate->id);

        if ($from !== '') {
            try {
                $query->where('created_at', '>=', Carbon::parse($from));
            } catch (\Throwable) {
            }
        }
        if ($to !== '') {
            try {
                $query->where('created_at', '<=', Carbon::parse($to));
            } catch (\Throwable) {
            }
        }

        return $this->streamCsv(
            $this->affiliateExportFilename($affiliate, 'tracking'),
            ['Date', 'Campaign', 'Source', 'Landing URL', 'Referrer', 'Session ID'],
            function ($handle) use ($query) {
                $query->orderBy('id')->chunkById(500, function ($rows) use ($handle) {
                    foreach ($rows as $click) {
                        fputcsv($handle, array_map([$this, 'csvCell'], [
                            $click->created_at?->format('Y-m-d H:i:s'),
                            $click->campaign?->name,
                            $click->source,
                            $click->landing_url,
                            $click->referrer,
                            $click->session_id,
                        ]), ',', '"', '');
                    }
                });
            }
        );
    }

    public function affiliatePayoutsExport(Request $request, Affiliate $affiliate)
    {
        $status = trim((string) $request->query('status'));
        $from = trim((string) $request->query('from'));
        $to = trim((string) $request->query('to'));
        $query = Payout::query()->where('affiliate_id', (int) $affiliate->id);

        if (in_array($status, ['pending', 'processing', 'paid', 'failed'], true)) {
            $query->where('status', $status);
        }
        if ($from !== '') {
            try {
                $query->where('created_at', '>=', Carbon::parse($from));
            } catch (\Throwable) {
            }
        }
        if ($to !== '') {
            try {
                $query->where('created_at', '<=', Carbon::parse($to));
            } catch (\Throwable) {
            }
        }

        return $this->streamCsv(
            $this->affiliateExportFilename($affiliate, 'payouts'),
            ['Created At', 'Amount', 'Currency', 'Status', 'Method', 'Reference', 'Paid At'],
            function ($handle) use ($query) {
                $query->orderBy('id')->chunkById(250, function ($rows) use ($handle) {
                    foreach ($rows as $payout) {
                        fputcsv($handle, array_map([$this, 'csvCell'], [
                            $payout->created_at?->format('Y-m-d H:i:s'),
                            number_format((float) $payout->amount, 6, '.', ''),
                            $payout->currency,
                            $payout->status,
                            $payout->method_type,
                            $payout->external_reference,
                            $payout->paid_at?->format('Y-m-d H:i:s'),
                        ]), ',', '"', '');
                    }
                });
            }
        );
    }

    public function affiliateUpdate(Request $request, Affiliate $affiliate)
    {
        $this->requireProgramManager($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', Rule::in(['pending', 'active', 'banned'])],
            'country' => ['nullable', 'string', 'size:2'],
            'payout_currency' => ['required', 'string', 'size:3'],
            'base_redirect_url' => ['nullable', 'url:http,https', 'max:2048'],
        ]);

        $affiliate->fill([
            'name' => trim($data['name']),
            'email' => isset($data['email']) ? strtolower(trim((string) $data['email'])) : null,
            'status' => $data['status'],
            'country' => isset($data['country']) ? strtoupper(trim((string) $data['country'])) : null,
            'payout_currency' => strtoupper($data['payout_currency']),
            'base_redirect_url' => $data['base_redirect_url'] ?? null,
        ])->save();

        return back()->with('status', 'Affiliate profile updated.');
    }

    public function affiliatePasswordUpdate(Request $request, Affiliate $affiliate)
    {
        $this->requireProgramManager($request);

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        $portalUser = $this->affiliatePortalUser($affiliate);

        if ($portalUser?->hasAffiliateAdminAccess()) {
            abort(403, 'Admin account passwords cannot be changed from an affiliate profile.');
        }

        if (! $portalUser) {
            $email = mb_strtolower(trim((string) $affiliate->email));
            if ($email === '') {
                return back()->withErrors([
                    'password' => 'Add an email address to the affiliate before creating portal login access.',
                ]);
            }

            $portalUser = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

            if ($portalUser?->hasAffiliateAdminAccess()) {
                abort(403, 'Admin account passwords cannot be changed from an affiliate profile.');
            }

            if (! $portalUser) {
                $portalUser = User::create([
                    'name' => trim((string) ($affiliate->name ?: $affiliate->public_code)),
                    'email' => $email,
                    'password' => Hash::make($data['password']),
                ]);
            }
        }

        $otherAffiliate = Affiliate::query()
            ->where('external_user_id', (int) $portalUser->id)
            ->where('id', '!=', (int) $affiliate->id)
            ->exists();

        if ($otherAffiliate) {
            return back()->withErrors([
                'password' => 'That portal user is already linked to another affiliate.',
            ]);
        }

        $portalUser->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->save();

        if ((int) $affiliate->external_user_id !== (int) $portalUser->id) {
            $affiliate->external_user_id = (int) $portalUser->id;
            $affiliate->save();
        }

        Log::notice('Affiliate portal password changed by administrator.', [
            'admin_user_id' => (int) $request->user()->id,
            'affiliate_id' => (int) $affiliate->id,
            'portal_user_id' => (int) $portalUser->id,
        ]);

        return back()->with('status', 'Affiliate portal password updated.');
    }

    public function affiliateRateUpdate(Request $request, Affiliate $affiliate, AffiliateCommissionPolicy $policy)
    {
        $this->requireProgramManager($request);

        $data = $request->validate([
            'product' => ['required', 'string', 'max:50'],
            'type' => ['nullable', Rule::in(['initial', 'recurring'])],
            'rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $product = $policy->normalizeProduct($data['product']);
        if ($product !== 'esim') {
            abort(422, 'Affiliate-specific rates are only supported for eSIM. VPN and Antivirus use the shared program rate.');
        }

        $rate = round(((float) $data['rate_percent']) / 100, 4);
        $policy->setAffiliateEsimRate((int) $affiliate->id, $rate, (int) $request->user()->id);

        return back()->with('status', 'eSIM rate updated.');
    }

    public function affiliateRateDelete(Request $request, Affiliate $affiliate, AffiliateCommissionPolicy $policy)
    {
        $this->requireProgramManager($request);

        $data = $request->validate([
            'product' => ['required', 'string', 'max:50'],
            'type' => ['nullable', Rule::in(['initial', 'recurring'])],
        ]);

        if ($policy->normalizeProduct($data['product']) !== 'esim') {
            abort(422, 'Only the eSIM affiliate rate can be reset individually.');
        }

        $rate = $policy->resetAffiliateEsimRate((int) $affiliate->id, (int) $request->user()->id);

        return back()->with('status', 'eSIM rate reset to '.number_format($rate * 100, 2).'%.');
    }

    public function ratesIndex(Request $request, AffiliateCommissionPolicy $policy)
    {
        $search = trim((string) $request->query('q'));
        $status = trim((string) $request->query('status'));

        $query = Affiliate::query()
            ->with(['commissionRules' => fn ($q) => $q
                ->where('product', 'esim')
                ->where('is_active', true)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')])
            ->withCount([
                'commissions as conversions_count' => fn ($q) => $q->whereIn('status', ['pending', 'approved', 'paid_out']),
            ])
            ->withSum([
                'commissions as earned_commission_total' => fn ($q) => $q->whereIn('status', ['pending', 'approved', 'paid_out']),
            ], 'amount')
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 WHEN status = 'pending' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->orderBy('public_code');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('public_code', 'like', "%{$search}%");
            });
        }

        if (in_array($status, ['pending', 'active', 'banned'], true)) {
            $query->where('status', $status);
        }

        $affiliates = $query->paginate(50)->withQueryString();
        $globalEsimRate = (float) $policy->globalRate('esim', 'initial')['rate'];

        foreach ($affiliates as $affiliateRow) {
            $rule = $affiliateRow->commissionRules->first();
            $affiliateRow->setAttribute('esim_rate', $rule ? (float) $rule->rate : $globalEsimRate);
            $affiliateRow->setAttribute('esim_rate_source', $rule ? 'affiliate' : 'program_default');
        }

        return view('admin.rates', array_merge($this->adminContext($request), [
            'globalRateMatrix' => $policy->matrix(),
            'affiliates' => $affiliates,
            'search' => $search,
            'statusFilter' => $status,
            'globalEsimRate' => $globalEsimRate,
        ]));
    }

    public function globalRateUpdate(Request $request, AffiliateCommissionPolicy $policy)
    {
        $this->requireProgramManager($request);

        $data = $request->validate([
            'product' => ['required', 'string', 'max:50'],
            'type' => ['nullable', Rule::in(['initial', 'recurring'])],
            'rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $product = $policy->normalizeProduct($data['product']);
        $type = ($data['type'] ?? 'initial') === 'recurring' ? 'recurring' : 'initial';
        $rate = round(((float) $data['rate_percent']) / 100, 4);

        $products = in_array($product, ['vpn', 'antivirus'], true)
            ? ['vpn', 'antivirus']
            : [$product];
        $types = $product === 'esim' ? ['initial', 'recurring'] : [$type];

        foreach ($products as $targetProduct) {
            foreach ($types as $targetType) {
                $rule = AffiliateCommissionRule::query()
                    ->whereNull('affiliate_id')
                    ->where('product', $targetProduct)
                    ->where('type', $targetType)
                    ->latest('id')
                    ->first();

                if (! $rule) {
                    $rule = new AffiliateCommissionRule([
                        'affiliate_id' => null,
                        'product' => $targetProduct,
                        'type' => $targetType,
                    ]);
                }

                $rule->fill([
                    'rate' => $rate,
                    'is_active' => true,
                    'updated_by_user_id' => $request->user()->id,
                ])->save();
            }
        }

        if ($product === 'esim') {
            return back()->with('status', 'Default eSIM rate updated.');
        }

        return back()->with('status', 'VPN and Antivirus rates updated.');
    }

    public function commissionsIndex(Request $request)
    {
        $status = trim((string) $request->query('status'));
        $type = trim((string) $request->query('type'));
        $product = trim((string) $request->query('product'));
        $affiliate = trim((string) $request->query('affiliate'));
        $search = trim((string) $request->query('q'));
        $dateFrom = trim((string) $request->query('date_from'));
        $dateTo = trim((string) $request->query('date_to'));

        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $query = AffiliateCommission::with(['affiliate', 'payout', 'statusLogs.changedBy', 'correctionLogs'])->orderByDesc('created_at');

        if (in_array($status, ['pending', 'approved', 'rejected', 'paid_out'], true)) {
            $query->where('status', $status);
        }
        if (in_array($type, ['initial', 'recurring'], true)) {
            $query->where('type', $type);
        }
        if ($product !== '') {
            $query->where('product', $product);
        }
        if ($affiliate !== '') {
            $query->whereHas('affiliate', fn ($q) => $q->where('public_code', 'like', "%{$affiliate}%"));
        }
        if ($dateFrom !== '') {
            $query->where('created_at', '>=', Carbon::parse($dateFrom));
        }
        if ($dateTo !== '') {
            $query->where('created_at', '<=', Carbon::parse($dateTo));
        }
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('external_payment_id', 'like', "%{$search}%")
                    ->orWhereHas('affiliate', function ($aq) use ($search) {
                        $aq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('public_code', 'like', "%{$search}%");
                    });
            });
        }

        $summary = [
            'pending' => (float) (clone $query)->where('status', 'pending')->sum('amount'),
            'approved' => (float) (clone $query)->where('status', 'approved')->sum('amount'),
            'paid_out' => (float) (clone $query)->where('status', 'paid_out')->sum('amount'),
            'rejected' => (float) (clone $query)->where('status', 'rejected')->sum('amount'),
        ];

        $commissions = $query->paginate(40)->withQueryString();
        $products = AffiliateCommission::query()->whereNotNull('product')->distinct()->orderBy('product')->pluck('product');

        return view('admin.commissions', array_merge($this->adminContext($request), compact(
            'commissions',
            'products',
            'summary',
            'status',
            'type',
            'product',
            'affiliate',
            'search',
            'dateFrom',
            'dateTo'
        )));
    }

    public function commissionStatusUpdate(Request $request, AffiliateCommission $commission)
    {
        $this->requireCommissionManager($request);

        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected', 'paid_out'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->changeCommissionStatus($request, $commission, $data['status'], $data['note'] ?? null);

        return back()->with('status', 'Commission status updated.');
    }

    public function commissionsBulkStatusUpdate(Request $request)
    {
        $this->requireCommissionManager($request);

        $data = $request->validate([
            'commission_ids' => ['required', 'array', 'min:1', 'max:100'],
            'commission_ids.*' => ['integer', 'exists:affiliate_commissions,id'],
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected', 'paid_out'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $commissions = AffiliateCommission::whereIn('id', $data['commission_ids'])->get();
        $isSuperAdmin = $request->user()?->affiliateAdminRole() === 'super_admin';
        $reversesPaidCommission = $commissions->contains(
            fn (AffiliateCommission $commission) => $commission->status === 'paid_out' && $data['status'] !== 'paid_out'
        );

        if ($reversesPaidCommission && ! $isSuperAdmin) {
            return back()->withErrors(['status' => 'Paid commissions are locked. Only a super admin can reverse them.']);
        }

        if ($reversesPaidCommission && strlen(trim((string) ($data['note'] ?? ''))) < 5) {
            return back()->withErrors(['note' => 'Add a note when reversing a paid commission.']);
        }

        DB::transaction(function () use ($request, $commissions, $data): void {
            foreach ($commissions as $commission) {
                $this->changeCommissionStatus($request, $commission, $data['status'], $data['note'] ?? null);
            }
        });

        return back()->with('status', $commissions->count().' commission(s) updated.');
    }

    private function changeCommissionStatus(Request $request, AffiliateCommission $commission, string $toStatus, ?string $note): void
    {
        $fromStatus = (string) $commission->status;
        if ($fromStatus === $toStatus) {
            return;
        }

        $isSuperAdmin = $request->user()?->affiliateAdminRole() === 'super_admin';
        if ($fromStatus === 'paid_out' && ! $isSuperAdmin) {
            abort(422, 'Paid commissions are locked. Only a super admin can reverse them.');
        }

        if ($fromStatus === 'paid_out' && $isSuperAdmin && strlen(trim((string) $note)) < 5) {
            abort(422, 'A note is required when reversing a paid commission.');
        }

        DB::transaction(function () use ($request, $commission, $fromStatus, $toStatus, $note) {
            $commission->status = $toStatus;

            if ($toStatus === 'pending') {
                $commission->approved_at = null;
                $commission->rejected_at = null;
                $commission->paid_out_at = null;
            } elseif ($toStatus === 'approved') {
                $commission->approved_at = $commission->approved_at ?: now();
                $commission->rejected_at = null;
                $commission->paid_out_at = null;
            } elseif ($toStatus === 'rejected') {
                $commission->rejected_at = now();
                $commission->approved_at = null;
                $commission->paid_out_at = null;
            } elseif ($toStatus === 'paid_out') {
                $commission->paid_out_at = now();
                $commission->approved_at = $commission->approved_at ?: now();
                $commission->rejected_at = null;
            }

            $commission->save();

            AffiliateCommissionStatusLog::create([
                'affiliate_commission_id' => $commission->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by_user_id' => $request->user()?->id,
                'note' => $note ? trim($note) : null,
            ]);
        });
    }

    public function campaignsIndex(Request $request, AffiliateCommissionPolicy $policy)
    {
        $search = trim((string) $request->query('q'));
        $validCommissionStatuses = ['pending', 'approved', 'paid_out'];
        $query = AffiliateCampaign::query()
            ->with('affiliate')
            ->withCount(['clicks', 'sessions'])
            ->withCount(['commissions as conversions_count' => fn ($q) => $q->whereIn('status', $validCommissionStatuses)])
            ->withSum(['commissions as commission_total' => fn ($q) => $q->whereIn('status', $validCommissionStatuses)], 'amount')
            ->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%")
                    ->orWhereHas('affiliate', fn ($aq) => $aq->where('public_code', 'like', "%{$search}%"));
            });
        }

        return view('admin.campaigns', array_merge($this->adminContext($request), [
            'campaigns' => $query->paginate(40)->withQueryString(),
            'search' => $search,
            'vpnInitialRate' => (float) $policy->globalRate('vpn', 'initial')['rate'],
            'vpnRecurringRate' => (float) $policy->globalRate('vpn', 'recurring')['rate'],
        ]));
    }

    public function trackingIndex(Request $request)
    {
        $affiliateCode = trim((string) $request->query('affiliate'));

        $clicks = AffiliateClick::with(['affiliate', 'campaign'])->latest();
        $sessions = AffiliateSession::with(['affiliate', 'campaign'])->latest();

        if ($affiliateCode !== '') {
            $clicks->whereHas('affiliate', fn ($q) => $q->where('public_code', 'like', "%{$affiliateCode}%"));
            $sessions->whereHas('affiliate', fn ($q) => $q->where('public_code', 'like', "%{$affiliateCode}%"));
        }

        return view('admin.tracking', array_merge($this->adminContext($request), [
            'clicks' => $clicks->paginate(30, ['*'], 'click_page')->withQueryString(),
            'sessions' => $sessions->paginate(30, ['*'], 'session_page')->withQueryString(),
            'affiliateCode' => $affiliateCode,
        ]));
    }

    public function payoutsIndex(Request $request)
    {
        $status = trim((string) $request->query('status'));
        $query = Payout::with('affiliate')->withCount('commissions')->latest();

        if (in_array($status, ['pending', 'processing', 'paid', 'failed'], true)) {
            $query->where('status', $status);
        }

        return view('admin.payouts', array_merge($this->adminContext($request), [
            'payouts' => $query->paginate(40)->withQueryString(),
            'statusFilter' => $status,
        ]));
    }

    public function payoutStatusUpdate(Request $request, Payout $payout)
    {
        $this->requireCommissionManager($request);

        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'processing', 'paid', 'failed'])],
            'external_reference' => ['nullable', 'string', 'max:255'],
        ]);

        $payout->status = $data['status'];
        $payout->external_reference = $data['external_reference'] ?? $payout->external_reference;
        $payout->paid_at = $data['status'] === 'paid' ? ($payout->paid_at ?: now()) : null;
        $payout->save();

        return back()->with('status', 'Payout status updated.');
    }

    public function usersIndex(Request $request)
    {
        $this->requireRoleManager($request);

        $users = User::query()
            ->orderByRaw('affiliate_admin_role IS NULL')
            ->orderBy('name')
            ->paginate(40);

        return view('admin.users', array_merge($this->adminContext($request), compact('users')));
    }

    public function userRoleUpdate(Request $request, User $user)
    {
        $this->requireRoleManager($request);

        if ($user->isEnvironmentAffiliateOwner()) {
            return back()->withErrors(['role' => 'This owner account is protected and cannot be changed here.']);
        }

        $data = $request->validate([
            'affiliate_admin_role' => ['nullable', Rule::in(['super_admin', 'admin', 'finance', 'analyst'])],
        ]);

        $newRole = $data['affiliate_admin_role'] ?: null;
        if ($user->is($request->user()) && $user->affiliateAdminRole() === 'super_admin' && $newRole !== 'super_admin') {
            $hasEnvironmentOwner = count((array) config('affiliate.admin_emails', [])) > 0;
            $otherSuperAdmins = User::query()
                ->where('id', '!=', $user->id)
                ->where('affiliate_admin_role', 'super_admin')
                ->exists();

            if (! $hasEnvironmentOwner && ! $otherSuperAdmins) {
                return back()->withErrors(['role' => 'Add another super admin before removing your own access.']);
            }
        }

        $user->affiliate_admin_role = $newRole;
        $user->save();

        return back()->with('status', 'Admin role updated.');
    }
}
