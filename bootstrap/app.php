<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // Logging request/response untuk seluruh request aplikasi
        $middleware->append(\App\Http\Middleware\ApiLoggingMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'data' => $e->errors(),
                ], 422);
            }

            if ($e instanceof ModelNotFoundException) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan',
                    'data' => null,
                ], 404);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated',
                    'data' => null,
                ], 401);
            }

            if ($e instanceof AuthorizationException) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Forbidden. Akses ditolak.',
                    'data' => null,
                ], 403);
            }

            if ($e instanceof HttpExceptionInterface) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage() ?: 'Terjadi kesalahan',
                    'data' => null,
                ], $e->getStatusCode());
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan pada server',
                'data' => null,
            ], 500);
        });
    })->create();