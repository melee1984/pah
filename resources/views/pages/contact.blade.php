@extends('templates.inside')
@section('content')

      <!-- Page Header Section Start Here -->
        <section class="page-header style-2">
            <div class="container">
                <div class="page-title text-center">
                    <h3>Contact Information</h3>
                    <ul class="breadcrumb">
                        <li><a href="#">Home</a></li>
                        <li>Contact</li>
                    </ul>
                </div>
            </div>
        </section>
        <!-- Page Header Section Ending Here -->


        <!-- Contact Us Section Start Here -->
        <section class="contact-information padding-tb pb-xl-0">
            <div class="container">

                @include('includes.error')

                <div class="section-wrapper">
                    <div class="row">
                        <div class="col-lg-6 col-12">
                            <h5>Contact Information</h5>
                            <div class="post-item">
                                <div class="post-thumb">
                                    <img src="images/icon/home.png" alt="contact">
                                </div>
                                <div class="post-content">
                                    <h6>Office Address</h6>
                                    <p>Deca Homes, Mintal, Block 10, Phase 9a, Lot 43</p>
                                    <p>Davao City, Philippines</p>
                                </div>
                            </div>
                            <div class="post-item">
                                <div class="post-thumb">
                                    <img src="images/icon/contact.png" alt="contact">
                                </div>
                                <div class="post-content">
                                    <h6>Contact No. : </h6>
                                    <p>+639162986547</p>
                                    <p>82 2243919</p>
                                </div>
                            </div>
                            <div class="post-item">
                                <div class="post-thumb">
                                    <img src="images/icon/email.png" alt="contact">
                                </div>
                                <div class="post-content">
                                    <h6>email adress : </h6>
                                    <p>info@pahatud.com</p>
                                </div>
                            </div>
                            <div class="post-item">
                                <div class="post-thumb">
                                    <img src="images/icon/website.png" alt="contact">
                                </div>
                                <div class="post-content">
                                    <h6>Website Address</h6>
                                    <p>http://www.pahatud.com</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-12">
                            <h5>Send Us A Message</h5>
                            
                            <form action="{{ route('contact.submit') }}" method="post" class="d-flex flex-wrap justify-content-between">
                                @csrf
                                <input type="text" placeholder="Your Name" name="name">
                                <input type="text" placeholder="Your Email" name="email">
                                <input class="w-100" type="text" placeholder="Subject" name="subject">
                                <textarea rows="8" placeholder="Your Message" name="message"></textarea>
                                <button type="submit" class="food-btn style-2"><span>Submit Message</span></button>
                            </form>
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