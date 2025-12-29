<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'activated' => EnsureAccountIsActive::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request)
        {
            if (!app()->environment(['local', 'testing']) && in_array($response->getStatusCode(), [500, 503, 404, 403]))
            {
                $message = match ($response->getStatusCode()) {
                    500 => 'Server error.',
                    404 => 'Not found.',
                    default => $exception->getMessage(),
                };

                return Inertia::render('ErrorPage', [
                    'status' => $response->getStatusCode(),
                    'message' => $message,
                ])
                ->toResponse($request)
                ->setStatusCode($response->getStatusCode());
            }
            elseif ($response->getStatusCode() === 419)
            {
                return back()->with([
                    'message' => 'Su sesión ha expirado. Por favor, recargue la página.',
                ]);
            }

            return $response;
        });
    })->create();
