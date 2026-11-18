<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $bootstrap['view'] === 'kitchen' ? 'Kitchen Display' : 'Cashier Dashboard' }} | {{ $bootstrap['merchant'] }}</title>
  <link rel="icon" href="{{ asset('images/favicon.ico') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/merchant-staff.css') }}">
  @vite('resources/js/merchant.js')
</head>
<body>
  <div id="app"><merchant-staff-workspace :config="{{ Illuminate\Support\Js::from($bootstrap) }}"></merchant-staff-workspace></div>
  <noscript>Enable JavaScript to view live branch orders.</noscript>
</body>
</html>
