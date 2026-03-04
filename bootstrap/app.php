<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            /**** OTHER MIDDLEWARE ALIASES ****/
            'localize'                => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class,
            'localizationRedirect'    => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect'   => \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
            'localeCookieRedirect'    => \Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect::class,
            'localeViewPath'          => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class,
            'role'                    => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle email verification exceptions for API requests
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            // Check if this is an email verification error (status 403) and API request
            if ($e->getStatusCode() === 403 && 
                $e->getMessage() === 'Your email address is not verified.' && 
                $request->expectsJson()) {
                
                return response()->json([
                    'success' => false,
                    'message' => __('Your email address is not verified. Please check your email and verify your account.'),
                    'error_code' => 'EMAIL_NOT_VERIFIED'
                ], 403);
            }
        });

        // exception for throttle
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => __('Too many requests. Please try again later.'),
            ], 429);
        });
    })->create();
