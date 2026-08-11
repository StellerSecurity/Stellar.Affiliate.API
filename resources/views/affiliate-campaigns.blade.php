@extends('layouts.affiliate')

@section('title', 'Campaigns · Stellar Affiliate')

@section('content')
    @php
        $campaignIsAdmin = (bool) ($isAdmin ?? false);
        $needsSetup = !$currentAffiliate && !$campaignIsAdmin;
        $destinationDefaults = [
            'esim' => $productDefaultDestinations['esim'] ?? config('affiliate.products.esim.default_redirect_url'),
            'vpn' => $productDefaultDestinations['vpn'] ?? config('affiliate.products.vpn.default_redirect_url'),
            'antivirus' => $productDefaultDestinations['antivirus'] ?? config('affiliate.products.antivirus.default_redirect_url'),
        ];
        $rates = $campaignCommissionRates ?? [];
        $esimInitialRate = (float) ($rates['esim']['initial'] ?? 0.10);
        $esimRecurringRate = (float) ($rates['esim']['recurring'] ?? $esimInitialRate);
        $vpnInitialRate = (float) ($rates['vpn']['initial'] ?? 1.00);
        $vpnRecurringRate = (float) ($rates['vpn']['recurring'] ?? 0.60);
        $antivirusInitialRate = (float) ($rates['antivirus']['initial'] ?? 1.00);
        $antivirusRecurringRate = (float) ($rates['antivirus']['recurring'] ?? 0.60);
        $percent = static fn (float $rate): string => rtrim(rtrim(number_format($rate * 100, 2), '0'), '.').'%';
    @endphp

    <section class="stellar-page-header">
        <div>
            <p class="stellar-eyebrow">Tracking links</p>
            <h1 class="stellar-page-title">Campaigns</h1>
            <p class="stellar-page-copy">Create a tracking link for each channel or placement.</p>
        </div>

        @if(!$needsSetup && $campaigns->total() > 0)
            <div class="stellar-actions">
                <a href="{{ route('affiliate.campaigns.export', request()->except('page')) }}" class="stellar-btn stellar-btn-secondary" data-download>Export CSV</a>
            </div>
        @endif
    </section>

    @if($needsSetup)
        <section class="stellar-hero">
            <div class="stellar-hero-grid">
                <div>
                    <p class="stellar-eyebrow">Setup required</p>
                    <h2 class="stellar-hero-title">Create your affiliate profile <span class="accent-text">before your first campaign.</span></h2>
                    <p class="stellar-hero-copy">The guided setup creates your public affiliate code first, then brings you straight back to campaign creation.</p>
                    <div class="stellar-actions">
                        <a href="{{ route('affiliate.onboarding') }}" class="stellar-btn stellar-btn-primary">Continue guided setup</a>
                    </div>
                </div>
                <div class="stellar-hero-panel">
                    <span class="stellar-hero-panel-label">Next step</span>
                    <div class="stellar-hero-panel-value">Affiliate profile</div>
                    <p class="stellar-field-help" style="margin-top: 8px;">Your affiliate code is ready.</p>
                </div>
            </div>
        </section>
    @else
        <section class="stellar-grid-2">
            <div class="stellar-card stellar-card-pad">
                <p class="stellar-eyebrow">New campaign</p>
                <h2 class="stellar-section-title">Create a clean tracking link</h2>
                <p class="stellar-section-copy">Choose the product, name the campaign and select where the traffic comes from.</p>

                <form method="POST" action="{{ route('affiliate.campaigns.store') }}" class="stellar-form-grid" style="margin-top: 20px;" data-campaign-builder>
                    @csrf

                    @if($campaignIsAdmin)
                        <div class="stellar-field">
                            <label for="affiliate-id" class="stellar-label">Affiliate</label>
                            <select id="affiliate-id" name="affiliate_id" class="stellar-select" required>
                                <option value="" disabled {{ old('affiliate_id') ? '' : 'selected' }}>Choose affiliate</option>
                                @foreach($affiliates as $aff)
                                    <option value="{{ $aff->id }}" {{ (string) old('affiliate_id') === (string) $aff->id ? 'selected' : '' }}>{{ $aff->public_code }}</option>
                                @endforeach
                            </select>
                            @error('affiliate_id')<span class="stellar-field-error">{{ $message }}</span>@enderror
                        </div>
                    @endif

                    <div class="stellar-field">
                        <label for="campaign-product" class="stellar-label">Product</label>
                        @php
                            $selectedProduct = old('product', 'esim');
                            $selectedDestination = old('redirect_url', $destinationDefaults[$selectedProduct] ?? $destinationDefaults['esim']);
                        @endphp
                        <select id="campaign-product" name="product" class="stellar-select" required>
                            <option value="esim" {{ $selectedProduct === 'esim' ? 'selected' : '' }}>Stellar eSIM</option>
                            <option value="vpn" {{ $selectedProduct === 'vpn' ? 'selected' : '' }}>Stellar VPN</option>
                            <option value="antivirus" {{ $selectedProduct === 'antivirus' ? 'selected' : '' }}>Stellar Antivirus</option>
                        </select>
                        @error('product')<span class="stellar-field-error">{{ $message }}</span>@enderror
                    </div>

                    @if(!$campaignIsAdmin)
                        <div
                            class="stellar-rate-preview"
                            data-commission-rate-preview
                            data-esim-initial="{{ $esimInitialRate }}"
                            data-esim-recurring="{{ $esimRecurringRate }}"
                            data-vpn-initial="{{ $vpnInitialRate }}"
                            data-vpn-recurring="{{ $vpnRecurringRate }}"
                            data-antivirus-initial="{{ $antivirusInitialRate }}"
                            data-antivirus-recurring="{{ $antivirusRecurringRate }}"
                        >
                            <div class="stellar-rate-preview-head">
                                <span>Your commission</span>
                                <strong data-rate-product>Stellar eSIM</strong>
                            </div>
                            <div class="stellar-rate-preview-values">
                                <div>
                                    <span data-rate-primary-label>Per sale</span>
                                    <strong data-rate-primary-value>{{ $percent($esimInitialRate) }}</strong>
                                </div>
                                <div data-rate-secondary hidden>
                                    <span>Recurring</span>
                                    <strong data-rate-secondary-value>{{ $percent($esimRecurringRate) }}</strong>
                                </div>
                            </div>
                            <p data-rate-description>Your eSIM rate applies to every eSIM sale.</p>
                        </div>
                    @endif

                    <div class="stellar-field">
                        <label for="campaign-destination" class="stellar-label">Destination URL <span class="stellar-label-note">editable</span></label>
                        <input
                            id="campaign-destination"
                            type="url"
                            name="redirect_url"
                            class="stellar-input"
                            value="{{ $selectedDestination }}"
                            maxlength="2048"
                            required
                            data-campaign-destination
                            data-default-esim="{{ $destinationDefaults['esim'] }}"
                            data-default-vpn="{{ $destinationDefaults['vpn'] }}"
                            data-default-antivirus="{{ $destinationDefaults['antivirus'] }}"
                            placeholder="https://..."
                        >
                        @error('redirect_url')<span class="stellar-field-error">{{ $message }}</span>@enderror
                        <span class="stellar-field-help">Visitors land here after tracking. The recommended product page is prefilled, but you can use your own landing page.</span>
                        <button type="button" class="stellar-text-button" data-use-product-destination>Use product default</button>
                    </div>

                    <div class="stellar-field">
                        <label for="campaign-name" class="stellar-label">Campaign name</label>
                        <input id="campaign-name" name="name" class="stellar-input" value="{{ old('name') }}" maxlength="255" required placeholder="e.g. youtube-review-august">
                        @error('name')<span class="stellar-field-error">{{ $message }}</span>@enderror
                        <span class="stellar-field-help">Use a unique name you will recognize in reports.</span>
                    </div>

                    <div class="stellar-field">
                        <label for="campaign-source" class="stellar-label">Traffic source</label>
                        <select id="campaign-source" name="source" class="stellar-select" required>
                            <option value="" disabled {{ old('source') ? '' : 'selected' }}>Choose source</option>
                            @foreach(['youtube' => 'YouTube', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'blog' => 'Blog / website', 'newsletter' => 'Newsletter', 'other' => 'Other'] as $value => $label)
                                <option value="{{ $value }}" {{ old('source') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('source')<span class="stellar-field-error">{{ $message }}</span>@enderror
                    </div>

                    <details class="stellar-detail-box">
                        <summary>Advanced tracking labels</summary>
                        <div class="stellar-form-grid">
                            <div class="stellar-field">
                                <label for="campaign-sub-1" class="stellar-label">Sub ID 1 <span class="stellar-label-note">optional</span></label>
                                <input id="campaign-sub-1" name="sub_id1" class="stellar-input" value="{{ old('sub_id1') }}" maxlength="255" placeholder="e.g. video-01">
                            </div>
                            <div class="stellar-field">
                                <label for="campaign-sub-2" class="stellar-label">Sub ID 2 <span class="stellar-label-note">optional</span></label>
                                <input id="campaign-sub-2" name="sub_id2" class="stellar-input" value="{{ old('sub_id2') }}" maxlength="255" placeholder="e.g. creator-name">
                            </div>
                        </div>
                    </details>

                    <button type="submit" class="stellar-btn stellar-btn-primary">Create campaign</button>
                </form>
            </div>

            <div class="stellar-card stellar-card-pad">
                <p class="stellar-eyebrow">How to use campaigns</p>
                <div class="stellar-checklist">
                    <div class="stellar-check-item is-done"><span class="stellar-check-dot">1</span><div><strong>One placement, one campaign</strong><span>Separate YouTube, Instagram, newsletters and websites so performance stays readable.</span></div></div>
                    <div class="stellar-check-item"><span class="stellar-check-dot">2</span><div><strong>Copy the generated link</strong><span>Your tracking link is ready to share.</span></div></div>
                    <div class="stellar-check-item"><span class="stellar-check-dot">3</span><div><strong>Check every conversion</strong><span>Open the Order ID to see the order, commission and status.</span></div></div>
                </div>
            </div>
        </section>

        <section class="stellar-card stellar-card-pad stellar-section">
            <div class="stellar-section-head">
                <div>
                    <h2 class="stellar-section-title">Find a campaign</h2>
                    <p class="stellar-section-copy">Filter links by product, traffic source or campaign name.</p>
                </div>
            </div>
            <form method="GET" class="stellar-filterbar stellar-filterbar-wide" role="search">
                <div class="stellar-field stellar-filter-search">
                    <label class="stellar-label" for="campaign-search">Search</label>
                    <input id="campaign-search" type="search" name="q" value="{{ $search }}" class="stellar-input" placeholder="Campaign, source or Sub ID">
                </div>
                <div class="stellar-field">
                    <label class="stellar-label" for="campaign-product-filter">Product</label>
                    <select id="campaign-product-filter" name="product" class="stellar-select">
                        <option value="">All products</option>
                        <option value="esim" {{ $currentProductFilter === 'esim' ? 'selected' : '' }}>Stellar eSIM</option>
                        <option value="vpn" {{ $currentProductFilter === 'vpn' ? 'selected' : '' }}>Stellar VPN</option>
                        <option value="antivirus" {{ $currentProductFilter === 'antivirus' ? 'selected' : '' }}>Stellar Antivirus</option>
                    </select>
                </div>
                <div class="stellar-field">
                    <label class="stellar-label" for="campaign-source-filter">Source</label>
                    <select id="campaign-source-filter" name="source" class="stellar-select">
                        <option value="">All sources</option>
                        @foreach(['youtube' => 'YouTube', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'blog' => 'Blog / website', 'newsletter' => 'Newsletter', 'other' => 'Other'] as $value => $label)
                            <option value="{{ $value }}" {{ $currentSourceFilter === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="stellar-field">
                    <label class="stellar-label" for="campaign-per-page">Rows</label>
                    <select id="campaign-per-page" name="per_page" class="stellar-select">
                        @foreach([25, 50, 100] as $size)
                            <option value="{{ $size }}" {{ $currentPerPage === $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="stellar-filter-actions">
                    <button type="submit" class="stellar-btn stellar-btn-primary">Apply filters</button>
                    @if($search || $currentProductFilter || $currentSourceFilter || $currentPerPage !== 25)
                        <a href="{{ route('affiliate.campaigns.index') }}" class="stellar-btn stellar-btn-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="stellar-section">
            <div class="stellar-section-head">
                <div>
                    <h2 class="stellar-section-title">Your campaign links</h2>
                    <p class="stellar-section-copy">{{ $campaigns->total() }} campaign{{ $campaigns->total() === 1 ? '' : 's' }} found.</p>
                </div>
                @if($campaigns->total() > 0)
                    <a href="{{ route('affiliate.campaigns.export', request()->except('page')) }}" class="stellar-btn stellar-btn-secondary stellar-btn-small" data-download>Export current view</a>
                @endif
            </div>

            @if($campaigns->isEmpty())
                <div class="stellar-card stellar-empty">
                    <div>
                        <span class="stellar-empty-icon">+</span>
                        @if($search || $currentProductFilter || $currentSourceFilter)
                            <h3>No campaigns match these filters</h3>
                            <p>Clear the filters to return to all campaign links.</p>
                            <div class="stellar-actions" style="justify-content: center;">
                                <a href="{{ route('affiliate.campaigns.index') }}" class="stellar-btn stellar-btn-secondary stellar-btn-small">Clear filters</a>
                            </div>
                        @else
                            <h3>No campaigns yet</h3>
                            <p>Create your first campaign above. Your tracking link is generated automatically.</p>
                        @endif
                    </div>
                </div>
            @else
                <div class="stellar-campaign-grid">
                    @foreach($campaigns as $campaign)
                        @php
                            $affiliate = $campaign->affiliate;
                            $campaignProduct = $campaign->product ?: 'esim';
                            $campaignDestination = $campaign->redirect_url ?: ($destinationDefaults[$campaignProduct] ?? $destinationDefaults['esim']);
                            $params = array_filter([
                                'src' => $campaign->source,
                                'campaign' => $campaign->name,
                                'sub1' => $campaign->sub_id1,
                                'sub2' => $campaign->sub_id2,
                                'product' => $campaignProduct,
                                'redirect' => $campaignDestination,
                            ], static fn($value) => $value !== null && $value !== '');
                            $trackingUrl = $affiliate
                                ? route('affiliate.track.public', ['code' => $affiliate->public_code]) . (count($params) ? '?' . http_build_query($params) : '')
                                : null;
                        @endphp
                        <article class="stellar-card stellar-campaign-card is-interactive">
                            <div class="stellar-campaign-head">
                                <div>
                                    <h3 class="stellar-campaign-title">{{ $campaign->name }}</h3>
                                    <div class="stellar-campaign-meta">
                                        @if($affiliate?->status === 'active')
                                            <span class="stellar-badge is-success">Active</span>
                                        @elseif($affiliate?->status === 'pending')
                                            <span class="stellar-badge is-warning">Pending approval</span>
                                        @else
                                            <span class="stellar-badge is-danger">Paused</span>
                                        @endif
                                        <span class="stellar-badge">{{ ucfirst($campaign->source ?: 'other') }}</span>
                                        @if($campaign->product)<span class="stellar-badge">{{ match($campaign->product) { 'esim' => 'Stellar eSIM', 'vpn' => 'Stellar VPN', 'antivirus' => 'Stellar Antivirus', default => ucfirst($campaign->product) } }}</span>@endif
                                        @if($campaign->product === 'esim' && $campaign->commission_rate !== null)
                                            <span class="stellar-badge">{{ $percent((float) $campaign->commission_rate) }} commission</span>
                                        @elseif($campaign->product === 'vpn')
                                            <span class="stellar-badge">{{ $percent($vpnInitialRate) }} first · {{ $percent($vpnRecurringRate) }} recurring</span>
                                        @elseif($campaign->product === 'antivirus')
                                            <span class="stellar-badge">{{ $percent($antivirusInitialRate) }} first · {{ $percent($antivirusRecurringRate) }} recurring</span>
                                        @endif
                                        @if($campaignIsAdmin && $affiliate)<span class="stellar-badge">{{ $affiliate->public_code }}</span>@endif
                                    </div>
                                </div>
                                <span class="stellar-code">#{{ $campaign->id }}</span>
                            </div>

                            @if($campaign->sub_id1 || $campaign->sub_id2)
                                <div class="stellar-campaign-meta" style="margin-top: 14px;">
                                    @if($campaign->sub_id1)<span class="stellar-code">sub1={{ $campaign->sub_id1 }}</span>@endif
                                    @if($campaign->sub_id2)<span class="stellar-code">sub2={{ $campaign->sub_id2 }}</span>@endif
                                </div>
                            @endif

                            @php
                                $campaignClicks = (int) ($campaign->clicks_count ?? 0);
                                $campaignConversions = (int) ($campaign->conversions_count ?? 0);
                                $campaignConversionRate = $campaignClicks > 0 ? ($campaignConversions / $campaignClicks) * 100 : 0;
                            @endphp
                            <div class="stellar-campaign-performance">
                                <div><span>Clicks</span><strong>{{ number_format($campaignClicks) }}</strong></div>
                                <div><span>Conversions</span><strong>{{ number_format($campaignConversions) }}</strong></div>
                                <div><span>Conv. rate</span><strong>{{ number_format($campaignConversionRate, 2) }}%</strong></div>
                                <div><span>Order value</span><strong>€{{ number_format((float) ($campaign->order_value_total ?? 0), 2, '.', ',') }}</strong></div>
                                <div><span>Commission</span><strong>€{{ \App\Support\CommissionMath::display($campaign->commission_total ?? 0) }}</strong></div>
                            </div>

                            <div class="stellar-stat-list" style="margin-top: 16px;">
                                <div class="stellar-stat-row">
                                    <span>Destination</span>
                                    <a href="{{ $campaignDestination }}" target="_blank" rel="noopener noreferrer" class="stellar-text-link">{{ $campaignDestination }}</a>
                                </div>
                            </div>

                            <details class="stellar-detail-box" style="margin-top: 14px;">
                                <summary>Edit campaign</summary>
                                <form method="POST" action="{{ route('affiliate.campaigns.update', ['campaign' => $campaign->id]) }}" class="stellar-form-grid" style="margin-top: 14px;" data-campaign-builder>
                                    @csrf
                                    @method('PATCH')

                                    <div class="stellar-detail-box" style="margin: 0;">
                                        <span class="stellar-field-help">Campaign name</span>
                                        <strong>{{ $campaign->name }}</strong>
                                    </div>

                                    <div class="stellar-form-row">
                                        <div class="stellar-field">
                                            <label for="campaign-source-{{ $campaign->id }}" class="stellar-label">Traffic source</label>
                                            <select id="campaign-source-{{ $campaign->id }}" name="source" class="stellar-select" required>
                                                @foreach(['youtube' => 'YouTube', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'blog' => 'Blog / website', 'newsletter' => 'Newsletter', 'other' => 'Other'] as $value => $label)
                                                    <option value="{{ $value }}" {{ ($campaign->source ?: 'other') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="stellar-field">
                                        <label for="campaign-product-{{ $campaign->id }}" class="stellar-label">Product</label>
                                        <select id="campaign-product-{{ $campaign->id }}" name="product" class="stellar-select" required>
                                            <option value="esim" {{ $campaignProduct === 'esim' ? 'selected' : '' }}>Stellar eSIM · {{ $percent($esimInitialRate) }}</option>
                                            <option value="vpn" {{ $campaignProduct === 'vpn' ? 'selected' : '' }}>Stellar VPN · {{ $percent($vpnInitialRate) }} first / {{ $percent($vpnRecurringRate) }} recurring</option>
                                            <option value="antivirus" {{ $campaignProduct === 'antivirus' ? 'selected' : '' }}>Stellar Antivirus · {{ $percent($antivirusInitialRate) }} first / {{ $percent($antivirusRecurringRate) }} recurring</option>
                                        </select>
                                    </div>

                                    <div class="stellar-field">
                                        <label for="campaign-destination-{{ $campaign->id }}" class="stellar-label">Destination URL</label>
                                        <input
                                            id="campaign-destination-{{ $campaign->id }}"
                                            type="url"
                                            name="redirect_url"
                                            class="stellar-input"
                                            value="{{ $campaignDestination }}"
                                            maxlength="2048"
                                            required
                                            data-campaign-destination
                                            data-default-esim="{{ $destinationDefaults['esim'] }}"
                                            data-default-vpn="{{ $destinationDefaults['vpn'] }}"
                                            data-default-antivirus="{{ $destinationDefaults['antivirus'] }}"
                                        >
                                        <span class="stellar-field-help">You can use the recommended product page or your own landing page.</span>
                                        <button type="button" class="stellar-text-button" data-use-product-destination>Use product default</button>
                                    </div>

                                    <div class="stellar-form-row">
                                        <div class="stellar-field">
                                            <label for="campaign-sub1-{{ $campaign->id }}" class="stellar-label">Sub ID 1 <span class="stellar-label-note">optional</span></label>
                                            <input id="campaign-sub1-{{ $campaign->id }}" name="sub_id1" class="stellar-input" value="{{ $campaign->sub_id1 }}" maxlength="255">
                                        </div>
                                        <div class="stellar-field">
                                            <label for="campaign-sub2-{{ $campaign->id }}" class="stellar-label">Sub ID 2 <span class="stellar-label-note">optional</span></label>
                                            <input id="campaign-sub2-{{ $campaign->id }}" name="sub_id2" class="stellar-input" value="{{ $campaign->sub_id2 }}" maxlength="255">
                                        </div>
                                    </div>

                                    <p class="stellar-field-help">Save your changes, then copy the updated link before sharing it. Previous conversions stay unchanged.</p>
                                    <button type="submit" class="stellar-btn stellar-btn-secondary stellar-btn-small">Save campaign</button>
                                </form>
                            </details>

                            <div style="margin-top: 16px;">
                                @if($trackingUrl && $affiliate?->status === 'active')
                                    <div class="stellar-link-box">
                                        <div class="stellar-link-value" title="{{ $trackingUrl }}">{{ $trackingUrl }}</div>
                                        <button type="button" class="stellar-btn stellar-btn-primary stellar-btn-small" data-copy="{{ $trackingUrl }}">Copy link</button>
                                    </div>
                                @elseif($trackingUrl)
                                    <div class="stellar-flash is-warning" style="margin: 0;">Tracking activates when this affiliate account is active.</div>
                                @else
                                    <div class="stellar-flash is-error" style="margin: 0;">This campaign is not linked to an affiliate.</div>
                                @endif
                            </div>

                            <p class="stellar-field-help" style="margin: 12px 0 0;">Created {{ $campaign->created_at?->format('M j, Y') }}</p>
                        </article>
                    @endforeach
                </div>

                <div class="stellar-pagination">{{ $campaigns->onEachSide(1)->links() }}</div>
            @endif
        </section>
    @endif
@endsection
