<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\AffiliateCampaign;
use App\Models\AffiliateClick;
use App\Models\AffiliateSession;
use App\Models\AffiliateCommission;
use App\Models\Payout as AffiliatePayout;
use App\Services\AffiliateOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AffiliatePortalController extends Controller
{
    private function resolvedAffiliate(Request $request): ?Affiliate
    {
        $affiliate = $request->attributes->get('affiliate');

        if ($affiliate instanceof Affiliate) {
            return $affiliate;
        }

        $user = $request->user();
        if (! $user) {
            return null;
        }

        // Try link by external_user_id
        $affiliate = Affiliate::query()
            ->where('external_user_id', $user->id)
            ->first();

        if ($affiliate) {
            $request->attributes->set('affiliate', $affiliate);
            return $affiliate;
        }

        // Fallback: match by email only if not linked yet, then auto-link
        $affiliate = Affiliate::query()
            ->whereNull('external_user_id')
            ->where('email', $user->email)
            ->first();

        if ($affiliate) {
            $affiliate->external_user_id = (int) $user->id;
            $affiliate->save();

            $request->attributes->set('affiliate', $affiliate);
            return $affiliate;
        }

        return null;
    }

    private function isAdmin(Request $request): bool
    {
        if ($request->session()->has('affiliate_impersonation')) {
            return false;
        }

        return (bool) $request->user()?->hasAffiliateAdminAccess();
    }

    private function productDefaultRedirect(string $product): string
    {
        $policy = app(\App\Services\AffiliateCommissionPolicy::class);
        $normalizedProduct = $policy->normalizeProduct($product);

        if (! in_array($normalizedProduct, ['esim', 'vpn', 'antivirus'], true)) {
            $normalizedProduct = 'esim';
        }

        return (string) config(
            'affiliate.products.'.$normalizedProduct.'.default_redirect_url',
            config('affiliate.products.esim.default_redirect_url')
        );
    }

    private function campaignDestination(AffiliateCampaign $campaign): string
    {
        $stored = trim((string) $campaign->redirect_url);

        return $stored !== ''
            ? $stored
            : $this->productDefaultRedirect((string) ($campaign->product ?: 'esim'));
    }

    private function campaignTrackingUrl(Affiliate $affiliate, ?AffiliateCampaign $campaign = null): string
    {
        $params = [];

        if ($campaign) {
            $params = array_filter([
                'src' => $campaign->source,
                'campaign' => $campaign->name,
                'sub1' => $campaign->sub_id1,
                'sub2' => $campaign->sub_id2,
                'product' => $campaign->product,
                'redirect' => $this->campaignDestination($campaign),
            ], static fn ($value) => $value !== null && $value !== '');
        }

        $url = route('affiliate.track.public', ['code' => $affiliate->public_code]);
        $query = http_build_query($params);

        return $query === '' ? $url : $url . '?' . $query;
    }


    private function pageSize(Request $request): int
    {
        $size = (int) $request->query('per_page', 25);

        return in_array($size, [25, 50, 100], true) ? $size : 25;
    }

    private function commissionFilters(Request $request): array
    {
        $status = trim((string) $request->query('status'));
        $type = trim((string) $request->query('type'));
        $product = trim((string) $request->query('product'));
        $search = trim((string) $request->query('q'));
        $from = trim((string) $request->query('from'));
        $to = trim((string) $request->query('to'));

        return [
            'status' => in_array($status, ['pending', 'approved', 'rejected', 'paid_out'], true) ? $status : '',
            'type' => in_array($type, ['initial', 'recurring'], true) ? $type : '',
            'product' => $product !== '' ? app(\App\Services\AffiliateCommissionPolicy::class)->normalizeProduct($product) : '',
            'q' => $search,
            'from' => $from,
            'to' => $to,
        ];
    }

    private function applyCommissionFilters($query, array $filters)
    {
        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['type'] !== '') {
            $query->where('type', $filters['type']);
        }

        if ($filters['product'] !== '') {
            $query->where('product', $filters['product']);
        }

        if ($filters['q'] !== '') {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('external_payment_id', 'like', "%{$search}%")
                    ->orWhereHas('campaign', function ($campaignQuery) use ($search) {
                        $campaignQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('source', 'like', "%{$search}%");
                    });
            });
        }

        if ($filters['from'] !== '') {
            try {
                $query->where('created_at', '>=', Carbon::parse($filters['from']));
            } catch (\Throwable) {
                // Invalid manual query-string values are ignored; browser controls send valid values.
            }
        }

        if ($filters['to'] !== '') {
            try {
                $query->where('created_at', '<=', Carbon::parse($filters['to']));
            } catch (\Throwable) {
                // Invalid manual query-string values are ignored; browser controls send valid values.
            }
        }

        return $query;
    }

    private function exportDateRange(Request $request): array
    {
        $fromInput = trim((string) $request->query('from'));
        $toInput = trim((string) $request->query('to'));
        $from = null;
        $to = null;

        if ($fromInput !== '') {
            try {
                $from = Carbon::parse($fromInput);
            } catch (\Throwable) {
                $fromInput = '';
            }
        }

        if ($toInput !== '') {
            try {
                $to = Carbon::parse($toInput);
            } catch (\Throwable) {
                $toInput = '';
            }
        }

        if ($from && $to && $to->lt($from)) {
            [$from, $to] = [$to, $from];
            [$fromInput, $toInput] = [$toInput, $fromInput];
        }

        return [$from, $to, $fromInput, $toInput];
    }

    private function applyCreatedAtRange($query, ?Carbon $from, ?Carbon $to)
    {
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

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

    private function analyticsPeriod(Request $request): array
    {
        $range = (string) $request->query('range', '30');
        if (! in_array($range, ['7', '30', '90', 'all'], true)) {
            $range = '30';
        }

        $from = match ($range) {
            '7' => now()->subDays(6)->startOfDay(),
            '30' => now()->subDays(29)->startOfDay(),
            '90' => now()->subDays(89)->startOfDay(),
            default => null,
        };

        return [
            'range' => $range,
            'from' => $from,
            'label' => match ($range) {
                '7' => 'Last 7 days',
                '30' => 'Last 30 days',
                '90' => 'Last 90 days',
                default => 'All time',
            },
        ];
    }

    public function onboarding(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $admin = $this->isAdmin($request);

        if ($admin) {
            return redirect()->route('affiliate.admin.dashboard');
        }

        $campaign = null;
        $trackingUrl = null;
        $onboardingCommissionRates = [];

        if ($currentAffiliate) {
            $campaign = AffiliateCampaign::query()
                ->where('affiliate_id', (int) $currentAffiliate->id)
                ->oldest('created_at')
                ->first();

            if ($campaign) {
                $trackingUrl = $this->campaignTrackingUrl($currentAffiliate, $campaign);
            }

            $policy = app(\App\Services\AffiliateCommissionPolicy::class);
            foreach (['esim', 'vpn', 'antivirus'] as $product) {
                $onboardingCommissionRates[$product] = [
                    'initial' => (float) $policy->effectiveRate((int) $currentAffiliate->id, $product, 'initial')['rate'],
                    'recurring' => (float) $policy->effectiveRate((int) $currentAffiliate->id, $product, 'recurring')['rate'],
                ];
            }
        }

        $step = ! $currentAffiliate ? 2 : (! $campaign ? 3 : 4);

        return view('affiliate-onboarding', [
            'currentAffiliate' => $currentAffiliate,
            'campaign' => $campaign,
            'trackingUrl' => $trackingUrl,
            'setupStep' => $step,
            'onboardingCommissionRates' => $onboardingCommissionRates,
            'productDefaultDestinations' => [
                'esim' => $this->productDefaultRedirect('esim'),
                'vpn' => $this->productDefaultRedirect('vpn'),
                'antivirus' => $this->productDefaultRedirect('antivirus'),
            ],
            'isAdmin' => false,
        ]);
    }

    /**
     * If no affiliate and not admin -> NO DATA (zeros/empty lists) + show CTA in UI.
     */
    public function dashboard(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $admin = $this->isAdmin($request);

        if ($admin) {
            return redirect()->route('affiliate.admin.dashboard');
        }

        $needsAffiliateSetup = false;

        if ($currentAffiliate) {
            $aid = (int) $currentAffiliate->id;

            $totalAffiliates = 1;
            $totalClicks = AffiliateClick::where('affiliate_id', $aid)->count();
            $totalSessions = AffiliateSession::where('affiliate_id', $aid)->count();
            $validCommissionStatuses = ['pending', 'approved', 'paid_out'];
            $totalEarnings = AffiliateCommission::where('affiliate_id', $aid)->whereIn('status', $validCommissionStatuses)->sum('amount');
            $totalConversions = AffiliateCommission::where('affiliate_id', $aid)->whereIn('status', $validCommissionStatuses)->count();
            $campaignCount = AffiliateCampaign::where('affiliate_id', $aid)->count();
            $primaryCampaign = AffiliateCampaign::where('affiliate_id', $aid)->oldest('created_at')->first();
            $quickTrackingUrl = $primaryCampaign ? $this->campaignTrackingUrl($currentAffiliate, $primaryCampaign) : null;
            $conversionRate = $totalClicks > 0 ? round(($totalConversions / $totalClicks) * 100, 2) : 0;

            $clicksLast30 = AffiliateClick::where('affiliate_id', $aid)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();

            $salesLast30 = AffiliateCommission::where('affiliate_id', $aid)
                ->whereIn('status', $validCommissionStatuses)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();

            $pendingCommission = AffiliateCommission::where('affiliate_id', $aid)
                ->where('status', 'pending')
                ->sum('amount');

            $approvedCommission = AffiliateCommission::where('affiliate_id', $aid)
                ->where('status', 'approved')
                ->sum('amount');

            $paidCommission = AffiliateCommission::where('affiliate_id', $aid)
                ->where('status', 'paid_out')
                ->sum('amount');

            $latestSales = AffiliateCommission::with('affiliate')
                ->where('affiliate_id', $aid)
                ->latest()
                ->take(10)
                ->get();

            return view('affiliate-dashboard', compact(
                'totalAffiliates',
                'totalClicks',
                'totalSessions',
                'totalEarnings',
                'totalConversions',
                'clicksLast30',
                'salesLast30',
                'pendingCommission',
                'approvedCommission',
                'paidCommission',
                'latestSales',
                'currentAffiliate',
                'needsAffiliateSetup',
                'admin',
                'campaignCount',
                'primaryCampaign',
                'quickTrackingUrl',
                'conversionRate'
            ));
        }

        // Non-affiliate user: show NOTHING (privacy)
        $needsAffiliateSetup = true;

        $totalAffiliates = 0;
        $totalClicks = 0;
        $totalSessions = 0;
        $totalEarnings = 0;
        $totalConversions = 0;

        $clicksLast30 = 0;
        $salesLast30 = 0;

        $pendingCommission = 0;
        $approvedCommission = 0;
        $paidCommission = 0;

        $latestSales = collect();
        $campaignCount = 0;
        $primaryCampaign = null;
        $quickTrackingUrl = null;
        $conversionRate = 0;

        return view('affiliate-dashboard', compact(
            'totalAffiliates',
            'totalClicks',
            'totalSessions',
            'totalEarnings',
            'totalConversions',
            'clicksLast30',
            'salesLast30',
            'pendingCommission',
            'approvedCommission',
            'paidCommission',
            'latestSales',
            'currentAffiliate',
            'needsAffiliateSetup',
            'admin',
            'campaignCount',
            'primaryCampaign',
            'quickTrackingUrl',
            'conversionRate'
        ));
    }

    /**
     * Keep the old profile URL useful without exposing a duplicate workspace.
     */
    public function affiliatesIndex(Request $request)
    {
        if ($this->isAdmin($request)) {
            return redirect()->route('affiliate.admin.affiliates.index');
        }

        if (! $this->resolvedAffiliate($request)) {
            return redirect()->route('affiliate.onboarding');
        }

        return redirect()->route('affiliate.settings');
    }

    /**
     * Create affiliate:
     * - Normal user creates ONE affiliate linked to themselves (external_user_id = auth user id)
     * - Admin can create unlinked affiliate by email (optional)
     */
    public function affiliatesStore(Request $request)
    {
        $admin = $this->isAdmin($request);
        $user = $request->user();

        if ($admin && ! $user?->canManageAffiliateProgram()) {
            abort(403, 'You do not have permission to create affiliates.');
        }

        if (! $user) {
            abort(401, 'Please log in.');
        }

        // If non-admin already has an affiliate, stop.
        $existing = Affiliate::where('external_user_id', $user->id)->first();
        if (! $admin && $existing) {
            return redirect()
                ->route('affiliate.onboarding')
                ->with('status', 'Your affiliate profile is ready. Continue setup.');
        }

        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['nullable', 'email', 'max:255'],
            'public_code'       => ['nullable', 'string', 'max:50', 'unique:affiliates,public_code'],
            'base_redirect_url' => ['nullable', 'url:http,https', 'max:2048'],
            'is_active'         => ['nullable', 'boolean'],
        ]);

        if (empty($data['public_code'])) {
            do {
                $data['public_code'] = strtoupper(Str::random(8));
            } while (Affiliate::where('public_code', $data['public_code'])->exists());
        }

        $isActive = $admin ? $request->boolean('is_active') : true;

        $affiliate = new Affiliate();
        $affiliate->name = $data['name'];

        // Email rules:
        // - If normal user: always prefer auth user email
        // - If admin: allow provided email (for later linking)
        if (! $admin) {
            $affiliate->email = (string) $user->email;
            $affiliate->external_user_id = (int) $user->id;
        } else {
            $affiliate->email = $data['email'] ?? null;

            // If admin provides an email that matches a user, link it
            if (! empty($affiliate->email)) {
                $linkedUserId = \App\Models\User::where('email', $affiliate->email)->value('id');
                $affiliate->external_user_id = $linkedUserId ? (int) $linkedUserId : null;
            }
        }

        $affiliate->public_code = $data['public_code'];
        $affiliate->base_redirect_url = $data['base_redirect_url'] ?? null;
        $affiliate->status = $isActive ? 'active' : 'banned';

        $affiliate->save();

        // Attach to request for this session navigation
        $request->attributes->set('affiliate', $affiliate);

        if (! $admin) {
            return redirect()
                ->route('affiliate.onboarding')
                ->with('status', 'Profile created. Create your first campaign.');
        }

        return redirect()
            ->route('affiliate.admin.affiliates.index')
            ->with('status', 'Affiliate created.');
    }

    public function campaignsIndex(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $admin = $this->isAdmin($request);

        if ($admin) {
            return redirect()->route('affiliate.admin.campaigns.index');
        }

        if (! $currentAffiliate) {
            return redirect()->route('affiliate.onboarding');
        }

        $search = trim((string) $request->query('q'));
        $productFilter = trim((string) $request->query('product'));
        $sourceFilter = trim((string) $request->query('source'));
        $pageSize = $this->pageSize($request);
        $validCommissionStatuses = ['pending', 'approved', 'paid_out'];

        $query = AffiliateCampaign::query()
            ->with('affiliate')
            ->withCount('clicks')
            ->withCount('sessions')
            ->withCount(['commissions as conversions_count' => fn ($q) => $q->whereIn('status', $validCommissionStatuses)])
            ->withSum(['commissions as commission_total' => fn ($q) => $q->whereIn('status', $validCommissionStatuses)], 'amount')
            ->withSum(['commissions as order_value_total' => fn ($q) => $q->whereIn('status', $validCommissionStatuses)], 'order_amount')
            ->where('affiliate_id', (int) $currentAffiliate->id)
            ->orderByDesc('created_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%")
                    ->orWhere('sub_id1', 'like', "%{$search}%")
                    ->orWhere('sub_id2', 'like', "%{$search}%");
            });
        }

        if (in_array($productFilter, ['esim', 'vpn', 'antivirus'], true)) {
            $query->where('product', $productFilter);
        } else {
            $productFilter = '';
        }

        if (in_array($sourceFilter, ['youtube', 'instagram', 'tiktok', 'blog', 'newsletter', 'other'], true)) {
            $query->where('source', $sourceFilter);
        } else {
            $sourceFilter = '';
        }

        $campaigns = $query->paginate($pageSize)->withQueryString();
        $affiliates = Affiliate::where('id', (int) $currentAffiliate->id)->get(['id', 'public_code']);

        $policy = app(\App\Services\AffiliateCommissionPolicy::class);
        $campaignCommissionRates = [];
        foreach (['esim', 'vpn', 'antivirus'] as $product) {
            $initial = $policy->effectiveRate((int) $currentAffiliate->id, $product, 'initial');
            $recurring = $policy->effectiveRate((int) $currentAffiliate->id, $product, 'recurring');

            $campaignCommissionRates[$product] = [
                'initial' => (float) $initial['rate'],
                'recurring' => (float) $recurring['rate'],
            ];
        }

        return view('affiliate-campaigns', [
            'campaigns' => $campaigns,
            'affiliates' => $affiliates,
            'search' => $search,
            'currentProductFilter' => $productFilter,
            'currentSourceFilter' => $sourceFilter,
            'currentPerPage' => $pageSize,
            'currentAffiliate' => $currentAffiliate,
            'isAdmin' => false,
            'campaignCommissionRates' => $campaignCommissionRates,
            'productDefaultDestinations' => [
                'esim' => $this->productDefaultRedirect('esim'),
                'vpn' => $this->productDefaultRedirect('vpn'),
                'antivirus' => $this->productDefaultRedirect('antivirus'),
            ],
        ]);
    }

    public function campaignsExport(Request $request)
    {
        $affiliate = $this->resolvedAffiliate($request);
        if (! $affiliate || $this->isAdmin($request)) {
            abort(404);
        }

        $search = trim((string) $request->query('q'));
        $product = trim((string) $request->query('product'));
        $source = trim((string) $request->query('source'));
        $validStatuses = ['pending', 'approved', 'paid_out'];
        [$from, $to] = $this->exportDateRange($request);

        $range = function ($q) use ($from, $to) {
            $this->applyCreatedAtRange($q, $from, $to);
        };
        $commissionRange = function ($q) use ($validStatuses, $from, $to) {
            $q->whereIn('status', $validStatuses);
            $this->applyCreatedAtRange($q, $from, $to);
        };

        $query = AffiliateCampaign::query()
            ->where('affiliate_id', (int) $affiliate->id)
            ->withCount(['clicks' => $range])
            ->withCount(['sessions' => $range])
            ->withCount(['commissions as conversions_count' => $commissionRange])
            ->withSum(['commissions as commission_total' => $commissionRange], 'amount')
            ->withSum(['commissions as order_value_total' => $commissionRange], 'order_amount');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%")
                    ->orWhere('sub_id1', 'like', "%{$search}%")
                    ->orWhere('sub_id2', 'like', "%{$search}%");
            });
        }
        if (in_array($product, ['esim', 'vpn', 'antivirus'], true)) {
            $query->where('product', $product);
        }
        if (in_array($source, ['youtube', 'instagram', 'tiktok', 'blog', 'newsletter', 'other'], true)) {
            $query->where('source', $source);
        }

        $filename = 'stellar-affiliate-campaigns-'.strtolower($affiliate->public_code).'-'.now()->format('Y-m-d').'.csv';

        return $this->streamCsv($filename, [
            'Campaign ID', 'Campaign', 'Product', 'Source', 'Sub ID 1', 'Sub ID 2', 'Destination URL',
            'Clicks', 'Sessions', 'Conversions', 'Conversion Rate %', 'Order Value', 'Commission', 'Tracking URL', 'Created At',
        ], function ($handle) use ($query, $affiliate) {
            $query->orderBy('id')->chunkById(250, function ($rows) use ($handle, $affiliate) {
                foreach ($rows as $campaign) {
                    $clicks = (int) ($campaign->clicks_count ?? 0);
                    $conversions = (int) ($campaign->conversions_count ?? 0);
                    $rate = $clicks > 0 ? ($conversions / $clicks) * 100 : 0;
                    fputcsv($handle, array_map([$this, 'csvCell'], [
                        $campaign->id,
                        $campaign->name,
                        $campaign->product,
                        $campaign->source,
                        $campaign->sub_id1,
                        $campaign->sub_id2,
                        $this->campaignDestination($campaign),
                        $clicks,
                        (int) ($campaign->sessions_count ?? 0),
                        $conversions,
                        number_format($rate, 4, '.', ''),
                        number_format((float) ($campaign->order_value_total ?? 0), 2, '.', ''),
                        number_format((float) ($campaign->commission_total ?? 0), 6, '.', ''),
                        $this->campaignTrackingUrl($affiliate, $campaign),
                        $campaign->created_at?->format('Y-m-d H:i:s'),
                    ]), ',', '"', '');
                }
            });
        });
    }

    public function campaignsStore(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $admin = $this->isAdmin($request);

        if ($admin && ! $request->user()?->canManageAffiliateProgram()) {
            abort(403, 'You do not have permission to create campaigns.');
        }

        if (! $currentAffiliate && ! $admin) {
            return redirect()
                ->route('affiliate.onboarding')
                ->with('status', 'Complete your affiliate profile first.');
        }

        $rules = [
            'affiliate_id' => $admin ? ['required', 'exists:affiliates,id'] : ['nullable'],
            'name'         => ['required', 'string', 'max:255'],
            'source'       => ['required', Rule::in(['youtube', 'instagram', 'tiktok', 'blog', 'newsletter', 'other'])],
            'sub_id1'      => ['nullable', 'string', 'max:255'],
            'sub_id2'      => ['nullable', 'string', 'max:255'],
            'product'      => ['nullable', 'string', Rule::in(['esim', 'vpn', 'antivirus'])],
            'redirect_url' => ['nullable', 'url:http,https', 'max:2048'],
        ];

        $data = $request->validate($rules);

        if ($currentAffiliate) {
            $data['affiliate_id'] = (int) $currentAffiliate->id;
        }

        $data['name'] = trim($data['name']);
        $data['source'] = strtolower(trim((string) ($data['source'] ?? ''))) ?: 'other';

        $policy = app(\App\Services\AffiliateCommissionPolicy::class);
        $data['product'] = $policy->normalizeProduct((string) ($data['product'] ?? 'esim'));
        $resolvedRate = $policy->effectiveRate((int) $data['affiliate_id'], $data['product'], 'initial');
        $data['commission_rate'] = (float) $resolvedRate['rate'];
        $data['redirect_url'] = trim((string) ($data['redirect_url'] ?? ''))
            ?: $this->productDefaultRedirect($data['product']);

        $duplicate = AffiliateCampaign::query()
            ->where('affiliate_id', (int) $data['affiliate_id'])
            ->where('name', $data['name'])
            ->exists();

        if ($duplicate) {
            return back()
                ->withErrors(['name' => 'You already have a campaign with this name.'])
                ->withInput();
        }

        AffiliateCampaign::create($data);

        if (! $admin && $request->input('return_to') === 'onboarding') {
            return redirect()
                ->route('affiliate.onboarding')
                ->with('status', 'Campaign created. Your tracking link is ready.');
        }

        return redirect()
            ->route('affiliate.campaigns.index')
            ->with('status', 'Campaign created.');
    }

    public function campaignUpdate(Request $request, AffiliateCampaign $campaign)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $admin = $this->isAdmin($request);

        if ($admin && ! $request->user()?->canManageAffiliateProgram()) {
            abort(403, 'You do not have permission to update campaigns.');
        }

        if (! $admin && (! $currentAffiliate || (int) $campaign->affiliate_id !== (int) $currentAffiliate->id)) {
            abort(404);
        }

        $affiliateId = (int) $campaign->affiliate_id;
        $data = $request->validate([
            'source' => ['required', Rule::in(['youtube', 'instagram', 'tiktok', 'blog', 'newsletter', 'other'])],
            'product' => ['required', Rule::in(['esim', 'vpn', 'antivirus'])],
            'redirect_url' => ['nullable', 'url:http,https', 'max:2048'],
            'sub_id1' => ['nullable', 'string', 'max:255'],
            'sub_id2' => ['nullable', 'string', 'max:255'],
        ]);

        $policy = app(\App\Services\AffiliateCommissionPolicy::class);
        $product = $policy->normalizeProduct((string) $data['product']);
        $resolvedRate = $policy->effectiveRate($affiliateId, $product, 'initial');

        $subId1 = trim((string) ($data['sub_id1'] ?? ''));
        $subId2 = trim((string) ($data['sub_id2'] ?? ''));

        $campaign->fill([
            'source' => $data['source'],
            'product' => $product,
            'commission_rate' => (float) $resolvedRate['rate'],
            'redirect_url' => trim((string) ($data['redirect_url'] ?? '')) ?: $this->productDefaultRedirect($product),
            'sub_id1' => $subId1 !== '' ? $subId1 : null,
            'sub_id2' => $subId2 !== '' ? $subId2 : null,
        ])->save();

        return back()->with('status', 'Campaign updated.');
    }

    public function campaignDestinationUpdate(Request $request, AffiliateCampaign $campaign)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $admin = $this->isAdmin($request);

        if ($admin && ! $request->user()?->canManageAffiliateProgram()) {
            abort(403, 'You do not have permission to update campaigns.');
        }

        if (! $admin) {
            if (! $currentAffiliate || (int) $campaign->affiliate_id !== (int) $currentAffiliate->id) {
                abort(404);
            }
        }

        $data = $request->validate([
            'redirect_url' => ['nullable', 'url:http,https', 'max:2048'],
        ]);

        $campaign->redirect_url = trim((string) ($data['redirect_url'] ?? ''))
            ?: $this->productDefaultRedirect((string) ($campaign->product ?: 'esim'));
        $campaign->save();

        return back()->with('status', 'Destination updated.');
    }

    public function analytics(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $admin = $this->isAdmin($request);

        if ($admin) {
            return redirect()->route('affiliate.admin.dashboard');
        }

        if (! $currentAffiliate) {
            return redirect()->route('affiliate.onboarding');
        }

        $period = $this->analyticsPeriod($request);
        $from = $period['from'];
        $aid = (int) $currentAffiliate->id;
        $validStatuses = ['pending', 'approved', 'paid_out'];

        $clicksQ = AffiliateClick::query()->where('affiliate_id', $aid);
        $sessionsQ = AffiliateSession::query()->where('affiliate_id', $aid);
        $salesQ = AffiliateCommission::query()->where('affiliate_id', $aid)->whereIn('status', $validStatuses);

        if ($from) {
            $clicksQ->where('created_at', '>=', $from);
            $sessionsQ->where('created_at', '>=', $from);
            $salesQ->where('created_at', '>=', $from);
        }

        $clicksPeriod = $clicksQ->count();
        $sessionsPeriod = $sessionsQ->count();
        $salesPeriod = $salesQ->count();
        $commissionPeriod = (clone $salesQ)->sum('amount');
        $orderValuePeriod = (clone $salesQ)->sum('order_amount');
        $conversionRate = $clicksPeriod > 0 ? round(($salesPeriod / $clicksPeriod) * 100, 2) : 0;
        $epc = $clicksPeriod > 0 ? $commissionPeriod / $clicksPeriod : 0;
        $averageOrderValue = $salesPeriod > 0 ? $orderValuePeriod / $salesPeriod : 0;

        $unattributedClicksQuery = AffiliateClick::query()
            ->where('affiliate_id', $aid)
            ->whereNull('campaign_id');
        $unattributedSalesQuery = AffiliateCommission::query()
            ->where('affiliate_id', $aid)
            ->whereNull('campaign_id')
            ->whereIn('status', $validStatuses);
        if ($from) {
            $unattributedClicksQuery->where('created_at', '>=', $from);
            $unattributedSalesQuery->where('created_at', '>=', $from);
        }
        $unattributedClicks = $unattributedClicksQuery->count();
        $unattributedConversions = $unattributedSalesQuery->count();
        $unattributedOrderValue = (clone $unattributedSalesQuery)->sum('order_amount');
        $unattributedCommission = (clone $unattributedSalesQuery)->sum('amount');

        $topCampaigns = AffiliateCampaign::query()
            ->where('affiliate_id', $aid)
            ->withCount(['clicks as period_clicks_count' => function ($q) use ($from) {
                if ($from) {
                    $q->where('created_at', '>=', $from);
                }
            }])
            ->withCount(['commissions as period_conversions_count' => function ($q) use ($from, $validStatuses) {
                $q->whereIn('status', $validStatuses);
                if ($from) {
                    $q->where('created_at', '>=', $from);
                }
            }])
            ->withSum(['commissions as period_commission_total' => function ($q) use ($from, $validStatuses) {
                $q->whereIn('status', $validStatuses);
                if ($from) {
                    $q->where('created_at', '>=', $from);
                }
            }], 'amount')
            ->withSum(['commissions as period_order_value_total' => function ($q) use ($from, $validStatuses) {
                $q->whereIn('status', $validStatuses);
                if ($from) {
                    $q->where('created_at', '>=', $from);
                }
            }], 'order_amount')
            ->get()
            ->sortByDesc(fn ($campaign) => (float) ($campaign->period_commission_total ?? 0))
            ->take(10)
            ->values();

        // Keep charts readable while the headline metrics can cover 90 days or all time.
        $chartFrom = $from && $from->greaterThan(now()->subDays(29)->startOfDay())
            ? $from->copy()
            : now()->subDays(29)->startOfDay();

        $dailyClicks = AffiliateClick::select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as clicks'))
            ->where('affiliate_id', $aid)
            ->where('created_at', '>=', $chartFrom)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $dailySales = AffiliateCommission::select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as sales'))
            ->where('affiliate_id', $aid)
            ->whereIn('status', $validStatuses)
            ->where('created_at', '>=', $chartFrom)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $daily = [];
        $cursor = $chartFrom->copy();
        while ($cursor->lte(now())) {
            $key = $cursor->toDateString();
            $daily[] = [
                'day' => $key,
                'clicks' => $dailyClicks[$key]->clicks ?? 0,
                'sales' => $dailySales[$key]->sales ?? 0,
            ];
            $cursor->addDay();
        }

        $recentConversionsQ = AffiliateCommission::with('campaign')
            ->where('affiliate_id', $aid)
            ->whereIn('status', $validStatuses)
            ->latest();
        if ($from) {
            $recentConversionsQ->where('created_at', '>=', $from);
        }

        return view('affiliate-analytics', [
            'clicksPeriod' => $clicksPeriod,
            'sessionsPeriod' => $sessionsPeriod,
            'salesPeriod' => $salesPeriod,
            'commissionPeriod' => $commissionPeriod,
            'orderValuePeriod' => $orderValuePeriod,
            'conversionRate' => $conversionRate,
            'epc' => $epc,
            'averageOrderValue' => $averageOrderValue,
            'topCampaigns' => $topCampaigns,
            'unattributedClicks' => $unattributedClicks,
            'unattributedConversions' => $unattributedConversions,
            'unattributedOrderValue' => $unattributedOrderValue,
            'unattributedCommission' => $unattributedCommission,
            'recentConversions' => $recentConversionsQ->take(10)->get(),
            'daily' => $daily,
            'chartLabel' => $chartFrom->toDateString() === now()->subDays(29)->startOfDay()->toDateString() ? 'Last 30 days' : $period['label'],
            'currentRange' => $period['range'],
            'rangeLabel' => $period['label'],
            'periodFromInput' => $from?->format('Y-m-d\TH:i'),
            'currentAffiliate' => $currentAffiliate,
            'isAdmin' => false,
            'needsAffiliateSetup' => false,
        ]);
    }

    public function clicksExport(Request $request)
    {
        $affiliate = $this->resolvedAffiliate($request);
        if (! $affiliate || $this->isAdmin($request)) {
            abort(404);
        }

        [$explicitFrom, $explicitTo, $fromInput, $toInput] = $this->exportDateRange($request);
        $query = AffiliateClick::query()
            ->with('campaign:id,name')
            ->where('affiliate_id', (int) $affiliate->id);

        if ($fromInput !== '' || $toInput !== '') {
            $this->applyCreatedAtRange($query, $explicitFrom, $explicitTo);
        } else {
            $period = $this->analyticsPeriod($request);
            if ($period['from']) {
                $query->where('created_at', '>=', $period['from']);
            }
        }

        $filename = 'stellar-affiliate-traffic-'.strtolower($affiliate->public_code).'-'.now()->format('Y-m-d').'.csv';

        return $this->streamCsv($filename, [
            'Date', 'Campaign', 'Source', 'Landing URL', 'Referrer', 'Session ID',
        ], function ($handle) use ($query) {
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
        });
    }

    public function payouts(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $admin = $this->isAdmin($request);

        if ($admin) {
            return redirect()->route('affiliate.admin.payouts.index');
        }

        if (! $currentAffiliate) {
            return redirect()->route('affiliate.onboarding');
        }

        $status = trim((string) $request->query('status'));
        $from = trim((string) $request->query('from'));
        $to = trim((string) $request->query('to'));
        $pageSize = $this->pageSize($request);
        $validPayoutStatuses = ['pending', 'processing', 'paid', 'failed'];

        $query = AffiliatePayout::with('affiliate')
            ->where('affiliate_id', (int) $currentAffiliate->id)
            ->orderByDesc('created_at');

        if (in_array($status, $validPayoutStatuses, true)) {
            $query->where('status', $status);
        } else {
            $status = '';
        }

        if ($from !== '') {
            try {
                $query->where('created_at', '>=', Carbon::parse($from));
            } catch (\Throwable) {
                $from = '';
            }
        }
        if ($to !== '') {
            try {
                $query->where('created_at', '<=', Carbon::parse($to));
            } catch (\Throwable) {
                $to = '';
            }
        }

        $payouts = $query->paginate($pageSize)->withQueryString();
        $aid = (int) $currentAffiliate->id;
        $availableCommission = AffiliateCommission::where('affiliate_id', $aid)->where('status', 'approved')->sum('amount');
        $pendingCommission = AffiliateCommission::where('affiliate_id', $aid)->where('status', 'pending')->sum('amount');
        $paidCommission = AffiliateCommission::where('affiliate_id', $aid)->where('status', 'paid_out')->sum('amount');
        $lastPayout = AffiliatePayout::where('affiliate_id', $aid)->where('status', 'paid')->orderByDesc('created_at')->first();

        return view('affiliate-payouts', [
            'payouts' => $payouts,
            'availableCommission' => $availableCommission,
            'pendingCommission' => $pendingCommission,
            'paidCommission' => $paidCommission,
            'lastPayout' => $lastPayout,
            'currentStatusFilter' => $status,
            'currentFromFilter' => $from,
            'currentToFilter' => $to,
            'currentPerPage' => $pageSize,
            'currentAffiliate' => $currentAffiliate,
            'isAdmin' => false,
            'needsAffiliateSetup' => false,
        ]);
    }

    public function payoutsExport(Request $request)
    {
        $affiliate = $this->resolvedAffiliate($request);
        if (! $affiliate || $this->isAdmin($request)) {
            abort(404);
        }

        $status = trim((string) $request->query('status'));
        $from = trim((string) $request->query('from'));
        $to = trim((string) $request->query('to'));
        $query = AffiliatePayout::query()->where('affiliate_id', (int) $affiliate->id);

        if (in_array($status, ['pending', 'processing', 'paid', 'failed'], true)) {
            $query->where('status', $status);
        }
        if ($from !== '') {
            try { $query->where('created_at', '>=', Carbon::parse($from)); } catch (\Throwable) {}
        }
        if ($to !== '') {
            try { $query->where('created_at', '<=', Carbon::parse($to)); } catch (\Throwable) {}
        }

        $filename = 'stellar-affiliate-payouts-'.strtolower($affiliate->public_code).'-'.now()->format('Y-m-d').'.csv';

        return $this->streamCsv($filename, [
            'Created At', 'Amount', 'Currency', 'Status', 'Method', 'Reference', 'Paid At',
        ], function ($handle) use ($query) {
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
        });
    }

    public function sales(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $admin = $this->isAdmin($request);

        if ($admin) {
            return redirect()->route('affiliate.admin.commissions.index');
        }

        if (! $currentAffiliate) {
            return redirect()->route('affiliate.onboarding');
        }

        $filters = $this->commissionFilters($request);
        $pageSize = $this->pageSize($request);
        $aid = (int) $currentAffiliate->id;

        $baseQuery = AffiliateCommission::query()
            ->with(['affiliate', 'campaign'])
            ->where('affiliate_id', $aid);
        $this->applyCommissionFilters($baseQuery, $filters);

        $sales = (clone $baseQuery)->orderByDesc('created_at')->paginate($pageSize)->withQueryString();
        $matchingCount = (clone $baseQuery)->count();
        $matchingOrderValue = (clone $baseQuery)->sum('order_amount');
        $eligibleQuery = (clone $baseQuery)->whereIn('status', ['pending', 'approved', 'paid_out']);
        $matchingCommission = (clone $eligibleQuery)->sum('amount');
        $eligibleCount = (clone $eligibleQuery)->count();
        $avgCommission = $eligibleCount > 0 ? $matchingCommission / $eligibleCount : 0;
        $hasActiveFilters = collect($filters)->contains(fn ($value) => $value !== '');

        return view('affiliate-sales', [
            'sales' => $sales,
            'matchingCommission' => $matchingCommission,
            'matchingOrderValue' => $matchingOrderValue,
            'matchingCount' => $matchingCount,
            'avgCommission' => $avgCommission,
            'hasActiveFilters' => $hasActiveFilters,
            'currentStatusFilter' => $filters['status'],
            'currentTypeFilter' => $filters['type'],
            'currentProductFilter' => $filters['product'],
            'currentSearch' => $filters['q'],
            'currentFromFilter' => $filters['from'],
            'currentToFilter' => $filters['to'],
            'currentPerPage' => $pageSize,
            'currentAffiliate' => $currentAffiliate,
            'isAdmin' => false,
            'needsAffiliateSetup' => false,
        ]);
    }

    public function salesExport(Request $request)
    {
        $affiliate = $this->resolvedAffiliate($request);
        if (! $affiliate || $this->isAdmin($request)) {
            abort(404);
        }

        $filters = $this->commissionFilters($request);
        $query = AffiliateCommission::query()
            ->with('campaign:id,name,source')
            ->where('affiliate_id', (int) $affiliate->id);
        $this->applyCommissionFilters($query, $filters);

        $campaignCandidates = AffiliateCampaign::query()
            ->where('affiliate_id', (int) $affiliate->id)
            ->orderBy('id')
            ->limit(2)
            ->get(['id', 'name', 'source']);
        $singleCampaignFallback = $campaignCandidates->count() === 1
            ? $campaignCandidates->first()
            : null;

        $filename = 'stellar-affiliate-conversions-'.strtolower($affiliate->public_code).'-'.now()->format('Y-m-d').'.csv';

        return $this->streamCsv($filename, [
            'Date', 'Order ID', 'Campaign', 'Source', 'Product', 'Commission Type', 'Order Value', 'Currency',
            'Rate Decimal', 'Rate %', 'Commission', 'Status', 'Eligible Payout At', 'Payout ID',
        ], function ($handle) use ($query, $singleCampaignFallback) {
            $query->orderBy('id')->chunkById(500, function ($rows) use ($handle, $singleCampaignFallback) {
                foreach ($rows as $sale) {
                    $campaign = $sale->campaign ?: $singleCampaignFallback;
                    $campaignName = $campaign?->name ?: 'Legacy / unattributed';
                    $campaignSource = $campaign?->source;

                    fputcsv($handle, array_map([$this, 'csvCell'], [
                        $sale->created_at?->format('Y-m-d H:i:s'),
                        $sale->getRawOriginal('order_id'),
                        $campaignName,
                        $campaignSource,
                        $sale->product,
                        $sale->type,
                        $sale->order_amount !== null ? number_format((float) $sale->order_amount, 2, '.', '') : null,
                        $sale->currency ?: 'EUR',
                        number_format((float) $sale->rate, 4, '.', ''),
                        number_format((float) $sale->rate * 100, 4, '.', ''),
                        number_format((float) $sale->amount, 6, '.', ''),
                        $sale->status,
                        $sale->eligible_payout_at?->format('Y-m-d H:i:s'),
                        $sale->payout_id,
                    ]), ',', '"', '');
                }
            });
        });
    }

    public function orderShow(Request $request, int $commission)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $admin = $this->isAdmin($request);

        if (! $currentAffiliate && ! $admin) {
            abort(404);
        }

        $commissionQuery = AffiliateCommission::with('affiliate')->whereKey($commission);

        // Ownership is checked against the local conversion before any Commerce API request.
        // A normal affiliate can therefore never use this endpoint as an arbitrary order lookup.
        if (! $admin && $currentAffiliate) {
            $commissionQuery->where('affiliate_id', (int) $currentAffiliate->id);
        } elseif (! $admin) {
            $commissionQuery->whereRaw('1 = 0');
        }

        $affiliateCommission = $commissionQuery->firstOrFail();
        $orderId = trim((string) $affiliateCommission->getRawOriginal('order_id'));

        if ($orderId === '') {
            abort(404);
        }

        $order = null;
        $orderError = null;

        try {
            /** @var AffiliateOrderService $orders */
            $orders = app(AffiliateOrderService::class);
            $order = $orders->getAffiliateOrder($orderId);
        } catch (\Throwable $exception) {
            Log::warning('Affiliate order lookup failed.', [
                'commission_id' => $affiliateCommission->id,
                'order_id' => $orderId,
                'exception' => $exception::class,
            ]);

            $orderError = 'Order details are temporarily unavailable. Try again in a moment.';
        }

        return view('affiliate-order', [
            'commission' => $affiliateCommission,
            'orderId' => $orderId,
            'order' => $order,
            'orderError' => $orderError,
            'currentAffiliate' => $currentAffiliate,
            'isAdmin' => $admin,
        ]);
    }

    public function settings(Request $request)
    {
        $currentAffiliate = $this->resolvedAffiliate($request);
        $admin = $this->isAdmin($request);

        if ($admin) {
            return redirect()->route('affiliate.admin.dashboard');
        }

        if (! $currentAffiliate) {
            return redirect()->route('affiliate.onboarding');
        }

        $rateMatrix = $currentAffiliate
            ? app(\App\Services\AffiliateCommissionPolicy::class)->matrix((int) $currentAffiliate->id)
            : app(\App\Services\AffiliateCommissionPolicy::class)->matrix();

        return view('affiliate-settings', [
            'currentAffiliate' => $currentAffiliate,
            'isAdmin' => $admin,
            'needsAffiliateSetup' => false,
            'rateMatrix' => $rateMatrix,
            'esimFeedUrl' => (string) config('affiliate.resources.esim_feed_url'),
        ]);
    }

    public function passwordUpdate(Request $request)
    {
        if ($request->session()->has('affiliate_impersonation')) {
            abort(403, 'Exit affiliate view before changing a password.');
        }

        $affiliate = $this->resolvedAffiliate($request);
        if (! $affiliate || $this->isAdmin($request)) {
            abort(403);
        }

        $user = $request->user();
        if (! $user || (int) $affiliate->external_user_id !== (int) $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'current_password' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], (string) $user->password)) {
            return back()->withErrors([
                'current_password' => 'Your current password is incorrect.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->save();

        Log::notice('Affiliate portal password changed by affiliate.', [
            'affiliate_id' => (int) $affiliate->id,
            'portal_user_id' => (int) $user->id,
        ]);

        return back()->with('status', 'Your password has been updated.');
    }

    // Legacy stubs (keeps old routes from exploding)
    public function campaigns()
    {
        return redirect()->route('affiliate.campaigns.index');
    }

    public function clicks()
    {
        return redirect()->route('affiliate.analytics');
    }

    public function sessions()
    {
        return redirect()->route('affiliate.analytics');
    }

    public function commissions()
    {
        return redirect()->route('affiliate.sales');
    }
}
