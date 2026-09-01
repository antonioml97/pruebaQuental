<?php

declare(strict_types=1);

/**
 * Construye y configura la instancia principal de Laravel.
 */

use App\Domain\Authentication\Exceptions\CsrfTokenMismatchException;
use App\Domain\Authentication\Exceptions\InvalidCredentialsException;
use App\Http\Middleware\AuthenticateAccessToken;
use App\Http\Middleware\VerifyDoubleSubmitCsrfToken;
use App\Http\Responses\ApiErrorResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
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
        $middleware->alias([
            'auth.token' => AuthenticateAccessToken::class,
            'csrf' => VerifyDoubleSubmitCsrfToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            static fn (Request $request, Throwable $exception): bool => $request->is('api/*')
                || $request->expectsJson(),
        );

        $exceptions->render(
            static function (InvalidCredentialsException $exception, Request $request): ?JsonResponse {
                return $request->is('api/*') ? ApiErrorResponse::invalidCredentials() : null;
            },
        );

        $exceptions->render(
            static function (AuthenticationException $exception, Request $request): ?JsonResponse {
                return $request->is('api/*') ? ApiErrorResponse::unauthenticated() : null;
            },
        );

        $exceptions->render(
            static function (CsrfTokenMismatchException $exception, Request $request): ?JsonResponse {
                return $request->is('api/*') ? ApiErrorResponse::csrfTokenMismatch() : null;
            },
        );

        $exceptions->render(
            static function (ThrottleRequestsException $exception, Request $request): ?JsonResponse {
                $retryAfter = (int) ($exception->getHeaders()['Retry-After'] ?? 60);

                return $request->is('api/*') ? ApiErrorResponse::tooManyRequests($retryAfter) : null;
            },
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
