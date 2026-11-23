@extends('layouts.marketplace')

@section('title', 'Payment #'.$payment->id.' | Marketly Admin')
@section('content')
<div class="site-container inner-page admin-page">
    <div class="breadcrumbs"><a href="{{ route('admin.dashboard') }}">Admin</a><span>›</span><a href="{{ route('admin.section','payments') }}">Payments</a><span>›</span><strong>Payment #{{ $payment->id }}</strong></div>
    <section class="account-heading"><div><span class="section-kicker">PAYMENT TRANSACTION</span><h1>Payment #{{ $payment->id }}</h1><p><span class="status-pill">{{ ucfirst($payment->status ?? 'pending') }}</span> · {{ ucfirst(str_replace('_',' ',$payment->method ?? 'Unknown method')) }}</p></div><a class="button-secondary" href="{{ route('admin.section','payments') }}">Back to payments</a></section>
    <div class="admin-workspace">@include('pages.admin-sidebar')<div class="admin-main"><div class="admin-content-grid">
        <section class="account-card"><h2>Transaction details</h2><dl><dt>Transaction ID</dt><dd>{{ $payment->transaction_id ?: 'Not supplied' }}</dd><dt>Payment method</dt><dd>{{ ucfirst(str_replace('_',' ',$payment->method ?? 'Unknown')) }}</dd><dt>Provider</dt><dd>{{ $payment->provider ?: 'Not supplied' }}</dd><dt>Status</dt><dd>{{ ucfirst($payment->status ?? 'pending') }}</dd><dt>Amount</dt><dd><strong>Rs. {{ number_format((float)$payment->amount,2) }}</strong></dd><dt>Payment recorded</dt><dd>{{ $payment->created_at ? \Illuminate\Support\Carbon::parse($payment->created_at)->format('M j, Y H:i') : '—' }}</dd><dt>Paid at</dt><dd>{{ $payment->paid_at ? \Illuminate\Support\Carbon::parse($payment->paid_at)->format('M j, Y H:i') : '—' }}</dd></dl></section>
        <section class="account-card"><h2>Related order</h2><p><a class="text-link" href="{{ route('admin.order.show',$payment->order_id) }}">Order #{{ $payment->order_id }}</a></p><p>Placed {{ $payment->order_created_at ? \Illuminate\Support\Carbon::parse($payment->order_created_at)->format('M j, Y H:i') : '—' }}</p><p>Status: {{ ucfirst($payment->order_status ?? 'pending') }}</p><p>Order total: Rs. {{ number_format((float)$payment->total_amount,2) }}</p><h3>Customer</h3><p>{{ $payment->customer_name ?? 'Guest' }}<br>{{ $payment->customer_email ?? '—' }}</p></section>
    </div></div></div>
</div>
@endsection
