<button class="admin-menu-toggle" type="button" data-admin-menu-toggle aria-controls="admin-sidebar" aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg><span>Menu</span></button>
<div class="admin-sidebar-backdrop" data-admin-sidebar-backdrop></div>
<nav id="admin-sidebar" class="admin-sidebar" aria-label="Admin navigation" aria-hidden="true">
    <button class="admin-drawer-close" type="button" data-admin-menu-close aria-label="Close admin menu">×</button>
    <div class="admin-drawer-heading">
        <div><span class="section-kicker">MARKETLY</span><strong>Admin Panel</strong></div>
    </div>
    <a href="{{ route('admin.dashboard') }}" class="admin-sidebar-link {{ $section === 'dashboard' ? 'is-current' : '' }}"><x-admin-nav-icon name="dashboard" /><span>Overview</span></a>
    @foreach($sections as $key => $label)
        <a href="{{ route('admin.section', $key) }}" class="admin-sidebar-link {{ $section === $key ? 'is-current' : '' }}"><x-admin-nav-icon :name="$key" /><span>{{ $label }}</span></a>
    @endforeach
</nav>

@once
    @push('scripts')
        <script>
            (() => {
                const toggle = document.querySelector('[data-admin-menu-toggle]');
                const drawer = document.getElementById('admin-sidebar');
                const backdrop = document.querySelector('[data-admin-sidebar-backdrop]');
                const closeButton = document.querySelector('[data-admin-menu-close]');

                if (!toggle || !drawer || !backdrop) return;

                const setOpen = (open) => {
                    drawer.classList.toggle('is-open', open);
                    backdrop.classList.toggle('is-visible', open);
                    drawer.setAttribute('aria-hidden', String(!open));
                    toggle.setAttribute('aria-expanded', String(open));
                    document.body.classList.toggle('admin-drawer-open', open);
                    if (!open) toggle.focus();
                };

                toggle.addEventListener('click', () => setOpen(!drawer.classList.contains('is-open')));
                closeButton?.addEventListener('click', () => setOpen(false));
                backdrop.addEventListener('click', () => setOpen(false));
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && drawer.classList.contains('is-open')) setOpen(false);
                });
            })();
        </script>
    @endpush
@endonce
