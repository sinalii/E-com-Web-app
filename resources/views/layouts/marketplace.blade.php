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
    <link href="{{ asset('css/marketplace.css') }}?v={{ filemtime(public_path('css/marketplace.css')) }}" rel="stylesheet">
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
            @unless(request()->routeIs('admin.*'))
            <form class="search-form" action="{{ route('shop') }}" method="get">
                <label class="sr-only" for="market-search">Search products</label>
                <select aria-label="Search category" name="category"><option value="">All categories</option>@foreach(($categories ?? collect()) as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
                <input id="market-search" name="q" type="search" placeholder="Search for anything..." value="{{ request('q') }}">
                <button type="submit" aria-label="Search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m16.5 16.5 4 4"></path></svg><span>Search</span></button>
            </form>
            @endunless
            <div class="header-actions">
                <a class="header-action account-action" href="{{ auth()->check() ? (auth()->user()->isAdmin() ? route('admin.dashboard') : route('account')) : route('login') }}"><span class="action-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"></circle><path d="M5 20c.8-3.3 3.2-5 7-5s6.2 1.7 7 5"></path></svg></span><span><small>{{ auth()->check() ? 'Welcome back' : 'Hello, sign in' }}</small><strong>{{ auth()->check() ? auth()->user()->name : 'Sign in' }}</strong></span></a>
                @if(auth()->check() && auth()->user()->isAdmin())
                    <a class="signup-link" href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
                    <form class="admin-header-logout" method="post" action="{{ route('logout') }}">@csrf<button class="admin-header-signout" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3"></path><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"></path></svg>Sign out</button></form>
                @endif
                @guest<a class="signup-link" href="{{ route('register') }}">Sign up</a>@endguest
                @unless(request()->routeIs('admin.*'))
                <a class="header-action icon-action" href="{{ auth()->check() ? (auth()->user()->isAdmin() ? route('admin.dashboard') : route('wishlist')) : route('login') }}" aria-label="Wishlist"><svg viewBox="0 0 24 24"><path d="M20.4 5.8a5 5 0 0 0-7.1 0L12 7.1l-1.3-1.3a5 5 0 0 0-7.1 7.1l1.3 1.3L12 21l7.1-6.8 1.3-1.3a5 5 0 0 0 0-7.1Z"></path></svg></a>
                <a class="header-action cart-action" href="{{ auth()->check() && auth()->user()->isAdmin() ? route('admin.dashboard') : (auth()->check() ? route('cart') : route('login')) }}"><span class="cart-icon"><svg viewBox="0 0 24 24"><path d="M3 4h2l2.2 11.2a2 2 0 0 0 2 1.6h8.5a2 2 0 0 0 2-1.6L21 8H6"></path><circle cx="10" cy="21" r="1"></circle><circle cx="18" cy="21" r="1"></circle></svg><i>{{ $cartCount ?? 0 }}</i></span><span><small>Your</small><strong>Cart</strong></span></a>
                @endunless
            </div>
        </div>
    </header>
    @unless(request()->routeIs('admin.*'))
    <nav class="category-bar" aria-label="Main navigation"><div class="site-container category-bar-inner">
        <button class="all-categories" type="button" aria-controls="category-drawer" aria-expanded="false" data-category-drawer-open><svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>All Categories</button>
        <div class="nav-categories">@foreach(($categories ?? collect())->take(5) as $category)<a href="{{ route('shop', ['category' => $category->id]) }}">{{ $category->name }}</a>@endforeach</div>
        @unless(auth()->check() && auth()->user()->isAdmin() && request()->routeIs('admin.*'))
        <div class="nav-promos"><a href="{{ route('shop', ['deal' => 1]) }}">Today's Deals</a><a href="{{ route('shop', ['sort' => 'orders']) }}">Best Sellers</a><a href="{{ route('shop', ['sort' => 'newest']) }}">New Arrivals</a><a href="{{ route('contact') }}">Customer Support</a></div>
        @endunless
    </div></nav>
    @endunless

    <div class="category-drawer-backdrop" data-category-drawer-close hidden></div>
    <aside class="category-drawer" id="category-drawer" aria-label="All categories" aria-hidden="true" inert>
        <svg class="category-drawer-icon-library" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <symbol id="drawer-icon-shirt" viewBox="0 0 24 24"><path d="m16 4 4 2 2 4-4 2v8H6v-8l-4-2 2-4 4-2a4 4 0 0 0 8 0Z"/></symbol>
            <symbol id="drawer-icon-phone" viewBox="0 0 24 24"><rect x="6" y="2" width="12" height="20" rx="2"/><path d="M11 18h2"/></symbol>
            <symbol id="drawer-icon-computer" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="13" rx="2"/><path d="M8 21h8m-4-5v5"/></symbol>
            <symbol id="drawer-icon-home" viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10H14v-7h-4v7H3z"/></symbol>
            <symbol id="drawer-icon-gem" viewBox="0 0 24 24"><path d="m6 3-5 6 11 13L23 9l-5-6zM1 9h22M6 3l6 19 6-19"/></symbol>
            <symbol id="drawer-icon-watch" viewBox="0 0 24 24"><rect x="5" y="7" width="14" height="10" rx="3"/><path d="m8 7 1-5h6l1 5m-8 10 1 5h6l1-5"/></symbol>
            <symbol id="drawer-icon-bag" viewBox="0 0 24 24"><path d="M5 8h14l1 13H4L5 8Zm4 0V6a3 3 0 0 1 6 0v2"/></symbol>
            <symbol id="drawer-icon-shoe" viewBox="0 0 24 24"><path d="M3 15c3 0 5-2 6-7l4 5 5 2 3 1v4H3z"/></symbol>
            <symbol id="drawer-icon-toy" viewBox="0 0 24 24"><path d="M7 10V5a2 2 0 1 1 4 0v5h2V5a2 2 0 1 1 4 0v6a5 5 0 0 1-5 5h-1l-3 5H5l2-6-3-3a2 2 0 0 1 3-3l2 2"/></symbol>
            <symbol id="drawer-icon-sport" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m8 4 4 4 5-1m3 5-5 2-1 6m-8-1 1-5-4-3"/></symbol>
            <symbol id="drawer-icon-car" viewBox="0 0 24 24"><path d="m5 11 2-5h10l2 5 3 2v5H2v-5z"/><circle cx="6" cy="17" r="2"/><circle cx="18" cy="17" r="2"/></symbol>
            <symbol id="drawer-icon-tools" viewBox="0 0 24 24"><path d="M14 7a5 5 0 0 0-7 7l-5 5 3 3 5-5a5 5 0 0 0 7-7l-3 3-3-3z"/></symbol>
            <symbol id="drawer-icon-book" viewBox="0 0 24 24"><path d="M4 4h6a3 3 0 0 1 3 3v14a3 3 0 0 0-3-3H4zM20 4h-4a3 3 0 0 0-3 3v14a3 3 0 0 1 3-3h4z"/></symbol>
            <symbol id="drawer-icon-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></symbol>
        </svg>
        <div class="category-drawer-header"><div><span class="section-kicker">MARKETLY MARKETPLACE</span><h2>Shop by Category</h2></div><button class="category-drawer-close" type="button" aria-label="Close categories" data-category-drawer-close><span></span><span></span></button></div>
        <a class="category-drawer-all" href="{{ route('shop') }}"><span class="category-drawer-all-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4zM8 9h8M8 13h5"/></svg></span><span><strong>Browse all products</strong><small>Explore everything in the marketplace</small></span><b aria-hidden="true">&#8594;</b></a>
        <div class="category-drawer-list">
            @foreach(($categoryTree ?? collect()) as $root)
                @php
                    $drawerCategory = request()->query('category');
                    $isDrawerRootSelected = (string) $drawerCategory === (string) $root->id || $drawerCategory === $root->slug;
                    $selectedChild = $root->children->first(fn ($child) => (string) $drawerCategory === (string) $child->id || $drawerCategory === $child->slug);
                    $icon = match (true) {
                        \Illuminate\Support\Str::contains($root->slug, ['clothing', 'apparel', 'underwear']) => 'shirt',
                        \Illuminate\Support\Str::contains($root->slug, ['phone', 'telecommunication']) => 'phone',
                        \Illuminate\Support\Str::contains($root->slug, ['computer', 'office', 'school']) => 'computer',
                        \Illuminate\Support\Str::contains($root->slug, ['home', 'garden', 'light', 'furniture']) => 'home',
                        \Illuminate\Support\Str::contains($root->slug, ['jewelry', 'wedding']) => 'gem',
                        \Illuminate\Support\Str::contains($root->slug, ['watch']) => 'watch',
                        \Illuminate\Support\Str::contains($root->slug, ['bag', 'luggage']) => 'bag',
                        \Illuminate\Support\Str::contains($root->slug, ['shoe']) => 'shoe',
                        \Illuminate\Support\Str::contains($root->slug, ['toy', 'kid', 'mother']) => 'toy',
                        \Illuminate\Support\Str::contains($root->slug, ['sport', 'outdoor']) => 'sport',
                        \Illuminate\Support\Str::contains($root->slug, ['automobile', 'motorcycle']) => 'car',
                        \Illuminate\Support\Str::contains($root->slug, ['tool', 'industrial']) => 'tools',
                        \Illuminate\Support\Str::contains($root->slug, ['book', 'music']) => 'book',
                        default => 'grid',
                    };
                    $drawerExpanded = $selectedChild || $isDrawerRootSelected;
                @endphp
                <section class="category-drawer-group {{ $drawerExpanded ? 'is-expanded is-current' : '' }}" data-category-group>
                    <button class="category-drawer-row" type="button" aria-expanded="{{ $drawerExpanded ? 'true' : 'false' }}" aria-controls="category-drawer-panel-{{ $root->id }}">
                        <span class="category-drawer-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#drawer-icon-{{ $icon }}"></use></svg></span>
                        <span class="category-drawer-name">{{ $root->name }}</span><span class="category-drawer-chevron" aria-hidden="true"></span>
                    </button>
                    <div class="category-drawer-panel" id="category-drawer-panel-{{ $root->id }}" aria-hidden="{{ $drawerExpanded ? 'false' : 'true' }}"><div class="category-drawer-panel-inner">
                        <a class="category-drawer-view-all {{ $isDrawerRootSelected ? 'is-selected' : '' }}" href="{{ route('shop', ['category' => $root->id]) }}">Shop all {{ $root->name }} <span>&#8594;</span></a>
                        <div class="category-drawer-children">
                            @foreach($root->children as $subcategory)
                                <a class="{{ $selectedChild && $selectedChild->id === $subcategory->id ? 'is-selected' : '' }}" href="{{ route('shop', ['category' => $subcategory->id]) }}">{{ $subcategory->name }}</a>
                            @endforeach
                        </div>
                    </div></div>
                </section>
            @endforeach
        </div>
    </aside>

    @if(session('success'))<div class="site-container"><div class="success-banner">{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="site-container"><div class="form-alert">{{ session('error') }}</div></div>@endif

    <main>@yield('content')</main>

    @unless(request()->routeIs('admin.*'))

    <section class="newsletter"><div class="site-container newsletter-inner"><div><span class="newsletter-kicker">THE MARKETLY EDIT</span><h2>Good finds, delivered.</h2><p>Get first access to fresh picks and offers worth opening.</p></div><form action="{{ route('newsletter.subscribe') }}" method="post">@csrf<label class="sr-only" for="newsletter-email">Email address</label><input id="newsletter-email" name="email" type="email" placeholder="Your email address" required><button type="submit">Join the list <span>→</span></button></form></div></section>
    <footer class="site-footer"><div class="site-container footer-grid">
        <div class="footer-brand"><a class="brand" href="{{ route('index') }}"><span class="brand-mark">m</span>marketly<span class="brand-dot">.</span></a><p>Discover everyday essentials and unexpected favorites from a marketplace made for you.</p><div class="social-links"><a href="#facebook" aria-label="Facebook">f</a><a href="#instagram" aria-label="Instagram">ig</a><a href="#youtube" aria-label="YouTube">▶</a></div></div>
        <div><h3>Shop</h3><a href="{{ route('shop') }}">All products</a><a href="{{ route('shop', ['deal' => 1]) }}">Today's deals</a><a href="{{ route('shop', ['sort' => 'orders']) }}">Best sellers</a><a href="{{ route('shop', ['sort' => 'newest']) }}">New arrivals</a></div>
        <div><h3>Customer care</h3><a href="{{ route('contact') }}">Contact us</a><a href="{{ route('contact') }}">Shipping information</a><a href="{{ route('contact') }}">Returns &amp; refunds</a><a href="{{ route('contact') }}">FAQs</a></div>
        <div><h3>About Marketly</h3><a href="{{ route('about') }}">Our story</a><a href="{{ route('services') }}">Why shop with us</a><a href="{{ route('contact') }}">Contact the team</a></div>
    </div><div class="site-container footer-bottom"><span>© {{ date('Y') }} Marketly Marketplace. All rights reserved.</span><div><a href="#privacy">Privacy</a><a href="#terms">Terms</a><span>Secure shopping&nbsp; · &nbsp;🔒</span></div></div></footer>

    @endunless
    @stack('scripts')
    <script>
    (() => {
        const drawer = document.getElementById('category-drawer');
        const openButton = document.querySelector('[data-category-drawer-open]');
        const backdrop = document.querySelector('.category-drawer-backdrop');
        const closeButtons = document.querySelectorAll('[data-category-drawer-close]');
        const groups = [...drawer.querySelectorAll('[data-category-group]')];
        const setOpen = (open, restoreFocus = true) => {
            drawer.classList.toggle('is-open', open);
            drawer.setAttribute('aria-hidden', String(!open));
            drawer.inert = !open;
            backdrop.hidden = !open;
            openButton.setAttribute('aria-expanded', String(open));
            document.body.classList.toggle('category-drawer-open', open);
            if (open) {
                drawer.querySelector('.category-drawer-close').focus();
                requestAnimationFrame(() => drawer.querySelector('.category-drawer-group.is-current')?.scrollIntoView({ block: 'nearest', behavior: 'smooth' }));
            } else if (restoreFocus) openButton.focus();
        };
        openButton.addEventListener('click', () => setOpen(true));
        closeButtons.forEach((button) => button.addEventListener('click', () => setOpen(false)));
        groups.forEach((group) => {
            const button = group.querySelector('.category-drawer-row');
            const panel = group.querySelector('.category-drawer-panel');
            button.addEventListener('click', () => {
                const shouldExpand = !group.classList.contains('is-expanded');
                groups.forEach((other) => other.classList.remove('is-current'));
                groups.forEach((other) => {
                    const expanded = other === group && shouldExpand;
                    other.classList.toggle('is-expanded', expanded);
                    other.querySelector('.category-drawer-row').setAttribute('aria-expanded', String(expanded));
                    other.querySelector('.category-drawer-panel').setAttribute('aria-hidden', String(!expanded));
                });
                if (shouldExpand) {
                    group.classList.add('is-current');
                    requestAnimationFrame(() => {
                        group.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                        window.setTimeout(() => {
                            const list = drawer.querySelector('.category-drawer-list');
                            const panelBottom = panel.getBoundingClientRect().bottom;
                            const listBottom = list.getBoundingClientRect().bottom;
                            if (panelBottom > listBottom) list.scrollBy({ top: panelBottom - listBottom + 16, behavior: 'smooth' });
                        }, 260);
                    });
                }
            });
        });
        drawer.querySelectorAll('.category-drawer-children a, .category-drawer-view-all, .category-drawer-all').forEach((link) => {
            link.addEventListener('click', () => setOpen(false, false));
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && drawer.classList.contains('is-open')) setOpen(false);
            if (event.key === 'Tab' && drawer.classList.contains('is-open')) {
                const focusable = [...drawer.querySelectorAll('button:not(:disabled), a[href]')].filter((element) => element.offsetParent !== null);
                const first = focusable[0];
                const last = focusable[focusable.length - 1];
                if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
                else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
            }
        });
    })();
    </script>
    <div class="market-toast" role="status" aria-live="polite" aria-atomic="true" data-market-toast></div>
    <script>
    (() => {
        const toast = document.querySelector('[data-market-toast]');
        let toastTimer;
        const notify = (message, isError = false) => {
            toast.textContent = message;
            toast.classList.toggle('is-error', isError);
            toast.classList.add('is-visible');
            window.clearTimeout(toastTimer);
            toastTimer = window.setTimeout(() => toast.classList.remove('is-visible'), 2800);
        };

        document.addEventListener('submit', async (event) => {
            const form = event.target.closest('form.async-action-form');
            if (!form) return;
            event.preventDefault();
            const button = event.submitter || form.querySelector('button[type="submit"]');
            if (!button || button.disabled) return;
            const originalText = button.textContent;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');

            try {
                const response = await fetch(form.action, {
                    method: (form.method || 'POST').toUpperCase(),
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const payload = await response.json();
                if (!response.ok) {
                    const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null;
                    throw new Error(firstError || payload.message || 'That action could not be completed.');
                }

                if (Object.hasOwn(payload, 'saved')) {
                    button.classList.toggle('is-saved', payload.saved);
                    button.textContent = payload.saved ? '♥' : '♡';
                    button.setAttribute('aria-label', payload.saved ? 'Remove from wishlist' : 'Save to wishlist');
                }
                if (Object.hasOwn(payload, 'cart_count')) {
                    document.querySelectorAll('[data-cart-count]').forEach((count) => { count.textContent = payload.cart_count; });
                    button.classList.add('is-added');
                    button.textContent = '✓';
                    window.setTimeout(() => { button.textContent = originalText; button.classList.remove('is-added'); }, 1300);
                }
                notify(payload.message || 'Updated successfully.');
            } catch (error) {
                notify(error.message || 'Please try again.', true);
            } finally {
                button.disabled = false;
                button.removeAttribute('aria-busy');
            }
        });
    })();
    </script>
</body>
</html>
