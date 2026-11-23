@extends('layouts.marketplace')

@section('title', $product->name.' | Marketly')

@section('content')
@php
    $images = $product->images->filter(fn ($image) => filled($image->image_url))->values();
    $mainImage = $images->first()?->image_url;
    if ($mainImage && !\Illuminate\Support\Str::startsWith($mainImage, ['http://', 'https://', '/'])) $mainImage = asset('furni/images/'.$mainImage);
    $defaultVariant = $product->variants->first(fn ($variant) => $variant->stock_quantity === null || $variant->stock_quantity > 0) ?: $product->variants->first();
    $defaultPrice = $defaultVariant ? ($defaultVariant->sale_price ?: $defaultVariant->price) : null;
    $roundedRating = $product->rating ? max(0, min(5, (int) round((float) $product->rating))) : 0;
@endphp
<main class="site-container product-detail-page">
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <a href="{{ route('index') }}">Home</a><span>›</span><a href="{{ route('shop') }}">Shop</a><span>›</span>
        <a href="{{ route('shop', ['category' => $product->category_id]) }}">{{ $product->category_name }}</a><span>›</span><strong>{{ $product->name }}</strong>
    </nav>

    <section class="product-detail">
        <div class="product-gallery" data-product-gallery>
            <div class="product-gallery-main">
                @if($mainImage)<button type="button" class="product-gallery-open" data-open-image-viewer aria-label="Open larger product image viewer"><img src="{{ $mainImage }}" alt="{{ $product->name }}" data-product-main-image><span>Click to zoom</span></button>@else<div class="product-gallery-empty">No product images available</div>@endif
                @if($images->count() > 1)<button type="button" class="product-gallery-nav product-gallery-previous" data-gallery-previous aria-label="Previous product image">‹</button><button type="button" class="product-gallery-nav product-gallery-next" data-gallery-next aria-label="Next product image">›</button>@endif
            </div>
            @if($images->count() > 1)
                <div class="product-gallery-thumbnails" aria-label="Product images">
                    @foreach($images as $image)
                        @php
                            $thumbnail = $image->image_url;
                            if (!\Illuminate\Support\Str::startsWith($thumbnail, ['http://', 'https://', '/'])) $thumbnail = asset('furni/images/'.$thumbnail);
                        @endphp
                        <button class="{{ $loop->first ? 'is-selected' : '' }}" type="button" data-product-thumbnail="{{ $thumbnail }}" aria-label="View product image {{ $loop->iteration }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"><img src="{{ $thumbnail }}" alt="" loading="lazy"></button>
                    @endforeach
                </div>
            @endif
        </div>
        @if($mainImage)<dialog class="product-image-viewer" data-product-image-viewer aria-label="Product image viewer">
            <div class="product-image-viewer-toolbar"><strong>{{ $product->name }}</strong><div><button type="button" data-zoom-out aria-label="Zoom out">−</button><button type="button" data-zoom-in aria-label="Zoom in">+</button><button type="button" data-close-image-viewer aria-label="Close image viewer">×</button></div></div>
            <div class="product-image-viewer-stage"><button type="button" data-viewer-previous aria-label="Previous image">‹</button><img src="{{ $mainImage }}" alt="{{ $product->name }}" data-viewer-image><button type="button" data-viewer-next aria-label="Next image">›</button></div>
            <p>Use the arrows to browse images · use + / − to zoom</p>
        </dialog>@endif

        <div class="product-detail-copy">
            <div class="product-detail-meta">
                <a href="{{ route('shop', ['category' => $product->category_id]) }}">{{ $product->category_name }}</a>
                @if($product->brand_name)<span>·</span><span>{{ $product->brand_name }}</span>@endif
                @auth
                    <form method="post" action="{{ route('wishlist.toggle', $product->id) }}" class="async-action-form product-detail-wishlist-form">
                        @csrf
                        <button class="wishlist-button product-detail-wishlist {{ in_array((int) $product->id, $wishlistIds ?? [], true) ? 'is-saved' : '' }}" type="submit" aria-label="{{ in_array((int) $product->id, $wishlistIds ?? [], true) ? 'Remove from' : 'Save to' }} wishlist">{{ in_array((int) $product->id, $wishlistIds ?? [], true) ? '♥' : '♡' }}</button>
                    </form>
                @else
                    <a class="wishlist-button product-detail-wishlist" href="{{ route('login') }}" aria-label="Sign in to save {{ $product->name }}">♡</a>
                @endauth
            </div>
            <h1>{{ $product->name }}</h1>
            <div class="product-detail-rating">
                <span aria-label="{{ $product->rating ? number_format((float) $product->rating, 1).' out of 5 stars' : 'No ratings yet' }}">{{ str_repeat('★', $roundedRating).str_repeat('☆', 5 - $roundedRating) }}</span>
                <span>{{ $product->rating ? number_format((float) $product->rating, 1) : 'New' }}</span>
                <a href="#product-reviews">{{ $product->review_count }} {{ \Illuminate\Support\Str::plural('review', $product->review_count) }}</a>
            </div>
            <p class="product-detail-description">{{ $product->description ?: 'Discover more about this product and choose the option that works for you.' }}</p>

            @if($product->variants->isNotEmpty())
                <form class="product-purchase" method="post" action="{{ route('cart.add') }}" data-product-purchase>
                    @csrf
                    <fieldset class="variant-choices">
                        <legend>Choose an option</legend>
                        <div class="variant-choice-list">
                            @foreach($product->variants as $variant)
                                @php
                                    $variantOptions = $variant->options->map(fn ($option) => $option->option_name.': '.$option->option_value)->implode(' · ');
                                    $variantLabel = $variantOptions ?: ($variant->sku ?: 'Standard');
                                    $variantPrice = $variant->sale_price ?: $variant->price;
                                    $variantInStock = $variant->stock_quantity === null || $variant->stock_quantity > 0;
                                    $isDefaultVariant = $defaultVariant && $variant->id == $defaultVariant->id;
                                @endphp
                                <label class="variant-choice {{ !$variantInStock ? 'is-unavailable' : '' }}">
                                    <input type="radio" name="variant_id" value="{{ $variant->id }}"
                                        data-price="{{ $variantPrice !== null ? number_format((float) $variantPrice, 2, '.', '') : '' }}"
                                        data-stock="{{ $variant->stock_quantity ?? '' }}"
                                        data-stock-label="{{ $variantInStock ? ($variant->stock_quantity === null ? 'In stock' : $variant->stock_quantity.' in stock') : 'Out of stock' }}"
                                        {{ $isDefaultVariant ? 'checked' : '' }} {{ !$variantInStock ? 'disabled' : '' }} required>
                                    <span>{{ $variantLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <div class="product-detail-price">
                        <div class="product-detail-price-copy">
                            <small>Item total</small>
                            <strong data-product-price>{{ $defaultPrice !== null ? 'Rs. '.number_format((float) $defaultPrice, 2) : 'Ask for price' }}</strong>
                            <small data-product-unit-price>{{ $defaultPrice !== null ? 'Rs. '.number_format((float) $defaultPrice, 2).' each' : '' }}</small>
                        </div>
                        <span class="{{ $defaultVariant && $defaultVariant->stock_quantity === 0 ? 'is-out-of-stock' : '' }}" data-product-stock>
                            {{ !$defaultVariant ? 'Currently unavailable' : ($defaultVariant->stock_quantity === null ? 'In stock' : ($defaultVariant->stock_quantity > 0 ? $defaultVariant->stock_quantity.' in stock' : 'Out of stock')) }}
                        </span>
                    </div>
                    <div class="product-buy-controls">
                        <label for="product-quantity">Quantity</label>
                        <input id="product-quantity" type="number" name="quantity" value="1" min="1" max="99" inputmode="numeric">
                        @auth
                            <button class="button-primary product-add-button" type="submit" {{ !$product->variants->contains(fn ($variant) => $variant->stock_quantity === null || $variant->stock_quantity > 0) ? 'disabled' : '' }}>Add to cart <span aria-hidden="true">+</span></button>
                        @else
                            <a class="button-primary product-add-button" href="{{ route('login') }}">Sign in to buy</a>
                        @endauth
                    </div>
                </form>
            @else
                <div class="product-unavailable">This product is currently unavailable.</div>
            @endif

            <div class="product-service-points"><span>✓ Secure checkout</span><span>✓ Free shipping available</span><span>✓ Customer support</span></div>
            @if($product->sku)<p class="product-sku">SKU: {{ $product->sku }}</p>@endif
        </div>
    </section>

    @if($product->attributes->isNotEmpty())
        <section class="product-information">
            <div class="section-heading"><div><span class="section-kicker">PRODUCT INFORMATION</span><h2>Details &amp; specifications</h2></div></div>
            <dl class="product-specifications">
                @foreach($product->attributes as $attribute)
                    <div><dt>{{ $attribute->name }}</dt><dd>{{ $attribute->value }}</dd></div>
                @endforeach
            </dl>
        </section>
    @endif

    <section class="product-information product-reviews" id="product-reviews">
        <div class="section-heading"><div><span class="section-kicker">CUSTOMER FEEDBACK</span><h2>Reviews</h2></div></div>
        @auth
            <form class="product-review-form" method="post" action="{{ route('products.reviews.store', $product->slug ?: $product->id) }}">
                @csrf
                <h3>{{ $userReview ? 'Update your review' : 'Write a review' }}</h3>
                <fieldset class="review-rating-fieldset">
                    <legend>Your rating</legend>
                    <div class="review-rating-input">
                        @for($rating = 5; $rating >= 1; $rating--)
                            <input id="review-rating-{{ $rating }}" type="radio" name="rating" value="{{ $rating }}" {{ (string) old('rating', $userReview->rating ?? '') === (string) $rating ? 'checked' : '' }} required>
                            <label for="review-rating-{{ $rating }}" aria-label="{{ $rating }} {{ \Illuminate\Support\Str::plural('star', $rating) }}">★</label>
                        @endfor
                    </div>
                    @error('rating')<p class="review-field-error">{{ $message }}</p>@enderror
                </fieldset>
                <label class="review-comment-label" for="review-comment">Your comment</label>
                <textarea id="review-comment" name="comment" rows="4" maxlength="2000" required placeholder="What did you think about this product?">{{ old('comment', $userReview->comment ?? '') }}</textarea>
                @error('comment')<p class="review-field-error">{{ $message }}</p>@enderror
                <button class="button-primary" type="submit">{{ $userReview ? 'Update review' : 'Post review' }}</button>
            </form>
        @else
            <p class="review-sign-in"><a href="{{ route('login') }}">Sign in</a> to rate and review this product.</p>
        @endauth
        @forelse($product->reviews as $review)
            @php $reviewRating = max(0, min(5, (int) $review->rating)); @endphp
            <article class="product-review">
                <div><strong>{{ $review->reviewer_name ?: 'Marketly customer' }}</strong><span aria-label="{{ $reviewRating }} out of 5 stars">{{ str_repeat('★', $reviewRating).str_repeat('☆', 5 - $reviewRating) }}</span></div>
                @if($review->comment)<p>{{ $review->comment }}</p>@endif
            </article>
        @empty
            <p class="product-no-reviews">There are no reviews for this product yet.</p>
        @endforelse
    </section>
</main>
@endsection

@push('scripts')
<script>
(() => {
    const gallery = document.querySelector('[data-product-gallery]');
    if (gallery && gallery.querySelector('[data-product-main-image]')) {
        const mainImage = gallery.querySelector('[data-product-main-image]');
        const thumbnails = [...gallery.querySelectorAll('[data-product-thumbnail]')];
        const sources = thumbnails.length ? thumbnails.map(button => button.dataset.productThumbnail) : [mainImage.src];
        const viewer = document.querySelector('[data-product-image-viewer]');
        const viewerImage = viewer?.querySelector('[data-viewer-image]');
        let selected = 0, zoom = 1;
        const show = index => {
            selected = (index + sources.length) % sources.length;
            mainImage.src = sources[selected];
            if (viewerImage) { viewerImage.src = sources[selected]; viewerImage.style.transform = `scale(${zoom})`; }
            thumbnails.forEach((button, i) => {
                button.classList.toggle('is-selected', i === selected);
                button.setAttribute('aria-pressed', i === selected ? 'true' : 'false');
            });
        };
        thumbnails.forEach((button,index) => button.addEventListener('click', () => show(index)));
        gallery.querySelector('[data-gallery-previous]')?.addEventListener('click', () => show(selected-1));
        gallery.querySelector('[data-gallery-next]')?.addEventListener('click', () => show(selected+1));
        gallery.querySelector('[data-open-image-viewer]')?.addEventListener('click', () => { zoom=1; show(selected); viewer?.showModal(); });
        viewer?.querySelector('[data-close-image-viewer]')?.addEventListener('click', () => viewer.close());
        viewer?.querySelector('[data-viewer-previous]')?.addEventListener('click', () => show(selected-1));
        viewer?.querySelector('[data-viewer-next]')?.addEventListener('click', () => show(selected+1));
        viewer?.querySelector('[data-zoom-in]')?.addEventListener('click', () => { zoom=Math.min(3,zoom+.25); show(selected); });
        viewer?.querySelector('[data-zoom-out]')?.addEventListener('click', () => { zoom=Math.max(1,zoom-.25); show(selected); });
        viewer?.addEventListener('click', event => { if (event.target === viewer) viewer.close(); });
        viewer?.addEventListener('keydown', event => {
            if (event.key === 'ArrowRight') { event.preventDefault(); show(selected+1); }
            if (event.key === 'ArrowLeft') { event.preventDefault(); show(selected-1); }
        });
    }

    const purchase = document.querySelector('[data-product-purchase]');
    if (!purchase) return;
    const price = purchase.querySelector('[data-product-price]');
    const unitPrice = purchase.querySelector('[data-product-unit-price]');
    const stock = purchase.querySelector('[data-product-stock]');
    const quantity = purchase.querySelector('[name="quantity"]');
    const formatPrice = (amount) => `Rs. ${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    const updatePrice = () => {
        const selected = purchase.querySelector('[name="variant_id"]:checked');
        const amount = selected ? selected.dataset.price : '';
        const count = Number(quantity.value);
        const safeCount = Number.isFinite(count) && count > 0 ? count : 1;
        price.textContent = amount ? formatPrice(Number(amount) * safeCount) : 'Ask for price';
        unitPrice.textContent = amount ? `${formatPrice(Number(amount))} each` : '';
    };
    purchase.querySelectorAll('[name="variant_id"]').forEach((input) => {
        input.addEventListener('change', () => {
            stock.textContent = input.dataset.stockLabel;
            stock.classList.toggle('is-out-of-stock', input.dataset.stock !== '' && Number(input.dataset.stock) < 1);
            quantity.max = input.dataset.stock && Number(input.dataset.stock) > 0 ? Math.min(99, Number(input.dataset.stock)) : 99;
            if (Number(quantity.value) > Number(quantity.max)) quantity.value = quantity.max;
            updatePrice();
        });
    });
    quantity.addEventListener('input', updatePrice);
    const selectedVariant = purchase.querySelector('[name="variant_id"]:checked');
    if (selectedVariant) selectedVariant.dispatchEvent(new Event('change'));
})();
</script>
@endpush
