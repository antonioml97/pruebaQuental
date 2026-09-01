<?php

declare(strict_types=1);

/**
 * Construye y configura la instancia principal de Laravel.
 */

use App\Http\Responses\ApiErrorResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            static fn (Request $request, Throwable $exception): bool => $request->is('api/*')
                || $request->expectsJson(),
        );

        $exceptions->render(
            static function (ValidationException $exception, Request $request): ?JsonResponse {
                return $request->is('api/*')
                    ? ApiErrorResponse::validation($exception->errors())
                    : null;
            },
        );

        $exceptions->render(
            static function (NotFoundHttpException $exception, Request $request): ?JsonResponse {
                return $request->is('api/*') ? ApiErrorResponse::notFound() : null;
            },
        );

        $exceptions->render(
            static function (MethodNotAllowedHttpException $exception, Request $request): ?JsonResponse {
                return $request->is('api/*') ? ApiErrorResponse::methodNotAllowed() : null;
            },
        );
    })->create();
