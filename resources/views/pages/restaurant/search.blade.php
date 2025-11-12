@extends('templates.inside')
@section('title', "Restaurants")

@section('content')
<!-- Page Header Section Start Here -->
 <section class="about about-page padding-tb">
    <div class="container" id="historybookings">
        <h2>{{ __('Search Results') }}</h2>
        <form class="form-inline" action="{{ route('search') }}" method="post">
             @csrf()
          <div class="row">
            <div class="col">
              <input type="text" name="s" placeholder="Enter your food name" class="form-control">
            </div>

             <div class="col">
                <button type="submit" class="btn btn-sm btn-pahatud btn-block" >Search</button>
            </div>
          </div>
        </form>
        <hr>
     
          @if (count($restaurants)>0)
              <front-search-list :restaurants="{{$restaurants}}"></front-search-list>
          @else 
              {{ __('No record found') }}
          @endif
        
    </div>
</section>
@endsection