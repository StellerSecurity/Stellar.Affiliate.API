@extends('layouts.affiliate')

@section('title', 'Campaigns · Admin')

@section('content')
    <section class="stellar-page-header">
        <div>
            <p class="stellar-eyebrow">Program activity</p>
            <h1 class="stellar-page-title">Campaigns</h1>
            <p class="stellar-page-copy">Compare campaign traffic, conversions and commission.</p>
        </div>
    </section>

    <section class="stellar-card stellar-card-pad">
        <form class="stellar-filterbar" method="GET">
            <div class="stellar-field stellar-filter-grow">
                <label class="stellar-label" for="admin-campaign-search">Search</label>
                <input id="admin-campaign-search" class="stellar-input" name="q" value="{{ $search }}" placeholder="Campaign, source or affiliate code">
            </div>
            <button type="submit" class="stellar-btn stellar-btn-primary">Search</button>
        </form>
    </section>

    <section class="stellar-card stellar-card-pad stellar-section">
        <div class="stellar-section-head">
            <div>
                <h2 class="stellar-section-title">Campaign performance</h2>
                <p class="stellar-section-copy">Campaign totals include conversions linked to that campaign.</p>
            </div>
        </div>

        <div class="stellar-table-wrap">
            <table class="stellar-table">
                <thead>
                <tr>
                    <th>Campaign</th>
                    <th>Affiliate</th>
                    <th>Source</th>
                    <th>Product</th>
                    <th>Rate</th>
                    <th>Clicks</th>
                    <th>Sessions</th>
                    <th>Conversions</th>
                    <th>Commission</th>
                    <th>Destination</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                @forelse($campaigns as $campaign)
                    <tr>
                        <td class="strong">{{ $campaign->name }}</td>
                        <td>
                            @if($campaign->affiliate)
                                <a class="stellar-text-link" href="{{ route('affiliate.admin.affiliates.show', $campaign->affiliate) }}">{{ $campaign->affiliate->public_code }}</a>
                                <div class="stellar-cell-sub">{{ $campaign->affiliate->name }}</div>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $campaign->source ?: 'other' }}</td>
                        <td>{{ match($campaign->product) { 'esim' => 'Stellar eSIM', 'vpn' => 'Stellar VPN', 'antivirus' => 'Stellar Antivirus', default => $campaign->product ? ucfirst($campaign->product) : '—' } }}</td>
                        <td class="strong">
                            @if($campaign->product === 'esim' && $campaign->commission_rate !== null)
                                {{ number_format((float) $campaign->commission_rate * 100, 2) }}%
                            @elseif(in_array($campaign->product, ['vpn', 'antivirus'], true))
                                {{ rtrim(rtrim(number_format($vpnInitialRate * 100, 2), '0'), '.') }}% / {{ rtrim(rtrim(number_format($vpnRecurringRate * 100, 2), '0'), '.') }}%
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ number_format($campaign->clicks_count) }}</td>
                        <td>{{ number_format($campaign->sessions_count) }}</td>
                        <td class="strong">{{ number_format($campaign->conversions_count) }}</td>
                        <td class="strong">€{{ \App\Support\CommissionMath::display($campaign->commission_total ?? 0) }}</td>
                        @php
                            $adminCampaignProduct = $campaign->product ?: 'esim';
                            $adminCampaignDestination = $campaign->redirect_url
                                ?: config('affiliate.products.'.$adminCampaignProduct.'.default_redirect_url', config('affiliate.products.esim.default_redirect_url'));
                        @endphp
                        <td><a class="stellar-text-link" href="{{ $adminCampaignDestination }}" target="_blank" rel="noopener noreferrer" title="{{ $adminCampaignDestination }}">Open ↗</a></td>
                        <td>{{ $campaign->created_at?->format('M j, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11">No campaigns found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="stellar-pagination">{{ $campaigns->links() }}</div>
    </section>
@endsection
