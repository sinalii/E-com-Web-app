@extends('layouts.marketplace')

@section('title', ($section === 'dashboard' ? 'Admin dashboard' : ($sections[$section] ?? 'Admin')) . ' | Marketly')

@section('content')
<div class="site-container inner-page admin-page">
    <div class="breadcrumbs"><strong>{{ $section === 'dashboard' ? 'Admin dashboard' : ($sections[$section] ?? 'Administration') }}</strong></div>
    <section class="account-heading"><div><span class="section-kicker">MARKETLY ADMINISTRATION</span><h1>{{ $section === 'dashboard' ? 'Store overview' : ($sections[$section] ?? 'Administration') }}</h1><p>Manage your store from one secure workspace.</p></div></section>
    <div class="admin-workspace">
        @include('pages.admin-sidebar')
        <div class="admin-main">

    @if($section === 'dashboard')
        <div class="admin-stats">
            <section class="account-card"><span class="section-kicker">PAID SALES</span><h2>Rs. {{ number_format($stats['sales'], 2) }}</h2><p>{{ number_format($stats['paid_orders']) }} paid orders</p></section>
            <section class="account-card"><span class="section-kicker">TOTAL ORDERS</span><h2>{{ number_format($stats['orders']) }}</h2><p>All active orders</p></section>
            <section class="account-card"><span class="section-kicker">CUSTOMERS</span><h2>{{ number_format($stats['customers']) }}</h2><p>Registered customer accounts</p></section>
            <section class="account-card"><span class="section-kicker">PRODUCTS</span><h2>{{ number_format($stats['products']) }}</h2><p>{{ number_format($stats['low_stock']) }} variants with stock at or below 5</p></section>
        </div>
        <div class="admin-content-grid">
            <section class="account-card admin-chart"><h2>Sales analytics</h2><p class="admin-muted">Paid order payments by month · last 12 months</p>
                @php($chartMax = max(1, ...$salesByMonth->values()->all()))
                <div class="admin-chart-bars">@foreach($salesByMonth as $month => $amount)<div class="admin-chart-column"><span title="Rs. {{ number_format($amount, 2) }}" style="height: {{ max(3, ($amount / $chartMax) * 100) }}%"></span><small>{{ $month }}</small></div>@endforeach</div>
            </section>
            <section class="account-card"><h2>Recent orders</h2>
                @forelse($recentOrders as $order)<div class="account-order"><div><strong>Order #{{ $order->id }}</strong><small>{{ $order->customer_name ?? 'Guest' }} · {{ \Illuminate\Support\Carbon::parse($order->created_at)->format('M j, Y') }}</small></div><div><span class="status-pill">{{ ucfirst($order->status ?? 'pending') }}</span><strong>Rs. {{ number_format((float) $order->total_amount, 2) }}</strong></div></div>
                @empty<p class="account-empty">Orders will appear here when customers check out.</p>@endforelse
                <a class="text-link" href="{{ route('admin.section', 'orders') }}">Manage orders →</a>
            </section>
        </div>
    @elseif($section === 'settings')
        <section class="account-card admin-form-card"><h2>Administrator profile</h2><p class="admin-muted">Update your administrator name, email, or password. Leave the password blank to keep it unchanged.</p>
            <form method="post" action="{{ route('admin.profile.update') }}" class="admin-form">@csrf @method('PUT')
                <label>Name<input name="name" value="{{ old('name', auth()->user()->name) }}" required maxlength="255"></label>
                <label>Email<input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required maxlength="255" autocomplete="email"></label>
                <label>New password<input type="password" name="password" autocomplete="new-password"></label>
                <label>Confirm new password<input type="password" name="password_confirmation" autocomplete="new-password"></label>
                @if($errors->any())<div class="form-alert">{{ $errors->first() }}</div>@endif
                <button class="button-primary" type="submit">Save settings</button>
            </form>
        </section>
    @else
        @if($section === 'categories')
            @php($editingIsMainCategory = $editing && $editing->parent_id === null)
            <div class="admin-category-forms">
                <section class="account-card admin-form-card admin-category-form-card">
                    <span class="section-kicker">MAIN CATEGORY</span>
                    <h2>{{ $editingIsMainCategory ? 'Edit Category' : 'Add New Category' }}</h2>
                    <form method="post" enctype="multipart/form-data" action="{{ $editingIsMainCategory ? route('admin.resource.update', ['categories', $editing->id]) : route('admin.resource.store', 'categories') }}" class="admin-form">
                        @csrf @if($editingIsMainCategory)@method('PUT')@endif
                        <input type="hidden" name="category_type" value="main">
                        <label>Category Name<input name="name" value="{{ old('category_type') === 'main' ? old('name') : (old('category_type') === null && $editingIsMainCategory ? $editing->name : '') }}" required maxlength="255"></label>
                        <label>Category Slug <small>Optional; generated from the name when blank.</small><input name="slug" value="{{ old('category_type') === 'main' ? old('slug') : (old('category_type') === null && $editingIsMainCategory ? $editing->slug : '') }}" maxlength="255"></label>
                        @if($editingIsMainCategory && $editing->image_path)<div class="admin-image-preview"><img src="{{ asset('storage/'.$editing->image_path) }}" alt="Current image for {{ $editing->name }}"><span>Current category image</span></div>@endif
                        <label class="admin-file-field"><span>Category Image {{ $editingIsMainCategory ? '' : '(required)' }}</span><input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" {{ $editingIsMainCategory ? '' : 'required' }}><small>JPG, JPEG, PNG, or WEBP · up to 5 MB{{ $editingIsMainCategory ? '. Leave empty to keep the current image.' : '' }}</small></label>
                        <label class="admin-checkbox"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $editingIsMainCategory ? (bool) $editing->is_active : ! $editing) ? 'checked' : '' }}> Active</label>
                        @if($errors->any() && old('category_type', $editingIsMainCategory ? 'main' : '') === 'main')<div class="form-alert">{{ $errors->first() }}</div>@endif
                        <button class="button-primary" type="submit">{{ $editingIsMainCategory ? 'Save Category' : 'Add Category' }}</button>
                        @if($editingIsMainCategory)<a class="text-link" href="{{ route('admin.section', 'categories') }}">Cancel edit</a>@endif
                    </form>
                </section>

                <section class="account-card admin-form-card admin-category-form-card">
                    <span class="section-kicker">CHILD CATEGORY</span>
                    <h2>{{ $editing && ! $editingIsMainCategory ? 'Edit Subcategory' : 'Add New Subcategory' }}</h2>
                    <form method="post" enctype="multipart/form-data" action="{{ $editing && ! $editingIsMainCategory ? route('admin.resource.update', ['categories', $editing->id]) : route('admin.resource.store', 'categories') }}" class="admin-form">
                        @csrf @if($editing && ! $editingIsMainCategory)@method('PUT')@endif
                        <input type="hidden" name="category_type" value="subcategory">
                        <label>Subcategory Name<input name="name" value="{{ old('category_type') === 'subcategory' ? old('name') : ($editing && ! $editingIsMainCategory ? $editing->name : '') }}" required maxlength="255"></label>
                        <label>Subcategory Slug <small>Optional; generated from the name when blank.</small><input name="slug" value="{{ old('category_type') === 'subcategory' ? old('slug') : ($editing && ! $editingIsMainCategory ? $editing->slug : '') }}" maxlength="255"></label>
                        <label>Parent Category<select name="parent_id" required><option value="">Choose a main category</option>@foreach($categoryParents as $parent)<option value="{{ $parent->id }}" {{ (string) old('category_type') === 'subcategory' ? ((string) old('parent_id') === (string) $parent->id ? 'selected' : '') : (($editing && ! $editingIsMainCategory && (string) $editing->parent_id === (string) $parent->id) ? 'selected' : '') }}>{{ $parent->name }}</option>@endforeach</select></label>
                        @if($editing && ! $editingIsMainCategory && $editing->image_path)<div class="admin-image-preview"><img src="{{ asset('storage/'.$editing->image_path) }}" alt="Current image for {{ $editing->name }}"><span>Current subcategory image</span></div>@endif
                        <label class="admin-file-field"><span>Subcategory Image {{ $editing && ! $editingIsMainCategory ? '' : '(required)' }}</span><input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" {{ $editing && ! $editingIsMainCategory ? '' : 'required' }}><small>JPG, JPEG, PNG, or WEBP · up to 5 MB{{ $editing && ! $editingIsMainCategory ? '. Leave empty to keep the current image.' : '' }}</small></label>
                        <label class="admin-checkbox"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" {{ old('category_type') === 'subcategory' ? (old('is_active') ? 'checked' : '') : (old('category_type') === null && $editing && ! $editingIsMainCategory ? ((bool) $editing->is_active ? 'checked' : '') : (! $editing ? 'checked' : '')) }}> Active</label>
                        @if($errors->any() && old('category_type', $editing && ! $editingIsMainCategory ? 'subcategory' : '') === 'subcategory')<div class="form-alert">{{ $errors->first() }}</div>@endif
                        <button class="button-primary" type="submit">{{ $editing && ! $editingIsMainCategory ? 'Save Subcategory' : 'Add Subcategory' }}</button>
                        @if($editing && ! $editingIsMainCategory)<a class="text-link" href="{{ route('admin.section', 'categories') }}">Cancel edit</a>@endif
                    </form>
                </section>
            </div>
        @elseif(in_array($section, ['products', 'brands', 'coupons', 'customers'], true) && ($section !== 'customers' || $editing))
            <section class="account-card admin-form-card admin-form-card--{{ $section }}"><span class="section-kicker">{{ strtoupper($sections[$section]) }}</span><h2>{{ $editing ? 'Edit record' : 'Add new record' }}</h2>
                @if($section === 'coupons')<p class="admin-muted">Coupons are stored in the existing coupon table. Customer checkout does not currently apply coupons.</p>@endif
                <form method="post" enctype="multipart/form-data" action="{{ $editing ? route('admin.resource.update', [$section, $editing->id]) : route('admin.resource.store', $section) }}" class="admin-form">
                    @csrf @if($editing)@method('PUT')@endif
                    @if($section === 'products' && $editing?->image_url)<div class="admin-image-preview"><img src="{{ $editing->image_url }}" alt="Current image for {{ $editing->name }}"><span>Current primary image</span></div>@endif
                    @foreach($fields as $field)
                        @php($value = old($field['name'], $editing->{$field['name']} ?? ($field['name'] === 'is_active' ? true : '')))
                        @if(($field['type'] ?? '') === 'checkbox')
                            <label class="admin-checkbox"><input type="checkbox" name="{{ $field['name'] }}" value="1" {{ $value ? 'checked' : '' }}> {{ $field['label'] }}</label>
                        @elseif(($field['type'] ?? '') === 'file')
                            <label class="admin-file-field"><span>{{ $field['label'] }}</span><input type="file" name="{{ $field['name'] }}" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG, or WEBP · up to 5 MB</small></label>
                        @elseif(($field['type'] ?? '') === 'main-category')
                            <label>Main Category<select id="product-main-category" name="main_category_id" data-subcategories-url="{{ route('admin.categories.subcategories', ['category' => '__CATEGORY__']) }}" required><option value="">Select Category</option>@foreach($mainCategories as $mainCategory)<option value="{{ $mainCategory->id }}" {{ (string) old('main_category_id', $editing->main_category_id ?? '') === (string) $mainCategory->id ? 'selected' : '' }}>{{ $mainCategory->name }}</option>@endforeach</select></label>
                        @elseif(($field['type'] ?? '') === 'subcategory')
                            <label>Subcategory<select id="product-subcategory" name="subcategory_id" data-selected-subcategory="{{ old('subcategory_id', $editing->subcategory_id ?? '') }}" disabled required><option value="">{{ old('main_category_id', $editing->main_category_id ?? '') ? 'Loading subcategories…' : 'Select a main category first' }}</option></select></label>
                        @elseif(($field['type'] ?? '') === 'textarea')
                            <label>{{ $field['label'] }}<textarea name="{{ $field['name'] }}" rows="3">{{ $value }}</textarea></label>
                        @elseif(($field['type'] ?? '') === 'select')
                            <label>{{ $field['label'] }}<select name="{{ $field['name'] }}">@foreach($field['options'] as $option => $optionLabel)<option value="{{ $option }}" {{ (string) $value === (string) $option ? 'selected' : '' }}>{{ $optionLabel }}</option>@endforeach</select></label>
                        @else
                            <label>{{ $field['label'] }}<input type="{{ $field['type'] ?? 'text' }}" name="{{ $field['name'] }}" value="{{ $value }}" {{ !empty($field['required']) ? 'required' : '' }}></label>
                        @endif
                    @endforeach
                    @if($errors->any())<div class="form-alert">{{ $errors->first() }}</div>@endif
                    <button class="button-primary" type="submit">{{ $editing ? 'Save changes' : 'Add record' }}</button>
                    @if($editing)<a class="text-link" href="{{ route('admin.section', $section) }}">Cancel edit</a>@endif
                </form>
            </section>
        @endif

        <section class="account-card admin-table-card"><h2>{{ $sections[$section] }}</h2>
            @if(in_array($section, ['products', 'categories', 'brands', 'customers', 'coupons'], true))
                @if($section === 'products')
                    <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Product</th><th>SKU</th><th>Category / Brand</th><th>From price</th><th>Stock</th><th>Actions</th></tr></thead><tbody>@forelse($records as $row)<tr><td><div class="admin-product-cell">@if($row->image_url)<img src="{{ $row->image_url }}" alt="">@else<span class="admin-product-placeholder">{{ strtoupper(substr($row->name, 0, 1)) }}</span>@endif<a class="text-link" href="{{ route('shop', ['q' => $row->name]) }}">{{ $row->name }}</a></div></td><td>{{ $row->sku }}</td><td>{{ $row->category_name ?? '—' }} / {{ $row->brand_name ?? '—' }}</td><td>{{ $row->starting_price === null ? '—' : 'Rs. '.number_format((float) $row->starting_price, 2) }}</td><td>{{ $row->stock_total ?? 0 }}</td><td>@include('pages.admin-actions', ['row' => $row, 'section' => $section, 'editable' => true, 'deletable' => true])</td></tr>@empty<tr><td colspan="6">No products found.</td></tr>@endforelse</tbody></table></div>
                @elseif($section === 'categories')
                    <div class="admin-category-hierarchy">
                        @forelse($records as $row)
                            <section class="admin-category-group">
                                <div class="admin-category-row">
                                    <div class="admin-category-identity">@if($row->image_path)<img src="{{ asset('storage/'.$row->image_path) }}" alt="">@else<span class="admin-category-image-placeholder">{{ strtoupper(substr($row->name, 0, 1)) }}</span>@endif<div><strong>{{ $row->name }}</strong><small>{{ $row->slug }}</small></div></div>
                                    <span class="admin-category-status {{ $row->is_active ? 'is-active' : 'is-inactive' }}">{{ $row->is_active ? 'Active' : 'Inactive' }}</span>
                                    @include('pages.admin-actions', ['row' => $row, 'section' => $section, 'editable' => true, 'deletable' => true])
                                </div>
                                @if($row->children->isNotEmpty())
                                    <div class="admin-subcategory-list"><span class="admin-subcategory-heading">Subcategories</span>
                                        @foreach($row->children as $child)
                                            <div class="admin-category-row admin-subcategory-row">
                                                <div class="admin-category-identity">@if($child->image_path)<img src="{{ asset('storage/'.$child->image_path) }}" alt="">@else<span class="admin-category-image-placeholder">{{ strtoupper(substr($child->name, 0, 1)) }}</span>@endif<div><strong>{{ $child->name }}</strong><small>{{ $child->slug }}</small></div></div>
                                                <span class="admin-category-status {{ $child->is_active ? 'is-active' : 'is-inactive' }}">{{ $child->is_active ? 'Active' : 'Inactive' }}</span>
                                                @include('pages.admin-actions', ['row' => $child, 'section' => $section, 'editable' => true, 'deletable' => true])
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </section>
                        @empty
                            <p class="account-empty">No categories found. Add a main category above to get started.</p>
                        @endforelse
                    </div>
                @elseif($section === 'brands')
                    <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Name</th><th>Slug</th><th>Logo</th><th>Actions</th></tr></thead><tbody>@forelse($records as $row)<tr><td>{{ $row->name }}</td><td>{{ $row->slug }}</td><td>{{ $row->logo ? 'Set' : '—' }}</td><td>@include('pages.admin-actions', ['row' => $row, 'section' => $section, 'editable' => true, 'deletable' => true])</td></tr>@empty<tr><td colspan="4">No brands found.</td></tr>@endforelse</tbody></table></div>
                @elseif($section === 'customers')
                    <form method="get" action="{{ route('admin.section', 'customers') }}" class="admin-inline-form" style="margin:0 0 16px;flex-wrap:wrap">
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name or email" aria-label="Search customers">
                        <select name="status" aria-label="Filter by account status"><option value="">All statuses</option><option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option><option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option><option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Blocked</option></select>
                        <button class="button-secondary" type="submit">Search</button>
                        @if(request()->filled('search') || request()->filled('status'))<a class="text-link" href="{{ route('admin.section', 'customers') }}">Clear</a>@endif
                    </form>
                    <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Customer</th><th>Email</th><th>Phone</th><th>Registered</th><th>Account status</th><th>Manage</th></tr></thead><tbody>
                        @forelse($records as $row)
                            <tr>
                                <td>{{ $row->name }}</td><td>{{ $row->email }}</td><td>{{ $row->phone ?: '—' }}</td>
                                <td>{{ $row->created_at ? \Illuminate\Support\Carbon::parse($row->created_at)->format('M j, Y') : '—' }}</td>
                                <td><span class="status-pill">{{ $row->is_blocked ? 'Blocked' : ($row->is_active ? 'Active' : 'Inactive') }}</span></td>
                                <td><div class="admin-row-actions"><a class="text-link" href="{{ route('admin.customer.show', $row->id) }}">Details</a><a class="text-link" href="{{ route('admin.section', ['customers', 'edit' => $row->id]) }}">Edit</a></div>@include('pages.admin-customer-status-actions', ['customer' => $row])</td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No customers match these filters.</td></tr>
                        @endforelse
                    </tbody></table></div>
                    {{ $records->links() }}
                @else
                    <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Code</th><th>Discount</th><th>Minimum</th><th>Active</th><th>Actions</th></tr></thead><tbody>@forelse($records as $row)<tr><td>{{ $row->code }}</td><td>{{ $row->discount_type === 'percentage' ? $row->discount_value.'%' : 'Rs. '.number_format((float) $row->discount_value, 2) }}</td><td>Rs. {{ number_format((float) $row->minimum_amount, 2) }}</td><td>{{ $row->is_active ? 'Yes' : 'No' }}</td><td>@include('pages.admin-actions', ['row' => $row, 'section' => $section, 'editable' => true, 'deletable' => true])</td></tr>@empty<tr><td colspan="5">No coupons found.</td></tr>@endforelse</tbody></table></div>
                @endif
            @elseif($section === 'orders')
                <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Order</th><th>Customer</th><th>Placed</th><th>Total</th><th>Status</th><th>Update status</th></tr></thead><tbody>@forelse($records as $row)<tr><td><a class="text-link" href="{{ route('admin.order.show', $row->id) }}">#{{ $row->id }} · View</a></td><td>{{ $row->customer_name ?? 'Guest' }}</td><td>{{ $row->created_at ? \Illuminate\Support\Carbon::parse($row->created_at)->format('M j, Y') : '—' }}</td><td>Rs. {{ number_format((float) $row->total_amount, 2) }}</td><td>{{ ucfirst($row->status ?? 'pending') }}</td><td><form method="post" action="{{ route('admin.resource.update', [$section, $row->id]) }}" class="admin-inline-form">@csrf @method('PUT')<select name="status">@if(!in_array($row->status, ['pending', 'processing', 'shipped', 'completed', 'cancelled'], true))<option value="{{ $row->status }}" selected>{{ ucfirst($row->status) }}</option>@endif @foreach(['pending', 'processing', 'shipped', 'completed', 'cancelled'] as $status)<option value="{{ $status }}" {{ ($row->status ?? 'pending') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>@endforeach</select><button class="button-secondary" type="submit">Update</button></form></td></tr>@empty<tr><td colspan="6">No orders found.</td></tr>@endforelse</tbody></table></div>{{ $records->links() }}
            @elseif($section === 'payments')
                <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Payment / Order</th><th>Customer</th><th>Method</th><th>Amount</th><th>Status</th><th>Update status</th></tr></thead><tbody>@forelse($records as $row)<tr><td>#{{ $row->id }} / Order #{{ $row->order_id }}</td><td>{{ $row->customer_name ?? 'Guest' }}</td><td>{{ $row->method ?? '—' }}</td><td>Rs. {{ number_format((float) $row->amount, 2) }}</td><td>{{ ucfirst($row->status ?? 'pending') }}</td><td><form method="post" action="{{ route('admin.resource.update', [$section, $row->id]) }}" class="admin-inline-form">@csrf @method('PUT')<select name="status"><option>pending</option><option>paid</option><option>failed</option><option>refunded</option></select><button class="button-secondary" type="submit">Update</button></form></td></tr>@empty<tr><td colspan="6">No payments found.</td></tr>@endforelse</tbody></table></div>{{ $records->links() }}
            @elseif($section === 'inventory')
                <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Product</th><th>Variant SKU</th><th>Price</th><th>Stock</th><th>Update stock</th></tr></thead><tbody>@forelse($records as $row)<tr><td>{{ $row->name }}</td><td>{{ $row->sku }}</td><td>Rs. {{ number_format((float) $row->price, 2) }}</td><td>{{ $row->stock_quantity ?? '—' }}</td><td><form method="post" action="{{ route('admin.resource.update', [$section, $row->id]) }}" class="admin-inline-form">@csrf @method('PUT')<input type="number" min="0" name="stock_quantity" value="{{ $row->stock_quantity }}" required><button class="button-secondary" type="submit">Save</button></form></td></tr>@empty<tr><td colspan="5">No inventory found.</td></tr>@endforelse</tbody></table></div>
            @elseif($section === 'reviews')
                <p class="admin-muted">Reviews are shown read-only. The current database has no moderation or archival field, so reviews are preserved.</p><div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Product</th><th>Customer</th><th>Rating</th><th>Review</th><th>Date</th></tr></thead><tbody>@forelse($records as $row)<tr><td>{{ $row->product_name ?? 'Product removed' }}</td><td>{{ $row->customer_name ?? 'Customer removed' }}</td><td>{{ $row->rating ?? '—' }} / 5</td><td>{{ $row->comment }}</td><td>{{ $row->created_at ? \Illuminate\Support\Carbon::parse($row->created_at)->format('M j, Y') : '—' }}</td></tr>@empty<tr><td colspan="5">No reviews found.</td></tr>@endforelse</tbody></table></div>{{ $records->links() }}
            @else
                <p class="admin-muted">Paid payment records from the existing order payment table.</p><div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Payment</th><th>Order</th><th>Amount</th><th>Paid at</th></tr></thead><tbody>@forelse($records as $row)<tr><td>#{{ $row->id }}</td><td>#{{ $row->order_id }}</td><td>Rs. {{ number_format((float) $row->amount, 2) }}</td><td>{{ ($row->paid_at ?? $row->created_at) ? \Illuminate\Support\Carbon::parse($row->paid_at ?? $row->created_at)->format('M j, Y H:i') : '—' }}</td></tr>@empty<tr><td colspan="4">No paid records yet.</td></tr>@endforelse</tbody></table></div>{{ $records->links() }}
            @endif
        </section>
    @endif
        </div>
    </div>
</div>
@endsection

@if($section === 'products')
    @push('scripts')
        <script>
            (() => {
                const mainCategory = document.getElementById('product-main-category');
                const subcategory = document.getElementById('product-subcategory');
                if (!mainCategory || !subcategory) return;

                const endpoint = mainCategory.dataset.subcategoriesUrl;
                let activeRequest;
                const resetSubcategory = (label) => {
                    subcategory.replaceChildren(new Option(label, ''));
                    subcategory.value = '';
                    subcategory.disabled = true;
                };

                const loadSubcategories = async (mainId, selectedId = '') => {
                    activeRequest?.abort();
                    if (!mainId) {
                        resetSubcategory('Select a main category first');
                        return;
                    }

                    resetSubcategory('Loading subcategories…');
                    activeRequest = new AbortController();
                    try {
                        const response = await fetch(endpoint.replace('__CATEGORY__', encodeURIComponent(mainId)), {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin',
                            signal: activeRequest.signal,
                        });
                        if (!response.ok) throw new Error('Subcategories could not be loaded.');
                        const options = await response.json();
                        subcategory.replaceChildren(new Option(options.length ? 'Select Subcategory' : 'No subcategories available', ''));
                        options.forEach((option) => subcategory.add(new Option(option.name, option.id)));
                        subcategory.disabled = options.length === 0;
                        if (selectedId && options.some((option) => String(option.id) === String(selectedId))) {
                            subcategory.value = String(selectedId);
                        }
                    } catch (error) {
                        if (error.name !== 'AbortError') resetSubcategory('Unable to load subcategories');
                    }
                };

                mainCategory.addEventListener('change', () => loadSubcategories(mainCategory.value));
                const selectedMain = mainCategory.value;
                if (selectedMain) loadSubcategories(selectedMain, subcategory.dataset.selectedSubcategory);
                else resetSubcategory('Select a main category first');
            })();
        </script>
    @endpush
@endif
