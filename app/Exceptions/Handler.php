<?php 

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

public function render($request, Throwable $exception)
{
    // Handle 404 errors
    if ($exception instanceof NotFoundHttpException) {
        return response()->view('errors.404', [], 404);
    }

    // Handle 500 errors (example)
    if ($exception instanceof \ErrorException) {
        return response()->view('errors.500', ['error' => $exception->getMessage()], 500);
    }

    // Default Laravel handling
    return parent::render($request, $exception);
}

public function report(Throwable $exception)
{
    \Log::error("Exception caught: {$exception->getMessage()}");

    parent::report($exception);
}




