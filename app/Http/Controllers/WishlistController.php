<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $items = DB::table('wishlist_item')
            ->join('product', 'product.id', '=', 'wishlist_item.product_id')
            ->leftJoin('category', 'category.id', '=', 'product.category_id')
            ->leftJoin('product_image', function ($join) {
                $join->on('product_image.product_id', '=', 'product.id')->where('product_image.is_primary', true);
            })
            ->leftJoinSub(
                DB::table('product_variant')->select('product_id')
                    ->selectRaw('MIN(id) as variant_id')
                    ->selectRaw('MIN(COALESCE(sale_price, price)) as price')
                    ->where('is_active', true)->groupBy('product_id'),
                'wishlist_prices',
                'wishlist_prices.product_id', '=', 'product.id'
            )
            ->where('wishlist_item.user_id', $request->user()->id)
            ->where('product.is_active', true)
            ->whereNull('product.deleted_at')
            ->select('product.id', 'product.name', 'product.description', 'category.name as category_name', 'product_image.image_url', 'wishlist_prices.price', 'wishlist_prices.variant_id')
            ->orderByDesc('wishlist_item.created_at')
            ->get();

        return view('pages.wishlist', [
            'categories' => DB::table('category')->where('is_active', true)->orderBy('name')->get(),
            'items' => $items,
            'wishlistIds' => $items->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'cartCount' => (int) DB::table('cart_item')->join('cart', 'cart.id', '=', 'cart_item.cart_id')
                ->where('cart.user_id', $request->user()->id)->sum('cart_item.quantity'),
        ]);
    }

    public function toggle(Request $request, int $product)
    {
        abort_unless(DB::table('product')->where('id', $product)->where('is_active', true)->whereNull('deleted_at')->exists(), 404);

        $saved = DB::table('wishlist_item')->where('user_id', $request->user()->id)->where('product_id', $product)->first();
        if ($saved) {
            DB::table('wishlist_item')->where('id', $saved->id)->delete();
            $message = 'Removed from your wishlist.';
        } else {
            DB::table('wishlist_item')->insert([
                'user_id' => $request->user()->id,
                'product_id' => $product,
                'created_at' => now(),
            ]);
            $message = 'Saved to your wishlist.';
        }

        return back()->with('success', $message);
    }
}
