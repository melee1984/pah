@extends('templates.inside')
@section('title', 'Contact us')
@section('content')

        @include('pages.partials.public-page-header', ['title' => 'Contact us', 'kicker' => 'We are here to help', 'description' => 'Questions about an order, partnership, or account? Send us a message and our local team will assist you.'])


        <!-- Contact Us Section Start Here -->
        <section class="contact-information public-contact-page">
            <div class="container">

                @include('includes.error')

                <div class="section-wrapper">
                    <div class="row">
                        <div class="col-lg-6 col-12">
                            <span class="public-section-kicker">Contact information</span>
                            <h2>Talk to the Pahatud team</h2>
                            <div class="post-item">
                                <div class="post-thumb">
                                    <img src="images/icon/home.png" alt="contact">
                                </div>
                                <div class="post-content">
                                    <h6>Office address</h6>
                                    <p>Deca Homes, Mintal, Block 10, Phase 9a, Lot 43</p>
                                    <p>Davao City, Philippines</p>
                                </div>
                            </div>
                            <div class="post-item">
                                <div class="post-thumb">
                                    <img src="images/icon/contact.png" alt="contact">
                                </div>
                                <div class="post-content">
                                    <h6>Phone</h6>
                                    <p><a href="tel:+639162986547">+63 916 298 6547</a></p>
                                    <p><a href="tel:+63822243919">(082) 224 3919</a></p>
                                </div>
                            </div>
                            <div class="post-item">
                                <div class="post-thumb">
                                    <img src="images/icon/email.png" alt="contact">
                                </div>
                                <div class="post-content">
                                    <h6>Email address</h6>
                                    <p><a href="mailto:info@pahatud.com">info@pahatud.com</a></p>
                                </div>
                            </div>
                            <div class="post-item">
                                <div class="post-thumb">
                                    <img src="images/icon/website.png" alt="contact">
                                </div>
                                <div class="post-content">
                                    <h6>Website</h6>
                                    <p><a href="{{ route('home') }}">www.pahatud.com</a></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-12">
                            <div class="public-contact-form">
                            <span class="public-section-kicker">Send a message</span>
                            <h2>How can we help?</h2>
                            <p>Include any useful order or account details so we can assist you faster.</p>
                            
                            <form action="{{ route('contact.submit') }}" method="post" class="d-flex flex-wrap justify-content-between">
                                @csrf
                                <label><span>Your name</span><input type="text" placeholder="Juan Dela Cruz" name="name" value="{{ old('name') }}" required></label>
                                <label><span>Email address</span><input type="email" placeholder="juan@example.com" name="email" value="{{ old('email') }}" required></label>
                                <label class="w-100"><span>Subject</span><input class="w-100" type="text" placeholder="How can we help?" name="subject" value="{{ old('subject') }}" required></label>
                                <label class="w-100"><span>Message</span><textarea rows="6" placeholder="Tell us what you need help with..." name="message" required>{{ old('message') }}</textarea></label>
                                <button type="submit" class="food-btn style-2"><span>Submit Message</span></button>
                            </form></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- Contact Us Section Ending Here -->


        <!-- G-Map Section Start Here -->
        <div class="gmaps-section">
            <div class="map-area">
                <iframe src="https://maps.google.com/maps?q=mintal, tacunan&t=&z=17&ie=UTF8&iwloc=&output=embed" style="border:0" allowfullscreen></iframe>
                
            </div>
        </div>
        <!-- G-Map Section Ending Here -->       

     @include('pages.includes.newsletter')


@endsection
