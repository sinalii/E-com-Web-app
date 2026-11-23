<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShopController extends Controller
{
    public function home()
    {
        [$categories, $categoryTree] = $this->categoryTree();
        $products = $this->productQuery()->get()->groupBy('category_id');
        $categoryCounts = $this->categoryCounts($products, $categoryTree);
        $categoryHighlights = $categories->values();
        $categoryImages = [
            'womens-clothing' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=900&q=85',
            'mens-clothing' => 'https://images.unsplash.com/photo-1516257984-b1b4d707412e?auto=format&fit=crop&w=900&q=85',
            'phones-telecommunications' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=900&q=85',
            'phone-accessories' => 'https://images.unsplash.com/photo-1605236453806-6ff36851218e?auto=format&fit=crop&w=900&q=85',
            'computer-office' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=900&q=85',
            'consumer-electronics' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?auto=format&fit=crop&w=900&q=85',
            'jewelry-accessories' => 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?auto=format&fit=crop&w=900&q=85',
            'watches' => 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=900&q=85',
            'home-garden' => 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=900&q=85',
            'home-appliances' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=900&q=85',
            'luggage-bags' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=900&q=85',
            'shoes' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=85',
            'mother-kids' => 'https://images.unsplash.com/photo-1519689680058-324335c77eba?auto=format&fit=crop&w=900&q=85',
            'toys-hobbies' => 'https://images.unsplash.com/photo-1558060370-d644479cb6f7?auto=format&fit=crop&w=900&q=85',
            'sports-outdoors' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=900&q=85',
            'automobiles-motorcycles' => 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&w=900&q=85',
            'tools-home-improvement' => 'https://images.unsplash.com/photo-1504148455328-c376907d081c?auto=format&fit=crop&w=900&q=85',
            'lights-lighting' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=900&q=85',
            'furniture' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=900&q=85',
            'office-school-supplies' => 'https://images.unsplash.com/photo-1455390582262-044c759a2ef3?auto=format&fit=crop&w=900&q=85',
            'beauty-health' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=900&q=85',
            'hair-extensions-wigs' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=900&q=85',
            'toys-kids-baby-overlap-baby-specific' => 'https://images.unsplash.com/photo-1516627145497-ae6968895b74?auto=format&fit=crop&w=900&q=85',
            'security-protection' => 'https://images.unsplash.com/photo-1558008258-3256797b43f3?auto=format&fit=crop&w=900&q=85',
            'industrial-business' => 'https://images.unsplash.com/photo-1581092160562-40aa08e78837?auto=format&fit=crop&w=900&q=85',
            'weddings-events' => 'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=900&q=85',
            'underwear-hosiery' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=900&q=85',
            'apparel-accessories' => 'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=900&q=85',
            'novelty-special-use' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=900&q=85',
            'books-music-collectibles' => 'https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=900&q=85',
            'food-grocery-where-legally-available' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=900&q=85',
        ];

        return view('pages.index', [
            'categories' => $categories,
            'categoryTree' => $categoryTree,
            'categoryCounts' => $categoryCounts,
            'categoryHighlights' => $categoryHighlights,
            'categoryPages' => $categoryHighlights->chunk(5)->values(),
            'categoryImages' => $categoryImages,
            'products' => $products,
            'featured' => $products->flatten()->unique('id')->take(8),
            'allProductCount' => $products->flatten()->unique('id')->count(),
            'cartCount' => $this->cartCount(),
            'wishlistIds' => $this->wishlistIds(),
        ]);
    }

    public function index(Request $request)
    {
        [$categories, $categoryTree, $allCategories] = $this->categoryTree();
        $selectedCategory = $this->resolveCategory($request->query('category'), $allCategories);
        $query = $this->productQuery();

        if ($selectedCategory) {
            $categoryIds = $selectedCategory->parent_id === null
                ? $categoryTree->firstWhere('id', $selectedCategory->id)->children->pluck('id')->all()
                : [$selectedCategory->id];
            if ($categoryIds) $query->whereIn('category.id', $categoryIds);
            else $query->whereRaw('1 = 0');
        }

        if ($request->filled('q')) {
            $term = trim($request->query('q'));
            $query->where(function (Builder $builder) use ($term) {
                $builder->where('product.name', 'like', '%'.$term.'%')
                    ->orWhere('product.description', 'like', '%'.$term.'%');
            });
        }

        if ($request->filled('min_price')) $query->whereRaw('COALESCE(product_variant.sale_price, product_variant.price) >= ?', [(float) $request->query('min_price')]);
        if ($request->filled('max_price')) $query->whereRaw('COALESCE(product_variant.sale_price, product_variant.price) <= ?', [(float) $request->query('max_price')]);
        if ($request->filled('min_rating')) $query->where('review_stats.average_rating', '>=', (float) $request->query('min_rating'));
        if ($request->boolean('in_stock')) $query->where('product_variant.stock_quantity', '>', 0);

        $filterCategoryIds = collect();
        if ($selectedCategory) {
            $filterCategoryIds = $selectedCategory->parent_id === null
                ? $categoryTree->firstWhere('id', $selectedCategory->id)->children->pluck('id')
                : collect([$selectedCategory->id]);
        }
        $filterDefinitions = $filterCategoryIds->isNotEmpty()
            ? DB::table('category_filter')->whereIn('category_id', $filterCategoryIds)
                ->orderBy('sort_order')->get()->unique('slug')->values()
            : collect();
        $subcategoryImages = [
            'Home & Garden' => 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=640&q=88',
            'Home Appliances' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=640&q=88',
            'Furniture' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=640&q=88',
            'Kitchen, Dining & Bar' => 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=640&q=88',
            'Home Décor' => 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=640&q=88',
            'Lighting' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=640&q=88',
            'Artificial Plants' => 'https://images.unsplash.com/photo-1497250681960-ef046c08a56e?auto=format&fit=crop&w=640&q=88',
            'Wall Decor & Clocks' => 'https://images.unsplash.com/photo-1563861826100-9cb868fdbe1c?auto=format&fit=crop&w=640&q=88',
            'Mirrors, Vases & Figurines' => 'https://images.unsplash.com/photo-1578500494198-246f612d3b3d?auto=format&fit=crop&w=640&q=88',
            'Cookware & Bakeware' => 'https://images.unsplash.com/photo-1556911220-bff31c812dba?auto=format&fit=crop&w=640&q=88',
            'Candles & Holders' => 'https://images.unsplash.com/photo-1603006905003-be475563bc59?auto=format&fit=crop&w=640&q=88',
            'Bedding Sets' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&w=640&q=88',
            'Drinkware & Barware' => 'https://images.unsplash.com/photo-1514228742587-6b1558f07855?auto=format&fit=crop&w=640&q=88',
            'Food Storage & Containers' => 'https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?auto=format&fit=crop&w=640&q=88',
            'Watches' => 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=640&q=88',
            'Phones & Telecommunications' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=640&q=88',
            'Phone Accessories' => 'https://images.unsplash.com/photo-1605236453806-6ff36851218e?auto=format&fit=crop&w=640&q=88',
            'Computer & Office' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=640&q=88',
            'Consumer Electronics' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?auto=format&fit=crop&w=640&q=88',
            'Women’s Clothing' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=640&q=88',
            'Men’s Clothing' => 'https://images.unsplash.com/photo-1516257984-b1b4d707412e?auto=format&fit=crop&w=640&q=88',
            'Shoes' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=640&q=88',
            'Luggage & Bags' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=640&q=88',
            'Beauty & Health' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=640&q=88',
            'Sports & Outdoors' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=640&q=88',
            'Toys & Hobbies' => 'https://images.unsplash.com/photo-1558060370-d644479cb6f7?auto=format&fit=crop&w=640&q=88',
            'Jewelry & Accessories' => 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?auto=format&fit=crop&w=640&q=88',
        ];
        $recommendedSubcategories = $selectedCategory && $selectedCategory->parent_id === null
            ? $categoryTree->firstWhere('id', $selectedCategory->id)->children->map(function ($subcategory) use ($subcategoryImages) {
                $subcategory->image_url = $subcategory->image_path
                    ? asset('storage/'.$subcategory->image_path)
                    : ($subcategoryImages[$subcategory->name]
                        ?? 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=640&q=88');
                return $subcategory;
            })
            : collect();

        foreach ($filterDefinitions as $filter) {
            if ($filter->input_type === 'attribute' && $request->filled('filters.'.$filter->slug)) {
                $values = array_values(array_filter((array) $request->input('filters.'.$filter->slug)));
                if ($values) {
                    $attributeNames = $this->attributeNames($filter->name);
                    $query->whereExists(function (Builder $subquery) use ($values, $attributeNames) {
                        $subquery->selectRaw('1')->from('product_attribute as selected_attribute')
                            ->whereColumn('selected_attribute.product_id', 'product.id')
                            ->whereIn(DB::raw('LOWER(TRIM(selected_attribute.name))'), $attributeNames)
                            ->whereIn('selected_attribute.value', $values);
                    });
                }
            }
        }

        if ($request->query('sort') === 'price_asc') $query->orderByRaw('COALESCE(product_variant.sale_price, product_variant.price) ASC');
        elseif ($request->query('sort') === 'price_desc') $query->orderByRaw('COALESCE(product_variant.sale_price, product_variant.price) DESC');
        elseif ($request->query('sort') === 'newest') $query->orderByDesc('product.created_at')->orderByDesc('product.updated_at')->orderByDesc('product.id');
        elseif ($request->query('sort') === 'rating') $query->orderByDesc('review_stats.average_rating');
        elseif ($request->query('sort') === 'orders') $query->orderByDesc('sales_stats.orders_count');
        elseif ($request->query('sort') === 'discount') $query->orderByRaw('(COALESCE(product_variant.price, 0) - COALESCE(product_variant.sale_price, product_variant.price)) DESC');
        else $query->orderBy('product.name');

        $items = $query->get()->unique(fn ($product) => $product->id)->values();
        $products = $items->groupBy('category_id');
        $categoryCounts = $this->categoryCounts($products, $categoryTree);
        $productCategories = $allCategories->filter(fn ($category) => $category->parent_id !== null && $products->has($category->id))->values();
        $facets = $this->facets($filterDefinitions, $selectedCategory, $categoryTree);

        return view('pages.shop', [
            'categories' => $categories,
            'categoryTree' => $categoryTree,
            'categoryCounts' => $categoryCounts,
            'productCategories' => $productCategories,
            'products' => $products,
            'orderedProducts' => $items,
            'allProductCount' => $items->unique('id')->count(),
            'selectedCategory' => $selectedCategory,
            'recommendedSubcategories' => $recommendedSubcategories,
            'facets' => $facets,
            'searchTerm' => $request->query('q'),
            'cartCount' => $this->cartCount(),
            'wishlistIds' => $this->wishlistIds(),
        ]);
    }

    public function show(string $product)
    {
        [$categories, $categoryTree] = $this->categoryTree();
        $query = DB::table('product')
            ->join('category', 'category.id', '=', 'product.category_id')
            ->leftJoin('brand', 'brand.id', '=', 'product.brand_id')
            ->where('product.is_active', true)
            ->whereNull('product.deleted_at')
            ->where('category.is_active', true);

        if (ctype_digit($product)) {
            $query->where('product.id', (int) $product);
        } else {
            $query->where('product.slug', $product);
        }

        $item = $query->select(
            'product.id', 'product.name', 'product.description', 'product.slug', 'product.sku',
            'category.id as category_id', 'category.name as category_name',
            'brand.name as brand_name'
        )->firstOrFail();

        $item->images = DB::table('product_image')->where('product_id', $item->id)
            ->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id')->get();
        $item->variants = DB::table('product_variant')->where('product_id', $item->id)
            ->where('is_active', true)->orderBy('id')->get();

        $variantIds = $item->variants->pluck('id');
        $optionsByVariant = DB::table('product_variant_option')->whereIn('product_variant_id', $variantIds)
            ->orderBy('id')->get()->groupBy('product_variant_id');
        foreach ($item->variants as $variant) {
            $variant->options = $optionsByVariant->get($variant->id, collect());
        }

        $item->attributes = DB::table('product_attribute')->where('product_id', $item->id)
            ->where(function ($query) {
                $query->whereNull('is_variant')->orWhere('is_variant', false);
            })->whereNotNull('value')->where('value', '<>', '')
            ->orderBy('name')->get();
        $item->reviews = DB::table('review')->leftJoin('user', 'user.id', '=', 'review.user_id')
            ->where('review.product_id', $item->id)->whereNotNull('review.rating')
            ->orderByDesc('review.created_at')
            ->select('review.rating', 'review.comment', 'review.created_at', 'user.name as reviewer_name')
            ->limit(8)->get();
        $item->rating = DB::table('review')->where('product_id', $item->id)->whereNotNull('rating')->avg('rating');
        $item->review_count = DB::table('review')->where('product_id', $item->id)->whereNotNull('rating')->count();
        $userReview = Auth::check()
            ? DB::table('review')->where('product_id', $item->id)->where('user_id', Auth::id())->first()
            : null;

        return view('pages.product', [
            'categories' => $categories,
            'categoryTree' => $categoryTree,
            'product' => $item,
            'userReview' => $userReview,
            'cartCount' => $this->cartCount(),
            'wishlistIds' => $this->wishlistIds(),
        ]);
    }

    public function page(string $page)
    {
        [$categories] = $this->categoryTree();

        $content = [
            'about' => ['eyebrow' => 'A marketplace made for discovery', 'title' => 'Good finds. Better everyday.', 'description' => 'Marketly brings useful technology, everyday essentials, and unexpected favorites together in one simple place.', 'detail_title' => 'Shopping should feel easy.', 'detail' => 'We make it easier to find products worth bringing into your life. Our marketplace is designed around clear choices, thoughtful discovery, and a smooth experience from first search to delivery.'],
            'services' => ['eyebrow' => 'Here for the whole journey', 'title' => 'A little more care at every step.', 'description' => 'From finding the right product to getting it to your door, we want every part of shopping to feel straightforward.', 'detail_title' => 'Made around you.', 'detail' => 'Browse curated categories, search across the collection, and get help when you need it. We are building a marketplace where great products are simple to discover and simple to buy.'],
            'contact' => ['eyebrow' => 'We are happy to help', 'title' => 'Let’s talk.', 'description' => 'Need a hand with a product or your order? Reach out and our customer care team will help point you in the right direction.', 'detail_title' => 'Customer care', 'detail' => 'For product, delivery, or order questions, contact the team managing your store and include your order reference when available. Customer care hours are Monday to Friday, 9:00 am to 5:00 pm Sri Lanka time.'],
        ][$page];

        return view('pages.marketplace-info', ['categories' => $categories, 'page' => $page, 'content' => $content, 'cartCount' => $this->cartCount()]);
    }

    private function categoryTree(): array
    {
        $all = DB::table('category')->where('is_active', true)->orderBy('name')->get();
        $children = $all->whereNotNull('parent_id')->groupBy('parent_id');
        $roots = $all->whereNull('parent_id')->values();
        $roots->each(function ($root) use ($children) {
            $root->children = $children->get($root->id, collect())->values();
        });

        return [$roots, $roots, $all];
    }

    private function resolveCategory(mixed $value, $categories): ?object
    {
        if ($value === null || $value === '') return null;

        return $categories->first(fn ($category) => (string) $category->id === (string) $value || $category->slug === (string) $value);
    }

    private function productQuery(): Builder
    {
        $reviews = DB::table('review')->select('product_id')
            ->selectRaw('AVG(rating) as average_rating, COUNT(rating) as review_count')
            ->groupBy('product_id');
        $sales = DB::table('order_item')->join('product_variant', 'product_variant.id', '=', 'order_item.product_variant_id')
            ->select('product_variant.product_id')->selectRaw('SUM(order_item.quantity) as orders_count')
            ->groupBy('product_variant.product_id');

        return DB::table('product')
            ->join('category', 'category.id', '=', 'product.category_id')
            ->leftJoin('category as parent_category', 'parent_category.id', '=', 'category.parent_id')
            ->leftJoin('product_variant', function ($join) {
                $join->on('product_variant.product_id', '=', 'product.id')->where('product_variant.is_active', true);
            })
            ->leftJoinSub(
                DB::table('product_image')->select('product_id')
                    ->selectRaw('COALESCE(MIN(CASE WHEN is_primary = 1 THEN id END), MIN(id)) as image_id')->groupBy('product_id'),
                'selected_product_image', 'selected_product_image.product_id', '=', 'product.id'
            )
            ->leftJoin('product_image', 'product_image.id', '=', 'selected_product_image.image_id')
            ->leftJoinSub($reviews, 'review_stats', 'review_stats.product_id', '=', 'product.id')
            ->leftJoinSub($sales, 'sales_stats', 'sales_stats.product_id', '=', 'product.id')
            ->where('product.is_active', true)
            ->whereNull('product.deleted_at')
            ->where('category.is_active', true)
            ->select(
                'product.id', 'product.name', 'product.description', 'product.slug', 'product.created_at',
                'category.id as category_id', 'category.name as category_name', 'category.parent_id as parent_category_id', 'parent_category.name as parent_name',
                'product_variant.id as variant_id', 'product_variant.price', 'product_variant.sale_price', 'product_variant.stock_quantity',
                'product_image.image_url', 'review_stats.average_rating as rating', 'review_stats.review_count', 'sales_stats.orders_count'
            );
    }

    private function facets($definitions, ?object $selectedCategory, $categoryTree): array
    {
        if (! $selectedCategory) return [];

        $categoryIds = $selectedCategory->parent_id === null
            ? $categoryTree->firstWhere('id', $selectedCategory->id)->children->pluck('id')
            : collect([$selectedCategory->id]);
        if ($categoryIds->isEmpty()) return [];

        $productIds = DB::table('product')->whereIn('category_id', $categoryIds)
            ->where('is_active', true)->whereNull('deleted_at')->pluck('id');

        $facets = [];
        foreach ($definitions as $definition) {
            if ($definition->input_type === 'range') {
                $prices = DB::table('product_variant')->whereIn('product_id', $productIds)->where('is_active', true)
                    ->selectRaw('MIN(COALESCE(sale_price, price)) as minimum, MAX(COALESCE(sale_price, price)) as maximum')->first();
                $facets[] = (object) [...(array) $definition, 'values' => [], 'minimum' => (float) ($prices?->minimum ?? 0), 'maximum' => (float) ($prices?->maximum ?? 0)];
                continue;
            }

            if ($definition->input_type === 'rating') {
                $ratings = DB::table('review')->whereIn('product_id', $productIds)->whereNotNull('rating')->distinct()->orderBy('rating')->pluck('rating');
                $facets[] = (object) [...(array) $definition, 'values' => $ratings->map(fn ($rating) => (object) ['value' => (string) $rating, 'count' => DB::table('review')->whereIn('product_id', $productIds)->where('rating', '>=', $rating)->distinct('product_id')->count('product_id')])->all()];
                continue;
            }

            if ($definition->input_type === 'availability') {
                $stockCount = DB::table('product_variant')->whereIn('product_id', $productIds)->where('is_active', true)->where('stock_quantity', '>', 0)->distinct('product_id')->count('product_id');
                $facets[] = (object) [...(array) $definition, 'values' => [(object) ['value' => 'In stock', 'count' => $stockCount]]];
                continue;
            }

            $attributeNames = $this->attributeNames($definition->name);
            if ($definition->input_type === 'shipping') $attributeNames = ['shipping', 'ships from', 'delivery time'];
            if ($definition->input_type === 'promotion') $attributeNames = ['promotion', 'promotions', 'discount', 'deal'];
            $values = DB::table('product_attribute')->whereIn('product_id', $productIds)
                ->whereIn(DB::raw('LOWER(TRIM(name))'), $attributeNames)
                ->whereNotNull('value')->where('value', '<>', '')
                ->select('value')->selectRaw('COUNT(DISTINCT product_id) as product_count')
                ->groupBy('value')->orderBy('value')->get()
                ->map(fn ($option) => (object) ['value' => $option->value, 'count' => (int) $option->product_count])->all();
            $facets[] = (object) [...(array) $definition, 'values' => $values];
        }

        return $facets;
    }

    private function attributeNames(string $label): array
    {
        $base = mb_strtolower(trim((string) preg_replace('/\s*\([^)]*\)/u', '', $label)));
        return array_values(array_unique([$base, Str::slug($base, ' ')]));
    }

    private function categoryCounts($products, $categoryTree): array
    {
        $counts = [];
        foreach ($products as $categoryId => $items) $counts[$categoryId] = $items->unique('id')->count();
        foreach ($categoryTree as $root) $counts[$root->id] = $root->children->sum(fn ($child) => $counts[$child->id] ?? 0);

        return $counts;
    }

    private function cartCount(): int
    {
        if (! Auth::check()) return 0;

        return (int) DB::table('cart_item')->join('cart', 'cart.id', '=', 'cart_item.cart_id')
            ->where('cart.user_id', Auth::id())->sum('cart_item.quantity');
    }

    private function wishlistIds(): array
    {
        if (! Auth::check()) return [];

        return DB::table('wishlist_item')->where('user_id', Auth::id())->pluck('product_id')->map(fn ($id) => (int) $id)->all();
    }
}
