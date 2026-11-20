<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $orders = DB::table('order')->where('user_id', $user->id)->whereNull('deleted_at')->orderByDesc('created_at')->get();
        $cartCount = (int) DB::table('cart_item')->join('cart', 'cart.id', '=', 'cart_item.cart_id')
            ->where('cart.user_id', $user->id)->sum('cart_item.quantity');

        return view('pages.account', [
            'user' => $user,
            'orders' => $orders,
            'categories' => DB::table('category')->where('is_active', true)->orderBy('name')->get(),
            'cartCount' => $cartCount,
        ]);
    }
}
