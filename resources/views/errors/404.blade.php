@extends('errors.layout')

@section('title', 'Page Not Found')
@section('brand', 'PahatudFood')

@section('body-class', 'not-found')

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/icofont.min.css') }}">
    <style>
        body.not-found {
            display: block;
            background: #f7f7f5;
            color: #181818;
            font-family: ui-monospace, 'SFMono-Regular', Menlo, Consolas, monospace;
        }

        .not-found *, .not-found *::before, .not-found *::after { box-sizing: border-box; }
        .not-found main { max-width: 1440px; margin: 0 auto; padding: 48px 64px 64px; text-align: left; }
        .not-found a { color: inherit; text-decoration: none; }
        .not-found a:focus-visible { outline: 3px solid #cf302b; outline-offset: 5px; }
        .error-header { display: flex; justify-content: center; }
        .error-brand { display: inline-flex; align-items: center; gap: 12px; font: 600 23px/1.2 Arial, sans-serif; }
        .error-brand img { width: 40px; height: 40px; border-radius: 8px; }
        .error-intro { display: grid; grid-template-columns: 200px minmax(0, 1fr); gap: 32px; padding: 86px 0 64px; }
        .error-code { color: #b8b8b5; font-size: 112px; line-height: 1; letter-spacing: -10px; padding-top: 8px; }
        .error-kicker { margin: 0 0 22px; color: #666662; font-size: 14px; letter-spacing: 3px; text-transform: uppercase; }
        .not-found h1 { margin: 0; color: #181818; font-size: clamp(32px, 3.5vw, 52px); line-height: 1.18; letter-spacing: -2px; }
        .error-description { max-width: 940px; margin: 28px 0 0; color: #62625f; font-size: 19px; line-height: 1.9; }
        .error-directory { border-top: 1px solid #dededb; padding-top: 40px; }
        .error-directory-heading { display: flex; justify-content: space-between; align-items: baseline; gap: 16px; margin-bottom: 22px; }
        .error-directory h2 { margin: 0; font-size: 18px; line-height: 1.5; }
        .error-directory-heading p { margin: 0; color: #70706b; font-size: 14px; }
        .error-links { margin: 0; padding: 0; list-style: none; overflow: hidden; border: 1px solid #dededb; border-radius: 22px; background: #fff; }
        .error-links li + li { border-top: 1px solid #e4e4e1; }
        .error-link { display: grid; grid-template-columns: 225px minmax(0, 1fr) 175px; align-items: center; gap: 24px; padding: 27px 30px; transition: background-color .15s; }
        .error-link:hover { background: #fff5f3; }
        .error-link:focus-visible { outline-offset: -4px; }
        .error-link-title { font-size: 20px; font-weight: 700; line-height: 1.4; }
        .error-link-description { color: #62625f; font-size: 16px; line-height: 1.65; }
        .error-link-path { display: flex; justify-content: flex-end; align-items: center; gap: 10px; color: #70706b; font-size: 14px; white-space: nowrap; }
        .error-link:hover .error-link-title, .error-link:hover i { color: #bd2925; }

        @media (max-width: 1000px) {
            .not-found main { padding: 36px 32px 48px; }
            .error-intro { grid-template-columns: 140px minmax(0, 1fr); gap: 24px; padding: 64px 0 48px; }
            .error-code { font-size: 84px; letter-spacing: -8px; }
            .error-link { grid-template-columns: 180px minmax(0, 1fr); gap: 8px 24px; padding: 24px; }
            .error-link-path { grid-column: 2; justify-content: flex-start; }
        }

        @media (max-width: 600px) {
            .not-found main { padding: 28px 20px 32px; }
            .error-brand { font-size: 21px; }
            .error-intro { grid-template-columns: 1fr; gap: 24px; padding: 44px 0 36px; }
            .error-code { font-size: 80px; padding: 0; }
            .error-kicker { margin-bottom: 16px; font-size: 12px; }
            .not-found h1 { font-size: 34px; letter-spacing: -1.5px; }
            .error-description { margin-top: 20px; font-size: 16px; line-height: 1.8; }
            .error-directory { padding-top: 28px; }
            .error-directory-heading { display: block; }
            .error-directory-heading p { margin-top: 8px; font-size: 13px; }
            .error-directory h2 { font-size: 17px; }
            .error-links { border-radius: 16px; }
            .error-link { grid-template-columns: 1fr; gap: 10px; padding: 22px; }
            .error-link-title { font-size: 19px; }
            .error-link-description { font-size: 15px; }
            .error-link-path { grid-column: auto; font-size: 13px; }
        }
    </style>
@endsection

@section('content')
    <header class="error-header">
        <a class="error-brand" href="{{ route('home') }}" aria-label="PahatudFood homepage">
            <img src="{{ asset('images/logo.jpg') }}" alt="" width="40" height="40">
            <span>PahatudFood</span>
        </a>
    </header>

    <section class="error-intro" aria-labelledby="error-title">
        <div class="error-code" aria-hidden="true">404</div>
        <div>
            <p class="error-kicker">404 / Page not found</p>
            <h1 id="error-title">This page doesn't exist.</h1>
            <p class="error-description">PahatudFood connects you with local restaurants, shops, and delivery services. Discover neighborhood favorites, order food, or request a delivery while supporting the businesses in your community.</p>
        </div>
    </section>

    <nav class="error-directory" aria-labelledby="directory-title">
        <div class="error-directory-heading">
            <h2 id="directory-title">Choose where to go next</h2>
            <p>Discover PahatudFood</p>
        </div>
        <ul class="error-links">
            @foreach ([
                ['aboutus', 'About PahatudFood', 'Get to know our story and how we connect local communities.', '/about-us'],
                ['restaurant.show', 'Restaurants', 'Explore local restaurants and find your next favorite meal.', '/restaurants'],
                ['bepartner', 'Become a partner', 'Learn how your business can grow with PahatudFood.', '/be-partner'],
                ['contactus', 'Contact us', 'Get in touch with our team for questions and support.', '/contact-us'],
                ['home', 'Homepage', 'Start here to explore food, shops, and local delivery services.', '/'],
            ] as [$route, $label, $description, $path])
                <li>
                    <a class="error-link" href="{{ route($route) }}">
                        <span class="error-link-title">{{ $label }}</span>
                        <span class="error-link-description">{{ $description }}</span>
                        <span class="error-link-path" aria-hidden="true">{{ $path }} <i class="icofont-arrow-up" style="transform: rotate(45deg)"></i></span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endsection
