<div class="admin-report">
    <div class="admin-report-heading"><div><span class="section-kicker">SALES PERFORMANCE</span><h2>Sales analytics</h2><p class="admin-muted">Revenue figures use successful payments recorded for orders in the selected date range.</p></div>
        <a class="button-primary admin-report-download" href="{{ route('admin.reports.download', request()->query()) }}">Download PDF</a>
    </div>

    <form class="account-card admin-report-filters" method="get" action="{{ route('admin.section', 'reports') }}" data-report-filter>
        <label>Date range<select name="range" data-range-select>
            @foreach(['today'=>'Today','yesterday'=>'Yesterday','last_7_days'=>'Last 7 Days','last_30_days'=>'Last 30 Days','this_month'=>'This Month','last_month'=>'Last Month','custom'=>'Custom Date Range'] as $value=>$label)
                <option value="{{ $value }}" {{ $report['filters']['range'] === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select></label>
        <label data-custom-date>From<input type="date" name="from" value="{{ $report['filters']['from'] }}"></label>
        <label data-custom-date>To<input type="date" name="to" value="{{ $report['filters']['to'] }}"></label>
        <label>Search<input type="search" name="search" value="{{ $report['filters']['search'] }}" placeholder="Order ID or customer"></label>
        <label>Order status<select name="status"><option value="">All statuses</option>@foreach($report['status_options'] as $status)<option value="{{ $status }}" {{ $report['filters']['status'] === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>@endforeach</select></label>
        <label>Payment method<select name="payment_method"><option value="">All methods</option>@foreach($report['payment_methods'] as $method)<option value="{{ $method }}" {{ $report['filters']['payment_method'] === $method ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$method)) }}</option>@endforeach</select></label>
        <button class="button-primary" type="submit">Search / Apply Filter</button>
    </form>

    @if($errors->any())<div class="form-alert admin-report-error">{{ $errors->first() }}</div>@endif
    @if(session('success'))<div class="admin-report-notice">{{ session('success') }}</div>@endif

    <div class="admin-report-kpis">
        <section class="account-card"><span class="section-kicker">TOTAL SALES</span><strong>Rs. {{ number_format($report['summary']['total_sales'],2) }}</strong><small>Sales value for paid orders</small></section>
        <section class="account-card"><span class="section-kicker">TOTAL ORDERS</span><strong>{{ number_format($report['summary']['total_orders']) }}</strong><small>Orders in selected period</small></section>
        <section class="account-card"><span class="section-kicker">TOTAL REVENUE</span><strong>Rs. {{ number_format($report['summary']['total_revenue'],2) }}</strong><small>Captured payments</small></section>
        <section class="account-card"><span class="section-kicker">AVERAGE ORDER VALUE</span><strong>Rs. {{ number_format($report['summary']['average_order_value'],2) }}</strong><small>Revenue per paid order</small></section>
        <section class="account-card"><span class="section-kicker">PRODUCTS SOLD</span><strong>{{ number_format($report['summary']['products_sold']) }}</strong><small>Units in paid orders</small></section>
    </div>

    <section class="account-card admin-report-chart-card">
        <div class="admin-report-chart-head"><div><h2>Revenue trends</h2><p class="admin-muted">{{ $report['range_label'] }}@if($report['revenue_change'] !== null) · {{ $report['revenue_change'] >= 0 ? '+' : '' }}{{ number_format($report['revenue_change'],1) }}% compared with previous period @endif</p></div>
            <div class="admin-report-granularity" role="group" aria-label="Chart interval">@foreach(['daily'=>'Daily','weekly'=>'Weekly','monthly'=>'Monthly','yearly'=>'Yearly'] as $key=>$label)<button type="button" data-granularity="{{ $key }}" class="{{ $key === 'daily' ? 'is-active' : '' }}">{{ $label }}</button>@endforeach</div>
        </div>
        <div class="admin-report-legend"><span><i></i>Selected period</span><span><i></i>Previous period</span></div>
        <div class="admin-report-canvas-wrap"><canvas data-sales-chart aria-label="Revenue comparison chart" role="img"></canvas></div>
    </section>

    <section class="account-card admin-table-card admin-report-table-card">
        <div class="admin-report-table-heading"><div><h2>Sales report</h2><p class="admin-muted">{{ $report['range_label'] }} · {{ number_format($report['records']->total()) }} orders</p></div></div>
        <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Order ID</th><th>Order Date</th><th>Customer</th><th>Products</th><th>Payment Method</th><th>Order Status</th><th>Total Amount</th></tr></thead><tbody>
            @forelse($report['records'] as $order)
                <tr><td>#{{ $order->id }}</td><td>{{ \Illuminate\Support\Carbon::parse($order->created_at)->format('M j, Y H:i') }}</td><td>{{ $order->customer_name ?? 'Guest' }}</td><td>{{ $order->products }}</td><td>{{ $order->payment_method }}</td><td><span class="status-pill">{{ ucfirst($order->status ?? 'pending') }}</span></td><td>Rs. {{ number_format((float) $order->total_amount,2) }}</td></tr>
            @empty<tr><td colspan="7" class="admin-report-empty">No orders match the selected filters.</td></tr>@endforelse
        </tbody></table></div>
        @if($report['records']->hasPages())<div class="admin-report-pagination">{{ $report['records']->links() }}</div>@endif
    </section>
</div>
@push('scripts')
<script>
(() => {
    const root = document.querySelector('.admin-report');
    if (!root) return;
    const reportData = @json($report['charts']);
    const canvas = root.querySelector('[data-sales-chart]');
    const context = canvas.getContext('2d');
    let active = 'daily';
    const draw = () => {
        const series = reportData[active];
        const ratio = window.devicePixelRatio || 1;
        const width = Math.max(300, canvas.clientWidth);
        const height = 270;
        canvas.width = width * ratio; canvas.height = height * ratio;
        context.setTransform(ratio, 0, 0, ratio, 0, 0);
        context.clearRect(0, 0, width, height);
        const pad = {left: 48, right: 14, top: 18, bottom: 42};
        const chartW = width - pad.left - pad.right, chartH = height - pad.top - pad.bottom;
        const max = Math.max(1, ...series.current, ...series.previous);
        context.font = '11px system-ui'; context.textAlign = 'right'; context.fillStyle = '#8290a3';
        for (let i=0; i<=4; i++) {
            const y = pad.top + chartH * i / 4;
            context.strokeStyle = '#edf1f6'; context.beginPath(); context.moveTo(pad.left,y); context.lineTo(width-pad.right,y); context.stroke();
            context.fillText('Rs. '+Math.round(max * (4-i) / 4).toLocaleString(), pad.left-8, y+4);
        }
        const step = chartW / Math.max(1, series.labels.length), barW = Math.min(18, step * .28);
        series.labels.forEach((label,i) => {
            const center = pad.left + step * (i + .5);
            [[series.previous[i], '#b8c8e4', -barW*.55],[series.current[i], '#3478e5', barW*.55]].forEach(([value,color,offset]) => {
                const h = chartH * value / max; context.fillStyle = color;
                context.beginPath(); context.roundRect(center+offset-barW/2,pad.top+chartH-h,barW,Math.max(2,h),3); context.fill();
            });
            if (series.labels.length <= 15 || i % Math.ceil(series.labels.length/12) === 0) {
                context.fillStyle='#8290a3'; context.textAlign='center'; context.fillText(label,center,height-17);
            }
        });
    };
    root.querySelectorAll('[data-granularity]').forEach(button => button.addEventListener('click', () => {
        active = button.dataset.granularity;
        root.querySelectorAll('[data-granularity]').forEach(item => item.classList.toggle('is-active', item === button)); draw();
    }));
    const range = root.querySelector('[data-range-select]');
    const syncDateInputs = () => root.querySelectorAll('[data-custom-date]').forEach(label => label.hidden = range.value !== 'custom');
    range.addEventListener('change', syncDateInputs); syncDateInputs();
    window.addEventListener('resize', draw); draw();
})();
</script>
@endpush
