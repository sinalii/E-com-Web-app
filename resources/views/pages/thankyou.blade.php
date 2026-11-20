@extends('layouts.marketplace')

@section('title', 'Order confirmation | Marketly')

@section('content')
<div class="site-container inner-page"><section class="order-confirmation">
    @if($order)
        <div class="confirmation-mark">✓</div><span class="section-kicker">ORDER CONFIRMED</span><h1>Thanks for shopping with Marketly.</h1><p>Your order <strong>#{{ $order->id }}</strong> has been placed. We’ll prepare it for delivery and keep you updated.</p><div class="confirmed-total">Order total <strong>Rs. {{ number_format((float) $order->total_amount, 2) }}</strong></div>
    @else
        <div class="confirmation-mark confirmation-pending">i</div><span class="section-kicker">ORDER UPDATE</span><h1>No order to show.</h1><p>We couldn’t find that order in your account. Your past orders are available from your account page.</p>
    @endif
    <a class="button-primary" href="{{ route('shop') }}">Continue shopping <span>→</span></a>
</section></div>
@endsection
