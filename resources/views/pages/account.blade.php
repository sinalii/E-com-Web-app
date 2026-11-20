@extends('layouts.marketplace')

@section('title', 'Your account | Marketly')

@section('content')
<div class="site-container inner-page">
    <div class="breadcrumbs"><a href="{{ route('index') }}">Home</a><span>›</span><strong>Your account</strong></div>
    <section class="account-heading"><div><span class="section-kicker">YOUR MARKETLY ACCOUNT</span><h1>Hello, {{ $user->name }}</h1><p>Manage your orders and account details.</p></div><form method="post" action="{{ route('logout') }}">@csrf<button class="button-secondary" type="submit">Sign out</button></form></section>
    <div class="account-grid"><section class="account-card"><h2>Account details</h2><dl><dt>Name</dt><dd>{{ $user->name }}</dd><dt>Email</dt><dd>{{ $user->email }}</dd></dl><a class="text-link account-wishlist-link" href="{{ route('wishlist') }}">View your wishlist <span>→</span></a></section><section class="account-card"><h2>Order history</h2>
        @forelse($orders as $order)
            <div class="account-order"><div><strong>Order #{{ $order->id }}</strong><small>{{ \Illuminate\Support\Carbon::parse($order->created_at)->format('M j, Y') }}</small></div><div><span class="status-pill">{{ ucfirst($order->status ?? 'pending') }}</span><strong>Rs. {{ number_format((float) $order->total_amount, 2) }}</strong></div></div>
        @empty
            <p class="account-empty">Your orders will appear here after checkout.</p>
        @endforelse
    </section></div>
</div>
@endsection
