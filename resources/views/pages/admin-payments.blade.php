@if(session('success'))<div class="admin-module-notice">{{ session('success') }}</div>@endif
@if($errors->any())<div class="form-alert admin-module-notice">{{ $errors->first() }}</div>@endif
<p class="admin-module-guidance">Transactions are read-only unless the record is cash on delivery. Confirm COD only after the courier/store has collected the money. Online statuses require a verified gateway callback; refunds are unavailable because no refund integration is configured.</p>
<form method="get" action="{{ route('admin.section', 'payments') }}" class="account-card admin-module-filters">
    <label>Search<input type="search" name="search" value="{{ request('search') }}" placeholder="Transaction, order or customer"></label>
    <label>Status<select name="status"><option value="">All statuses</option>@foreach(['pending','paid','failed','refunded'] as $status)<option value="{{ $status }}" {{ request('status')===$status?'selected':'' }}>{{ ucfirst($status) }}</option>@endforeach</select></label>
    <label>Method<select name="method"><option value="">All methods</option>@foreach($paymentMethods as $method)<option value="{{ $method }}" {{ request('method')===$method?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$method)) }}</option>@endforeach</select></label>
    <label>From<input type="date" name="date_from" value="{{ request('date_from') }}"></label><label>To<input type="date" name="date_to" value="{{ request('date_to') }}"></label>
    <button class="button-primary" type="submit">Apply filters</button>@if(request()->query())<a class="text-link" href="{{ route('admin.section', 'payments') }}">Clear</a>@endif
</form>
<div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Payment</th><th>Order</th><th>Customer</th><th>Transaction ID</th><th>Method</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($records as $row)
    <tr><td><a class="text-link" href="{{ route('admin.payment.show',$row->id) }}">#{{ $row->id }} · Details</a></td><td><a class="text-link" href="{{ route('admin.order.show',$row->order_id) }}">#{{ $row->order_id }}</a></td><td>{{ $row->customer_name ?? 'Guest' }}</td><td>{{ $row->transaction_id ?: '—' }}</td><td>{{ ucfirst(str_replace('_',' ',$row->method ?? '—')) }}</td><td>Rs. {{ number_format((float)$row->amount,2) }}</td><td><span class="status-pill">{{ ucfirst($row->status ?? 'pending') }}</span></td><td>@if($row->method === 'cash_on_delivery' && $row->status === 'pending' && in_array($row->order_status,['shipped','delivered','completed'],true))<form method="post" action="{{ route('admin.payment.confirm-cod',$row->id) }}" data-confirm="Confirm cash collection for payment #{{ $row->id }}? Only continue if the cash has actually been collected.">@csrf @method('PATCH')<button class="button-secondary" type="submit">Confirm COD collected</button></form>@elseif($row->method === 'cash_on_delivery' && $row->status === 'pending')<span class="admin-muted">Awaiting delivery/collection</span>@else<span class="admin-muted">{{ $row->status === 'paid' ? 'Recorded' : 'No manual action' }}</span>@endif</td></tr>
@empty<tr><td colspan="8">No payment transactions match these filters.</td></tr>@endforelse
</tbody></table></div>{{ $records->links() }}
<script>
document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', event => { if (!window.confirm(form.dataset.confirm)) event.preventDefault(); }));
</script>
