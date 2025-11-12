@extends('merchant.template.empty')

@section('content')

<div class="register-box">
    <merchant-register-form :accounttype="{{ $accountType }}"></merchant-register-form>
</div>
<!-- /.register-box -->

@endsection

