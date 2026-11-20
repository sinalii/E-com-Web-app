<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#172554">
    <meta name="description" content="Shop trusted picks for your home, work, and everyday life.">
    <title>@yield('title', 'Marketly | Discover more')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ asset('css/marketplace.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <div class="utility-bar">
        <div class="site-container utility-inner">
            <span>Welcome to Marketly — a better way to discover.</span>
            <div class="utility-links"><a href="{{ route('contact') }}">Help &amp; Contact</a><span>Deliver to <strong>Sri Lanka</strong></span><button type="button">EN&nbsp; · &nbsp;LKR</button></div>
        </div>
    </div>
    <header class="site-header">
        <div class="site-container header-main">
            <a class="brand" href="{{ route('index') }}" aria-label="Marketly home"><span class="brand-mark">m</span>marketly<span class="brand-dot">.</span></a>
            <form class="search-form" action="{{ route('shop') }}" method="get">
                <label class="sr-only" for="market-search">Search products</label>
                <select aria-label="Search category" name="category"><option value="">All categories</option>@foreach(($categories ?? collect()) as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
                <input id="market-search" name="q" type="search" placeholder="Search for anything..." value="{{ request('q') }}">
                <button type="submit" aria-label="Search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m16.5 16.5 4 4"></path></svg><span>Search</span></button>
            </form>
            <div class="header-actions">
                <a class="header-action account-action" href="{{ auth()->check() ? route('account') : route('login') }}"><span class="action-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"></circle><path d="M5 20c.8-3.3 3.2-5 7-5s6.2 1.7 7 5"></path></svg></span><span><small>{{ auth()->check() ? 'Welcome back' : 'Hello, sign in' }}</small><strong>{{ auth()->check() ? auth()->user()->name : 'Sign in' }}</strong></span></a>
                @guest<a class="signup-link" href="{{ route('register') }}">Sign up</a>@endguest
                <a class="header-action icon-action" href="{{ auth()->check() ? route('wishlist') : route('login') }}" aria-label="Wishlist"><svg viewBox="0 0 24 24"><path d="M20.4 5.8a5 5 0 0 0-7.1 0L12 7.1l-1.3-1.3a5 5 0 0 0-7.1 7.1l1.3 1.3L12 21l7.1-6.8 1.3-1.3a5 5 0 0 0 0-7.1Z"></path></svg></a>
                <a class="header-action cart-action" href="{{ route('cart') }}"><span class="cart-icon"><svg viewBox="0 0 24 24"><path d="M3 4h2l2.2 11.2a2 2 0 0 0 2 1.6h8.5a2 2 0 0 0 2-1.6L21 8H6"></path><circle cx="10" cy="21" r="1"></circle><circle cx="18" cy="21" r="1"></circle></svg><i>{{ $cartCount ?? 0 }}</i></span><span><small>Your</small><strong>Cart</strong></span></a>
            </div>
        </div>
    </header>
    <nav class="category-bar" aria-label="Main navigation"><div class="site-container category-bar-inner">
        <a class="all-categories" href="{{ route('shop') }}"><svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>All Categories</a>
        <div class="nav-categories">@foreach(($categories ?? collect())->take(5) as $category)<a href="{{ route('shop', ['category' => $category->id]) }}">{{ $category->name }}</a>@endforeach</div>
        <div class="nav-promos"><a href="{{ route('shop') }}?deal=1"><span class="nav-spark">✦</span> Today's Deals</a><a href="{{ route('shop') }}">Best Sellers</a><a href="{{ route('shop') }}">New Arrivals</a><a href="{{ route('contact') }}">Customer Support</a></div>
    </div></nav>

    @if(session('success'))<div class="site-container"><div class="success-banner">{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="site-container"><div class="form-alert">{{ session('error') }}</div></div>@endif

    <main>@yield('content')</main>

    <section class="newsletter"><div class="site-container newsletter-inner"><div><span class="newsletter-kicker">THE MARKETLY EDIT</span><h2>Good finds, delivered.</h2><p>Get first access to fresh picks and offers worth opening.</p></div><form action="{{ route('newsletter.subscribe') }}" method="post">@csrf<label class="sr-only" for="newsletter-email">Email address</label><input id="newsletter-email" name="email" type="email" placeholder="Your email address" required><button type="submit">Join the list <span>→</span></button></form></div></section>
    <footer class="site-footer"><div class="site-container footer-grid">
        <div class="footer-brand"><a class="brand" href="{{ route('index') }}"><span class="brand-mark">m</span>marketly<span class="brand-dot">.</span></a><p>Discover everyday essentials and unexpected favorites from a marketplace made for you.</p><div class="social-links"><a href="#facebook" aria-label="Facebook">f</a><a href="#instagram" aria-label="Instagram">ig</a><a href="#youtube" aria-label="YouTube">▶</a></div></div>
        <div><h3>Shop</h3><a href="{{ route('shop') }}">All products</a><a href="{{ route('shop') }}">Today's deals</a><a href="{{ route('shop') }}">Best sellers</a><a href="{{ route('shop') }}">New arrivals</a></div>
        <div><h3>Customer care</h3><a href="{{ route('contact') }}">Contact us</a><a href="{{ route('contact') }}">Shipping information</a><a href="{{ route('contact') }}">Returns &amp; refunds</a><a href="{{ route('contact') }}">FAQs</a></div>
        <div><h3>About Marketly</h3><a href="{{ route('about') }}">Our story</a><a href="{{ route('services') }}">Why shop with us</a><a href="{{ route('contact') }}">Contact the team</a></div>
    </div><div class="site-container footer-bottom"><span>© {{ date('Y') }} Marketly Marketplace. All rights reserved.</span><div><a href="#privacy">Privacy</a><a href="#terms">Terms</a><span>Secure shopping&nbsp; · &nbsp;🔒</span></div></div></footer>
    @stack('scripts')
</body>
</html>
