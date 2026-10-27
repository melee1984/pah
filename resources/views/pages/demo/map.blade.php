@extends('templates.empty')

@section('content')
    <div class="container-fluid">
        <sample-map></sample-map>
    </div>
@endsection

<style>
  gmp-map {
    height: 100%;
  }
  html, body {
    height: 100%;
    margin: 0;
    padding: 0;
  }
</style>
