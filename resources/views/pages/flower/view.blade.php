@extends('templates.inside')
@section('title', 'Flower Delivery Near You')
@section('description', 'Order affordable flowers, gifts, and cakes from local florists with convenient same-day delivery through Pahatud.')
@section('social_image', asset('images/flowerstore-banner.png'))
@section('social_image_alt', 'Flowers, gifts, and cakes available for delivery through Pahatud')
@section('social_image_width', '1200')
@section('social_image_height', '700')

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
