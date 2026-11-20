@extends('layouts.marketplace')

@section('title', 'Marketly | Everything you love, all in one place')

@section('content')
<div class="site-container home-content">
    <section class="promo-grid" aria-label="Featured promotions">
        <a class="promo-card promo-large promo-smart" href="{{ route('shop', ['category' => 'smart-home']) }}" style="--promo-image:url('https://images.unsplash.com/photo-1558002038-1055907df827?auto=format&fit=crop&w=1200&q=85')">
            <span class="promo-label">SMART LIVING · SAVE UP TO 30%</span><h1>Smart Home<br>Essentials Bundle</h1><p>Make your home work smarter, every day.</p><span class="promo-link">Shop smart home <b>→</b></span>
        </a>
        <a class="promo-card promo-large promo-audio" href="{{ route('shop', ['category' => 'audio']) }}" style="--promo-image:url('https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=1200&q=85')">
            <span class="promo-label">SOUND THAT MOVES YOU</span><h2>JBL Wireless<br>Headphones</h2><p>Find your focus. Feel every beat.</p><span class="promo-link">Discover audio <b>→</b></span>
        </a>
        <a class="promo-card promo-small promo-computing" href="{{ route('shop', ['category' => 'computing']) }}" style="--promo-image:url('https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=900&q=85')">
            <span class="promo-label">WORK, REIMAGINED</span><h2>Apple Slim Laptop</h2><p>Thin design. Big ideas.</p><span class="promo-link">Explore laptops <b>→</b></span>
        </a>
        <a class="promo-card promo-small promo-earbuds" href="{{ route('shop', ['category' => 'audio']) }}" style="--promo-image:url('https://images.unsplash.com/photo-1606220945770-b5b6c2c55bf1?auto=format&fit=crop&w=900&q=85')">
            <span class="promo-label">MADE FOR YOUR MOMENT</span><h2>JBL Live Over-Ear<br>Headphones</h2><p>Big sound. Zero distractions.</p><span class="promo-link">Listen in <b>→</b></span>
        </a>
        <a class="promo-card promo-small promo-wearable" href="{{ route('shop', ['category' => 'wearables']) }}" style="--promo-image:url('https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=900&q=85')">
            <span class="promo-label">YOUR DAY, IN SYNC</span><h2>Apple Smartwatch<br>Series 9</h2><p>More insight, right on your wrist.</p><span class="promo-link">Shop wearables <b>→</b></span>
        </a>
    </section>

    <section class="section-block categories-section">
        <div class="section-heading"><div><span class="section-kicker">FIND YOUR NEXT FAVORITE</span><h2>Featured categories</h2></div><a class="text-link" href="{{ route('shop') }}">Browse all categories <span>→</span></a></div>
        <div class="category-grid">
            @foreach($categories as $category)
                @php
                    $firstProduct = $products->get($category->id, collect())->first();
                    $categoryImage = $category->image ?? null;
                    $image = $categoryImage
                        ? 'https://images.unsplash.com/' . $categoryImage . '?auto=format&fit=crop&w=700&q=80'
                        : ($firstProduct->image_url ?? 'https://images.unsplash.com/photo-1498049794561-7780e7231661?auto=format&fit=crop&w=700&q=80');
                    $itemCount = $products->get($category->id, collect())->count();
                @endphp
                <a class="category-card" href="{{ route('shop', ['category' => $category->id]) }}">
                    <img src="{{ $image }}" alt="" loading="lazy"><span class="category-shade"></span>
                    <span class="category-card-copy"><strong>{{ $category->name }}</strong><small>{{ $itemCount }} curated picks</small></span><span class="category-arrow">↗</span>
                </a>
            @endforeach
        </div>
    </section>

    <section class="deal-strip">
        <div><span class="deal-icon">✦</span><div><strong>Today’s best deals</strong><small>Fresh finds. Better prices. While they last.</small></div></div>
        <a href="{{ route('shop') }}">Explore all deals <span>→</span></a>
    </section>

    <section class="section-block featured-section">
        <div class="section-heading"><div><span class="section-kicker">HAND-PICKED FOR YOU</span><h2>Popular right now</h2></div><a class="text-link" href="{{ route('shop') }}">See all products <span>→</span></a></div>
        <div class="product-grid">
            @foreach($featured as $product)
                @php
                    $price = $product->sale_price ?: $product->price;
                    $productImage = $product->image_url ?: 'https://images.unsplash.com/photo-1498049794561-7780e7231661?auto=format&fit=crop&w=800&q=80';
                @endphp
                <article class="market-product-card">
                    <div class="product-image-wrap"><img src="{{ $productImage }}" alt="{{ $product->name }}" loading="lazy">
                        @if(!empty($product->badge))<span class="product-badge">{{ $product->badge }}</span>@endif
                        @auth
                            <form method="post" action="{{ route('wishlist.toggle', $product->id) }}" class="wishlist-form">@csrf<button class="wishlist-button {{ in_array((int) $product->id, $wishlistIds ?? [], true) ? 'is-saved' : '' }}" type="submit" aria-label="{{ in_array((int) $product->id, $wishlistIds ?? [], true) ? 'Remove from' : 'Save to' }} wishlist">{{ in_array((int) $product->id, $wishlistIds ?? [], true) ? '♥' : '♡' }}</button></form>
                        @else
                            <a class="wishlist-button" href="{{ route('login') }}" aria-label="Sign in to save {{ $product->name }}">♡</a>
                        @endauth
                    </div>
                    <div class="product-card-body"><div class="product-rating"><span>★ ★ ★ ★ ★</span><small>{{ number_format((float) ($product->rating ?? 4.8), 1) }} ({{ $product->review_count ?? 0 }})</small></div>
                        <h3><a href="{{ route('shop') }}">{{ $product->name }}</a></h3><p>{{ \Illuminate\Support\Str::limit($product->description ?? '', 70) }}</p>
                        <div class="product-buy-row"><strong>Rs. {{ number_format((float) $price, 2) }}</strong>
                            @auth
                                <form method="post" action="{{ route('cart.add') }}">@csrf<input type="hidden" name="variant_id" value="{{ $product->variant_id }}"><button class="add-cart" type="submit" aria-label="Add {{ $product->name }} to cart">+</button></form>
                            @else
                                <a href="{{ route('login') }}" class="add-cart" aria-label="Sign in to add {{ $product->name }} to cart">+</a>
                            @endauth
                        </div><span class="shipping-note">✓ &nbsp;Free shipping available</span>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="campaign-banner">
        <div><span class="section-kicker">A LITTLE UPGRADE GOES A LONG WAY</span><h2>Make your everyday<br>feel extraordinary.</h2><p>Explore thoughtful tech and accessories picked for the way you live.</p><a href="{{ route('shop') }}" class="button-primary">Explore the collection <span>→</span></a></div>
        <img src="https://images.unsplash.com/photo-1498049794561-7780e7231661?auto=format&fit=crop&w=900&q=85" alt="A selection of modern electronics" loading="lazy">
    </section>
</div>
@endsection
