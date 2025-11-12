@extends('templates.inside')
@section('title', 'Flowers to your Love, send gifts and cakes with same day delivery via Pahatud Delivery Services Davao City')

@section('og')

    <meta property="og:image:type" content="image/jpeg" />
    <meta property="og:image:width" content="500" />
    <meta property="og:image:height" content="500" />
    <meta property="og:image:alt" content="Order Flowers and Delivered to your Door step via Pahatud Delivery Services in Davao City" />

    <meta name="keywords" content="flowers,store,online flowers,flower,affordable flowers">
    <meta property="og:title" content="Flower Store, beautiful floral arrangements, quality gifts on and same day Flower Delivery | Online Flower Shop via Pahatud Delivery Services" />

    <meta property="og:url" content="{{ route('flowerstore.show') }}" />
    <meta property="og:type" content="website" />
    <meta property="og:image" content="{{ asset('images/flowerstore-banner.png') }}" />
    <meta property="og:image:secure_url" content="{{ asset('images/flowerstore-banner.png') }}" />

    <meta property="og:description" content="Pick affordable flower store from only P350 with same day delivery in Davao City. For your Birthday, Romance, Valentines, Mothers Day and more via Pahatud Delivery Services." />

@endsection

@section('content')
    <div class="container flowerstore bottom-line">
        <h3>ORDER FLOWERS TODAY DELIVERED BY LOCAL FLORISTS IN DAVAO CITY</h3>
        <div class="row ">
            <div class="col-md-6 col-xs-12">
                <p>Our Available Flowerstore for you !!!</p>
            </div>
            <div class="col-md-6 col-xs-12 text-right">
                Same Day DELIVERY and <b>FREE DELIVERY</b>
            </div>
        </div>
    </div>
    <section class="popular-foods padding-tb style-2">
        <front-flower-list></front-flower-list>
    </section>
    <!-- Popular Food Section Ending Here -->
@endsection