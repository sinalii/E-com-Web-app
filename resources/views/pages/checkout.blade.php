@extends('layouts.marketplace')

@section('title', 'Secure checkout | Marketly')

@section('content')
<div class="site-container inner-page">
    <div class="breadcrumbs"><a href="{{ route('index') }}">Home</a><span>›</span><a href="{{ route('cart') }}">Your cart</a><span>›</span><strong>Checkout</strong></div>
    <section class="checkout-heading"><span class="section-kicker">SECURE CHECKOUT</span><h1>Almost yours.</h1><p>A few simple steps and your next great find is on its way.</p></section>
    <div class="checkout-steps"><div class="step-active"><i>1</i><span>Delivery</span></div><b></b><div><i>2</i><span>Payment</span></div><b></b><div><i>3</i><span>Confirmation</span></div></div>
    @if($errors->any())<div class="form-alert">{{ $errors->first() }}</div>@endif
    <form class="checkout-layout" method="post" action="{{ route('checkout.place') }}">@csrf
        <section class="checkout-form-card"><h2>Delivery details</h2><p class="form-hint">Where should we send your order?</p>
            <div class="form-grid"><label>Full name<input name="contact_name" value="{{ old('contact_name', $user->name) }}" required autocomplete="name"></label><label>Phone number<input name="phone" type="tel" value="{{ old('phone', $user->phone) }}" required autocomplete="tel"></label><label class="wide-field">Address line 1<input name="address_line1" value="{{ old('address_line1') }}" required autocomplete="address-line1"></label><label class="wide-field">Address line 2 <span>(optional)</span><input name="address_line2" value="{{ old('address_line2') }}" autocomplete="address-line2"></label><label>City<input name="city" value="{{ old('city') }}" required autocomplete="address-level2"></label><label>Postal code <span>(optional)</span><input name="postal_code" value="{{ old('postal_code') }}" autocomplete="postal-code"></label><label class="wide-field">Country<input name="country" value="{{ old('country', 'Sri Lanka') }}" required autocomplete="country-name"></label></div>
            <h2 class="payment-heading">Payment method</h2><label class="payment-option"><input type="radio" name="payment_method" value="cash_on_delivery" checked><span><strong>Cash on delivery</strong><small>Pay when your order arrives.</small></span><b>COD</b></label>
        </section>
        <aside class="order-summary"><h2>Your order</h2>@foreach($cartItems as $item)<div class="checkout-item"><span>{{ $item->name }} <small>× {{ $item->quantity }}</small></span><strong>Rs. {{ number_format((float) $item->unit_price * $item->quantity, 2) }}</strong></div>@endforeach<div><span>Subtotal</span><strong>Rs. {{ number_format((float) $subtotal, 2) }}</strong></div><div><span>Shipping</span><strong class="summary-green">Free</strong></div><div class="summary-total"><span>Total</span><strong>Rs. {{ number_format((float) $subtotal, 2) }}</strong></div><button class="button-primary summary-checkout" type="submit">Place order <span>→</span></button><small class="secure-note">🔒 &nbsp;Your information is protected</small></aside>
    </form>
</div>
@endsection
