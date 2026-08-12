<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
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

        $middleware->alias([
            'rider.application' => \App\Http\Middleware\AuthenticateRiderApplication::class,
            'rider.approved' => \App\Http\Middleware\EnsureApprovedRider::class,
        ]);
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $exception) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();
