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
use Illuminate\Support\Facades\Log;
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

        $search = $request->query('q');

        $validCommissionStatuses = ['pending', 'approved', 'paid_out'];

        $query = AffiliateCampaign::query()
            ->with('affiliate')
            ->withCount('clicks')
            ->withCount(['commissions as conversions_count' => fn ($q) => $q->whereIn('status', $validCommissionStatuses)])
            ->withSum(['commissions as commission_total' => fn ($q) => $q->whereIn('status', $validCommissionStatuses)], 'amount')
            ->orderByDesc('created_at');

        if ($currentAffiliate) {
            $query->where('affiliate_id', (int) $currentAffiliate->id);
        } elseif (! $admin) {
            // No affiliate and not admin => empty
            $query->whereRaw('1 = 0');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%")
                    ->orWhereHas('affiliate', function ($qa) use ($search) {
                        $qa->where('public_code', 'like', "%{$search}%");
                    });
            });
        }

        $campaigns = $query->paginate(25)->withQueryString();

        // Dropdown affiliates
        if ($admin) {
            $affiliates = Affiliate::orderBy('public_code')->get(['id', 'public_code']);
        } elseif ($currentAffiliate) {
            $affiliates = Affiliate::where('id', (int) $currentAffiliate->id)->get(['id', 'public_code']);
        } else {
            $affiliates = collect();
        }

        $policy = app(\App\Services\AffiliateCommissionPolicy::class);
        $campaignCommissionRates = [];
        foreach (['esim', 'vpn', 'antivirus'] as $product) {
            $initial = $currentAffiliate
                ? $policy->effectiveRate((int) $currentAffiliate->id, $product, 'initial')
                : $policy->globalRate($product, 'initial');
            $recurring = $currentAffiliate
                ? $policy->effectiveRate((int) $currentAffiliate->id, $product, 'recurring')
                : $policy->globalRate($product, 'recurring');

            $campaignCommissionRates[$product] = [
                'initial' => (float) $initial['rate'],
                'recurring' => (float) $recurring['rate'],
            ];
        }

        return view('affiliate-campaigns', [
            'campaigns'  => $campaigns,
            'affiliates' => $affiliates,
            'search'     => $search,
            'currentAffiliate' => $currentAffiliate,
            'isAdmin' => $admin,
            'campaignCommissionRates' => $campaignCommissionRates,
            'productDefaultDestinations' => [
                'esim' => $this->productDefaultRedirect('esim'),
                'vpn' => $this->productDefaultRedirect('vpn'),
                'antivirus' => $this->productDefaultRedirect('antivirus'),
            ],
        ]);
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
        $from = now()->subDays(30);

        if ($admin) {
            return redirect()->route('affiliate.admin.dashboard');
        }

        if (! $currentAffiliate) {
            return redirect()->route('affiliate.onboarding');
        }

        $clicksQ = AffiliateClick::query()->where('created_at', '>=', $from);
        $sessionsQ = AffiliateSession::query()->where('created_at', '>=', $from);
        $validCommissionStatuses = ['pending', 'approved', 'paid_out'];
        $salesQ = AffiliateCommission::query()
            ->whereIn('status', $validCommissionStatuses)
            ->where('created_at', '>=', $from);

        if ($currentAffiliate) {
            $aid = (int) $currentAffiliate->id;
            $clicksQ->where('affiliate_id', $aid);
            $sessionsQ->where('affiliate_id', $aid);
            $salesQ->where('affiliate_id', $aid);
        }

        $clicksLast30   = $clicksQ->count();
        $sessionsLast30 = $sessionsQ->count();
        $salesLast30    = $salesQ->count();
        $revenueLast30  = (clone $salesQ)->sum('amount');

        $conversionRate = $clicksLast30 > 0 ? round(($salesLast30 / max($clicksLast30, 1)) * 100, 2) : 0;
        $epc            = $clicksLast30 > 0 ? round($revenueLast30 / max($clicksLast30, 1), 4) : 0;

        $topAffiliatesQ = AffiliateCommission::with('affiliate')
            ->select(
                'affiliate_id',
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('SUM(amount) as total_commission')
            )
            ->whereIn('status', $validCommissionStatuses)
            ->where('created_at', '>=', $from);

        if ($currentAffiliate) {
            $topAffiliatesQ->where('affiliate_id', (int) $currentAffiliate->id);
        }

        $topAffiliates = $topAffiliatesQ
            ->groupBy('affiliate_id')
            ->orderByDesc('total_commission')
            ->limit(10)
            ->get();

        $from7 = now()->subDays(7)->startOfDay();

        $dailyClicksQ = AffiliateClick::select(
            DB::raw('DATE(created_at) as day'),
            DB::raw('COUNT(*) as clicks')
        )->where('created_at', '>=', $from7);

        $dailySalesQ = AffiliateCommission::select(
            DB::raw('DATE(created_at) as day'),
            DB::raw('COUNT(*) as sales')
        )->whereIn('status', $validCommissionStatuses)
            ->where('created_at', '>=', $from7);

        if ($currentAffiliate) {
            $aid = (int) $currentAffiliate->id;
            $dailyClicksQ->where('affiliate_id', $aid);
            $dailySalesQ->where('affiliate_id', $aid);
        }

        $dailyClicks = $dailyClicksQ
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $dailySales = $dailySalesQ
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $recentConversionsQ = AffiliateCommission::with('affiliate')->latest();
        if ($currentAffiliate) {
            $recentConversionsQ->where('affiliate_id', (int) $currentAffiliate->id);
        }
        $recentConversions = $recentConversionsQ->take(10)->get();

        $daily = [];
        $cursor = $from7->copy();
        while ($cursor->lte(now())) {
            $key = $cursor->toDateString();
            $daily[] = [
                'day'    => $key,
                'clicks' => $dailyClicks[$key]->clicks ?? 0,
                'sales'  => $dailySales[$key]->sales ?? 0,
            ];
            $cursor->addDay();
        }

        return view('affiliate-analytics', [
            'clicksLast30'   => $clicksLast30,
            'sessionsLast30' => $sessionsLast30,
            'salesLast30'    => $salesLast30,
            'revenueLast30'  => $revenueLast30,
            'conversionRate' => $conversionRate,
            'epc'            => $epc,
            'topAffiliates'  => $topAffiliates,
            'recentConversions' => $recentConversions,
            'daily'          => $daily,
            'currentAffiliate' => $currentAffiliate,
            'isAdmin' => $admin,
            'needsAffiliateSetup' => false,
        ]);
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

        $status = $request->query('status');

        $query = AffiliatePayout::with('affiliate')->orderByDesc('created_at');

        if ($currentAffiliate) {
            $query->where('affiliate_id', (int) $currentAffiliate->id);
        }

        if ($status && in_array($status, ['pending', 'processing', 'paid', 'failed'], true)) {
            $query->where('status', $status);
        }

        $payouts = $query->paginate(25)->withQueryString();

        $availableCommissionQ = AffiliateCommission::where('status', 'approved');
        $pendingCommissionQ = AffiliateCommission::where('status', 'pending');
        $paidCommissionQ = AffiliateCommission::where('status', 'paid_out');

        if ($currentAffiliate) {
            $aid = (int) $currentAffiliate->id;
            $availableCommissionQ->where('affiliate_id', $aid);
            $pendingCommissionQ->where('affiliate_id', $aid);
            $paidCommissionQ->where('affiliate_id', $aid);
        }

        $availableCommission = $availableCommissionQ->sum('amount');
        $pendingCommission = $pendingCommissionQ->sum('amount');
        $paidCommission = $paidCommissionQ->sum('amount');

        $lastPayoutQ = AffiliatePayout::where('status', 'paid')->orderByDesc('created_at');
        if ($currentAffiliate) {
            $lastPayoutQ->where('affiliate_id', (int) $currentAffiliate->id);
        }
        $lastPayout = $lastPayoutQ->first();

        return view('affiliate-payouts', [
            'payouts'              => $payouts,
            'availableCommission'  => $availableCommission,
            'pendingCommission'    => $pendingCommission,
            'paidCommission'       => $paidCommission,
            'lastPayout'           => $lastPayout,
            'currentStatusFilter'  => $status,
            'currentAffiliate' => $currentAffiliate,
            'isAdmin' => $admin,
            'needsAffiliateSetup' => false,
        ]);
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

        $status  = $request->query('status');
        $type = $request->query('type');
        $affiliateCode = $request->query('affiliate'); // admin-only filter
        $product = trim((string) $request->query('product'));

        $query = AffiliateCommission::with('affiliate')->orderByDesc('created_at');

        if ($currentAffiliate) {
            $query->where('affiliate_id', (int) $currentAffiliate->id);
        } elseif ($admin && $affiliateCode) {
            $query->whereHas('affiliate', function ($q) use ($affiliateCode) {
                $q->where('public_code', $affiliateCode);
            });
        }

        if ($status && in_array($status, ['pending', 'approved', 'rejected', 'paid_out'], true)) {
            $query->where('status', $status);
        }

        if ($type && in_array($type, ['initial', 'recurring'], true)) {
            $query->where('type', $type);
        }

        if ($product !== '') {
            $query->where('product', app(\App\Services\AffiliateCommissionPolicy::class)->normalizeProduct($product));
        }

        $sales = $query->paginate(25)->withQueryString();

        $validCommissionStatuses = ['pending', 'approved', 'paid_out'];
        $totalsQ = AffiliateCommission::query()->whereIn('status', $validCommissionStatuses);
        $last30Q = AffiliateCommission::whereIn('status', $validCommissionStatuses)
            ->where('created_at', '>=', now()->subDays(30));

        if ($currentAffiliate) {
            $aid = (int) $currentAffiliate->id;
            $totalsQ->where('affiliate_id', $aid);
            $last30Q->where('affiliate_id', $aid);
        } elseif ($admin && $affiliateCode) {
            $totalsQ->whereHas('affiliate', fn ($q) => $q->where('public_code', $affiliateCode));
            $last30Q->whereHas('affiliate', fn ($q) => $q->where('public_code', $affiliateCode));
        }

        $totalCommission = $totalsQ->sum('amount');
        $totalSalesCount = (clone $totalsQ)->count();
        $avgCommission   = $totalSalesCount > 0 ? $totalCommission / $totalSalesCount : 0;

        $last30Commission = $last30Q->sum('amount');
        $last30Count      = (clone $last30Q)->count();

        return view('affiliate-sales', [
            'sales'                => $sales,
            'totalCommission'      => $totalCommission,
            'totalSalesCount'      => $totalSalesCount,
            'avgCommission'        => $avgCommission,
            'last30Commission'     => $last30Commission,
            'last30Count'          => $last30Count,
            'currentStatusFilter'  => $status,
            'currentTypeFilter'    => $type,
            'currentAffiliateCode' => $affiliateCode,
            'currentProductFilter' => $product,
            'currentAffiliate' => $currentAffiliate,
            'isAdmin' => $admin,
            'needsAffiliateSetup' => false,
        ]);
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
