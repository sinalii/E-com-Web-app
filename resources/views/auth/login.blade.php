@extends('layouts.marketplace')

@section('title', 'Sign in | Marketly')

@section('content')
<div class="site-container inner-page"><section class="auth-card">
    <span class="section-kicker">WELCOME BACK</span><h1>Sign in to Marketly</h1><p class="auth-intro">Sign in to manage your cart and continue shopping.</p>
    @if($errors->any())<div class="form-alert">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('login.store') }}" autocomplete="off">@csrf
        <label for="email">Email address</label><input id="email" name="email" type="email" autocomplete="off" required>
        <label for="password">Password</label><input id="password" name="password" type="password" autocomplete="off" required>
        <button class="button-primary" type="submit">Sign in <span>→</span></button>
    </form>
    <div class="auth-switch"><span>New to Marketly?</span><a class="auth-signup-button" href="{{ route('register') }}">Create your account</a></div>
</section></div>
@endsection
