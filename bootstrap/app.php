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
    ->withMiddleware(
        /**
         * Registra los alias de los middlewares propios de autenticación y CSRF.
         *
         * @param  Middleware  $middleware  Configuración de la cadena HTTP que recibe los alias.
         */
        function (Middleware $middleware): void {
            $middleware->alias([
                'auth.token' => AuthenticateAccessToken::class,
                'csrf' => VerifyDoubleSubmitCsrfToken::class,
            ]);
        })
    ->withExceptions(
        /**
         * Define cómo se traducen los fallos conocidos al contrato JSON de la API.
         *
         * @param  Exceptions  $exceptions  Registro de renderizadores y negociación de errores de Laravel.
         */
        function (Exceptions $exceptions): void {
            $exceptions->shouldRenderJsonWhen(
                /**
                 * Selecciona JSON para rutas de API y clientes que lo solicitan explícitamente.
                 *
                 * @param  Request  $request  Petición cuyo origen y cabeceras determinan el formato.
                 * @param  Throwable  $exception  Fallo recibido por Laravel; no altera la negociación del formato.
                 */
                static fn (Request $request, Throwable $exception): bool => $request->is('api/*')
                    || $request->expectsJson(),
            );

            $exceptions->render(
                /**
                 * Traduce credenciales incorrectas sin revelar cuál de ellas falló.
                 *
                 * @param  InvalidCredentialsException  $exception  Fallo de comprobación de credenciales.
                 * @param  Request  $request  Petición que permite restringir esta respuesta a la API.
                 * @return JsonResponse|null Error público de la API, o null para delegar en Laravel.
                 */
                static function (InvalidCredentialsException $exception, Request $request): ?JsonResponse {
                    return $request->is('api/*') ? ApiErrorResponse::invalidCredentials() : null;
                },
            );

            $exceptions->render(
                /**
                 * Traduce una sesión ausente o inválida al error de autenticación público.
                 *
                 * @param  AuthenticationException  $exception  Fallo de autenticación de la petición.
                 * @param  Request  $request  Petición que permite restringir esta respuesta a la API.
                 * @return JsonResponse|null Error público de la API, o null para delegar en Laravel.
                 */
                static function (AuthenticationException $exception, Request $request): ?JsonResponse {
                    return $request->is('api/*') ? ApiErrorResponse::unauthenticated() : null;
                },
            );

            $exceptions->render(
                /**
                 * Traduce el rechazo de la protección CSRF de doble envío.
                 *
                 * @param  CsrfTokenMismatchException  $exception  Fallo por token CSRF ausente o inconsistente.
                 * @param  Request  $request  Petición que permite restringir esta respuesta a la API.
                 * @return JsonResponse|null Error público de la API, o null para delegar en Laravel.
                 */
                static function (CsrfTokenMismatchException $exception, Request $request): ?JsonResponse {
                    return $request->is('api/*') ? ApiErrorResponse::csrfTokenMismatch() : null;
                },
            );

            $exceptions->render(
                /**
                 * Conserva el tiempo de espera al traducir el límite de intentos.
                 *
                 * @param  ThrottleRequestsException  $exception  Rechazo del limitador con la cabecera Retry-After.
                 * @param  Request  $request  Petición que permite restringir esta respuesta a la API.
                 * @return JsonResponse|null Error público de la API, o null para delegar en Laravel.
                 */
                static function (ThrottleRequestsException $exception, Request $request): ?JsonResponse {
                    $retryAfter = (int) ($exception->getHeaders()['Retry-After'] ?? 60);

                    return $request->is('api/*') ? ApiErrorResponse::tooManyRequests($retryAfter) : null;
                },
            );

            $exceptions->render(
                /**
                 * Agrupa los errores de entrada por campo en la envoltura pública.
                 *
                 * @param  ValidationException  $exception  Fallo que aporta los mensajes de validación.
                 * @param  Request  $request  Petición que permite restringir esta respuesta a la API.
                 * @return JsonResponse|null Error público de la API, o null para delegar en Laravel.
                 */
                static function (ValidationException $exception, Request $request): ?JsonResponse {
                    return $request->is('api/*')
                        ? ApiErrorResponse::validation($exception->errors())
                        : null;
                },
            );

            $exceptions->render(
                /**
                 * Traduce la ausencia de una ruta o recurso sin exponer detalles internos.
                 *
                 * @param  NotFoundHttpException  $exception  Fallo de resolución de la ruta o el recurso.
                 * @param  Request  $request  Petición que permite restringir esta respuesta a la API.
                 * @return JsonResponse|null Error público de la API, o null para delegar en Laravel.
                 */
                static function (NotFoundHttpException $exception, Request $request): ?JsonResponse {
                    return $request->is('api/*') ? ApiErrorResponse::notFound() : null;
                },
            );

            $exceptions->render(
                /**
                 * Traduce un verbo HTTP no admitido por la ruta de API.
                 *
                 * @param  MethodNotAllowedHttpException  $exception  Fallo de coincidencia entre ruta y método HTTP.
                 * @param  Request  $request  Petición que permite restringir esta respuesta a la API.
                 * @return JsonResponse|null Error público de la API, o null para delegar en Laravel.
                 */
                static function (MethodNotAllowedHttpException $exception, Request $request): ?JsonResponse {
                    return $request->is('api/*') ? ApiErrorResponse::methodNotAllowed() : null;
                },
            );
        })->create();
