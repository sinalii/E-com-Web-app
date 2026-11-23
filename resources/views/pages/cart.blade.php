@extends('layouts.marketplace')

@section('title', 'Your cart | Marketly')

@section('content')
<div class="site-container inner-page">
    <div class="breadcrumbs"><a href="{{ route('index') }}">Home</a><span>›</span><strong>Your cart</strong></div>
    <section class="checkout-heading"><span class="section-kicker">YOUR MARKETLY BAG</span><h1>Your cart <span class="cart-heading-count">({{ $cartCount }})</span></h1><p>Review your picks before you check out.</p></section>
    @if($errors->any())<div class="form-alert">{{ $errors->first() }}</div>@endif

    @if($cartItems->isEmpty())
        <div class="empty-cart-card"><div class="empty-cart-icon"><svg viewBox="0 0 24 24"><path d="M3 4h2l2.2 11.2a2 2 0 0 0 2 1.6h8.5a2 2 0 0 0 2-1.6L21 8H6"></path><circle cx="10" cy="21" r="1"></circle><circle cx="18" cy="21" r="1"></circle></svg></div><h2>Your cart is ready for something good.</h2><p>You haven’t added anything yet. Browse the latest finds and come back when you’re ready.</p><a class="button-primary" href="{{ route('shop') }}">Start exploring <span>→</span></a></div>
    @else
        <div class="cart-layout"><div class="cart-items-list">
            @foreach($cartItems as $item)
                @php
                    $image = $item->image_url ?: 'https://images.unsplash.com/photo-1498049794561-7780e7231661?auto=format&fit=crop&w=400&q=80';
                    if (\Illuminate\Support\Str::startsWith($image, 'storage/')) $image = '/'.$image;
                    elseif (\Illuminate\Support\Str::startsWith($image, ['products/', 'categories/'])) $image = '/storage/'.$image;
                @endphp
                <article class="cart-line-item"><img src="{{ $image }}" alt="{{ $item->name }}"><div class="cart-line-info"><h2>{{ $item->name }}</h2><p>In stock: {{ $item->stock_quantity ?? 'Available' }}</p><strong>Rs. {{ number_format((float) $item->unit_price, 2) }}</strong></div>
                    <form class="quantity-form" method="post" action="{{ route('cart.update', $item->cart_item_id) }}">@csrf @method('PATCH')<label for="quantity-{{ $item->cart_item_id }}">Qty</label><input id="quantity-{{ $item->cart_item_id }}" type="number" name="quantity" min="1" max="99" value="{{ $item->quantity }}"><button type="submit">Update</button></form>
                    <div class="cart-line-total"><strong>Rs. {{ number_format((float) $item->unit_price * $item->quantity, 2) }}</strong><form method="post" action="{{ route('cart.remove', $item->cart_item_id) }}">@csrf @method('DELETE')<button type="submit" class="remove-link">Remove</button></form></div>
                </article>
            @endforeach
            <a class="continue-shopping" href="{{ route('shop') }}">← Continue shopping</a>
        </div><aside class="order-summary"><h2>Order summary</h2><div><span>Subtotal</span><strong>Rs. {{ number_format((float) $subtotal, 2) }}</strong></div><div><span>Shipping</span><strong class="summary-green">Free</strong></div><p>Shipping and taxes are calculated at checkout.</p><div class="summary-total"><span>Total</span><strong>Rs. {{ number_format((float) $subtotal, 2) }}</strong></div><a href="{{ route('checkout') }}" class="button-primary summary-checkout">Continue to checkout <span>→</span></a><small class="secure-note">🔒 &nbsp;Secure checkout</small></aside></div>
    @endif
</div>
@endsection
