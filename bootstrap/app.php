<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

         $middleware->group('admin', [
            \App\Http\Middleware\isAdmin::class,
        ]);

        $middleware->group('merchant', [
            \App\Http\Middleware\isMerchant::class,
        ]);

        $middleware->group('logged', [
            \App\Http\Middleware\isLogged::class,
        ]);

        $middleware->group('isRequest', [
            \App\Http\Middleware\isRequest::class,
        ]);
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
