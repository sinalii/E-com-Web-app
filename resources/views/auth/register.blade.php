@extends('layouts.marketplace')

@section('title', 'Create account | Marketly')

@section('content')
<div class="site-container inner-page"><section class="auth-card">
    <span class="section-kicker">JOIN MARKETLY</span><h1>Create your account</h1><p class="auth-intro">Create an account to save your cart and place orders.</p>
    @if($errors->any())<div class="form-alert">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('register.store') }}">@csrf
        <label for="name">Full name</label><input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required>
        <label for="email">Email address</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
        <label for="password">Password</label><input id="password" name="password" type="password" autocomplete="new-password" required>
        <label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
        <button class="button-primary" type="submit">Create account <span>→</span></button>
    </form>
    <p class="auth-switch">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
</section></div>
@endsection
