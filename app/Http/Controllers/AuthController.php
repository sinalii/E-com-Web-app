<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $credentials = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string'],
        ]);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Those details did not match an account.']);
        }

        if (! $request->user()->isAdmin() && (! $request->user()->is_active || $request->user()->is_blocked)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'This account is inactive or blocked.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route($request->user()->isAdmin() ? 'admin.dashboard' : 'account')
            ->with('success', 'You are signed in.');
    }

    public function registerForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:user,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = User::create($data);
        $user->forceFill(['role' => User::ROLE_CUSTOMER, 'is_admin' => false])->save();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account')->with('success', 'Your account is ready.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('index')->with('success', 'You have signed out.')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private');
    }
}
