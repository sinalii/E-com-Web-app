@extends('layouts.marketplace')

@section('title', 'Shop all products | Marketly')

@section('content')
<div class="site-container shop-page">
    <div class="breadcrumbs"><a href="{{ route('index') }}">Home</a><span>›</span><strong>Shop</strong></div>
    <section class="shop-heading"><div><span class="section-kicker">THE MARKETPLACE</span><h1>{{ $searchTerm ? 'Search results' : 'Explore all products' }}</h1><p>{{ $searchTerm ? 'Showing matches for “' . $searchTerm . '”' : 'Find something useful, beautiful, or a little bit of both.' }}</p></div><span class="results-count">{{ $products->flatten()->count() }} products</span></section>
    <div class="shop-layout">
        <aside class="shop-sidebar"><h2>Categories</h2><a class="sidebar-category {{ !request('category') ? 'selected' : '' }}" href="{{ route('shop') }}">All products <span>{{ $products->flatten()->count() }}</span></a>
            @foreach($categories as $category)
                <a class="sidebar-category {{ (string) request('category') === (string) $category->id ? 'selected' : '' }}" href="{{ route('shop', array_filter(['category' => $category->id, 'q' => request('q')])) }}">{{ $category->name }}<span>{{ $products->get($category->id, collect())->count() }}</span></a>
            @endforeach
            <div class="sidebar-note"><span>✦</span><strong>Shopping made simple</strong><p>Discover trusted picks and everyday essentials, all in one place.</p></div>
        </aside>
        <div class="shop-results">
            <div class="shop-toolbar"><span>Showing <strong>{{ $products->flatten()->count() }}</strong> products</span><label>Sort by <select><option>Recommended</option><option>Price: low to high</option><option>Price: high to low</option></select></label></div>
            @forelse($categories as $category)
                @php
                    $categoryProducts = $products->get($category->id, collect());
                @endphp
                @if($categoryProducts->isNotEmpty())
                    <section class="shop-category-section">
                        @if(!request('category'))<div class="shop-category-title"><h2>{{ $category->name }}</h2><a href="{{ route('shop', array_filter(['category' => $category->id, 'q' => request('q')])) }}">View category <span>→</span></a></div>@endif
                        <div class="product-grid shop-product-grid">
                            @foreach($categoryProducts as $product)
                                @php
                                    $price = $product->sale_price ?: $product->price;
                                    $image = $product->image_url ?: 'https://images.unsplash.com/photo-1498049794561-7780e7231661?auto=format&fit=crop&w=800&q=80';
                                    if (!\Illuminate\Support\Str::startsWith($image, ['http://', 'https://', '/'])) $image = asset('furni/images/' . $image);
                                @endphp
                                <article class="market-product-card">
                                    <div class="product-image-wrap"><img src="{{ $image }}" alt="{{ $product->name }}" loading="lazy">
                                        @if(!empty($product->badge))<span class="product-badge">{{ $product->badge }}</span>@endif
                                        @auth
                                            <form method="post" action="{{ route('wishlist.toggle', $product->id) }}" class="wishlist-form">@csrf<button class="wishlist-button {{ in_array((int) $product->id, $wishlistIds ?? [], true) ? 'is-saved' : '' }}" type="submit" aria-label="{{ in_array((int) $product->id, $wishlistIds ?? [], true) ? 'Remove from' : 'Save to' }} wishlist">{{ in_array((int) $product->id, $wishlistIds ?? [], true) ? '♥' : '♡' }}</button></form>
                                        @else
                                            <a class="wishlist-button" href="{{ route('login') }}" aria-label="Sign in to save {{ $product->name }}">♡</a>
                                        @endauth
                                    </div>
                                    <div class="product-card-body"><div class="product-rating"><span>★ ★ ★ ★ ★</span><small>{{ number_format((float) ($product->rating ?? 4.8), 1) }} ({{ $product->review_count ?? 0 }})</small></div>
                                        <h3><a href="#product">{{ $product->name }}</a></h3><p>{{ \Illuminate\Support\Str::limit($product->description ?? '', 70) }}</p>
                                        <div class="product-buy-row"><strong>{{ $price !== null ? 'Rs. ' . number_format((float) $price, 2) : 'Ask for price' }}</strong>
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
                @endif
            @empty
                <div class="empty-results"><span>⌕</span><h2>No products found</h2><p>Try a different search or browse all categories.</p><a class="button-primary" href="{{ route('shop') }}">Browse all products</a></div>
            @endforelse
        </div>
    </div>
</div>
@endsection
