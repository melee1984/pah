@extends('templates.customer-auth')
@section('title', 'Sign in')
@section('content')
<section class="customer-auth-section">
    <div class="container">
        <nav class="public-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <i class="icofont-rounded-right" aria-hidden="true"></i>
            <span aria-current="page">Sign in</span>
        </nav>
        <div class="customer-auth-grid">
            <div class="customer-auth-copy">
                <span class="public-page-kicker">Your PahatudFood account</span>
                <h1>A little help.<br>A lot of care.</h1>
                <p>Your local favorites and a team ready to help. Sign in to manage your orders and keep your support conversations in one place.</p>
                <ul class="customer-auth-benefits">
                    <li><i class="icofont-fast-delivery" aria-hidden="true"></i><span><strong>Help with your order</strong>Tell us about a delivery, payment, or account concern.</span></li>
                    <li><i class="icofont-ui-message" aria-hidden="true"></i><span><strong>Every reply, together</strong>Track your requests and continue the conversation.</span></li>
                    <li><i class="icofont-lock" aria-hidden="true"></i><span><strong>Just for you</strong>Your support requests stay private to you and our team.</span></li>
                </ul>
            </div>
            <div class="customer-auth-card">
                <h2>Welcome back</h2>
                <p>Sign in with your PahatudFood account.</p>
                @if (session('message'))
                    <p class="customer-auth-error" role="alert">{{ session('message') }}</p>
                @endif
                @if (session('status'))
                    <p role="status">{{ session('status') }}</p>
                @endif
                <form method="POST" action="{{ route('login.submit') }}">
                    @csrf
                    <label for="email">Email address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="you@example.com" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email')<p id="email-error" class="customer-auth-error" role="alert">{{ $message }}</p>@enderror
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                    @error('password')<p id="password-error" class="customer-auth-error" role="alert">{{ $message }}</p>@enderror
                    <div class="customer-auth-options">
                        <label class="customer-auth-remember" for="remember"><input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>Remember me</label>
                        <a href="{{ route('password.request') }}">Forgot password?</a>
                    </div>
                    @include('components.turnstile', ['action' => 'customer_login'])
                    <button type="submit" class="customer-auth-submit">Sign in <i class="icofont-arrow-right" aria-hidden="true"></i></button>
                    <div class="customer-auth-divider"><span>or continue with</span></div>
                    <a href="/login/facebook?url=request-booking" class="customer-auth-social"><i class="icofont-facebook" aria-hidden="true"></i> Facebook</a>
                    <p class="customer-auth-register">New to PahatudFood? <a href="{{ route('register') }}">Create an account</a></p>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
