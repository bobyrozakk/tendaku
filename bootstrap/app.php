<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureVendorOwner;
use App\Http\Middleware\SetActiveVendor;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_PROTO,
        );
        $middleware->validateCsrfTokens(except: ['webhook/midtrans']);
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'vendor.owner' => EnsureVendorOwner::class,
            'vendor.active' => SetActiveVendor::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
