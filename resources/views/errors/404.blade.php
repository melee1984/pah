@extends('app')

@section('title', 'Page Not Found')

@section('content')
    <div class="text-center">
        <h1 class="text-4xl font-bold text-red-500">404</h1>
        <p class="mt-4 text-lg">Sorry, the page you are looking for could not be found.</p>
        <a href="{{ url('/') }}" class="text-blue-600 underline">Go to homepage</a>
    </div>
@endsection
