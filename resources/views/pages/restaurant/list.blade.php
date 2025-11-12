@extends('templates.inside')
@section('title', "Restaurants")

@section('content')
    <!-- Page Header Section Start Here -->
   
    <section class="page-header style-2">
        <div class="container">
            <div class="page-title text-center">
                <h3>All Restaurants</h3>
                <ul class="breadcrumb">
                    <li><a href="{{ URL::to('/') }}">Home</a></li>
                    <li>Restaurants</li>
                </ul>
            </div>
        </div>
    </section>
    
    <section class="popular-foods padding-tb style-2">
        <div id="restaurantView">
            <front-restaurant-list></front-restaurant-list>
        </div>
    </section>
    <!-- Popular Food Section Ending Here -->
@endsection