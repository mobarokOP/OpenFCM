<?php

use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\NotifyAuth;
use App\Http\Middleware\ResolveApplication;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'resolve.app' => ResolveApplication::class,
            'apikey' => AuthenticateApiKey::class,
            'notify.auth' => NotifyAuth::class,
        ]);

        // API is stateless; throttle device-facing + auth endpoints.
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Uniform JSON error envelope for the whole API.
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('v1/*') && ! $request->expectsJson()) {
                return null;
            }

            [$status, $code, $message, $details] = match (true) {
                $e instanceof ValidationException => [422, 'validation_error', 'The given data was invalid.', $e->errors()],
                $e instanceof AuthenticationException => [401, 'unauthenticated', 'Authentication required.', null],
                $e instanceof ModelNotFoundException,
                $e instanceof NotFoundHttpException => [404, 'not_found', 'Resource not found.', null],
                $e instanceof HttpExceptionInterface => [$e->getStatusCode(), 'http_error', $e->getMessage() ?: 'Request failed.', null],
                default => [500, 'server_error', app()->hasDebugModeEnabled() ? $e->getMessage() : 'Something went wrong.', null],
            };

            return response()->json([
                'error' => array_filter([
                    'code' => $code,
                    'message' => $message,
                    'details' => $details,
                ], fn ($v) => $v !== null),
            ], $status);
        });
    })->create();
