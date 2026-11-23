@if(session('success'))<div class="admin-module-notice">{{ session('success') }}</div>@endif
@if($errors->any())<div class="form-alert admin-module-notice">{{ $errors->first() }}</div>@endif
<form method="get" action="{{ route('admin.section', 'orders') }}" class="account-card admin-module-filters">
    <label>Search<input type="search" name="search" value="{{ request('search') }}" placeholder="Order ID, customer or email"></label>
    <label>Order status<select name="status"><option value="">All statuses</option>@foreach(['pending'=>'Pending','processing'=>'Processing','shipped'=>'Shipped','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $value=>$label)<option value="{{ $value }}" {{ request('status')===$value?'selected':'' }}>{{ $label }}</option>@endforeach</select></label>
    <label>From<input type="date" name="date_from" value="{{ request('date_from') }}"></label><label>To<input type="date" name="date_to" value="{{ request('date_to') }}"></label>
    <button class="button-primary" type="submit">Apply filters</button>@if(request()->query())<a class="text-link" href="{{ route('admin.section', 'orders') }}">Clear</a>@endif
</form>
<div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Order</th><th>Customer</th><th>Placed</th><th>Total</th><th>Status</th><th>Fulfillment</th><th>Cancel</th></tr></thead><tbody>
@forelse($records as $row)
    @php($current = $row->status === 'completed' ? 'delivered' : ($row->status ?: 'pending'))
    <tr><td><a class="text-link" href="{{ route('admin.order.show', $row->id) }}">#{{ $row->id }} · Details</a></td><td>{{ $row->customer_name ?? 'Guest' }}</td><td>{{ $row->created_at ? \Illuminate\Support\Carbon::parse($row->created_at)->format('M j, Y') : '—' }}</td><td>Rs. {{ number_format((float)$row->total_amount,2) }}</td><td><span class="status-pill">{{ ucfirst($current) }}</span></td>
    <td>@php($next = ['pending'=>['processing'=>'Processing'],'processing'=>['shipped'=>'Shipped'],'shipped'=>['delivered'=>'Delivered']][$current] ?? []) @if($next)<form method="post" action="{{ route('admin.resource.update',['orders',$row->id]) }}" class="admin-inline-form">@csrf @method('PUT')<select name="status" aria-label="Next fulfillment status">@foreach($next as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select><button class="button-secondary" type="submit">Update</button></form>@else<span class="admin-muted">—</span>@endif</td>
    <td>@if(in_array($current,['pending','processing'],true))<form method="post" action="{{ route('admin.resource.update',['orders',$row->id]) }}" class="admin-inline-form" data-confirm="Cancel order #{{ $row->id }}? Stock will be returned. Paid orders require a refund first.">@csrf @method('PUT')<input type="hidden" name="status" value="cancelled"><button class="button-secondary admin-danger-button" type="submit">Cancel</button></form>@else<span class="admin-muted">—</span>@endif</td></tr>
@empty<tr><td colspan="7">No orders match these filters.</td></tr>@endforelse
</tbody></table></div>{{ $records->links() }}
<script>
document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', event => { if (!window.confirm(form.dataset.confirm)) event.preventDefault(); }));
</script>
