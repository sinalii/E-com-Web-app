<span class="admin-sidebar-icon" aria-hidden="true">
    @switch($name)
        @case('dashboard')
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="5" rx="2"/><rect x="13" y="10" width="8" height="11" rx="2"/><rect x="3" y="13" width="8" height="8" rx="2"/></svg>
            @break
        @case('products')
            <svg viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="M3 8v9l9 5 9-5V8M12 13v9"/></svg>
            @break
        @case('categories')
            <svg viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5M3 16l9 5 9-5"/></svg>
            @break
        @case('brands')
            <svg viewBox="0 0 24 24"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V4h9l8.6 6.6a2 2 0 0 1 0 2.8Z"/><circle cx="7.5" cy="8" r="1"/></svg>
            @break
        @case('customers')
            <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.3 2.8-5 6.5-5s5.9 1.7 6.5 5M16 5.2a3.5 3.5 0 0 1 0 6.7M17 15c2.6.4 4.1 2 4.5 5"/></svg>
            @break
        @case('orders')
            <svg viewBox="0 0 24 24"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2Z"/><path d="M9 3.5h6v3H9zM8 12h8M8 16h8"/></svg>
            @break
        @case('payments')
            <svg viewBox="0 0 24 24"><rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M3 10h18M7 15h4"/></svg>
            @break
        @case('coupons')
            <svg viewBox="0 0 24 24"><path d="M3 7a2 2 0 0 0 0 4v2a2 2 0 0 0 0 4h18a2 2 0 0 0 0-4v-2a2 2 0 0 0 0-4H3Z"/><path d="M13 7v2m0 3v2m0 3v2"/></svg>
            @break
        @case('inventory')
            <svg viewBox="0 0 24 24"><path d="M4 4h16v5H4zM4 11h7v9H4zM13 11h7v9h-7z"/><path d="M7 6.5h.01M10 6.5h.01"/></svg>
            @break
        @case('reviews')
            <svg viewBox="0 0 24 24"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3Z"/></svg>
            @break
        @case('reports')
            <svg viewBox="0 0 24 24"><path d="M4 20V4M4 20h17"/><path d="m7 15 4-4 3 2 6-7"/><path d="M16 6h4v4"/></svg>
            @break
        @case('settings')
            <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.2"/><path d="M2.5 20c.6-3.2 2.8-4.8 6.5-4.8 1.5 0 2.8.3 3.8 1M18 13.5l.8 1.4 1.6.3v1.6l-1.6.3-.8 1.4-1.4-.8-1.4.8-.8-1.4-1.6-.3v-1.6l1.6-.3.8-1.4 1.4.8 1.4-.8Z"/></svg>
            @break
    @endswitch
</span>
