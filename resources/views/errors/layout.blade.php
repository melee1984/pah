<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | @yield('brand', config('app.name', 'Pahatud'))</title>
    <style>
        body {
            align-items: center;
            background: #f8fafc;
            color: #334155;
            display: flex;
            font-family: Arial, sans-serif;
            justify-content: center;
            margin: 0;
            min-height: 100vh;
        }

        main {
            max-width: 36rem;
            padding: 2rem;
            text-align: center;
        }

        h1 {
            color: #dc2626;
            font-size: 4rem;
            margin: 0;
        }

        a {
            color: #2563eb;
        }
    </style>
    @yield('styles')
</head>
<body class="@yield('body-class')">
    <main>
        @yield('content')
    </main>
</body>
</html>
