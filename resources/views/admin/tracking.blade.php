@extends('layouts.affiliate')

@section('title', 'Tracking · Admin')

@section('content')
    <section class="stellar-page-header">
        <div>
            <p class="stellar-eyebrow">Attribution</p>
            <h1 class="stellar-page-title">Tracking activity</h1>
            <p class="stellar-page-copy">View clicks, sessions and referral URLs across the program.</p>
        </div>
    </section>

    <section class="stellar-card stellar-card-pad">
        <form method="GET" class="stellar-filterbar">
            <div class="stellar-field stellar-filter-grow">
                <label class="stellar-label" for="tracking-affiliate-filter">Affiliate code</label>
                <input id="tracking-affiliate-filter" class="stellar-input" name="affiliate" value="{{ $affiliateCode }}" placeholder="Filter by affiliate code">
            </div>
            <button type="submit" class="stellar-btn stellar-btn-primary">Filter</button>
        </form>
    </section>

    <section class="stellar-card stellar-card-pad stellar-section">
        <h2 class="stellar-section-title">Recent clicks</h2>
        <div class="stellar-table-wrap" style="margin-top: 14px;">
            <table class="stellar-table">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Affiliate</th>
                    <th>Campaign</th>
                    <th>Source</th>
                    <th>Landing</th>
                    <th>Referrer</th>
                </tr>
                </thead>
                <tbody>
                @forelse($clicks as $click)
                    @php
                        $landingUrl = trim((string) $click->landing_url);
                        $referrerUrl = trim((string) $click->referrer);
                        $landingIsUrl = \Illuminate\Support\Str::startsWith($landingUrl, ['http://', 'https://']);
                        $referrerIsUrl = \Illuminate\Support\Str::startsWith($referrerUrl, ['http://', 'https://']);
                    @endphp
                    <tr>
                        <td>{{ $click->created_at?->format('M j, Y · H:i') }}</td>
                        <td>
                            @if($click->affiliate)
                                <a class="stellar-text-link" href="{{ route('affiliate.admin.affiliates.show', $click->affiliate) }}">{{ $click->affiliate->public_code }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $click->campaign?->name ?: '—' }}</td>
                        <td>{{ $click->source ?: '—' }}</td>
                        <td>
                            @if($landingUrl !== '')
                                <div class="stellar-url-actions">
                                    @if($landingIsUrl)
                                        <a class="stellar-tracking-url" href="{{ $landingUrl }}" target="_blank" rel="noopener noreferrer" title="{{ $landingUrl }}">{{ $landingUrl }}</a>
                                    @else
                                        <span class="stellar-tracking-url" title="{{ $landingUrl }}">{{ $landingUrl }}</span>
                                    @endif
                                    <button type="button" class="stellar-copy-mini" data-copy="{{ $landingUrl }}">Copy</button>
                                </div>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if($referrerUrl !== '')
                                <div class="stellar-url-actions">
                                    @if($referrerIsUrl)
                                        <a class="stellar-tracking-url" href="{{ $referrerUrl }}" target="_blank" rel="noopener noreferrer" title="{{ $referrerUrl }}">{{ $referrerUrl }}</a>
                                    @else
                                        <span class="stellar-tracking-url" title="{{ $referrerUrl }}">{{ $referrerUrl }}</span>
                                    @endif
                                    <button type="button" class="stellar-copy-mini" data-copy="{{ $referrerUrl }}">Copy</button>
                                </div>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No clicks found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="stellar-pagination">{{ $clicks->links() }}</div>
    </section>

    <section class="stellar-card stellar-card-pad stellar-section">
        <h2 class="stellar-section-title">Recent sessions</h2>
        <div class="stellar-table-wrap" style="margin-top: 14px;">
            <table class="stellar-table">
                <thead>
                <tr>
                    <th>Created</th>
                    <th>Affiliate</th>
                    <th>Campaign</th>
                    <th>Source</th>
                    <th>Session</th>
                    <th>Expires</th>
                </tr>
                </thead>
                <tbody>
                @forelse($sessions as $session)
                    <tr>
                        <td>{{ $session->created_at?->format('M j, Y · H:i') }}</td>
                        <td>
                            @if($session->affiliate)
                                <a class="stellar-text-link" href="{{ route('affiliate.admin.affiliates.show', $session->affiliate) }}">{{ $session->affiliate->public_code }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $session->campaign?->name ?: '—' }}</td>
                        <td>{{ $session->source ?: '—' }}</td>
                        <td>
                            <div class="stellar-code-actions">
                                <span class="stellar-code">{{ $session->session_token }}</span>
                                <button type="button" class="stellar-copy-mini" data-copy="{{ $session->session_token }}">Copy</button>
                            </div>
                        </td>
                        <td>{{ $session->expires_at?->format('M j, Y · H:i') ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No sessions found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="stellar-pagination">{{ $sessions->links() }}</div>
    </section>
@endsection
