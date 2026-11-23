@extends('layouts.marketplace')

@section('title', 'Your wishlist | Marketly')

@section('content')
<div class="site-container inner-page">
    <div class="breadcrumbs"><a href="{{ route('index') }}">Home</a><span>›</span><strong>Wishlist</strong></div>
    <section class="checkout-heading"><span class="section-kicker">SAVED FOR LATER</span><h1>Your wishlist <span class="cart-heading-count">({{ $items->count() }})</span></h1><p>Keep the things you love close at hand.</p></section>
    @if($items->isEmpty())
        <div class="empty-cart-card"><div class="empty-cart-icon wishlist-empty-icon">♡</div><h2>Your wishlist is waiting for a favorite.</h2><p>Tap the heart on a product to save it here for later.</p><a class="button-primary" href="{{ route('shop') }}">Explore products <span>→</span></a></div>
    @else
        <div class="product-grid shop-product-grid wishlist-grid">
            @foreach($items as $product)
                @php
                    $image = $product->image_url ?: 'https://images.unsplash.com/photo-1498049794561-7780e7231661?auto=format&fit=crop&w=800&q=80';
                    if (!\Illuminate\Support\Str::startsWith($image, ['http://', 'https://', '/'])) $image = asset('furni/images/' . $image);
                @endphp
                <article class="market-product-card"><div class="product-image-wrap"><a class="product-image-link" href="{{ route('products.show', $product->slug ?: $product->id) }}" aria-label="View {{ $product->name }}"><img src="{{ $image }}" alt="{{ $product->name }}" loading="lazy"></a>
                    <form method="post" action="{{ route('wishlist.toggle', $product->id) }}" class="wishlist-form">@csrf<button class="wishlist-button is-saved" type="submit" aria-label="Remove {{ $product->name }} from wishlist">♥</button></form>
                </div><div class="product-card-body"><small class="wishlist-category">{{ $product->category_name }}</small><h3><a href="{{ route('products.show', $product->slug ?: $product->id) }}">{{ $product->name }}</a></h3><p>{{ \Illuminate\Support\Str::limit($product->description ?? '', 70) }}</p><div class="product-buy-row"><strong>{{ $product->price !== null ? 'Rs. ' . number_format((float) $product->price, 2) : 'Ask for price' }}</strong>
                    @if($product->variant_id)<form method="post" action="{{ route('cart.add') }}" class="async-action-form">@csrf<input type="hidden" name="variant_id" value="{{ $product->variant_id }}"><button class="add-cart" type="submit" aria-label="Add {{ $product->name }} to cart">+</button></form>@endif
                </div></div></article>
            @endforeach
        </div>
    @endif
</div>
@endsection
