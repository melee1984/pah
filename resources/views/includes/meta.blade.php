
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
@hasSection('og')
   @yield('og')
@else 
<meta property="og:image" content="http://pahatud.com/images/pahatud-logo-with-motor.jpg" />
<meta property="og:image:secure_url" content="https://pahatud.com/images/pahatud-logo-with-motor.jpg" />
<meta property="og:image:type" content="image/jpeg" />
<meta property="og:image:width" content="500" />
<meta property="og:image:height" content="500" />
<meta property="og:image:alt" content=" " />
<meta property="og:title" content="Pahatud - Delivery Services" />
<meta property="og:url" content="https://pahatud.com" />
<meta property="og:type" content="website" />
<meta property="og:description" content="Pahatud Davao based startup offers affordable errands for you! We deliver local goods and food items. We simple connects entrepreneur, restaurants, and online seller. You can use our service to deliver your item to your customers." />
<!--- Facebook --> 
@endif

<meta property="fb:app_id" content="591633281717714"/>
<meta name="csrf-token" content="{{ csrf_token() }}">
<!-- favicon -->
<link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}" type="image/ico">
<!-- animate scss -->
<link rel="stylesheet" href="{{ asset('assets/css/animate.css') }}">
<!-- bootstarp css -->
<link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
<!-- icofont -->
<link rel="stylesheet" href="{{ asset('assets/css/icofont.min.css') }}">
<!-- lightcase css -->
<link rel="stylesheet" href="{{ asset('assets/css/lightcase.css') }}">
<!-- swiper css -->
<link rel="stylesheet" href="{{ asset('assets/css/swiper.min.css') }}">
<!-- custom scss -->
<link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

<!-- <link href="{{ asset('css/app.css') }}" rel="stylesheet"> -->
