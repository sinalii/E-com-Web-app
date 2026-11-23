<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\SalesReportPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    private const SECTIONS = [
        'products' => 'Product Management',
        'categories' => 'Category Management',
        'brands' => 'Brand Management',
        'customers' => 'Customer Management',
        'orders' => 'Order Management',
        'payments' => 'Payment Management',
        'coupons' => 'Coupon Management',
        'inventory' => 'Inventory / Stock',
        'reviews' => 'Reviews and Ratings',
        'reports' => 'Sales Analytics and Reports',
        'settings' => 'Admin Profile & Settings',
    ];

    public function index()
    {
        $sales = DB::table('order_payment')->where('status', 'paid')->sum('amount');
        $paidOrders = DB::table('order_payment')->where('status', 'paid')->distinct()->count('order_id');
        $monthStart = now()->subMonths(11)->startOfMonth();
        $monthlySales = DB::table('order_payment')->where('status', 'paid')
            ->where(function ($query) use ($monthStart) {
                $query->where('paid_at', '>=', $monthStart)->orWhere(function ($fallback) use ($monthStart) {
                    $fallback->whereNull('paid_at')->where('created_at', '>=', $monthStart);
                });
            })
            ->orderBy('paid_at')->get(['amount', 'paid_at', 'created_at']);
        $salesByMonth = collect(range(11, 0))->mapWithKeys(function (int $monthsAgo) use ($monthlySales) {
            $month = now()->startOfMonth()->subMonths($monthsAgo);
            $sum = $monthlySales->filter(function ($payment) use ($month) {
                $date = $payment->paid_at ?? $payment->created_at;
                return $date && \Illuminate\Support\Carbon::parse($date)->format('Y-m') === $month->format('Y-m');
            })->sum('amount');

            return [$month->format('M Y') => (float) $sum];
        });

        return view('pages.admin', [
            'section' => 'dashboard',
            'sections' => self::SECTIONS,
            'adminCategoryTree' => $this->categorySidebarTree(),
            'stats' => [
                'sales' => (float) $sales,
                'orders' => DB::table('order')->whereNull('deleted_at')->count(),
                'customers' => $this->customerQuery()->count(),
                'products' => DB::table('product')->whereNull('deleted_at')->count(),
                'paid_orders' => $paidOrders,
                'low_stock' => DB::table('product_variant')->join('product', 'product.id', '=', 'product_variant.product_id')
                    ->whereNull('product.deleted_at')->where('product_variant.is_active', true)->where('product_variant.stock_quantity', '<=', 5)->count(),
            ],
            'salesByMonth' => $salesByMonth,
            'recentOrders' => DB::table('order')->leftJoin('user', 'user.id', '=', 'order.user_id')
                ->whereNull('order.deleted_at')->orderByDesc('order.created_at')->limit(8)
                ->get(['order.id', 'order.status', 'order.total_amount', 'order.created_at', 'user.name as customer_name']),
            'records' => collect(),
            'fields' => [],
            'editing' => null,
        ]);
    }

    public function section(Request $request, string $section)
    {
        abort_unless(array_key_exists($section, self::SECTIONS), 404);
        if ($section === 'reports') return $this->salesReportPage($request);
        if ($section === 'orders') $request->validate(['search' => ['nullable','string','max:120'], 'status' => ['nullable',Rule::in(['pending','processing','shipped','delivered','completed','cancelled'])], 'date_from' => ['nullable','date'], 'date_to' => ['nullable','date','after_or_equal:date_from']]);
        if ($section === 'payments') $request->validate(['search' => ['nullable','string','max:120'], 'status' => ['nullable',Rule::in(['pending','paid','failed','refunded'])], 'method' => ['nullable','string','max:100'], 'date_from' => ['nullable','date'], 'date_to' => ['nullable','date','after_or_equal:date_from']]);

        $records = $this->records($section, $request);
        $editing = null;

        if ($request->filled('edit') && in_array($section, ['products', 'categories', 'brands', 'customers', 'coupons'], true)) {
            $editing = $this->editableRecord($section, (int) $request->query('edit'));
            abort_unless($editing, 404);

            if ($section === 'products') {
                $linkedCategory = $editing->category_id
                    ? DB::table('category')->where('id', $editing->category_id)->first()
                    : null;
                if ($linkedCategory?->parent_id !== null) {
                    $editing->main_category_id = $editing->main_category_id ?: $linkedCategory->parent_id;
                    $editing->subcategory_id = $linkedCategory->id;
                } elseif (! $editing->main_category_id && $linkedCategory) {
                    $editing->main_category_id = $linkedCategory->id;
                    $editing->subcategory_id = null;
                } else {
                    $editing->subcategory_id = null;
                }
            }
        }

        return view('pages.admin', [
            'section' => $section,
            'sections' => self::SECTIONS,
            'adminCategoryTree' => $this->categorySidebarTree(),
            'records' => $records,
            'categoryParents' => $section === 'categories'
                ? DB::table('category')->whereNull('parent_id')->orderBy('name')->get(['id', 'name'])
                : collect(),
            'mainCategories' => $section === 'products'
                ? DB::table('category')->whereNull('parent_id')->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : collect(),
            'fields' => $this->fields($section),
            'editing' => $editing,
            'stats' => [],
            'salesByMonth' => collect(),
            'recentOrders' => collect(),
            'paymentMethods' => $section === 'payments' ? DB::table('payment')->whereNotNull('method')->distinct()->orderBy('method')->pluck('method') : collect(),
            'admin' => $section === 'settings' ? $request->user() : null,
            'storeSettings' => $section === 'settings' && Schema::hasTable('store_settings') ? DB::table('store_settings')->where('id', 1)->first() : null,
            'supportsOtherSessions' => $section === 'settings' && config('session.driver') === 'database' && Schema::hasTable('sessions'),
            'productImages' => $section === 'products' && $editing
                ? DB::table('product_image')->where('product_id', $editing->id)->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id')->get()
                : collect(),
        ]);
    }

    public function downloadSalesReport(Request $request)
    {
        $report = $this->buildSalesReport($request, true);
        $pdf = SalesReportPdf::render($report);
        $filename = 'marketly-sales-report-'.$report['filters']['from'].'-to-'.$report['filters']['to'].'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($pdf),
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function salesReportPage(Request $request)
    {
        $report = $this->buildSalesReport($request);

        return view('pages.admin', [
            'section' => 'reports',
            'sections' => self::SECTIONS,
            'adminCategoryTree' => $this->categorySidebarTree(),
            'records' => $report['records'],
            'fields' => [],
            'editing' => null,
            'stats' => [],
            'salesByMonth' => collect(),
            'recentOrders' => collect(),
            'report' => $report,
        ]);
    }

    private function buildSalesReport(Request $request, bool $download = false): array
    {
        $validated = $request->validate([
            'range' => ['nullable', Rule::in(['today', 'yesterday', 'last_7_days', 'last_30_days', 'this_month', 'last_month', 'custom'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['nullable', 'string', 'max:100'],
        ]);
        $preset = $validated['range'] ?? 'last_30_days';
        $today = now()->startOfDay();
        [$start, $end] = match ($preset) {
            'today' => [$today->copy(), $today->copy()->endOfDay()],
            'yesterday' => [$today->copy()->subDay(), $today->copy()->subDay()->endOfDay()],
            'last_7_days' => [$today->copy()->subDays(6), $today->copy()->endOfDay()],
            'this_month' => [$today->copy()->startOfMonth(), $today->copy()->endOfDay()],
            'last_month' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->startOfMonth()->subSecond()],
            'custom' => [
                isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : null,
                isset($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : null,
            ],
            default => [$today->copy()->subDays(29), $today->copy()->endOfDay()],
        };
        if (! $start || ! $end) {
            throw ValidationException::withMessages(['from' => 'Choose both a start and end date for a custom range.']);
        }

        $filters = [
            'range' => $preset,
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'search' => trim((string) ($validated['search'] ?? '')),
            'status' => (string) ($validated['status'] ?? ''),
            'payment_method' => (string) ($validated['payment_method'] ?? ''),
        ];
        $days = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
        $previousEnd = $start->copy()->startOfDay()->subSecond();
        $previousStart = $previousEnd->copy()->startOfDay()->subDays($days - 1);
        $orders = $this->salesOrdersQuery($filters, $start, $end);
        $previousOrders = $this->salesOrdersQuery($filters, $previousStart, $previousEnd);
        $summary = $this->salesSummary(clone $orders);
        $previousSummary = $this->salesSummary(clone $previousOrders);
        $currentRevenue = (float) $summary->total_revenue;
        $previousRevenue = (float) $previousSummary->total_revenue;
        $paidOrders = (int) $summary->paid_orders;
        $revenueChange = $previousRevenue > 0
            ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100
            : null;

        $charts = [];
        foreach (['daily', 'weekly', 'monthly', 'yearly'] as $granularity) {
            $charts[$granularity] = $this->salesChartSeries(
                clone $orders,
                clone $previousOrders,
                $start,
                $end,
                $previousStart,
                $previousEnd,
                $granularity
            );
        }

        $reportRows = (clone $orders)->select([
            'order.id', 'order.created_at', 'order.status', 'order.total_amount',
            'user.name as customer_name',
        ])->orderByDesc('order.created_at')->orderByDesc('order.id');
        $records = $download
            ? $this->decorateSalesReportRows($reportRows->get())
            : $reportRows->paginate(25)->withQueryString();
        if (! $download) $records->setCollection($this->decorateSalesReportRows($records->getCollection()));

        return [
            'summary' => [
                'total_sales' => (float) $summary->total_sales,
                'total_orders' => (int) $summary->total_orders,
                'total_revenue' => $currentRevenue,
                'average_order_value' => $paidOrders ? $currentRevenue / $paidOrders : 0,
                'products_sold' => (int) $summary->products_sold,
                'paid_orders' => $paidOrders,
            ],
            'previous_revenue' => $previousRevenue,
            'revenue_change' => $revenueChange,
            'charts' => $charts,
            'records' => $records,
            'filters' => $filters,
            'range_label' => $start->format('M j, Y').' to '.$end->format('M j, Y'),
            'generated_at' => now(),
            'status_options' => DB::table('order')->whereNotNull('status')->distinct()->orderBy('status')->pluck('status'),
            'payment_methods' => DB::table('payment')->whereNotNull('method')->distinct()->orderBy('method')->pluck('method'),
        ];
    }

    private function salesOrdersQuery(array $filters, Carbon $start, Carbon $end)
    {
        $paidPayments = DB::table('order_payment')->where('status', 'paid')
            ->select('order_id')->selectRaw('SUM(COALESCE(amount, 0)) as paid_amount')->groupBy('order_id');
        $paidOrderIds = DB::table('order_payment')->where('status', 'paid')->select('order_id')->distinct();
        $productUnits = DB::table('order_item')->joinSub($paidOrderIds, 'paid_order_ids', function ($join) {
            $join->on('paid_order_ids.order_id', '=', 'order_item.order_id');
        })->select('order_item.order_id')->selectRaw('SUM(COALESCE(order_item.quantity, 0)) as units_sold')->groupBy('order_item.order_id');

        $query = DB::table('order')->leftJoin('user', 'user.id', '=', 'order.user_id')
            ->leftJoinSub($paidPayments, 'paid_totals', 'paid_totals.order_id', '=', 'order.id')
            ->leftJoinSub($productUnits, 'product_totals', 'product_totals.order_id', '=', 'order.id')
            ->whereNull('order.deleted_at')
            ->whereBetween('order.created_at', [$start, $end]);

        if ($filters['status'] !== '') $query->where('order.status', $filters['status']);
        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                if (ctype_digit($search)) $builder->where('order.id', (int) $search)->orWhere('user.name', 'like', '%'.$search.'%')->orWhere('user.email', 'like', '%'.$search.'%');
                else $builder->where('user.name', 'like', '%'.$search.'%')->orWhere('user.email', 'like', '%'.$search.'%');
            });
        }
        if ($filters['payment_method'] !== '') {
            $query->whereExists(function ($subquery) use ($filters) {
                $subquery->selectRaw('1')->from('order_payment as report_payment')
                    ->join('payment as report_method', 'report_method.id', '=', 'report_payment.payment_id')
                    ->whereColumn('report_payment.order_id', 'order.id')
                    ->where('report_method.method', $filters['payment_method']);
            });
        }

        return $query;
    }

    private function salesSummary($query): object
    {
        return $query->selectRaw('COUNT(`order`.`id`) as total_orders,
            SUM(CASE WHEN `paid_totals`.`order_id` IS NOT NULL THEN COALESCE(`order`.`subtotal`, `order`.`total_amount`, `paid_totals`.`paid_amount`) ELSE 0 END) as total_sales,
            SUM(COALESCE(`paid_totals`.`paid_amount`, 0)) as total_revenue,
            SUM(CASE WHEN `paid_totals`.`order_id` IS NOT NULL THEN 1 ELSE 0 END) as paid_orders,
            SUM(CASE WHEN `paid_totals`.`order_id` IS NOT NULL THEN COALESCE(`product_totals`.`units_sold`, 0) ELSE 0 END) as products_sold')
            ->first();
    }

    private function salesChartSeries($currentQuery, $previousQuery, Carbon $start, Carbon $end, Carbon $previousStart, Carbon $previousEnd, string $granularity): array
    {
        $days = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
        $count = match ($granularity) {
            'daily' => $days,
            'weekly' => (int) ceil($days / 7),
            'monthly' => ($end->year * 12 + $end->month) - ($start->year * 12 + $start->month) + 1,
            default => $end->year - $start->year + 1,
        };
        $count = max(1, $count);
        $labels = [];
        for ($index = 0; $index < $count; $index++) {
            $bucketDate = match ($granularity) {
                'daily' => $start->copy()->startOfDay()->addDays($index),
                'weekly' => $start->copy()->startOfDay()->addDays($index * 7),
                'monthly' => $start->copy()->startOfMonth()->addMonthsNoOverflow($index),
                default => $start->copy()->startOfYear()->addYears($index),
            };
            $labels[] = match ($granularity) {
                'daily' => $bucketDate->format('M j'),
                'weekly' => 'Week '.($index + 1),
                'monthly' => $bucketDate->format('M Y'),
                default => $bucketDate->format('Y'),
            };
        }

        return [
            'labels' => $labels,
            'current' => $this->bucketedRevenue($currentQuery, $start, $end, $count),
            'previous' => $this->bucketedRevenue($previousQuery, $previousStart, $previousEnd, $count),
        ];
    }

    private function bucketedRevenue($query, Carbon $start, Carbon $end, int $count): array
    {
        $daily = $query->selectRaw('DATE(`order`.`created_at`) as sale_date, SUM(COALESCE(`paid_totals`.`paid_amount`, 0)) as revenue')
            ->groupByRaw('DATE(`order`.`created_at`)')->get();
        $totalDays = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
        $values = array_fill(0, $count, 0.0);

        foreach ($daily as $row) {
            $offset = $start->copy()->startOfDay()->diffInDays(Carbon::parse($row->sale_date)->startOfDay());
            $index = min($count - 1, (int) floor($offset * $count / $totalDays));
            $values[$index] += (float) $row->revenue;
        }

        return $values;
    }

    private function decorateSalesReportRows($rows)
    {
        if ($rows->isEmpty()) return $rows;
        $ids = $rows->pluck('id')->all();
        $products = DB::table('order_item')->leftJoin('product_variant', 'product_variant.id', '=', 'order_item.product_variant_id')
            ->leftJoin('product', 'product.id', '=', 'product_variant.product_id')->whereIn('order_item.order_id', $ids)
            ->orderBy('order_item.id')->get(['order_item.order_id', 'order_item.product_name', 'product.name as current_product_name', 'order_item.quantity'])
            ->groupBy('order_id');
        $methods = DB::table('order_payment')->leftJoin('payment', 'payment.id', '=', 'order_payment.payment_id')
            ->whereIn('order_payment.order_id', $ids)->get(['order_payment.order_id', 'payment.method'])->groupBy('order_id');

        return $rows->map(function ($order) use ($products, $methods) {
            $orderItems = $products->get($order->id, collect());
            $order->products = $orderItems->map(function ($item) {
                $name = $item->product_name ?: ($item->current_product_name ?: 'Product');
                return $name.' x '.max(1, (int) $item->quantity);
            })->implode(', ') ?: '—';
            $order->payment_method = $methods->get($order->id, collect())->pluck('method')->filter()->unique()->implode(', ') ?: '—';

            return $order;
        });
    }

    public function customerDetails(int $customer)
    {
        $record = $this->customerQuery()->where('id', $customer)->first();
        abort_unless($record, 404);

        return view('pages.admin-customer', [
            'customer' => $record,
            'addresses' => DB::table('address')->where('user_id', $record->id)->orderByDesc('is_default')->orderBy('id')->get(),
            'orders' => DB::table('order')->where('user_id', $record->id)->orderByDesc('created_at')->paginate(25)->withQueryString(),
            'sections' => self::SECTIONS,
            'section' => 'customers',
            'adminCategoryTree' => $this->categorySidebarTree(),
        ]);
    }

    public function updateCustomerStatus(Request $request, int $customer)
    {
        $data = $request->validate(['action' => ['required', Rule::in(['activate', 'deactivate', 'block', 'unblock'])]]);
        abort_unless($this->customerQuery()->where('id', $customer)->exists(), 404);

        [$column, $value, $message] = match ($data['action']) {
            'activate' => ['is_active', true, 'Customer account activated.'],
            'deactivate' => ['is_active', false, 'Customer account deactivated.'],
            'block' => ['is_blocked', true, 'Customer account blocked.'],
            'unblock' => ['is_blocked', false, 'Customer account unblocked.'],
        };
        DB::table('user')->where('id', $customer)->update([$column => $value, 'updated_at' => now()]);

        return back()->with('success', $message);
    }

    public function categorySubcategories(int $category)
    {
        abort_unless(DB::table('category')->where('id', $category)->whereNull('parent_id')->where('is_active', true)->exists(), 404);

        return DB::table('category')->where('parent_id', $category)->where('is_active', true)
            ->orderBy('name')->get(['id', 'name']);
    }

    public function orderDetails(int $id)
    {
        $order = DB::table('order')->leftJoin('user', 'user.id', '=', 'order.user_id')
            ->where('order.id', $id)->whereNull('order.deleted_at')
            ->first(['order.*', 'user.name as customer_name', 'user.email as customer_email']);
        abort_unless($order, 404);

        return view('pages.admin-order', [
            'order' => $order,
            'items' => DB::table('order_item')->leftJoin('product_variant', 'product_variant.id', '=', 'order_item.product_variant_id')
                ->where('order_item.order_id', $id)->get(['order_item.*', 'product_variant.sku']),
            'shipping' => DB::table('shipping')->leftJoin('address', 'address.id', '=', 'shipping.address_id')
                ->where('shipping.order_id', $id)->first(['shipping.*', 'address.contact_name', 'address.phone', 'address.address_line1', 'address.address_line2', 'address.city', 'address.state', 'address.postal_code', 'address.country']),
            'payments' => DB::table('order_payment')->leftJoin('payment', 'payment.id', '=', 'order_payment.payment_id')
                ->where('order_payment.order_id', $id)->get(['order_payment.*', 'payment.method', 'payment.provider', 'payment.transaction_id']),
            'sections' => self::SECTIONS,
            'section' => 'orders',
            'adminCategoryTree' => $this->categorySidebarTree(),
        ]);
    }

    public function paymentDetails(int $id)
    {
        $payment = DB::table('order_payment')->leftJoin('payment', 'payment.id', '=', 'order_payment.payment_id')
            ->leftJoin('order', 'order.id', '=', 'order_payment.order_id')
            ->leftJoin('user', 'user.id', '=', 'order.user_id')->where('order_payment.id', $id)
            ->first(['order_payment.*', 'payment.method', 'payment.provider', 'payment.transaction_id', 'payment.status as payment_status', 'order.status as order_status', 'order.total_amount', 'order.created_at as order_created_at', 'user.name as customer_name', 'user.email as customer_email']);
        abort_unless($payment, 404);

        return view('pages.admin-payment', [
            'payment' => $payment,
            'sections' => self::SECTIONS,
            'section' => 'payments',
            'adminCategoryTree' => $this->categorySidebarTree(),
        ]);
    }

    public function confirmCodCollected(int $id)
    {
        DB::transaction(function () use ($id) {
            $entry = DB::table('order_payment')->where('id', $id)->first();
            abort_unless($entry, 404);
            $order = DB::table('order')->where('id', $entry->order_id)->lockForUpdate()->first();
            abort_unless($order && in_array($order->status, ['shipped', 'delivered', 'completed'], true), 409, 'COD can be confirmed after the order has shipped, once cash has actually been collected.');
            $entry = DB::table('order_payment')->where('id', $id)->lockForUpdate()->first();
            abort_unless($entry, 404);
            $payment = DB::table('payment')->where('id', $entry->payment_id)->lockForUpdate()->first();
            abort_unless($payment, 404);
            abort_unless($payment->method === 'cash_on_delivery', 409, 'Only cash-on-delivery payments can be confirmed here.');
            abort_unless($entry->status === 'pending' && $payment->status === 'pending', 409, 'This payment is no longer awaiting collection.');

            DB::table('order_payment')->where('id', $id)->update(['status' => 'paid', 'paid_at' => now()]);
            DB::table('payment')->where('id', $entry->payment_id)->update(['status' => 'paid']);
        });

        return back()->with('success', 'Cash-on-delivery collection confirmed.');
    }

    public function store(Request $request, string $section)
    {
        abort_unless(array_key_exists($section, self::SECTIONS), 404);
        $data = $this->validatedData($request, $section);

        match ($section) {
            'products' => $this->createProduct($data),
            'categories' => $this->createCategory($data),
            'brands' => DB::table('brand')->insert($this->slugged($data, 'brand')),
            'customers' => abort(405),
            'coupons' => DB::table('coupon')->insert($data + ['created_by' => $request->user()->id]),
            default => abort(404),
        };

        return redirect()->route('admin.section', $section)->with('success', self::SECTIONS[$section].' saved.');
    }

    public function update(Request $request, string $section, int $id)
    {
        abort_unless(array_key_exists($section, self::SECTIONS), 404);
        abort_if($section === 'payments', 405, 'Payment statuses are controlled by verified payment processing.');
        $data = $this->validatedData($request, $section, $id);

        match ($section) {
            'products' => $this->updateProduct($id, $data),
            'categories' => $this->updateCategory($id, $data),
            'brands' => DB::table('brand')->where('id', $id)->update($this->slugged($data, 'brand')),
            'customers' => $this->updateCustomer($id, $data),
            'orders' => $this->updateOrder($id, $data['status']),
            'coupons' => DB::table('coupon')->where('id', $id)->update($data),
            'inventory' => DB::table('product_variant')->where('id', $id)->update(['stock_quantity' => $data['stock_quantity'], 'updated_at' => now()]),
            default => abort(405),
        };

        return redirect()->route('admin.section', $section)->with('success', self::SECTIONS[$section].' updated.');
    }

    public function destroy(string $section, int $id)
    {
        abort_unless(array_key_exists($section, self::SECTIONS), 404);

        if ($section === 'products') {
            DB::table('product')->where('id', $id)->whereNull('deleted_at')->update(['deleted_at' => now()]);
        } elseif ($section === 'categories') {
            $category = DB::table('category')->where('id', $id)->first();
            abort_unless($category, 404);
            abort_if(DB::table('product')->where('category_id', $id)->exists()
                || DB::table('category')->where('parent_id', $id)->exists()
                || DB::table('category_filter')->where('category_id', $id)->exists(), 409, 'Move its products, filters, and subcategories before removing this category.');
            DB::table('category')->where('id', $id)->delete();
            if ($category->image_path) Storage::disk('public')->delete($category->image_path);
        } elseif ($section === 'brands') {
            abort_if(DB::table('product')->where('brand_id', $id)->exists(), 409, 'Move its products before removing this brand.');
            DB::table('brand')->where('id', $id)->delete();
        } elseif ($section === 'coupons') {
            abort_if(DB::table('order')->where('coupon_id', $id)->exists(), 409, 'This coupon is referenced by order history and cannot be removed.');
            DB::table('coupon')->where('id', $id)->delete();
        } elseif ($section === 'reviews') {
            abort(405, 'Reviews are read-only because the existing schema has no moderation or archive field.');
        } else {
            abort(405);
        }

        return redirect()->route('admin.section', $section)->with('success', 'Record removed.');
    }

    public function updateProfile(Request $request)
    {
        $admin = $request->user();
        $oldProfilePath = $admin->profile;
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('user', 'email')->ignore($admin->id)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('user', 'phone')->ignore($admin->id)],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        $newProfilePath = null;
        if ($request->file('profile_image')) {
            $newProfilePath = $request->file('profile_image')->storePublicly('admin-profiles', 'public');
            abort_unless($newProfilePath, 500, 'The profile image could not be saved.');
        }
        try {
            $admin->name = $data['name'];
            $admin->email = $data['email'];
            $admin->phone = $data['phone'];
            if ($newProfilePath) $admin->profile = $newProfilePath;
            $admin->save();
        } catch (\Throwable $exception) {
            if ($newProfilePath) Storage::disk('public')->delete($newProfilePath);
            throw $exception;
        }
        if ($newProfilePath && $oldProfilePath && Str::startsWith($oldProfilePath, 'admin-profiles/')) {
            Storage::disk('public')->delete($oldProfilePath);
        }

        return redirect()->route('admin.section', 'settings')->with('success', 'Administrator profile updated.')->withFragment('profile');
    }

    public function updateStoreSettings(Request $request)
    {
        $data = $request->validate([
            'store_name' => ['required','string','max:150'],
            'email' => ['nullable','email:rfc','max:255'],
            'phone' => ['nullable','string','max:30'],
            'address' => ['nullable','string','max:2000'],
            'currency' => ['required',Rule::in(['LKR','USD','EUR','GBP'])],
            'store_logo' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:3072'],
        ]);
        $existing = DB::table('store_settings')->where('id',1)->first();
        $newLogoPath = $request->file('store_logo')?->storePublicly('store', 'public');
        if ($request->file('store_logo') && ! $newLogoPath) abort(500, 'The store logo could not be saved.');
        $values = [
            'store_name' => trim($data['store_name']), 'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null, 'address' => $data['address'] ?? null,
            'currency' => $data['currency'], 'logo_path' => $newLogoPath ?: ($existing->logo_path ?? null),
            'updated_by' => $request->user()->id, 'updated_at' => now(),
        ];
        try {
            DB::table('store_settings')->updateOrInsert(['id' => 1], $values + ['created_at' => $existing->created_at ?? now()]);
        } catch (\Throwable $exception) {
            if ($newLogoPath) Storage::disk('public')->delete($newLogoPath);
            throw $exception;
        }
        if ($newLogoPath && $existing?->logo_path) Storage::disk('public')->delete($existing->logo_path);

        return redirect()->route('admin.section','settings')->with('success','Store settings updated.')->withFragment('store-settings');
    }

    public function updatePassword(Request $request)
    {
        $admin = $request->user();
        $data = $request->validate([
            'current_password' => ['required','string'],
            'password' => ['required','confirmed',Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);
        if (! Hash::check($data['current_password'], $admin->password)) {
            throw ValidationException::withMessages(['current_password' => 'The current password is incorrect.']);
        }
        $admin->password = Hash::make($data['password']);
        $admin->save();
        $this->deleteOtherDatabaseSessions($request);

        $message = config('session.driver') === 'database' && Schema::hasTable('sessions')
            ? 'Password changed. Other active database sessions have been signed out.'
            : 'Password changed successfully.';
        return redirect()->route('admin.section','settings')->with('success',$message)->withFragment('security');
    }

    public function logoutOtherSessions(Request $request)
    {
        abort_unless(config('session.driver') === 'database' && Schema::hasTable('sessions'), 404);
        $this->deleteOtherDatabaseSessions($request);

        return redirect()->route('admin.section','settings')->with('success','Other active sessions have been signed out.')->withFragment('security');
    }

    private function deleteOtherDatabaseSessions(Request $request): void
    {
        if (config('session.driver') !== 'database' || ! Schema::hasTable('sessions')) return;
        DB::table(config('session.table', 'sessions'))->where('user_id', $request->user()->id)
            ->where('id', '<>', $request->session()->getId())->delete();
    }

    public function updateNotificationPreferences(Request $request)
    {
        $data = $request->validate([
            'notify_new_orders' => ['nullable','boolean'], 'notify_payments' => ['nullable','boolean'],
            'notify_low_stock' => ['nullable','boolean'], 'notify_new_customers' => ['nullable','boolean'],
        ]);
        $existing = DB::table('store_settings')->where('id',1)->first();
        $values = [
            'store_name' => $existing->store_name ?? config('app.name','Marketly'),
            'logo_path' => $existing->logo_path ?? null, 'email' => $existing->email ?? null,
            'phone' => $existing->phone ?? null, 'address' => $existing->address ?? null,
            'currency' => $existing->currency ?? 'LKR',
            'notify_new_orders' => $request->boolean('notify_new_orders'),
            'notify_payments' => $request->boolean('notify_payments'),
            'notify_low_stock' => $request->boolean('notify_low_stock'),
            'notify_new_customers' => $request->boolean('notify_new_customers'),
            'updated_by' => $request->user()->id, 'updated_at' => now(),
        ];
        DB::table('store_settings')->updateOrInsert(['id' => 1], $values + ['created_at' => $existing->created_at ?? now()]);

        return redirect()->route('admin.section','settings')->with('success','Notification preferences saved.')->withFragment('notifications');
    }

    private function records(string $section, ?Request $request = null)
    {
        return match ($section) {
            'products' => DB::table('product')->leftJoin('category', 'category.id', '=', 'product.category_id')
                ->leftJoin('brand', 'brand.id', '=', 'product.brand_id')->leftJoinSub(
                    DB::table('product_variant')->select('product_id')->selectRaw('MIN(price) as starting_price, SUM(stock_quantity) as stock_total')->groupBy('product_id'),
                    'variant_totals', 'variant_totals.product_id', '=', 'product.id'
                )->leftJoinSub($this->productImageSelection(), 'selected_product_image', 'selected_product_image.product_id', '=', 'product.id')
                ->leftJoin('product_image', 'product_image.id', '=', 'selected_product_image.image_id')
                ->whereNull('product.deleted_at')->orderBy('product.name')
                ->get(['product.id', 'product.name', 'product.sku', 'product.status', 'product_image.image_url', 'category.name as category_name', 'brand.name as brand_name', 'variant_totals.starting_price', 'variant_totals.stock_total']),
            'categories' => $this->categoryRecords(),
            'brands' => DB::table('brand')->orderBy('name')->get(['id', 'name', 'slug', 'logo']),
            'customers' => $this->customerRecords($request ?? request()),
            'orders' => $this->orderRecords($request ?? request()),
            'payments' => $this->paymentRecords($request ?? request()),
            'coupons' => DB::table('coupon')->orderByDesc('id')->get(['id', 'code', 'discount_type', 'discount_value', 'minimum_amount', 'start_date', 'end_date', 'is_active']),
            'inventory' => DB::table('product_variant')->join('product', 'product.id', '=', 'product_variant.product_id')->whereNull('product.deleted_at')->orderBy('product.name')->get(['product_variant.id', 'product.name', 'product_variant.sku', 'product_variant.price', 'product_variant.stock_quantity', 'product_variant.is_active']),
            'reviews' => DB::table('review')->leftJoin('product', 'product.id', '=', 'review.product_id')->leftJoin('user', 'user.id', '=', 'review.user_id')->orderByDesc('review.created_at')->paginate(25, ['review.id', 'review.rating', 'review.comment', 'review.created_at', 'product.name as product_name', 'user.name as customer_name'])->withQueryString(),
            'reports' => DB::table('order_payment')->where('status', 'paid')->orderByDesc('paid_at')->orderByDesc('created_at')->paginate(50, ['id', 'order_id', 'amount', 'status', 'paid_at', 'created_at'])->withQueryString(),
            default => collect(),
        };
    }

    private function orderRecords(Request $request)
    {
        $query = DB::table('order')->leftJoin('user', 'user.id', '=', 'order.user_id')->whereNull('order.deleted_at');
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') $query->where(function ($builder) use ($search) {
            if (ctype_digit($search)) $builder->where('order.id', (int) $search)->orWhere('user.name', 'like', '%'.$search.'%')->orWhere('user.email', 'like', '%'.$search.'%');
            else $builder->where('user.name', 'like', '%'.$search.'%')->orWhere('user.email', 'like', '%'.$search.'%');
        });
        if ($request->filled('status')) {
            if ($request->query('status') === 'delivered') $query->whereIn('order.status', ['delivered', 'completed']);
            else $query->where('order.status', $request->query('status'));
        }
        if ($request->filled('date_from')) $query->whereDate('order.created_at', '>=', $request->query('date_from'));
        if ($request->filled('date_to')) $query->whereDate('order.created_at', '<=', $request->query('date_to'));

        return $query->orderByDesc('order.created_at')->paginate(25, ['order.id', 'order.user_id', 'order.status', 'order.total_amount', 'order.created_at', 'user.name as customer_name'])->withQueryString();
    }

    private function paymentRecords(Request $request)
    {
        $query = DB::table('order_payment')->leftJoin('payment', 'payment.id', '=', 'order_payment.payment_id')
            ->leftJoin('order', 'order.id', '=', 'order_payment.order_id')->leftJoin('user', 'user.id', '=', 'order.user_id');
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') $query->where(function ($builder) use ($search) {
            if (ctype_digit($search)) $builder->where('order_payment.order_id', (int) $search)->orWhere('order_payment.id', (int) $search)->orWhere('user.name', 'like', '%'.$search.'%')->orWhere('payment.transaction_id', 'like', '%'.$search.'%');
            else $builder->where('user.name', 'like', '%'.$search.'%')->orWhere('user.email', 'like', '%'.$search.'%')->orWhere('payment.transaction_id', 'like', '%'.$search.'%');
        });
        foreach (['status' => 'order_payment.status', 'method' => 'payment.method'] as $filter => $column) if ($request->filled($filter)) $query->where($column, $request->query($filter));
        if ($request->filled('date_from')) $query->whereDate('order_payment.created_at', '>=', $request->query('date_from'));
        if ($request->filled('date_to')) $query->whereDate('order_payment.created_at', '<=', $request->query('date_to'));

        return $query->orderByDesc('order_payment.created_at')->orderByDesc('order_payment.id')->paginate(25, [
            'order_payment.id', 'order_payment.order_id', 'order_payment.amount', 'order_payment.status', 'order_payment.paid_at', 'order_payment.created_at',
            'payment.method', 'payment.provider', 'payment.transaction_id', 'payment.status as gateway_status', 'user.name as customer_name', 'order.status as order_status',
        ])->withQueryString();
    }

    private function editableRecord(string $section, int $id): ?object
    {
        return match ($section) {
            'products' => DB::table('product')->leftJoin('product_variant', 'product_variant.product_id', '=', 'product.id')
                ->leftJoinSub($this->productImageSelection(), 'selected_product_image', 'selected_product_image.product_id', '=', 'product.id')
                ->leftJoin('product_image', 'product_image.id', '=', 'selected_product_image.image_id')
                ->where('product.id', $id)->whereNull('product.deleted_at')->orderBy('product_variant.id')
                ->first(['product.*', 'product_variant.price', 'product_variant.sale_price', 'product_variant.stock_quantity', 'product_image.image_url']),
            'categories' => DB::table('category')->where('id', $id)->first(),
            'brands' => DB::table('brand')->where('id', $id)->first(),
            'customers' => $this->customerQuery()->where('id', $id)->first(),
            'coupons' => DB::table('coupon')->where('id', $id)->first(),
            default => null,
        };
    }

    private function fields(string $section): array
    {
        $categories = fn () => DB::table('category')->orderBy('name')->pluck('name', 'id')->all();
        $brands = fn () => DB::table('brand')->orderBy('name')->pluck('name', 'id')->all();
        return match ($section) {
            'products' => [
                ['name' => 'name', 'label' => 'Product name', 'required' => true], ['name' => 'slug', 'label' => 'Slug (optional)'],
                ['name' => 'sku', 'label' => 'Product SKU', 'required' => true], ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'main_category_id', 'label' => 'Main Category', 'type' => 'main-category'], ['name' => 'subcategory_id', 'label' => 'Subcategory', 'type' => 'subcategory'],
                ['name' => 'brand_id', 'label' => 'Brand', 'type' => 'select', 'options' => ['' => 'No brand'] + $brands()],
                ['name' => 'price', 'label' => 'Price', 'type' => 'number', 'required' => true], ['name' => 'sale_price', 'label' => 'Sale price (optional)', 'type' => 'number'],
                ['name' => 'stock_quantity', 'label' => 'Initial stock', 'type' => 'number', 'required' => true], ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
            ],
            'categories' => [],
            'brands' => [['name' => 'name', 'label' => 'Brand name', 'required' => true], ['name' => 'slug', 'label' => 'Slug (optional)'], ['name' => 'logo', 'label' => 'Logo URL (optional)', 'type' => 'url']],
            'customers' => [['name' => 'name', 'label' => 'Customer name', 'required' => true], ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true], ['name' => 'phone', 'label' => 'Phone'], ['name' => 'address', 'label' => 'Address', 'type' => 'textarea']],
            'coupons' => [['name' => 'code', 'label' => 'Coupon code', 'required' => true], ['name' => 'discount_type', 'label' => 'Discount type', 'type' => 'select', 'options' => ['percentage' => 'Percentage', 'fixed' => 'Fixed amount']], ['name' => 'discount_value', 'label' => 'Discount value', 'type' => 'number', 'required' => true], ['name' => 'minimum_amount', 'label' => 'Minimum order amount', 'type' => 'number'], ['name' => 'start_date', 'label' => 'Starts at', 'type' => 'datetime-local'], ['name' => 'end_date', 'label' => 'Ends at', 'type' => 'datetime-local'], ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox']],
            default => [],
        };
    }

    private function productImageSelection()
    {
        return DB::table('product_image')->select('product_id')
            ->selectRaw('COALESCE(MIN(CASE WHEN is_primary = 1 THEN id END), MIN(id)) as image_id')
            ->groupBy('product_id');
    }

    private function validatedData(Request $request, string $section, ?int $id = null): array
    {
        if (in_array($section, ['products', 'categories', 'brands'], true) && $request->filled('name') && ! $request->filled('slug')) {
            $request->merge(['slug' => $this->uniqueSlug($request->input('name'), $section === 'products' ? 'product' : ($section === 'categories' ? 'category' : 'brand'), $id)]);
        }
        if ($section === 'categories') $request->merge(['is_active' => $request->boolean('is_active')]);
        $mainCategoryId = is_numeric($request->input('main_category_id'))
            ? (int) $request->input('main_category_id')
            : 0;
        $rules = match ($section) {
            'products' => [
                'name' => ['required', 'string', 'max:255'], 'slug' => ['nullable', 'string', 'max:255', Rule::unique('product', 'slug')->ignore($id)],
                'sku' => ['required', 'string', 'max:90', Rule::unique('product', 'sku')->ignore($id)], 'description' => ['nullable', 'string'],
                'main_category_id' => ['required', 'integer', Rule::exists('category', 'id')->whereNull('parent_id')->where('is_active', true)],
                'subcategory_id' => ['required', 'integer', Rule::exists('category', 'id')->where('parent_id', $mainCategoryId)->where('is_active', true)],
                'brand_id' => ['nullable', 'integer', 'exists:brand,id'],
                'price' => ['required', 'numeric', 'min:0'], 'sale_price' => ['nullable', 'numeric', 'min:0', 'lte:price'], 'stock_quantity' => ['required', 'integer', 'min:0'],
                'images' => ['nullable','array','max:12'], 'images.*' => ['image','mimes:jpg,jpeg,png,webp','max:5120'],
                'image' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'], 'image_url' => ['nullable','url','max:2048'],
                'image_order' => ['nullable','array','max:30'], 'image_order.*' => ['string','max:80'],
                'remove_image_ids' => ['nullable','array','max:30'], 'remove_image_ids.*' => ['integer','min:1'],
                'discarded_image_indexes' => ['nullable','array','max:12'], 'discarded_image_indexes.*' => ['integer','min:0'],
                'primary_image_reference' => ['nullable','string','max:80'], 'is_active' => ['nullable', 'boolean'],
            ],
            'categories' => [
                'category_type' => ['required', Rule::in(['main', 'subcategory'])],
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['nullable', 'string', 'max:255', Rule::unique('category', 'slug')->ignore($id)],
                'parent_id' => $request->input('category_type') === 'subcategory'
                    ? ['required', 'integer', Rule::exists('category', 'id')->whereNull('parent_id'), $id ? Rule::notIn([$id]) : 'integer']
                    : ['nullable'],
                'image' => [$id ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                'is_active' => ['required', 'boolean'],
            ],
            'brands' => ['name' => ['required', 'string', 'max:255'], 'slug' => ['nullable', 'string', 'max:255', Rule::unique('brand', 'slug')->ignore($id)], 'logo' => ['nullable', 'url', 'max:2048']],
            'customers' => ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email:rfc', 'max:255', Rule::unique('user', 'email')->ignore($id)], 'phone' => ['nullable', 'string', 'max:20', Rule::unique('user', 'phone')->ignore($id)], 'address' => ['nullable', 'string', 'max:255']],
            'coupons' => ['code' => ['required', 'string', 'max:100', Rule::unique('coupon', 'code')->ignore($id)], 'discount_type' => ['required', Rule::in(['percentage', 'fixed'])], 'discount_value' => ['required', 'numeric', 'gt:0'], 'minimum_amount' => ['nullable', 'numeric', 'min:0'], 'start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date'], 'is_active' => ['nullable', 'boolean']],
            'orders' => ['status' => ['required', Rule::in(['pending', 'processing', 'shipped', 'delivered', 'cancelled'])]],
            'inventory' => ['stock_quantity' => ['required', 'integer', 'min:0']],
            default => abort(404),
        };

        $data = $request->validate($rules);
        if ($section === 'products' && ! $id && DB::table('product_variant')->where('sku', $data['sku'].'-DEFAULT')->exists()) {
            throw ValidationException::withMessages(['sku' => 'That SKU is already used by a product variant.']);
        }
        if (in_array($section, ['products', 'categories', 'coupons'], true)) {
            $data['is_active'] = $request->boolean('is_active');
        }
        if ($section === 'products') $data['status'] = $data['is_active'] ? 'active' : 'inactive';
        if ($section === 'coupons') $data['code'] = Str::upper($data['code']);
        if ($section === 'customers') $data['email'] = Str::lower($data['email']);

        return $data;
    }

    private function createProduct(array $data): void
    {
        $storedPaths = [];
        try {
            DB::transaction(function () use ($data, &$storedPaths) {
                $productId = DB::table('product')->insertGetId([
                    'name' => $data['name'], 'slug' => $data['slug'] ?: Str::slug($data['name']), 'sku' => $data['sku'],
                    'description' => $data['description'] ?? null, 'main_category_id' => $data['main_category_id'], 'category_id' => $data['subcategory_id'], 'brand_id' => $data['brand_id'] ?? null,
                    'status' => $data['status'], 'is_active' => $data['is_active'], 'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('product_variant')->insert([
                    'product_id' => $productId, 'sku' => $data['sku'].'-DEFAULT', 'price' => $data['price'], 'sale_price' => $data['sale_price'] ?? null,
                    'stock_quantity' => $data['stock_quantity'], 'is_active' => $data['is_active'], 'created_at' => now(), 'updated_at' => now(),
                ]);
                $removedPaths = [];
                $this->persistProductImages($productId, $data, $storedPaths, $removedPaths);
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) Storage::disk('public')->delete($path);
            throw $exception;
        }
    }

    private function updateProduct(int $id, array $data): void
    {
        abort_unless(DB::table('product')->where('id', $id)->whereNull('deleted_at')->exists(), 404);
        $storedPaths = [];
        $removedImageUrls = [];
        try {
            DB::transaction(function () use ($id, $data, &$storedPaths, &$removedImageUrls) {
                DB::table('product')->where('id', $id)->whereNull('deleted_at')->update([
                    'name' => $data['name'], 'slug' => $data['slug'] ?: Str::slug($data['name']), 'sku' => $data['sku'],
                    'description' => $data['description'] ?? null, 'main_category_id' => $data['main_category_id'], 'category_id' => $data['subcategory_id'], 'brand_id' => $data['brand_id'] ?? null,
                    'status' => $data['status'], 'is_active' => $data['is_active'], 'updated_at' => now(),
                ]);
                $variant = DB::table('product_variant')->where('product_id', $id)->orderBy('id')->first();
                if ($variant) {
                    DB::table('product_variant')->where('id', $variant->id)->update(['price' => $data['price'], 'sale_price' => $data['sale_price'] ?? null, 'stock_quantity' => $data['stock_quantity'], 'is_active' => $data['is_active'], 'updated_at' => now()]);
                } else {
                    DB::table('product_variant')->insert(['product_id' => $id, 'sku' => $data['sku'].'-DEFAULT', 'price' => $data['price'], 'sale_price' => $data['sale_price'] ?? null, 'stock_quantity' => $data['stock_quantity'], 'is_active' => $data['is_active'], 'created_at' => now(), 'updated_at' => now()]);
                }
                $this->persistProductImages($id, $data, $storedPaths, $removedImageUrls);
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) Storage::disk('public')->delete($path);
            throw $exception;
        }
        foreach ($removedImageUrls as $url) $this->deleteProductImageFile($url);
    }

    private function persistProductImages(int $productId, array $data, array &$storedPaths, array &$removedImageUrls): void
    {
        $existing = DB::table('product_image')->where('product_id', $productId)->orderBy('sort_order')->orderBy('id')->get();
        $removeIds = collect($data['remove_image_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        if ($removeIds->isNotEmpty()) {
            $toRemove = $existing->whereIn('id', $removeIds);
            if ($toRemove->count() !== $removeIds->count()) {
                throw ValidationException::withMessages(['remove_image_ids' => 'One or more selected images do not belong to this product.']);
            }
            foreach ($toRemove as $image) $removedImageUrls[] = $image->image_url;
            DB::table('product_image')->where('product_id', $productId)->whereIn('id', $removeIds)->delete();
        }

        $remaining = DB::table('product_image')->where('product_id', $productId)->orderBy('sort_order')->orderBy('id')->get();
        $discarded = collect($data['discarded_image_indexes'] ?? [])->map(fn ($index) => (int) $index)->unique();
        $uploadFiles = array_values($data['images'] ?? []);
        foreach ($discarded as $index) if (! array_key_exists($index, $uploadFiles)) {
            throw ValidationException::withMessages(['discarded_image_indexes' => 'An uploaded image selection is invalid.']);
        }
        $newImageCount = count($uploadFiles) - $discarded->count() + (! empty($data['image']) ? 1 : 0) + (! empty($data['image_url']) ? 1 : 0);
        if ($remaining->count() + $newImageCount > 12) {
            throw ValidationException::withMessages(['images' => 'A product can have a maximum of 12 images. Remove existing images or select fewer files.']);
        }
        $newImages = [];
        foreach ($uploadFiles as $index => $file) {
            if ($discarded->contains($index)) continue;
            $path = $file->storePublicly('products', 'public');
            if (! $path) throw ValidationException::withMessages(['images' => 'A product image could not be saved.']);
            $storedPaths[] = $path;
            $newImages['new:'.$index] = '/storage/'.ltrim($path, '/');
        }
        if (! empty($data['image'])) {
            $path = $data['image']->storePublicly('products', 'public');
            if (! $path) throw ValidationException::withMessages(['image' => 'The product image could not be saved.']);
            $storedPaths[] = $path;
            $newImages['legacy-file'] = '/storage/'.ltrim($path, '/');
        }
        if (! empty($data['image_url'])) $newImages['legacy-url'] = $data['image_url'];

        $existingById = $remaining->keyBy('id');
        $references = [];
        foreach ($data['image_order'] ?? [] as $reference) {
            if (isset($references[$reference])) continue;
            if (Str::startsWith($reference, 'existing:')) {
                $id = (int) Str::after($reference, 'existing:');
                if ($existingById->has($id)) $references[$reference] = ['type' => 'existing', 'image' => $existingById->get($id)];
            } elseif (isset($newImages[$reference])) {
                $references[$reference] = ['type' => 'new', 'url' => $newImages[$reference]];
            }
        }
        foreach ($remaining as $image) $references['existing:'.$image->id] ??= ['type' => 'existing', 'image' => $image];
        foreach ($newImages as $reference => $url) $references[$reference] ??= ['type' => 'new', 'url' => $url];

        $primaryReference = $data['primary_image_reference'] ?? null;
        if ($primaryReference && ! isset($references[$primaryReference])) {
            throw ValidationException::withMessages(['primary_image_reference' => 'Select a primary image that belongs to this product.']);
        }
        if (! $primaryReference) {
            foreach ($references as $reference => $entry) {
                if ($entry['type'] === 'existing' && (bool) $entry['image']->is_primary) { $primaryReference = $reference; break; }
            }
            $primaryReference ??= array_key_first($references);
        }

        $sortOrder = 0;
        foreach ($references as $reference => $entry) {
            if ($entry['type'] === 'existing') {
                DB::table('product_image')->where('id', $entry['image']->id)->where('product_id', $productId)
                    ->update(['sort_order' => $sortOrder + 1, 'is_primary' => $reference === $primaryReference]);
            } else {
                DB::table('product_image')->insert([
                    'product_id' => $productId, 'image_url' => $entry['url'], 'is_primary' => $reference === $primaryReference,
                    'sort_order' => $sortOrder + 1, 'created_at' => now(),
                ]);
            }
            $sortOrder++;
        }
    }

    private function deleteProductImageFile(?string $url): void
    {
        if (! $url || Str::startsWith($url, ['http://','https://'])) return;
        $path = Str::startsWith($url, '/storage/') ? Str::after($url, '/storage/') : (Str::startsWith($url, 'products/') ? $url : null);
        if ($path) Storage::disk('public')->delete($path);
    }

    private function createCategory(array $data): void
    {
        $imagePath = $data['image']->storePublicly('categories', 'public');
        abort_unless($imagePath, 500, 'The category image could not be saved.');

        try {
            DB::table('category')->insert([
                'name' => $data['name'],
                'slug' => $data['slug'] ?: Str::slug($data['name']),
                'parent_id' => $data['category_type'] === 'subcategory' ? (int) $data['parent_id'] : null,
                'image_path' => $imagePath,
                'is_active' => $data['is_active'],
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($imagePath);
            throw $exception;
        }
    }

    private function updateCategory(int $id, array $data): void
    {
        $category = DB::table('category')->where('id', $id)->first();
        abort_unless($category, 404);

        $updates = [
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']),
            'parent_id' => $data['category_type'] === 'subcategory' ? (int) $data['parent_id'] : null,
            'is_active' => $data['is_active'],
        ];

        if ($data['image'] ?? null) {
            $imagePath = $data['image']->storePublicly('categories', 'public');
            abort_unless($imagePath, 500, 'The category image could not be saved.');
            $updates['image_path'] = $imagePath;
        }

        DB::table('category')->where('id', $id)->update($updates);

        if (isset($imagePath) && $category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }
    }

    private function categoryRecords()
    {
        $categories = DB::table('category')->orderBy('name')->get();
        $children = $categories->whereNotNull('parent_id')->groupBy('parent_id');

        return $categories->whereNull('parent_id')->values()->map(function ($category) use ($children) {
            $category->children = $children->get($category->id, collect())->values();

            return $category;
        });
    }

    private function updateCustomer(int $id, array $data): void
    {
        abort_unless($this->customerQuery()->where('id', $id)->exists(), 404);
        DB::table('user')->where('id', $id)->whereNull('deleted_at')->update($data + ['updated_at' => now()]);
    }

    private function customerRecords(Request $request)
    {
        $query = $this->customerQuery();
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        match ($request->query('status')) {
            'active' => $query->where('is_active', true)->where('is_blocked', false),
            'inactive' => $query->where('is_active', false)->where('is_blocked', false),
            'blocked' => $query->where('is_blocked', true),
            default => null,
        };

        return $query->orderByDesc('created_at')->paginate(25, ['id', 'name', 'email', 'phone', 'created_at', 'is_active', 'is_blocked'])->withQueryString();
    }

    private function updateOrder(int $id, string $status): void
    {
        DB::transaction(function () use ($id, $status) {
            $order = DB::table('order')->where('id', $id)->whereNull('deleted_at')->lockForUpdate()->first();
            abort_unless($order, 404);
            $current = $order->status === 'completed' ? 'delivered' : ($order->status ?: 'pending');
            $transitions = [
                'pending' => ['processing', 'cancelled'],
                'processing' => ['shipped', 'cancelled'],
                'shipped' => ['delivered'],
                'delivered' => [],
                'cancelled' => [],
            ];
            abort_unless($status === $current || in_array($status, $transitions[$current] ?? [], true), 409, 'This order cannot move to the selected status.');
            if ($status === $current) return;
            if ($status === 'cancelled' && $current !== 'cancelled') {
                abort_if(DB::table('order_payment')->where('order_id', $id)->where('status', 'paid')->exists(), 409, 'Refund the paid amount before cancelling this order.');
                $items = DB::table('order_item')->where('order_id', $id)->get();
                foreach ($items as $item) {
                    if ($item->product_variant_id) DB::table('product_variant')->where('id', $item->product_variant_id)->whereNotNull('stock_quantity')->increment('stock_quantity', (int) $item->quantity);
                }
            }
            DB::table('order')->where('id', $id)->update(['status' => $status, 'updated_at' => now()]);
            $shippingStatus = match ($status) { 'processing' => 'processing', 'shipped' => 'shipped', 'delivered' => 'delivered', 'cancelled' => 'cancelled', default => 'pending' };
            $shippingUpdates = ['status' => $shippingStatus];
            if ($status === 'shipped') $shippingUpdates['shipped_at'] = now();
            if ($status === 'delivered') $shippingUpdates['delivered_at'] = now();
            DB::table('shipping')->where('order_id', $id)->update($shippingUpdates);
        });
    }

    private function slugged(array $data, string $table): array
    {
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        if ($table === 'category') $data['parent_id'] = $data['parent_id'] ?: null;
        return $data;
    }

    private function uniqueSlug(string $name, string $table, ?int $ignoreId): string
    {
        $base = Str::slug($name) ?: Str::random(8);
        $slug = $base;
        $suffix = 2;
        while (DB::table($table)->where('slug', $slug)->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function customerQuery()
    {
        return DB::table('user')->whereNull('deleted_at')
            ->where(function ($query) { $query->whereNull('is_admin')->orWhere('is_admin', false); })
            ->where(function ($query) { $query->whereNull('role')->orWhere('role', '<>', User::ROLE_ADMIN); });
    }

    private function categorySidebarTree()
    {
        $categories = DB::table('category')->orderBy('name')->get(['id', 'name', 'parent_id']);
        $children = $categories->whereNotNull('parent_id')->groupBy('parent_id');

        return $categories->whereNull('parent_id')->values()->map(function ($category) use ($children) {
            $category->children = $children->get($category->id, collect())->values();

            return $category;
        });
    }
}
