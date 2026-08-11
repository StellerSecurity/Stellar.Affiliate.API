@extends('layouts.affiliate')

@section('title', 'Order details · Stellar Affiliate')

@section('content')
    @php
        $commissionStatus = $commission->status ?: 'unknown';
        $commissionStatusClass = match($commissionStatus) {
            'approved', 'paid_out' => 'is-success',
            'pending' => 'is-warning',
            'rejected' => 'is-danger',
            default => '',
        };
        $commissionStatusLabel = match($commissionStatus) { 'paid_out' => 'Paid out', 'pending' => 'Pending review', default => ucfirst($commissionStatus) };
        $commissionRate = (float) $commission->rate;
        $commissionCurrency = strtoupper($commission->currency ?: 'EUR');

        $orderStatus = is_array($order) ? ($order['status'] ?? 'unknown') : 'unknown';
        $orderStatusClass = match(strtolower((string) $orderStatus)) {
            'paid', 'completed', 'fulfilled', 'shipped', 'delivered' => 'is-success',
            'pending', 'created', 'processing', 'authorized' => 'is-warning',
            'cancelled', 'canceled', 'failed', 'refunded' => 'is-danger',
            default => '',
        };
        $orderStatusLabel = ucfirst(str_replace('_', ' ', (string) $orderStatus));
        $orderCurrency = is_array($order) ? strtoupper($order['currency'] ?? 'EUR') : 'EUR';
    @endphp

    <section class="stellar-page-header stellar-order-page-header">
        <div>
            <p class="stellar-eyebrow">Converted order</p>
            <h1 class="stellar-page-title">Order details</h1>
            <p class="stellar-page-copy">Order items, total, status and your commission.</p>
        </div>
        <a href="{{ route(($isAdmin ?? false) ? 'affiliate.admin.commissions.index' : 'affiliate.sales') }}" class="stellar-btn stellar-btn-secondary">Back to conversions</a>
    </section>

    <section class="stellar-card stellar-card-pad stellar-order-identity">
        <div>
            <span class="stellar-order-label">Order ID</span>
            <strong class="stellar-order-id">{{ $orderId }}</strong>
        </div>
        <div class="stellar-order-identity-meta">
            <span>Conversion #{{ $commission->id }}</span>
            <span>{{ $commission->created_at?->format('M j, Y · H:i') ?: 'Date unavailable' }}</span>
        </div>
    </section>

    @if($orderError)
        <section class="stellar-card stellar-card-pad stellar-section stellar-order-unavailable" role="status">
            <div class="stellar-order-unavailable-icon" aria-hidden="true">!</div>
            <div>
                <h2 class="stellar-section-title">Order details unavailable</h2>
                <p class="stellar-section-copy">{{ $orderError }}</p>
                <p class="stellar-section-copy">Order ID and commission details are still available below.</p>
            </div>
        </section>

        <section class="stellar-grid-3 stellar-section">
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">Commission</div>
                <div class="stellar-metric-value stellar-metric-value-compact">{{ $commissionCurrency }} {{ \App\Support\CommissionMath::display($commission->amount) }}</div>
                <div class="stellar-metric-detail">Your commission.</div>
            </article>
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">Commission type</div>
                <div class="stellar-metric-value stellar-metric-value-compact">{{ $commission->type === 'initial' ? 'First payment' : ($commission->type === 'recurring' ? 'Recurring' : '—') }}</div>
                <div class="stellar-metric-detail">{{ rtrim(rtrim(number_format($commissionRate * 100, 2), '0'), '.') }}% commission rate.</div>
            </article>
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">Commission status</div>
                <div class="stellar-order-metric-badge"><span class="stellar-badge {{ $commissionStatusClass }}">{{ $commissionStatusLabel }}</span></div>
                <div class="stellar-metric-detail">Current payout status.</div>
            </article>
        </section>
    @else
        <section class="stellar-grid-4 stellar-section">
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">Order total</div>
                <div class="stellar-metric-value stellar-metric-value-compact">{{ $orderCurrency }} {{ number_format(((int) ($order['grand_total_cents'] ?? 0)) / 100, 2) }}</div>
                <div class="stellar-metric-detail">Order total.</div>
            </article>
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">Order status</div>
                <div class="stellar-order-metric-badge"><span class="stellar-badge {{ $orderStatusClass }}">{{ $orderStatusLabel }}</span></div>
                <div class="stellar-metric-detail">Order status.</div>
            </article>
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">Your commission</div>
                <div class="stellar-metric-value stellar-metric-value-compact">{{ $commissionCurrency }} {{ \App\Support\CommissionMath::display($commission->amount) }}</div>
                <div class="stellar-metric-detail">{{ rtrim(rtrim(number_format($commissionRate * 100, 2), '0'), '.') }}% · {{ $commission->type === 'initial' ? 'First payment' : ($commission->type === 'recurring' ? 'Recurring' : 'Commission') }}</div>
            </article>
            <article class="stellar-card stellar-metric">
                <div class="stellar-metric-label">Commission status</div>
                <div class="stellar-order-metric-badge"><span class="stellar-badge {{ $commissionStatusClass }}">{{ $commissionStatusLabel }}</span></div>
                <div class="stellar-metric-detail">Current payout status.</div>
            </article>
        </section>

        <section class="stellar-grid-2 stellar-section">
            <article class="stellar-card stellar-card-pad">
                <div class="stellar-section-head">
                    <div>
                        <p class="stellar-eyebrow">Order</p>
                        <h2 class="stellar-section-title">Order summary</h2>
                    </div>
                </div>

                <div class="stellar-order-summary">
                    <div class="stellar-order-summary-row">
                        <span>Created</span>
                        <strong>{{ $order['created_at'] ?? '—' }}</strong>
                    </div>
                    <div class="stellar-order-summary-row">
                        <span>Subtotal</span>
                        <strong>{{ $orderCurrency }} {{ number_format(((int) ($order['subtotal_cents'] ?? 0)) / 100, 2) }}</strong>
                    </div>
                    @if(((int) ($order['discount_cents'] ?? 0)) !== 0)
                        <div class="stellar-order-summary-row">
                            <span>Discount</span>
                            <strong>− {{ $orderCurrency }} {{ number_format(abs((int) $order['discount_cents']) / 100, 2) }}</strong>
                        </div>
                    @endif
                    <div class="stellar-order-summary-row">
                        <span>Tax</span>
                        <strong>{{ $orderCurrency }} {{ number_format(((int) ($order['tax_cents'] ?? 0)) / 100, 2) }}</strong>
                    </div>
                    <div class="stellar-order-summary-row">
                        <span>Shipping</span>
                        <strong>{{ $orderCurrency }} {{ number_format(((int) ($order['shipping_cents'] ?? 0)) / 100, 2) }}</strong>
                    </div>
                    <div class="stellar-order-summary-row is-total">
                        <span>Total</span>
                        <strong>{{ $orderCurrency }} {{ number_format(((int) ($order['grand_total_cents'] ?? 0)) / 100, 2) }}</strong>
                    </div>
                </div>
            </article>

            <article class="stellar-card stellar-card-pad">
                <div class="stellar-section-head">
                    <div>
                        <p class="stellar-eyebrow">Affiliate</p>
                        <h2 class="stellar-section-title">Attribution</h2>
                    </div>
                </div>

                <div class="stellar-order-summary">
                    @if($isAdmin ?? false)
                        <div class="stellar-order-summary-row">
                            <span>Affiliate</span>
                            <strong>{{ $commission->affiliate?->public_code ?: '—' }}</strong>
                        </div>
                    @endif
                    <div class="stellar-order-summary-row">
                        <span>Conversion ID</span>
                        <strong>#{{ $commission->id }}</strong>
                    </div>
                    <div class="stellar-order-summary-row">
                        <span>Product</span>
                        <strong>{{ app(\App\Services\AffiliateCommissionPolicy::class)->productLabel($commission->product) }}</strong>
                    </div>
                    <div class="stellar-order-summary-row">
                        <span>Commission type</span>
                        <strong>{{ $commission->type === 'initial' ? 'First payment' : ($commission->type === 'recurring' ? 'Recurring' : '—') }}</strong>
                    </div>
                    <div class="stellar-order-summary-row">
                        <span>Rate</span>
                        <strong>{{ rtrim(rtrim(number_format($commissionRate * 100, 2), '0'), '.') }}%</strong>
                    </div>
                    <div class="stellar-order-summary-row">
                        <span>Commission</span>
                        <strong>{{ $commissionCurrency }} {{ \App\Support\CommissionMath::display($commission->amount) }}</strong>
                    </div>
                    <div class="stellar-order-summary-row">
                        <span>Eligible payout</span>
                        <strong>{{ $commission->eligible_payout_at?->format('M j, Y') ?: '—' }}</strong>
                    </div>
                </div>
            </article>
        </section>

        <section class="stellar-card stellar-card-pad stellar-section">
            <div class="stellar-section-head">
                <div>
                    <p class="stellar-eyebrow">Order contents</p>
                    <h2 class="stellar-section-title">Products</h2>
                    <p class="stellar-section-copy">Order items and totals.</p>
                </div>
                <span class="stellar-kicker">{{ count($order['items'] ?? []) }} item{{ count($order['items'] ?? []) === 1 ? '' : 's' }}</span>
            </div>

            @if(empty($order['items']))
                <div class="stellar-empty stellar-order-empty">
                    <div>
                        <h3>No product lines returned</h3>
                        <p>No order items available.</p>
                    </div>
                </div>
            @else
                <div class="stellar-table-wrap">
                    <table class="stellar-table stellar-order-items-table">
                        <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Qty</th>
                            <th>Unit price</th>
                            <th>Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($order['items'] as $item)
                            <tr>
                                <td class="strong">{{ $item['name'] ?? 'Product' }}</td>
                                <td><span class="stellar-code">{{ $item['sku'] ?: '—' }}</span></td>
                                <td>{{ number_format((int) ($item['qty'] ?? 0)) }}</td>
                                <td>{{ $orderCurrency }} {{ number_format(((int) ($item['unit_price_cents'] ?? 0)) / 100, 2) }}</td>
                                <td class="strong">{{ $orderCurrency }} {{ number_format(((int) ($item['line_total_cents'] ?? 0)) / 100, 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
@endsection
