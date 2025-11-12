@extends('templates.inside')

@section('content')

 <!-- Page Header Section Start Here -->
    <section class="page-header style-2">
        <div class="container">
            <div class="page-title text-center">
                <h3>Privacy Policy</h3>
                <ul class="breadcrumb">
                    <li><a href="{{ URL::to('/') }}">Home</a></li>
                    <li>Privacy Policy</li>
                </ul>
            </div>
        </div>
    </section>
    <!-- Page Header Section Ending Here -->
    
    <!-- About Section Start here -->
	<section class="about about-page padding-tb">
        <div class="container">
            
            @include('includes.error')

            <div class="row align-items-center">
                <div class="col-lg-12 col-12">
                     <div class="col-lg-12 col-12">
                        <div class="section-wrapper">
                            
                            <h6>Who we are</h6>
                            <p>Pahatud.("us", "we", or "our") operates <a href="http://www.pahatud.com">www.pahatud.com</a> (the "Site"). </p>
            
                            <p>This page informs you of our policies regarding the collection, use and disclosure of Personal Information we receive from users of the Site.</p>

                            <p>We may use, collect, process and disclose your information when you use this website (pahatud.com’) the services offered by pahatud and the third party providers (the 'Provider') through this website (the 'Services'). 'You' and 'your' when used in this Privacy Policy includes any person who accesses this Website or use the Services.</p>

                            <h6>Information collection and used</h6>

                            <p>While using pahatud, we may ask you to provide us with certain personally identifiable information that can be used to contact or identify you. Personally identifiable information may include, but is not limited to your name, mobile number, email address, birth date, and address ("Personal Information").</p>

                            <h6>Log data</h6>
                            <p>Like many site operators, we collect information that your browser sends whenever you visit pahatud.com ("Log Data").</p>

                            <p>This Log Data may include information such as your computer's Internet Protocol ("IP") address, browser type, browser version, the pages of our Site that you visit, the time and date of your visit, the time spent on those pages, your current location and other statistics.</p>

                            <h6>Usage of Information</h6>
                            <p>pahatud will not sell or rent your Personal Information to third parties. pahatud will use Personal Information and other data collected through this Website and platform to provide you with the Services, to continually improve this Website and the Services, and to contact you in relation to the Services.</p>

                            <p>From time to time, we may also make use of your Personal Information to contact you for feedback on your use of this Website and platform, to assist us in improving this Website and platform, or to offer special savings or promotions to you, where you have indicated your consent to receiving such communications. If you would prefer not to receive notices of special savings or promotions, you may simply opt-out from receiving them by replying to us through the link provided in these notices.</p>

                            <h6>Cookies</h6>
                            <p>Cookies are files with small amount of data, which may include an anonymous unique identifier. Cookies are sent to your browser from a web site and stored on your computer's hard drive.</p>

                            <p>Like many sites, we use "cookies" to collect information. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent. However, if you do not accept cookies, you may not be able to use some portions of our Website and platform.</p>

                            <h6>Data Access and correction</h6>
                            <p>You may access and correct your Personal Information through this Website and platform or make your data access or correction request by sending your request by email at support@pahatud.com . When handling a data access or correction request, we have the right to check the identity of the requester to ensure that he/she is the person entitled to make the data access or correction request.</p>

                            <h6>Security</h6>
                            <p>The security of your Personal Information is important to us but remember that no method of transmission over the Internet, or method of electronic storage, is 100% secure. While we strive to use commercially acceptable means to protect your Personal Information, we cannot guarantee its absolute security.</p>

                            <h6>Changes to this Privacy Policy</h6>
                            <p>This Privacy Policy is effective as of January 31, 2020 and will remain in effect except with respect to any changes in its provisions in the future, which will be in effect immediately after being posted on this page.</p>

                            <p>We reserve the right to update or change our Privacy Policy at any time and you should check this Privacy Policy periodically. Your continued use of the Service after we post any modifications to the Privacy Policy on this page will constitute your acknowledgment of the modifications and your consent to abide and be bound by the modified Privacy Policy.</p>

                            <p>If we make any material changes to this Privacy Policy, we will notify you either through the email address you have provided us, or by placing a prominent notice on our website.</p>

                            <h6>Contact Us</h6>
                            <p>If you have any questions about this Privacy Policy, please contact us by email at support@pahatud.com or via our website “contact us”.</p>


                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
	<!-- About Section Ending here -->
	
	@include('pages.includes.sponsor')

   	@include('pages.includes.newsletter')


@endsection