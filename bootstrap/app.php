<?php

use App\Exceptions\ShippingPreviewException;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureVendorOwner;
use App\Http\Middleware\RedirectIfUnverified;
use App\Http\Middleware\SetActiveVendor;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        $middleware->web(append: [
            RedirectIfUnverified::class,
        ]);
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'vendor.owner' => EnsureVendorOwner::class,
            'vendor.active' => SetActiveVendor::class,
            'super_admin' => EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('katalog/booking/shipping-preview')) {
                return null;
            }
            $status = match (true) {
                $exception instanceof ShippingPreviewException => $exception->httpStatus,
                $exception instanceof ValidationException => 422,
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                default => 500,
            };
            $code = $exception instanceof ShippingPreviewException ? $exception->errorCode : match ($status) {
                419 => 'csrf_token_mismatch', 422 => 'validation_failed', 429 => 'too_many_requests', default => 'shipping_preview_failed',
            };
            $message = $exception instanceof ShippingPreviewException ? $exception->getMessage() : match ($status) {
                419 => 'Sesi berakhir. Muat ulang halaman terlebih dahulu.',
                422 => 'Data preview pengiriman tidak valid.',
                429 => 'Terlalu banyak permintaan. Silakan tunggu sebelum mencoba lagi.',
                default => 'Preview pengiriman belum dapat diproses.',
            };
            $payload = ['success' => false, 'status' => $code, 'message' => $message, 'delivery_fee' => null];
            if ($exception instanceof ValidationException) {
                $payload['errors'] = $exception->errors();
            }
            $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];

            return response()->json($payload, $status, $headers);
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
