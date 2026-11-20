<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShopController extends Controller
{
    public function home()
    {
        [$categories, $products] = $this->catalog();

        return view('pages.index', [
            'categories' => $categories,
            'products' => $products,
            'featured' => $products->flatten()->take(8),
            'cartCount' => $this->cartCount(),
            'wishlistIds' => $this->wishlistIds(),
        ]);
    }

    public function index(Request $request)
    {
        [$categories, $products] = $this->catalog();

        if ($request->filled('category')) {
            $categoryId = (string) $request->query('category');
            $categories = $categories->filter(fn ($category) => (string) $category->id === $categoryId)->values();
        }

        if ($request->filled('q')) {
            $term = mb_strtolower($request->query('q'));
            $products = $products->map(fn ($items) => $items->filter(fn ($product) =>
                str_contains(mb_strtolower($product->name ?? ''), $term)
                || str_contains(mb_strtolower($product->description ?? ''), $term)
            )->values());
            $categories = $categories->filter(fn ($category) => $products->get($category->id, collect())->isNotEmpty())->values();
        }

        return view('pages.shop', [
            'categories' => $categories,
            'products' => $products,
            'searchTerm' => $request->query('q'),
            'cartCount' => $this->cartCount(),
            'wishlistIds' => $this->wishlistIds(),
        ]);
    }

    public function page(string $page)
    {
        [$categories] = $this->catalog();

        $content = [
            'about' => [
                'eyebrow' => 'A marketplace made for discovery',
                'title' => 'Good finds. Better everyday.',
                'description' => 'Marketly brings useful technology, everyday essentials, and unexpected favorites together in one simple place.',
                'detail_title' => 'Shopping should feel easy.',
                'detail' => 'We make it easier to find products worth bringing into your life. Our marketplace is designed around clear choices, thoughtful discovery, and a smooth experience from first search to delivery.',
            ],
            'services' => [
                'eyebrow' => 'Here for the whole journey',
                'title' => 'A little more care at every step.',
                'description' => 'From finding the right product to getting it to your door, we want every part of shopping to feel straightforward.',
                'detail_title' => 'Made around you.',
                'detail' => 'Browse curated categories, search across the collection, and get help when you need it. We are building a marketplace where great products are simple to discover and simple to buy.',
            ],
            'contact' => [
                'eyebrow' => 'We are happy to help',
                'title' => 'Let’s talk.',
                'description' => 'Need a hand with a product or your order? Reach out and our customer care team will help point you in the right direction.',
                'detail_title' => 'Customer care',
                'detail' => 'For product, delivery, or order questions, contact the team managing your store and include your order reference when available. Customer care hours are Monday to Friday, 9:00 am to 5:00 pm Sri Lanka time.',
            ],
        ][$page];

        return view('pages.marketplace-info', [
            'categories' => $categories,
            'page' => $page,
            'content' => $content,
            'cartCount' => $this->cartCount(),
        ]);
    }

    private function catalog(): array
    {
        $categories = DB::table('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = DB::table('product')
            ->leftJoin('category', 'category.id', '=', 'product.category_id')
            ->leftJoin('product_variant', function ($join) {
                $join->on('product_variant.product_id', '=', 'product.id')
                    ->where('product_variant.is_active', true);
            })
            ->leftJoin('product_image', function ($join) {
                $join->on('product_image.product_id', '=', 'product.id')
                    ->where('product_image.is_primary', true);
            })
            ->where('product.is_active', true)
            ->whereNull('product.deleted_at')
            ->select(
                'product.id', 'product.name', 'product.description', 'product.slug',
                'category.id as category_id', 'category.name as category_name',
                'product_variant.id as variant_id', 'product_variant.price', 'product_variant.sale_price', 'product_variant.stock_quantity',
                'product_image.image_url'
            )
            ->orderBy('product.name')
            ->get()
            ->groupBy('category_id');

        if ($categories->isEmpty() || $products->isEmpty()) {
            $catalog = [
                ['id' => 'smart-home', 'name' => 'Smart Home', 'image' => 'photo-1558002038-1055907df827', 'items' => [
                    ['Smart Home Essentials Bundle', 'Smart lighting and sound for a more connected home.', 18990, 'photo-1558002038-1055907df827', 'Top pick', 4.8, 326],
                    ['Mini Wi-Fi Smart Speaker', 'Room-filling sound with voice control.', 8490, 'photo-1543512214-318c7553f230', 'Free shipping', 4.7, 214],
                    ['Smart LED Light Kit', 'Set the mood with millions of colors.', 4290, 'photo-1507473885765-e6ed057f782c', null, 4.6, 98],
                ]],
                ['id' => 'audio', 'name' => 'Audio', 'image' => 'photo-1505740420928-5e560c06d30e', 'items' => [
                    ['JBL Wireless Headphones', 'Immersive sound with all-day comfort.', 12990, 'photo-1505740420928-5e560c06d30e', 'Best seller', 4.9, 842],
                    ['Studio Wireless Earbuds', 'Compact, clear sound wherever you go.', 6990, 'photo-1606220945770-b5b6c2c55bf1', 'Limited deal', 4.7, 405],
                    ['Portable Bluetooth Speaker', 'Big sound, ready for the outdoors.', 9990, 'photo-1608043152269-423dbba4e7e1', null, 4.6, 176],
                ]],
                ['id' => 'computing', 'name' => 'Computing', 'image' => 'photo-1517336714731-489689fd1ca8', 'items' => [
                    ['Apple Slim Laptop', 'Lightweight performance for work and play.', 189900, 'photo-1517336714731-489689fd1ca8', 'New arrival', 4.8, 291],
                    ['Ultra-wide Monitor', 'A vivid, expansive workspace.', 79900, 'photo-1527443224154-c4a3942d3acf', null, 4.7, 133],
                    ['Mechanical Keyboard', 'A satisfying upgrade for your desk.', 15900, 'photo-1587829741301-dc798b83add3', null, 4.6, 87],
                ]],
                ['id' => 'wearables', 'name' => 'Wearables', 'image' => 'photo-1523275335684-37898b6baf30', 'items' => [
                    ['Apple Smartwatch Series 9', 'Stay connected and keep your day on track.', 89900, 'photo-1523275335684-37898b6baf30', 'Popular', 4.8, 512],
                    ['Fitness Watch Pro', 'Training insights and a bright AMOLED display.', 24900, 'photo-1575311373937-040b8e1fd5b6', 'Free shipping', 4.6, 208],
                    ['Everyday Smart Band', 'Simple wellness tracking, all day long.', 11900, 'photo-1508685096489-7aacd43bd3b1', null, 4.5, 76],
                ]],
            ];

            $categories = collect($catalog)->map(fn ($category) => (object) [
                'id' => $category['id'],
                'name' => $category['name'],
                'image' => $category['image'],
            ]);

            $products = collect($catalog)->mapWithKeys(function ($category) {
                $items = collect($category['items'])->map(fn ($item) => (object) [
                    'name' => $item[0],
                    'description' => $item[1],
                    'price' => $item[2],
                    'sale_price' => null,
                    'image_url' => 'https://images.unsplash.com/' . $item[3] . '?auto=format&fit=crop&w=800&q=85',
                    'badge' => $item[4],
                    'rating' => $item[5],
                    'review_count' => $item[6],
                ]);

                return [$category['id'] => $items];
            });
        } else {
            $categories = $categories->map(function ($category) {
                $category->image = null;
                return $category;
            });
            $products = $products->map(fn ($items) => $items->map(function ($product) {
                $product->badge = null;
                $product->rating = 4.8;
                $product->review_count = 0;
                return $product;
            }));
        }

        return [$categories, $products];
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
