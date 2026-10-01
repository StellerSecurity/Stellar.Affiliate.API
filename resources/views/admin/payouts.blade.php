@extends('layouts.affiliate')
@section('title', 'Payouts · Admin')
@section('content')
<section class="stellar-page-header">
    <div>
        <p class="stellar-eyebrow">Financial operations</p>
        <h1 class="stellar-page-title">Payouts</h1>
        <p class="stellar-page-copy">Review payment drafts, approvals and completed payment references.</p>
    </div>
</section>
<section class="stellar-card stellar-card-pad">
    <form class="stellar-filterbar" method="GET">
        <div class="stellar-field">
            <label class="stellar-label" for="payout-status-filter">Status</label>
            <select id="payout-status-filter" class="stellar-select" name="status">
                <option value="">All</option>
                @foreach(['pending','processing','paid','failed'] as $option)
                    <option value="{{ $option }}" {{ $statusFilter === $option ? 'selected' : '' }}>{{ ucfirst($option) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="stellar-btn stellar-btn-primary">Filter</button>
    </form>
</section>
<section class="stellar-card stellar-card-pad stellar-section">
    <div class="stellar-table-wrap">
        <table class="stellar-table">
            <thead><tr><th>Date</th><th>Affiliate</th><th>Amount</th><th>Method</th><th>Commissions</th><th>Reference</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($payouts as $payout)
                @php
                    $isDraft = ($payout->method_details_snapshot['submission_type'] ?? null) === 'payment_draft';
                    $statusLabel = match (true) {
                        $payout->provider_state === 'draft' => 'Awaiting finance approval',
                        $payout->provider_state === 'draft_sent_or_deleted' => 'Sent by payment provider',
                        $payout->provider_state === 'pending' => 'Pending payment review',
                        default => ucfirst($payout->status),
                    };
                @endphp
                <tr>
                    <td>{{ $payout->created_at?->format('M j, Y · H:i') }}</td>
                    <td>@if($payout->affiliate)<a class="stellar-text-link" href="{{ route('affiliate.admin.affiliates.show', $payout->affiliate) }}">{{ $payout->affiliate->public_code }}</a>@else<span>—</span>@endif</td>
                    <td class="strong">{{ $payout->currency }} {{ \App\Support\CommissionMath::display($payout->amount) }}</td>
                    <td>{{ ucfirst($payout->method_type ?: '—') }}</td>
                    <td>{{ number_format((int) ($payout->commissions_count ?? 0)) }}</td>
                    <td>{{ $payout->external_reference ?: '—' }}</td>
                    <td><span class="stellar-badge {{ $payout->status === 'paid' ? 'is-success' : ($payout->status === 'failed' ? 'is-danger' : 'is-warning') }}">{{ $statusLabel }}</span></td>
                    <td>
                        @if($payout->request_id)
                            @if($isDraft && $payout->provider_state === 'draft')
                                <strong>Approval required</strong>
                                <span class="stellar-cell-sub">Open the payment draft and approve it to send</span>
                            @else
                                <span class="stellar-cell-sub">Managed by payment reconciliation</span>
                            @endif
                            @if($payout->scheduled_at)<span class="stellar-cell-sub">Draft date: {{ $payout->scheduled_at->format('Y-m-d H:i') }}</span>@endif
                            @if($payout->attention_reason)<span class="stellar-cell-sub">Needs attention: {{ str_replace('_', ' ', $payout->attention_reason) }}</span>@endif
                        @elseif(auth()->user()?->canManageAffiliateCommissions())
                            <form method="POST" action="{{ route('affiliate.admin.payouts.status', $payout) }}" class="stellar-inline-form">
                                @csrf @method('PATCH')
                                <select class="stellar-select stellar-compact-select" name="status" aria-label="Payout status">
                                    @foreach(['pending','processing','paid','failed'] as $option)<option value="{{ $option }}" {{ $payout->status === $option ? 'selected' : '' }}>{{ ucfirst($option) }}</option>@endforeach
                                </select>
                                <input class="stellar-input stellar-ref-input" name="external_reference" aria-label="Payment reference" value="{{ $payout->external_reference }}" placeholder="Reference">
                                <button type="submit" class="stellar-btn stellar-btn-secondary stellar-btn-small">Save</button>
                            </form>
                        @else
                            <span class="stellar-cell-sub">Read only</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">No payouts found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="stellar-pagination">{{ $payouts->links() }}</div>
</section>
@endsection
