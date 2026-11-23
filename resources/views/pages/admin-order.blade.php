@extends('layouts.marketplace')

@section('title', 'Order #'.$order->id.' | Marketly Admin')

@section('content')
<div class="site-container inner-page admin-page">
    <div class="breadcrumbs"><a href="{{ route('admin.dashboard') }}">Admin</a><span>›</span><a href="{{ route('admin.section', 'orders') }}">Orders</a><span>›</span><strong>Order #{{ $order->id }}</strong></div>
    <section class="account-heading"><div><span class="section-kicker">ORDER DETAILS</span><h1>Order #{{ $order->id }}</h1><p>{{ $order->created_at ? \Illuminate\Support\Carbon::parse($order->created_at)->format('M j, Y H:i') : 'Date unavailable' }} · {{ ucfirst($order->status ?? 'pending') }}</p></div><a class="button-secondary" href="{{ route('admin.section', 'orders') }}">Back to orders</a></section>
    <div class="admin-workspace">
        @include('pages.admin-sidebar')
        <div class="admin-main">
    <div class="admin-content-grid">
        <section class="account-card"><h2>Items</h2><div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Unit price</th><th>Subtotal</th></tr></thead><tbody>@forelse($items as $item)<tr><td>{{ $item->product_name }}</td><td>{{ $item->sku ?? '—' }}</td><td>{{ $item->quantity }}</td><td>Rs. {{ number_format((float) $item->unit_price, 2) }}</td><td>Rs. {{ number_format((float) $item->subtotal, 2) }}</td></tr>@empty<tr><td colspan="5">No order items recorded.</td></tr>@endforelse</tbody></table></div></section>
        <section class="account-card"><h2>Customer and totals</h2><dl><dt>Customer</dt><dd>{{ $order->customer_name ?? 'Guest' }}</dd><dt>Email</dt><dd>{{ $order->customer_email ?? '—' }}</dd><dt>Subtotal</dt><dd>Rs. {{ number_format((float) $order->subtotal, 2) }}</dd><dt>Discount</dt><dd>Rs. {{ number_format((float) $order->discount, 2) }}</dd><dt>Shipping</dt><dd>Rs. {{ number_format((float) $order->shipping_cost, 2) }}</dd><dt>Total</dt><dd><strong>Rs. {{ number_format((float) $order->total_amount, 2) }}</strong></dd></dl></section>
        <section class="account-card"><h2>Delivery</h2>@if($shipping)<p>{{ $shipping->contact_name }} · {{ $shipping->phone }}</p><p>{{ $shipping->address_line1 }} {{ $shipping->address_line2 }}<br>{{ $shipping->city }} {{ $shipping->postal_code }}<br>{{ $shipping->country }}</p><p>{{ ucfirst($shipping->status ?? 'pending') }} @if($shipping->tracking_number)· Tracking {{ $shipping->tracking_number }}@endif</p>@else<p class="admin-muted">No shipping record.</p>@endif</section>
        <section class="account-card"><h2>Payments</h2>@forelse($payments as $payment)<div class="account-order"><div><strong>{{ ucfirst($payment->method ?? 'Payment') }}</strong><small>{{ $payment->transaction_id ?? 'No transaction reference' }}</small></div><div><span class="status-pill">{{ ucfirst($payment->status ?? 'pending') }}</span><strong>Rs. {{ number_format((float) $payment->amount, 2) }}</strong></div></div>@empty<p class="admin-muted">No payment records.</p>@endforelse</section>
    </div>
        </div>
    </div>
</div>
@endsection