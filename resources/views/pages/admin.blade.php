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
        @if(in_array($section, ['products', 'categories', 'brands', 'coupons'], true))
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
                    <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Name</th><th>Slug</th><th>Parent</th><th>Active</th><th>Actions</th></tr></thead><tbody>@forelse($records as $row)<tr><td>{{ $row->name }}</td><td>{{ $row->slug }}</td><td>{{ $row->parent_name ?? '—' }}</td><td>{{ $row->is_active ? 'Yes' : 'No' }}</td><td>@include('pages.admin-actions', ['row' => $row, 'section' => $section, 'editable' => true, 'deletable' => true])</td></tr>@empty<tr><td colspan="5">No categories found.</td></tr>@endforelse</tbody></table></div>
                @elseif($section === 'brands')
                    <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Name</th><th>Slug</th><th>Logo</th><th>Actions</th></tr></thead><tbody>@forelse($records as $row)<tr><td>{{ $row->name }}</td><td>{{ $row->slug }}</td><td>{{ $row->logo ? 'Set' : '—' }}</td><td>@include('pages.admin-actions', ['row' => $row, 'section' => $section, 'editable' => true, 'deletable' => true])</td></tr>@empty<tr><td colspan="4">No brands found.</td></tr>@endforelse</tbody></table></div>
                @elseif($section === 'customers')
                    <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Joined</th><th>Actions</th></tr></thead><tbody>@forelse($records as $row)<tr><td>{{ $row->name }}</td><td>{{ $row->email }}</td><td>{{ $row->phone ?? '—' }}</td><td>{{ $row->created_at ? \Illuminate\Support\Carbon::parse($row->created_at)->format('M j, Y') : '—' }}</td><td>@include('pages.admin-actions', ['row' => $row, 'section' => $section, 'editable' => true, 'deletable' => false])</td></tr>@empty<tr><td colspan="5">No customers found.</td></tr>@endforelse</tbody></table></div>
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
