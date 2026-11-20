@extends('layouts.marketplace')

@section('title', 'Shop all products | Marketly')

@section('content')
<div class="site-container shop-page">
    <div class="breadcrumbs"><a href="{{ route('index') }}">Home</a><span>›</span><strong>Shop</strong></div>
    <section class="shop-heading">
        <div><span class="section-kicker">THE MARKETPLACE</span><h1>{{ $selectedCategory?->name ?? ($searchTerm ? 'Search results' : 'Explore all products') }}</h1>
            <p>{{ $selectedCategory ? 'Browse '.$selectedCategory->name.' and refine the results.' : ($searchTerm ? 'Showing matches for “'.$searchTerm.'”' : 'Choose a category or use the filters to find what you need.') }}</p>
        </div>
        <span class="results-count">{{ $products->flatten()->unique('id')->count() }} products</span>
    </section>

    @if($selectedCategory && $selectedCategory->parent_id === null)
        <section class="subcategory-showcase" aria-label="Subcategories in {{ $selectedCategory->name }}">
            <div class="subcategory-tabs" role="tablist" aria-label="Category collections"><button class="active" type="button" role="tab" aria-selected="true">Recommended</button></div>
            <div class="subcategory-carousel" data-subcategory-carousel>
                <button class="subcategory-nav subcategory-nav-prev" type="button" aria-label="Scroll to previous subcategories" data-subcategory-prev>‹</button>
                <div class="subcategory-strip" data-subcategory-track>
                @foreach($recommendedSubcategories as $subcategory)
                    <a class="subcategory-tile" href="{{ route('shop', ['category' => $subcategory->id]) }}">
                        <img src="{{ $subcategory->image_url }}" alt="" loading="lazy">
                        <span>{{ $subcategory->name }}</span>
                    </a>
                @endforeach
                </div>
                <button class="subcategory-nav subcategory-nav-next" type="button" aria-label="Scroll to next subcategories" data-subcategory-next>›</button>
            </div>
        </section>
    @endif

    <div class="shop-layout">
        <aside class="shop-sidebar" aria-label="Product filters">
            <form class="sidebar-section filter-form" action="{{ route('shop') }}" method="get">
                @if(request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
                @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                <div class="filter-heading"><div><span class="section-kicker">REFINE YOUR SEARCH</span><h2>Filters</h2></div>@if(request()->hasAny(['min_price','max_price','min_rating','in_stock','filters']))<a href="{{ route('shop', array_filter(['category' => request('category'), 'q' => request('q')])) }}">Clear all</a>@endif</div>
                @if(!$selectedCategory || $selectedCategory->parent_id === null)
                    <p class="filter-hint">Choose a subcategory to see its product filters.</p>
                @elseif(empty($facets))
                    <p class="filter-hint">Filters will appear when this category has products with matching details.</p>
                @else
                    @foreach($facets as $facet)
                        <details class="filter-group" {{ request()->filled('filters.'.$facet->slug) || ($facet->slug === 'price' && request()->hasAny(['min_price','max_price'])) || ($facet->slug === 'customer-feedback' && request()->filled('min_rating')) ? 'open' : '' }}>
                            <summary>{{ $facet->name }}</summary>
                            @if($facet->input_type === 'range')
                                <div class="price-range"><label>Min<input type="number" name="min_price" min="0" value="{{ request('min_price') }}" placeholder="{{ number_format($facet->minimum, 0) }}"></label><label>Max<input type="number" name="max_price" min="0" value="{{ request('max_price') }}" placeholder="{{ number_format($facet->maximum, 0) }}"></label></div>
                            @elseif($facet->input_type === 'rating')
                                @foreach($facet->values as $rating)<label class="facet-option"><input type="radio" name="min_rating" value="{{ $rating->value }}" {{ (string) request('min_rating') === (string) $rating->value ? 'checked' : '' }}><span>{{ $rating->value }} stars &amp; up</span><small>{{ $rating->count }}</small></label>@endforeach
                            @elseif($facet->input_type === 'availability')
                                @foreach($facet->values as $option)<label class="facet-option"><input type="checkbox" name="in_stock" value="1" {{ request()->boolean('in_stock') ? 'checked' : '' }}><span>{{ $option->value }}</span><small>{{ $option->count }}</small></label>@endforeach
                            @else
                                @forelse($facet->values as $option)
                                    <label class="facet-option"><input type="checkbox" name="filters[{{ $facet->slug }}][]" value="{{ $option->value }}" {{ in_array($option->value, (array) request('filters.'.$facet->slug, []), true) ? 'checked' : '' }}><span>{{ $option->value }}</span><small>{{ $option->count }}</small></label>
                                @empty
                                    <p class="facet-empty">No matching options in this category yet.</p>
                                @endforelse
                            @endif
                        </details>
                    @endforeach
                    <button class="button-primary filter-apply" type="submit">Apply filters</button>
                @endif
            </form>
        </aside>

        <div class="shop-results">
            <form class="shop-toolbar" action="{{ route('shop') }}" method="get">
                @if(request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
                @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                @foreach(['min_price','max_price','min_rating','in_stock'] as $key)@if(request()->has($key))<input type="hidden" name="{{ $key }}" value="{{ request($key) }}">@endif @endforeach
                @foreach((array) request('filters', []) as $key => $values)@foreach((array) $values as $value)<input type="hidden" name="filters[{{ $key }}][]" value="{{ $value }}">@endforeach @endforeach
                <span>Showing <strong>{{ $products->flatten()->unique('id')->count() }}</strong> products</span>
                <label>Sort by <select name="sort" onchange="this.form.submit()"><option value="best_match" {{ request('sort', 'best_match') === 'best_match' ? 'selected' : '' }}>Best match</option><option value="orders" {{ request('sort') === 'orders' ? 'selected' : '' }}>Best selling</option><option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest arrivals</option><option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Price: low to high</option><option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Price: high to low</option><option value="rating" {{ request('sort') === 'rating' ? 'selected' : '' }}>Top rated</option><option value="discount" {{ request('sort') === 'discount' ? 'selected' : '' }}>Biggest discount</option></select></label>
            </form>

            @if($products->flatten()->isEmpty())
                <div class="empty-results"><span>⌕</span><h2>No products found</h2><p>Try changing the filters or browse another category.</p><a class="button-primary" href="{{ route('shop') }}">Browse all products</a></div>
            @else
                @foreach($productCategories as $category)
                    @php
                        $categoryProducts = $products->get($category->id, collect());
                    @endphp
                    <section class="shop-category-section">
                        @if(!$selectedCategory || $selectedCategory->parent_id === null)
                            @php
                                $parentCategory = $categoryTree->first(fn ($root) => $root->id === $category->parent_id);
                            @endphp
                            <div class="shop-category-title"><div><span>{{ $parentCategory?->name }}</span><h2>{{ $category->name }}</h2></div><a href="{{ route('shop', ['category' => $category->id]) }}">Filter this category <span>→</span></a></div>
                        @endif
                        <div class="product-grid shop-product-grid">
                            @foreach($categoryProducts as $product)
                                @php
                                    $price = $product->sale_price ?: $product->price;
                                    $image = $product->image_url ?: 'https://images.unsplash.com/photo-1498049794561-7780e7231661?auto=format&fit=crop&w=800&q=80';
                                    if (!\Illuminate\Support\Str::startsWith($image, ['http://', 'https://', '/'])) $image = asset('furni/images/' . $image);
                                @endphp
                                <article class="market-product-card">
                                    <div class="product-image-wrap"><a class="product-image-link" href="{{ route('products.show', $product->slug ?: $product->id) }}" aria-label="View {{ $product->name }}"><img src="{{ $image }}" alt="{{ $product->name }}" loading="lazy"></a>
                                        @auth<form method="post" action="{{ route('wishlist.toggle', $product->id) }}" class="wishlist-form async-action-form">@csrf<button class="wishlist-button {{ in_array((int) $product->id, $wishlistIds ?? [], true) ? 'is-saved' : '' }}" type="submit" aria-label="Toggle wishlist">{{ in_array((int) $product->id, $wishlistIds ?? [], true) ? '♥' : '♡' }}</button></form>
                                        @else<a class="wishlist-button" href="{{ route('login') }}" aria-label="Sign in to save {{ $product->name }}">♡</a>@endauth
                                    </div>
                                    <div class="product-card-body"><div class="product-rating"><span>★ ★ ★ ★ ★</span><small>{{ $product->rating ? number_format((float) $product->rating, 1) : 'New' }}{{ $product->review_count ? ' ('.$product->review_count.')' : '' }}</small></div>
                                        <h3><a href="{{ route('products.show', $product->slug ?: $product->id) }}">{{ $product->name }}</a></h3><p>{{ \Illuminate\Support\Str::limit($product->description ?? '', 70) }}</p>
                                        <div class="product-buy-row"><strong>{{ $price !== null ? 'Rs. ' . number_format((float) $price, 2) : 'Ask for price' }} </strong>@auth<form method="post" action="{{ route('cart.add') }}" class="async-action-form">@csrf<input type="hidden" name="variant_id" value="{{ $product->variant_id }}"><button class="add-cart" type="submit" aria-label="Add {{ $product->name }} to cart">+</button></form>@else<a href="{{ route('login') }}" class="add-cart" aria-label="Sign in to add {{ $product->name }} to cart">+</a>@endauth</div>
                                        <span class="shipping-note">{{ $product->stock_quantity > 0 ? '✓  In stock' : 'Currently unavailable' }}</span>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    document.querySelectorAll('[data-subcategory-carousel]').forEach((carousel) => {
        const track = carousel.querySelector('[data-subcategory-track]');
        const previous = carousel.querySelector('[data-subcategory-prev]');
        const next = carousel.querySelector('[data-subcategory-next]');
        const amount = () => Math.max(track.clientWidth * .82, 300);
        previous.addEventListener('click', () => track.scrollBy({ left: -amount(), behavior: 'smooth' }));
        next.addEventListener('click', () => track.scrollBy({ left: amount(), behavior: 'smooth' }));
        const update = () => {
            previous.disabled = track.scrollLeft <= 2;
            next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
        };
        track.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        update();
    });
})();
</script>
@endpush
