@extends('layouts.marketplace')

@section('title', 'Marketly | Everything you love, all in one place')

@section('content')
<div class="site-container home-content">
    <section class="category-carousel" aria-label="Shop categories" data-category-carousel>
        <div class="category-carousel-heading"><div><span class="section-kicker">FIND YOUR NEXT FAVORITE</span><h2>Shop by category</h2></div></div>
        <div class="category-carousel-viewport">
        <div class="category-carousel-track" data-carousel-track>
            @foreach($categoryPages as $page)
                <div class="category-carousel-page promo-grid">
                    @foreach($page as $category)
                        @php
                            $image = $category->image_path
                                ? asset('storage/'.$category->image_path)
                                : ($categoryImages[$category->slug] ?? 'https://images.unsplash.com/photo-1498049794561-7780e7231661?auto=format&fit=crop&w=700&q=80');
                        @endphp
                        <a class="promo-card category-slide {{ $loop->first || $loop->iteration === 2 ? 'promo-large' : 'promo-small' }}" href="{{ route('shop', ['category' => $category->id]) }}" style="--promo-image:url('{{ $image }}')">
                            <span class="promo-label">MARKETLY COLLECTION</span><h2>{{ $category->name }}</h2><p>Explore the latest in {{ strtolower($category->name) }}.</p><span class="promo-link">Shop {{ $category->name }} <b>→</b></span>
                        </a>
                    @endforeach
                </div>
            @endforeach
        </div>
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
                    if (\Illuminate\Support\Str::startsWith($productImage, 'storage/')) $productImage = '/'.$productImage;
                    elseif (\Illuminate\Support\Str::startsWith($productImage, ['products/', 'categories/'])) $productImage = '/storage/'.$productImage;
                @endphp
                <article class="market-product-card">
                    <div class="product-image-wrap"><a class="product-image-link" href="{{ route('products.show', $product->slug ?: $product->id) }}" aria-label="View {{ $product->name }}"><img src="{{ $productImage }}" alt="{{ $product->name }}" loading="lazy"></a>
                        @if(!empty($product->badge))<span class="product-badge">{{ $product->badge }}</span>@endif
                        @auth
                            <form method="post" action="{{ route('wishlist.toggle', $product->id) }}" class="wishlist-form async-action-form">@csrf<button class="wishlist-button {{ in_array((int) $product->id, $wishlistIds ?? [], true) ? 'is-saved' : '' }}" type="submit" aria-label="{{ in_array((int) $product->id, $wishlistIds ?? [], true) ? 'Remove from' : 'Save to' }} wishlist">{{ in_array((int) $product->id, $wishlistIds ?? [], true) ? '♥' : '♡' }}</button></form>
                        @else
                            <a class="wishlist-button" href="{{ route('login') }}" aria-label="Sign in to save {{ $product->name }}">♡</a>
                        @endauth
                    </div>
                    <div class="product-card-body"><div class="product-rating"><span>★ ★ ★ ★ ★</span><small>{{ number_format((float) ($product->rating ?? 4.8), 1) }} ({{ $product->review_count ?? 0 }})</small></div>
                        <h3><a href="{{ route('products.show', $product->slug ?: $product->id) }}">{{ $product->name }}</a></h3><p>{{ \Illuminate\Support\Str::limit($product->description ?? '', 70) }}</p>
                        <div class="product-buy-row"><strong>Rs. {{ number_format((float) $price, 2) }}</strong>
                            @auth
                                <form method="post" action="{{ route('cart.add') }}" class="async-action-form">@csrf<input type="hidden" name="variant_id" value="{{ $product->variant_id }}"><button class="add-cart" type="submit" aria-label="Add {{ $product->name }} to cart">+</button></form>
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

@push('scripts')
<script>
(() => {
    const carousel = document.querySelector('[data-category-carousel]');
    if (!carousel) return;
    const track = carousel.querySelector('[data-carousel-track]');
    const viewport = carousel.querySelector('.category-carousel-viewport');
    let direction = 0;
    let lastStep = 0;
    let animationFrame = 0;
    const stepSize = () => viewport.clientWidth + parseFloat(getComputedStyle(track).gap || 0);
    const move = (amount) => {
        const max = Math.max(0, track.scrollWidth - viewport.clientWidth);
        track.style.transform = `translate3d(-${Math.max(0, Math.min(max, amount))}px, 0, 0)`;
    };
    const current = () => Math.abs(new DOMMatrixReadOnly(getComputedStyle(track).transform).m41);
    const animate = (time) => {
        if (!direction) return;
        if (time - lastStep > 1100) {
            move(current() + (direction * stepSize()));
            lastStep = time;
        }
        animationFrame = requestAnimationFrame(animate);
    };
    const setDirection = (value) => {
        direction = value;
        if (direction && !animationFrame) { lastStep = 0; animationFrame = requestAnimationFrame(animate); }
        if (!direction) { cancelAnimationFrame(animationFrame); animationFrame = 0; }
    };
    viewport.addEventListener('mousemove', (event) => {
        const bounds = viewport.getBoundingClientRect();
        const edge = Math.min(90, bounds.width * .18);
        if (event.clientX > bounds.right - edge) setDirection(1);
        else if (event.clientX < bounds.left + edge) setDirection(-1);
        else setDirection(0);
    });
    viewport.addEventListener('mouseleave', () => setDirection(0));
    window.addEventListener('resize', () => move(current()));
})();
</script>
@endpush
