@extends('layouts.marketplace')

@section('title', ucfirst($page) . ' | Marketly')

@section('content')
<div class="site-container inner-page">
    <div class="breadcrumbs"><a href="{{ route('index') }}">Home</a><span>›</span><strong>{{ ucfirst($page) }}</strong></div>
    <section class="inner-hero">
        <span class="section-kicker">{{ $content['eyebrow'] }}</span>
        <h1>{{ $content['title'] }}</h1>
        <p>{{ $content['description'] }}</p>
    </section>
    <section class="info-panel"><div class="info-icon">✦</div><div><span class="section-kicker">MARKETLY MARKETPLACE</span><h2>{{ $content['detail_title'] }}</h2><p>{{ $content['detail'] }}</p><a class="text-link" href="{{ route('shop') }}">Explore the marketplace <span>→</span></a></div></section>
</div>
@endsection
