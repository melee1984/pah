@extends('agent.layouts.app')

@section('title', 'Restaurant application')

@section('content')
    <div class="agent-page-head"><div><p class="agent-eyebrow">Your network</p><h1>Restaurant application</h1><p>View or update submitted details and documents.</p></div><a href="{{ route('agent.restaurants.index') }}">Back to restaurants</a></div>
    @include('restaurant.application-details', ['agentView' => true])
@endsection
