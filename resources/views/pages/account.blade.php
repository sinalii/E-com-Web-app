@extends('layouts.marketplace')

@section('title', 'Your account | Marketly')

@section('content')
<div class="site-container inner-page">
    <div class="breadcrumbs"><a href="{{ route('index') }}">Home</a><span>›</span><strong>Your account</strong></div>
    <section class="account-heading"><div><span class="section-kicker">YOUR MARKETLY ACCOUNT</span><h1>Hello, {{ $user->name }}</h1><p>Manage your orders and account details.</p></div><form method="post" action="{{ route('logout') }}">@csrf<button class="button-secondary" type="submit">Sign out</button></form></section>
    <div class="account-grid account-grid-profile">
        <section class="account-card"><h2>Account details</h2><dl><dt>Name</dt><dd>{{ $user->name }}</dd><dt>Email</dt><dd>{{ $user->email }}</dd><dt>Phone</dt><dd>{{ $user->phone ?? 'Not added' }}</dd><dt>Default address</dt><dd>{{ $defaultAddress ? trim(($defaultAddress->address_line1 ?? '').', '.($defaultAddress->city ?? '').', '.($defaultAddress->country ?? ''), ' ,') : 'Not added' }}</dd></dl><a class="text-link account-wishlist-link" href="{{ route('wishlist') }}">View your wishlist <span>→</span></a></section>
        <section class="account-card">
            <h2>Edit profile and delivery details</h2>
            <form method="post" action="{{ route('account.profile.update') }}" class="admin-form account-profile-form">
                @csrf @method('PUT')
                <label>Full name<input name="name" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name"></label>
                <label>Email<input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email"></label>
                <label>Phone number<input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" required maxlength="20" autocomplete="tel"></label>
                <label>Additional phone<input type="tel" name="additional_phone" value="{{ old('additional_phone', $defaultAddress->additional_phone ?? '') }}" maxlength="20"></label>
                <label>Address line 1<input name="address_line1" value="{{ old('address_line1', $defaultAddress->address_line1 ?? '') }}" required autocomplete="address-line1"></label>
                <label>Address line 2<input name="address_line2" value="{{ old('address_line2', $defaultAddress->address_line2 ?? '') }}" autocomplete="address-line2"></label>
                <label>City<input name="city" value="{{ old('city', $defaultAddress->city ?? '') }}" required autocomplete="address-level2"></label>
                <label>State / province<input name="state" value="{{ old('state', $defaultAddress->state ?? '') }}" maxlength="100"></label>
                <label>Postal code<input name="postal_code" value="{{ old('postal_code', $defaultAddress->postal_code ?? '') }}" maxlength="20" autocomplete="postal-code"></label>
                <label>Country<input name="country" value="{{ old('country', $defaultAddress->country ?? 'Sri Lanka') }}" required maxlength="100" autocomplete="country-name"></label>
                <label class="wide-profile-field">Delivery instructions<textarea name="delivery_instructions" rows="3" maxlength="2000">{{ old('delivery_instructions', $defaultAddress->delivery_instructions ?? '') }}</textarea></label>
                <label>New password<input type="password" name="password" autocomplete="new-password"></label>
                <label>Confirm password<input type="password" name="password_confirmation" autocomplete="new-password"></label>
                @if($errors->any())<div class="form-alert">{{ $errors->first() }}</div>@endif
                <button class="button-primary" type="submit">Save profile</button>
            </form>
        </section>
        <section class="account-card"><h2>Order history</h2>
        @forelse($orders as $order)
            <div class="account-order"><div><strong>Order #{{ $order->id }}</strong><small>{{ \Illuminate\Support\Carbon::parse($order->created_at)->format('M j, Y') }}</small></div><div><span class="status-pill">{{ ucfirst($order->status ?? 'pending') }}</span><strong>Rs. {{ number_format((float) $order->total_amount, 2) }}</strong></div></div>
        @empty
            <p class="account-empty">Your orders will appear here after checkout.</p>
        @endforelse
    </section></div>
</div>
@endsection
