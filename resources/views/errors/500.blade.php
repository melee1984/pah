@extends('app')

@section('title', 'Server Error')

@section('content')
  <div class="text-center">
    <h1 class="text-5xl text-red-600 font-bold">500</h1>
    <p class="text-lg mt-4">Whoops! Something went wrong on our servers.</p>
    @isset($error)
      <p class="text-sm mt-2 text-gray-500">{{ $error }}</p>
    @endisset
    <a href="{{ url('/') }}" class="text-blue-500 underline mt-6 inline-block">Try again</a>
  </div>
@endsection
