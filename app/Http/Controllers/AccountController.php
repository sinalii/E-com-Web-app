<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $orders = DB::table('order')->where('user_id', $user->id)->whereNull('deleted_at')->orderByDesc('created_at')->get();
        $cartCount = (int) DB::table('cart_item')->join('cart', 'cart.id', '=', 'cart_item.cart_id')
            ->where('cart.user_id', $user->id)->sum('cart_item.quantity');
        $defaultAddress = DB::table('address')->where('user_id', $user->id)
            ->orderByDesc('is_default')->orderByDesc('id')->first();

        return view('pages.account', [
            'user' => $user,
            'orders' => $orders,
            'defaultAddress' => $defaultAddress,
            'categories' => DB::table('category')->whereNull('parent_id')->where('is_active', true)->orderBy('name')->get(),
            'cartCount' => $cartCount,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
            'phone' => trim((string) $request->input('phone')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('user', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('user', 'phone')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', 'min:8', 'regex:/[A-Za-z]/', 'regex:/[0-9]/'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'additional_phone' => ['nullable', 'string', 'max:20'],
            'delivery_instructions' => ['nullable', 'string', 'max:2000'],
        ]);

        $updates = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address_line1'],
            'updated_at' => now(),
        ];
        if (! empty($data['password'])) $updates['password'] = Hash::make($data['password']);
        DB::table('user')->where('id', $user->id)->update($updates);

        DB::table('address')->where('user_id', $user->id)->update(['is_default' => false]);
        DB::table('address')->insert([
            'user_id' => $user->id,
            'contact_name' => $data['name'],
            'phone' => $data['phone'],
            'additional_phone' => $data['additional_phone'] ?? null,
            'address_line1' => $data['address_line1'],
            'address_line2' => $data['address_line2'] ?? null,
            'city' => $data['city'],
            'state' => $data['state'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'country' => $data['country'],
            'delivery_instructions' => $data['delivery_instructions'] ?? null,
            'is_default' => true,
        ]);

        $user->forceFill($updates);
        Auth::setUser($user);

        return redirect()->route('account')->with('success', 'Profile and delivery details updated.');
    }
}
