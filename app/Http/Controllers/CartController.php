<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = $this->currentCart($request->user()->id);
        $items = $cart ? $this->items($cart->id) : collect();

        return view('pages.cart', [
            'categories' => $this->categories(),
            'cartItems' => $items,
            'subtotal' => $items->sum(fn ($item) => $item->unit_price * $item->quantity),
            'cartCount' => $items->sum('quantity'),
        ]);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variant,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);
        $quantity = $data['quantity'] ?? 1;

        DB::transaction(function () use ($request, $data, $quantity) {
            $variant = DB::table('product_variant')
                ->join('product', 'product.id', '=', 'product_variant.product_id')
                ->where('product_variant.id', $data['variant_id'])
                ->where('product_variant.is_active', true)
                ->where('product.is_active', true)
                ->whereNull('product.deleted_at')
                ->lockForUpdate()
                ->select('product_variant.*', 'product.name as product_name')
                ->first();

            if (! $variant) {
                throw ValidationException::withMessages(['product' => 'This product is no longer available.']);
            }

            $cart = $this->currentCart($request->user()->id, true);
            $cartItem = DB::table('cart_item')->where('cart_id', $cart->id)
                ->where('product_variant_id', $variant->id)->lockForUpdate()->first();
            $newQuantity = $quantity + ($cartItem->quantity ?? 0);

            if ($variant->stock_quantity !== null && $newQuantity > $variant->stock_quantity) {
                throw ValidationException::withMessages(['quantity' => 'There is not enough stock for that quantity.']);
            }

            $price = $variant->sale_price ?: $variant->price;
            if ($cartItem) {
                DB::table('cart_item')->where('id', $cartItem->id)->update([
                    'quantity' => $newQuantity,
                    'unit_price' => $price,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('cart_item')->insert([
                    'cart_id' => $cart->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return redirect()->route('cart')->with('success', 'Added to your cart.');
    }

    public function update(Request $request, int $item)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);
        $cart = $this->currentCart($request->user()->id);
        abort_unless($cart, 404);

        $cartItem = DB::table('cart_item')->where('id', $item)->where('cart_id', $cart->id)
            ->join('product_variant', 'product_variant.id', '=', 'cart_item.product_variant_id')
            ->select('cart_item.id', 'cart_item.product_variant_id', 'product_variant.stock_quantity')
            ->first();
        abort_unless($cartItem, 404);

        if ($cartItem->stock_quantity !== null && $data['quantity'] > $cartItem->stock_quantity) {
            return back()->withErrors(['quantity' => 'There is not enough stock for that quantity.']);
        }

        DB::table('cart_item')->where('id', $cartItem->id)->update(['quantity' => $data['quantity'], 'updated_at' => now()]);

        return back()->with('success', 'Cart quantity updated.');
    }

    public function remove(Request $request, int $item)
    {
        $cart = $this->currentCart($request->user()->id);
        abort_unless($cart, 404);
        DB::table('cart_item')->where('id', $item)->where('cart_id', $cart->id)->delete();

        return back()->with('success', 'Item removed from your cart.');
    }

    public function checkout(Request $request)
    {
        $cart = $this->currentCart($request->user()->id);
        $items = $cart ? $this->items($cart->id) : collect();
        if ($items->isEmpty()) {
            return redirect()->route('cart')->with('error', 'Add an item to your cart before checking out.');
        }

        return view('pages.checkout', [
            'categories' => $this->categories(),
            'cartItems' => $items,
            'subtotal' => $items->sum(fn ($item) => $item->unit_price * $item->quantity),
            'cartCount' => $items->sum('quantity'),
            'user' => $request->user(),
        ]);
    }

    public function placeOrder(Request $request)
    {
        $data = $request->validate([
            'contact_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'payment_method' => ['required', Rule::in(['cash_on_delivery'])],
        ]);

        $orderId = DB::transaction(function () use ($request, $data) {
            $cart = $this->currentCart($request->user()->id, true);
            $items = $cart ? $this->items($cart->id, true) : collect();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }

            foreach ($items as $item) {
                if ($item->stock_quantity !== null && $item->quantity > $item->stock_quantity) {
                    throw ValidationException::withMessages(['cart' => $item->name.' no longer has enough stock.']);
                }
            }

            $subtotal = $items->sum(fn ($item) => $item->unit_price * $item->quantity);
            $addressId = DB::table('address')->insertGetId([
                'user_id' => $request->user()->id,
                'contact_name' => $data['contact_name'],
                'phone' => $data['phone'],
                'address_line1' => $data['address_line1'],
                'address_line2' => $data['address_line2'] ?? null,
                'city' => $data['city'],
                'postal_code' => $data['postal_code'] ?? null,
                'country' => $data['country'],
                'is_default' => false,
            ]);

            $orderId = DB::table('order')->insertGetId([
                'user_id' => $request->user()->id,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount' => 0,
                'shipping_cost' => 0,
                'tax' => 0,
                'total_amount' => $subtotal,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('shipping')->insert([
                'order_id' => $orderId,
                'address_id' => $addressId,
                'status' => 'pending',
                'created_at' => now(),
            ]);

            $paymentId = DB::table('payment')->insertGetId([
                'method' => 'cash_on_delivery',
                'provider' => 'manual',
                'status' => 'pending',
                'created_at' => now(),
            ]);
            DB::table('order_payment')->insert([
                'order_id' => $orderId,
                'payment_id' => $paymentId,
                'amount' => $subtotal,
                'status' => 'pending',
                'created_at' => now(),
            ]);

            foreach ($items as $item) {
                DB::table('order_item')->insert([
                    'order_id' => $orderId,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $item->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount' => 0,
                    'subtotal' => $item->unit_price * $item->quantity,
                ]);
                if ($item->stock_quantity !== null) {
                    DB::table('product_variant')->where('id', $item->product_variant_id)
                        ->decrement('stock_quantity', $item->quantity);
                }
            }

            DB::table('cart_item')->where('cart_id', $cart->id)->delete();

            return $orderId;
        });

        return redirect()->route('thankyou', ['order' => $orderId]);
    }

    public function thankyou(Request $request)
    {
        $order = DB::table('order')->where('id', $request->query('order'))
            ->where('user_id', $request->user()->id)->first();

        return view('pages.thankyou', [
            'categories' => $this->categories(),
            'cartCount' => $this->cartCount($request->user()->id),
            'order' => $order,
        ]);
    }

    private function currentCart(int $userId, bool $lock = false): ?object
    {
        $query = DB::table('cart')->where('user_id', $userId)->orderByDesc('id');
        if ($lock) $query->lockForUpdate();
        $cart = $query->first();
        if ($cart) return $cart;

        $id = DB::table('cart')->insertGetId(['user_id' => $userId, 'created_at' => now(), 'updated_at' => now()]);
        return DB::table('cart')->where('id', $id)->first();
    }

    private function items(int $cartId, bool $lock = false)
    {
        $query = DB::table('cart_item')
            ->join('product_variant', 'product_variant.id', '=', 'cart_item.product_variant_id')
            ->join('product', 'product.id', '=', 'product_variant.product_id')
            ->leftJoin('product_image', function ($join) {
                $join->on('product_image.product_id', '=', 'product.id')->where('product_image.is_primary', true);
            })
            ->where('cart_item.cart_id', $cartId)
            ->select(
                'cart_item.id as cart_item_id', 'cart_item.quantity', 'cart_item.unit_price',
                'product_variant.id as product_variant_id', 'product_variant.stock_quantity',
                'product.name', 'product.slug', 'product_image.image_url'
            );
        if ($lock) $query->lockForUpdate();

        return $query->get();
    }

    private function categories()
    {
        return DB::table('category')->whereNull('parent_id')->where('is_active', true)->orderBy('name')->get();
    }

    private function cartCount(int $userId): int
    {
        return (int) DB::table('cart_item')->join('cart', 'cart.id', '=', 'cart_item.cart_id')
            ->where('cart.user_id', $userId)->sum('cart_item.quantity');
    }
}
