<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
        $records = $this->records($section);
        $editing = null;

        if ($request->filled('edit') && in_array($section, ['products', 'categories', 'brands', 'customers', 'coupons'], true)) {
            $editing = $this->editableRecord($section, (int) $request->query('edit'));
            abort_unless($editing, 404);
        }

        return view('pages.admin', [
            'section' => $section,
            'sections' => self::SECTIONS,
            'adminCategoryTree' => $this->categorySidebarTree(),
            'records' => $records,
            'fields' => $this->fields($section),
            'editing' => $editing,
            'stats' => [],
            'salesByMonth' => collect(),
            'recentOrders' => collect(),
        ]);
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
                ->where('shipping.order_id', $id)->first(['shipping.*', 'address.contact_name', 'address.phone', 'address.address_line1', 'address.address_line2', 'address.city', 'address.postal_code', 'address.country']),
            'payments' => DB::table('order_payment')->leftJoin('payment', 'payment.id', '=', 'order_payment.payment_id')
                ->where('order_payment.order_id', $id)->get(['order_payment.*', 'payment.method', 'payment.provider', 'payment.transaction_id']),
            'sections' => self::SECTIONS,
            'section' => 'orders',
            'adminCategoryTree' => $this->categorySidebarTree(),
        ]);
    }

    public function store(Request $request, string $section)
    {
        abort_unless(array_key_exists($section, self::SECTIONS), 404);
        $data = $this->validatedData($request, $section);

        match ($section) {
            'products' => $this->createProduct($data),
            'categories' => DB::table('category')->insert($this->slugged($data, 'category')),
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
        $data = $this->validatedData($request, $section, $id);

        match ($section) {
            'products' => $this->updateProduct($id, $data),
            'categories' => DB::table('category')->where('id', $id)->update($this->slugged($data, 'category')),
            'brands' => DB::table('brand')->where('id', $id)->update($this->slugged($data, 'brand')),
            'customers' => $this->updateCustomer($id, $data),
            'orders' => $this->updateOrder($id, $data['status']),
            'payments' => $this->updatePayment($id, $data['status']),
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
            abort_if(DB::table('product')->where('category_id', $id)->exists()
                || DB::table('category')->where('parent_id', $id)->exists()
                || DB::table('category_filter')->where('category_id', $id)->exists(), 409, 'Move its products, filters, and subcategories before removing this category.');
            DB::table('category')->where('id', $id)->delete();
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
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('user', 'email')->ignore($admin->id)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('user', 'phone')->ignore($admin->id)],
            'password' => ['nullable', 'confirmed', 'min:8', 'regex:/[A-Za-z]/', 'regex:/[0-9]/'],
        ]);
        $admin->name = $data['name'];
        $admin->email = Str::lower($data['email']);
        $admin->phone = $data['phone'];
        if (! empty($data['password'])) $admin->password = Hash::make($data['password']);
        $admin->save();

        return redirect()->route('admin.section', 'settings')->with('success', 'Profile settings updated.');
    }

    private function records(string $section)
    {
        return match ($section) {
            'products' => DB::table('product')->leftJoin('category', 'category.id', '=', 'product.category_id')
                ->leftJoin('brand', 'brand.id', '=', 'product.brand_id')->leftJoinSub(
                    DB::table('product_variant')->select('product_id')->selectRaw('MIN(price) as starting_price, SUM(stock_quantity) as stock_total')->groupBy('product_id'),
                    'variant_totals', 'variant_totals.product_id', '=', 'product.id'
                )->leftJoin('product_image', function ($join) { $join->on('product_image.product_id', '=', 'product.id')->where('product_image.is_primary', true); })
                ->whereNull('product.deleted_at')->orderBy('product.name')
                ->get(['product.id', 'product.name', 'product.sku', 'product.status', 'product_image.image_url', 'category.name as category_name', 'brand.name as brand_name', 'variant_totals.starting_price', 'variant_totals.stock_total']),
            'categories' => DB::table('category')->leftJoin('category as parent', 'parent.id', '=', 'category.parent_id')->orderBy('category.name')->get(['category.id', 'category.name', 'category.slug', 'category.is_active', 'parent.name as parent_name']),
            'brands' => DB::table('brand')->orderBy('name')->get(['id', 'name', 'slug', 'logo']),
            'customers' => $this->customerQuery()->orderByDesc('created_at')->get(['id', 'name', 'email', 'phone', 'created_at']),
            'orders' => DB::table('order')->leftJoin('user', 'user.id', '=', 'order.user_id')->whereNull('order.deleted_at')->orderByDesc('order.created_at')->paginate(25, ['order.id', 'order.user_id', 'order.status', 'order.total_amount', 'order.created_at', 'user.name as customer_name'])->withQueryString(),
            'payments' => DB::table('order_payment')->leftJoin('payment', 'payment.id', '=', 'order_payment.payment_id')->leftJoin('order', 'order.id', '=', 'order_payment.order_id')->leftJoin('user', 'user.id', '=', 'order.user_id')->orderByDesc('order_payment.created_at')->paginate(25, ['order_payment.id', 'order_payment.order_id', 'order_payment.amount', 'order_payment.status', 'order_payment.paid_at', 'payment.method', 'payment.transaction_id', 'user.name as customer_name'])->withQueryString(),
            'coupons' => DB::table('coupon')->orderByDesc('id')->get(['id', 'code', 'discount_type', 'discount_value', 'minimum_amount', 'start_date', 'end_date', 'is_active']),
            'inventory' => DB::table('product_variant')->join('product', 'product.id', '=', 'product_variant.product_id')->whereNull('product.deleted_at')->orderBy('product.name')->get(['product_variant.id', 'product.name', 'product_variant.sku', 'product_variant.price', 'product_variant.stock_quantity', 'product_variant.is_active']),
            'reviews' => DB::table('review')->leftJoin('product', 'product.id', '=', 'review.product_id')->leftJoin('user', 'user.id', '=', 'review.user_id')->orderByDesc('review.created_at')->paginate(25, ['review.id', 'review.rating', 'review.comment', 'review.created_at', 'product.name as product_name', 'user.name as customer_name'])->withQueryString(),
            'reports' => DB::table('order_payment')->where('status', 'paid')->orderByDesc('paid_at')->orderByDesc('created_at')->paginate(50, ['id', 'order_id', 'amount', 'status', 'paid_at', 'created_at'])->withQueryString(),
            default => collect(),
        };
    }

    private function editableRecord(string $section, int $id): ?object
    {
        return match ($section) {
            'products' => DB::table('product')->leftJoin('product_variant', 'product_variant.product_id', '=', 'product.id')->leftJoin('product_image', function ($join) { $join->on('product_image.product_id', '=', 'product.id')->where('product_image.is_primary', true); })->where('product.id', $id)->whereNull('product.deleted_at')->orderBy('product_variant.id')->first(['product.*', 'product_variant.price', 'product_variant.sale_price', 'product_variant.stock_quantity', 'product_image.image_url']),
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
                ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'options' => ['' => 'No category'] + $categories()], ['name' => 'brand_id', 'label' => 'Brand', 'type' => 'select', 'options' => ['' => 'No brand'] + $brands()],
                ['name' => 'price', 'label' => 'Price', 'type' => 'number', 'required' => true], ['name' => 'sale_price', 'label' => 'Sale price (optional)', 'type' => 'number'],
                ['name' => 'stock_quantity', 'label' => 'Initial stock', 'type' => 'number', 'required' => true], ['name' => 'image', 'label' => 'Product image', 'type' => 'file'], ['name' => 'image_url', 'label' => 'Or enter an image URL (optional)', 'type' => 'url'], ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
            ],
            'categories' => [['name' => 'name', 'label' => 'Category name', 'required' => true], ['name' => 'slug', 'label' => 'Slug (optional)'], ['name' => 'parent_id', 'label' => 'Parent category ID (optional)', 'type' => 'number'], ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox']],
            'brands' => [['name' => 'name', 'label' => 'Brand name', 'required' => true], ['name' => 'slug', 'label' => 'Slug (optional)'], ['name' => 'logo', 'label' => 'Logo URL (optional)', 'type' => 'url']],
            'customers' => [['name' => 'name', 'label' => 'Customer name', 'required' => true], ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true], ['name' => 'phone', 'label' => 'Phone']],
            'coupons' => [['name' => 'code', 'label' => 'Coupon code', 'required' => true], ['name' => 'discount_type', 'label' => 'Discount type', 'type' => 'select', 'options' => ['percentage' => 'Percentage', 'fixed' => 'Fixed amount']], ['name' => 'discount_value', 'label' => 'Discount value', 'type' => 'number', 'required' => true], ['name' => 'minimum_amount', 'label' => 'Minimum order amount', 'type' => 'number'], ['name' => 'start_date', 'label' => 'Starts at', 'type' => 'datetime-local'], ['name' => 'end_date', 'label' => 'Ends at', 'type' => 'datetime-local'], ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox']],
            default => [],
        };
    }

    private function validatedData(Request $request, string $section, ?int $id = null): array
    {
        if (in_array($section, ['products', 'categories', 'brands'], true) && $request->filled('name') && ! $request->filled('slug')) {
            $request->merge(['slug' => $this->uniqueSlug($request->input('name'), $section === 'products' ? 'product' : ($section === 'categories' ? 'category' : 'brand'), $id)]);
        }
        $rules = match ($section) {
            'products' => [
                'name' => ['required', 'string', 'max:255'], 'slug' => ['nullable', 'string', 'max:255', Rule::unique('product', 'slug')->ignore($id)],
                'sku' => ['required', 'string', 'max:90', Rule::unique('product', 'sku')->ignore($id)], 'description' => ['nullable', 'string'],
                'category_id' => ['required', 'integer', 'exists:category,id'], 'brand_id' => ['nullable', 'integer', 'exists:brand,id'],
                'price' => ['required', 'numeric', 'min:0'], 'sale_price' => ['nullable', 'numeric', 'min:0', 'lte:price'], 'stock_quantity' => ['required', 'integer', 'min:0'], 'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], 'image_url' => ['nullable', 'url', 'max:2048'], 'is_active' => ['nullable', 'boolean'],
            ],
            'categories' => ['name' => ['required', 'string', 'max:255'], 'slug' => ['nullable', 'string', 'max:255', Rule::unique('category', 'slug')->ignore($id)], 'parent_id' => array_values(array_filter(['nullable', 'integer', 'exists:category,id', $id ? Rule::notIn([$id]) : null])), 'is_active' => ['nullable', 'boolean']],
            'brands' => ['name' => ['required', 'string', 'max:255'], 'slug' => ['nullable', 'string', 'max:255', Rule::unique('brand', 'slug')->ignore($id)], 'logo' => ['nullable', 'url', 'max:2048']],
            'customers' => ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email:rfc', 'max:255', Rule::unique('user', 'email')->ignore($id)], 'phone' => ['nullable', 'string', 'max:20']],
            'coupons' => ['code' => ['required', 'string', 'max:100', Rule::unique('coupon', 'code')->ignore($id)], 'discount_type' => ['required', Rule::in(['percentage', 'fixed'])], 'discount_value' => ['required', 'numeric', 'gt:0'], 'minimum_amount' => ['nullable', 'numeric', 'min:0'], 'start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date'], 'is_active' => ['nullable', 'boolean']],
            'orders' => ['status' => ['required', Rule::in(['pending', 'processing', 'shipped', 'completed', 'cancelled'])]],
            'payments' => ['status' => ['required', Rule::in(['pending', 'paid', 'failed', 'refunded'])]],
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
        DB::transaction(function () use ($data) {
            $imageUrl = $this->productImageUrl($data);
            $productId = DB::table('product')->insertGetId([
                'name' => $data['name'], 'slug' => $data['slug'] ?: Str::slug($data['name']), 'sku' => $data['sku'],
                'description' => $data['description'] ?? null, 'category_id' => $data['category_id'] ?? null, 'brand_id' => $data['brand_id'] ?? null,
                'status' => $data['status'], 'is_active' => $data['is_active'], 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('product_variant')->insert([
                'product_id' => $productId, 'sku' => $data['sku'].'-DEFAULT', 'price' => $data['price'], 'sale_price' => $data['sale_price'] ?? null,
                'stock_quantity' => $data['stock_quantity'], 'is_active' => $data['is_active'], 'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($imageUrl) DB::table('product_image')->insert(['product_id' => $productId, 'image_url' => $imageUrl, 'is_primary' => true, 'sort_order' => 1, 'created_at' => now()]);
        });
    }

    private function updateProduct(int $id, array $data): void
    {
        DB::transaction(function () use ($id, $data) {
            $imageUrl = $this->productImageUrl($data);
            DB::table('product')->where('id', $id)->whereNull('deleted_at')->update([
                'name' => $data['name'], 'slug' => $data['slug'] ?: Str::slug($data['name']), 'sku' => $data['sku'],
                'description' => $data['description'] ?? null, 'category_id' => $data['category_id'] ?? null, 'brand_id' => $data['brand_id'] ?? null,
                'status' => $data['status'], 'is_active' => $data['is_active'], 'updated_at' => now(),
            ]);
            $variant = DB::table('product_variant')->where('product_id', $id)->orderBy('id')->first();
            if ($variant) {
                DB::table('product_variant')->where('id', $variant->id)->update(['price' => $data['price'], 'sale_price' => $data['sale_price'] ?? null, 'stock_quantity' => $data['stock_quantity'], 'is_active' => $data['is_active'], 'updated_at' => now()]);
            } else {
                DB::table('product_variant')->insert(['product_id' => $id, 'sku' => $data['sku'].'-DEFAULT', 'price' => $data['price'], 'sale_price' => $data['sale_price'] ?? null, 'stock_quantity' => $data['stock_quantity'], 'is_active' => $data['is_active'], 'created_at' => now(), 'updated_at' => now()]);
            }
            if ($imageUrl) {
                $image = DB::table('product_image')->where('product_id', $id)->where('is_primary', true)->orderBy('id')->first();
                if ($image) DB::table('product_image')->where('id', $image->id)->update(['image_url' => $imageUrl]);
                else DB::table('product_image')->insert(['product_id' => $id, 'image_url' => $imageUrl, 'is_primary' => true, 'sort_order' => 1, 'created_at' => now()]);
            }
        });
    }

    private function productImageUrl(array $data): ?string
    {
        if (! empty($data['image'])) {
            $path = $data['image']->storePublicly('products', 'public');
            abort_unless($path, 500, 'The product image could not be saved.');

            // Keep uploaded image URLs relative to the active host and port.
            // APP_URL may omit the port used by `php artisan serve` in development.
            return '/storage/'.ltrim($path, '/');
        }

        return $data['image_url'] ?? null;
    }

    private function updateCustomer(int $id, array $data): void
    {
        abort_unless($this->customerQuery()->where('id', $id)->exists(), 404);
        DB::table('user')->where('id', $id)->whereNull('deleted_at')->update($data + ['updated_at' => now()]);
    }

    private function updateOrder(int $id, string $status): void
    {
        DB::transaction(function () use ($id, $status) {
            $order = DB::table('order')->where('id', $id)->whereNull('deleted_at')->lockForUpdate()->first();
            abort_unless($order, 404);
            if ($status === 'cancelled' && $order->status !== 'cancelled') {
                abort_if(DB::table('order_payment')->where('order_id', $id)->where('status', 'paid')->exists(), 409, 'Refund the paid amount before cancelling this order.');
                $items = DB::table('order_item')->where('order_id', $id)->get();
                foreach ($items as $item) {
                    if ($item->product_variant_id) DB::table('product_variant')->where('id', $item->product_variant_id)->whereNotNull('stock_quantity')->increment('stock_quantity', (int) $item->quantity);
                }
            } elseif ($order->status === 'cancelled' && $status !== 'cancelled') {
                $items = DB::table('order_item')->where('order_id', $id)->get();
                foreach ($items as $item) {
                    if (! $item->product_variant_id) continue;
                    $variant = DB::table('product_variant')->where('id', $item->product_variant_id)->lockForUpdate()->first();
                    abort_unless($variant && ($variant->stock_quantity === null || (int) $variant->stock_quantity >= (int) $item->quantity), 409, 'There is not enough stock to reopen this order.');
                    if ($variant->stock_quantity !== null) DB::table('product_variant')->where('id', $variant->id)->decrement('stock_quantity', (int) $item->quantity);
                }
            }
            DB::table('order')->where('id', $id)->update(['status' => $status, 'updated_at' => now()]);
        });
    }

    private function updatePayment(int $id, string $status): void
    {
        $payment = DB::table('order_payment')->where('id', $id)->first();
        abort_unless($payment, 404);
        DB::table('order_payment')->where('id', $id)->update(['status' => $status, 'paid_at' => $status === 'paid' ? ($payment->paid_at ?? now()) : null]);
        DB::table('payment')->where('id', $payment->payment_id)->update(['status' => $status]);
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
