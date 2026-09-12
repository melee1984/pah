@extends('templates.inside')

@section('title', 'About us')

@section('content')
    @include('pages.partials.public-page-header', ['title' => 'About Pahatud', 'kicker' => 'Local delivery, meaningful connections', 'description' => 'We connect customers with neighborhood restaurants and merchants while creating opportunities for local partners.'])

    <section class="public-about-page">
        <div class="container">
            @include('includes.error')
            <div class="public-about-intro">
                <div class="public-about-visual">
                    <div class="public-about-logo"><img src="{{ asset('images/logo-big.png') }}" alt="Pahatud"></div>
                    <span>Davao-born delivery service</span>
                </div>
                <div class="public-about-copy">
                    <span class="public-section-kicker">Our story</span>
                    <h2>Ready, set, delivered.</h2>
                    <p><strong>Pahatud</strong> means “to request to deliver.” It reflects the simple idea behind our service: make reliable local delivery easier and more accessible.</p>
                    <p>We bring customers, restaurants, entrepreneurs, and online sellers together through one convenient platform—helping local businesses reach more people while customers enjoy the products they love.</p>
                    <a class="public-primary-link" href="{{ route('home') }}#restaurants">Discover local restaurants <i class="icofont-arrow-right"></i></a>
                </div>
            </div>
            <div class="public-value-grid">
                <article><i class="icofont-location-pin"></i><h3>Rooted locally</h3><p>Built around the needs of Davao customers, restaurants, merchants, and riders.</p></article>
                <article><i class="icofont-handshake-deal"></i><h3>Growing together</h3><p>Creating practical opportunities for local partners to reach and serve more customers.</p></article>
                <article><i class="icofont-fast-delivery"></i><h3>Delivery with purpose</h3><p>Making every request clear, convenient, and handled with care from order to arrival.</p></article>
            </div>
        </div>
    </section>
@endsection
