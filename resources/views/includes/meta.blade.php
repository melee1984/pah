@php
    $pageTitle = trim($__env->yieldContent('title'));
    $seoTitle = trim($__env->yieldContent('seo_title'));
    $seoTitle = $seoTitle !== ''
        ? $seoTitle
        : ($pageTitle !== '' ? $pageTitle.' | PahatudFood' : 'PahatudFood | Local Food Delivery in Davao City');

    $defaultDescription = 'Order food from local Davao restaurants with PahatudFood. Discover nearby favorites, order in the app, and have your meal delivered to your door.';
    $seoDescription = trim($__env->yieldContent('description')) ?: $defaultDescription;
    $canonicalUrl = trim($__env->yieldContent('canonical')) ?: url()->current();
    $customSocialImage = trim($__env->yieldContent('social_image'));
    $socialImage = $customSocialImage ?: asset('images/pahatud-social.jpg');
    $socialImageAlt = trim($__env->yieldContent('social_image_alt')) ?: 'PahatudFood local food delivery in Davao City';
    $socialImageWidth = trim($__env->yieldContent('social_image_width')) ?: ($customSocialImage === '' ? '1200' : '');
    $socialImageHeight = trim($__env->yieldContent('social_image_height')) ?: ($customSocialImage === '' ? '630' : '');
    $seoRobots = trim($__env->yieldContent('robots')) ?: ($defaultRobots ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1');

    $structuredData = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => url('/').'#organization',
                'name' => 'Pahatud',
                'alternateName' => 'PahatudFood',
                'url' => url('/'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('images/logo.jpg'),
                    'width' => 1024,
                    'height' => 1024,
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => url('/').'#website',
                'url' => url('/'),
                'name' => 'PahatudFood',
                'description' => $defaultDescription,
                'publisher' => ['@id' => url('/').'#organization'],
                'inLanguage' => 'en-PH',
            ],
            [
                '@type' => 'WebPage',
                '@id' => $canonicalUrl.'#webpage',
                'url' => $canonicalUrl,
                'name' => $seoTitle,
                'description' => $seoDescription,
                'isPartOf' => ['@id' => url('/').'#website'],
                'primaryImageOfPage' => [
                    '@type' => 'ImageObject',
                    'url' => $socialImage,
                ],
                'inLanguage' => 'en-PH',
            ],
        ],
    ];
@endphp

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">
<meta name="robots" content="{{ $seoRobots }}">
<link rel="canonical" href="{{ $canonicalUrl }}">

<meta property="og:locale" content="en_PH">
<meta property="og:type" content="website">
<meta property="og:site_name" content="PahatudFood">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:image" content="{{ $socialImage }}">
<meta property="og:image:secure_url" content="{{ $socialImage }}">
<meta property="og:image:alt" content="{{ $socialImageAlt }}">
@if ($socialImageWidth !== '' && $socialImageHeight !== '')
<meta property="og:image:width" content="{{ $socialImageWidth }}">
<meta property="og:image:height" content="{{ $socialImageHeight }}">
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $socialImage }}">
<meta name="twitter:image:alt" content="{{ $socialImageAlt }}">

@unless(str_contains($seoRobots, 'noindex'))
<script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endunless

<meta property="fb:app_id" content="591633281717714">
<meta name="csrf-token" content="{{ csrf_token() }}">
@if (config('services.turnstile.enabled'))
<meta name="turnstile-site-key" content="{{ config('services.turnstile.site_key') }}">
@endif

<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
<link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
<meta name="theme-color" content="#ff0000">

<link rel="stylesheet" href="{{ asset('assets/css/animate.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/icofont.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/lightcase.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/swiper.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
