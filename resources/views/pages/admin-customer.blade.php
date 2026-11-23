@extends('layouts.marketplace')

@section('title', $customer->name.' | Customer Management | Marketly')

@section('content')
<div class="site-container inner-page admin-page">
    <div class="breadcrumbs"><a href="{{ route('admin.section', 'customers') }}">Customer Management</a><span>›</span><strong>{{ $customer->name }}</strong></div>
    <section class="account-heading">
        <div><span class="section-kicker">CUSTOMER DETAILS</span><h1>{{ $customer->name }}</h1><p>Registered {{ $customer->created_at ? \Illuminate\Support\Carbon::parse($customer->created_at)->format('M j, Y') : 'on an unknown date' }}</p></div>
        <div class="admin-row-actions"><a class="button-secondary" href="{{ route('admin.section', ['customers', 'edit' => $customer->id]) }}">Edit customer</a><a class="button-secondary" href="{{ route('admin.section', 'customers') }}">Back to customers</a></div>
    </section>
    <div class="admin-workspace">
        @include('pages.admin-sidebar')
        <div class="admin-main">
            <section class="account-card">
                <h2>Account information</h2>
                <dl><dt>Customer ID</dt><dd>#{{ $customer->id }}</dd><dt>Email</dt><dd>{{ $customer->email }}</dd><dt>Email verified</dt><dd>{{ $customer->email_verify ? 'Yes' : 'No' }}</dd><dt>Phone</dt><dd>{{ $customer->phone ?: '—' }}</dd><dt>Profile</dt><dd>{{ $customer->profile ?: '—' }}</dd><dt>Account status</dt><dd><span class="status-pill">{{ $customer->is_blocked ? 'Blocked' : ($customer->is_active ? 'Active' : 'Inactive') }}</span></dd><dt>Legacy address</dt><dd>{{ $customer->address ?: '—' }}</dd></dl>
                @include('pages.admin-customer-status-actions', ['customer' => $customer])
            </section>

            <section class="account-card admin-table-card">
                <h2>Saved addresses</h2>
                @forelse($addresses as $address)
                    <article class="account-card" style="margin:12px 0 0;padding:14px">
                        <strong>{{ $address->contact_name ?: $customer->name }}{{ $address->is_default ? ' · Default' : '' }}</strong>
                        <p>{{ $address->phone ?: '—' }}</p>
                        <p>{{ $address->address_line1 }}{{ $address->address_line2 ? ', '.$address->address_line2 : '' }}</p>
                        <p>{{ collect([$address->city, $address->state, $address->postal_code, $address->country])->filter()->implode(', ') ?: '—' }}</p>
                    </article>
                @empty
                    <p class="account-empty">No saved addresses.{{ $customer->address ? ' The legacy address is shown above.' : '' }}</p>
                @endforelse
            </section>

            <section class="account-card admin-table-card">
                <h2>Order history</h2>
                <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Order</th><th>Date</th><th>Total</th><th>Status</th></tr></thead><tbody>
                    @forelse($orders as $order)
                        <tr><td>@if($order->deleted_at)#{{ $order->id }}@else<a class="text-link" href="{{ route('admin.order.show', $order->id) }}">#{{ $order->id }}</a>@endif</td><td>{{ $order->created_at ? \Illuminate\Support\Carbon::parse($order->created_at)->format('M j, Y') : '—' }}</td><td>Rs. {{ number_format((float) $order->total_amount, 2) }}</td><td><span class="status-pill">{{ ucfirst($order->status ?: 'pending') }}{{ $order->deleted_at ? ' · Archived' : '' }}</span></td></tr>
                    @empty
                        <tr><td colspan="4">No orders found for this customer.</td></tr>
                    @endforelse
                </tbody></table></div>
                {{ $orders->links() }}
            </section>
        </div>
    </div>
</div>
@endsection
