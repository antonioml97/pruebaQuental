<?php

declare(strict_types=1);

/**
 * Construye errores JSON homogéneos para la API pública.
 */

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Centraliza códigos, mensajes y estructura de errores HTTP conocidos.
 */
final class ApiErrorResponse
{
    /**
     * Responde cuando la cookie de autenticación no representa una sesión válida.
     */
    public static function unauthenticated(): JsonResponse
    {
        return self::make(
            code: 'unauthenticated',
            message: 'Es necesario iniciar sesión para acceder a este recurso.',
            details: [],
            status: 401,
        );
    }

    /**
     * Responde sin indicar qué parte de las credenciales era incorrecta.
     */
    public static function invalidCredentials(): JsonResponse
    {
        return self::make(
            code: 'invalid_credentials',
            message: 'Las credenciales proporcionadas no son válidas.',
            details: [],
            status: 401,
        );
    }

    /**
     * Responde cuando la cookie y la cabecera CSRF no coinciden.
     */
    public static function csrfTokenMismatch(): JsonResponse
    {
        return self::make(
            code: 'csrf_token_mismatch',
            message: 'La protección CSRF de la petición no es válida.',
            details: [],
            status: 419,
        );
    }

    /**
     * Responde al superar el límite de intentos de autenticación.
     */
    public static function tooManyRequests(int $retryAfter): JsonResponse
    {
        return self::make(
            code: 'too_many_requests',
            message: 'Se han realizado demasiados intentos. Inténtalo más tarde.',
            details: [],
            status: 429,
            headers: ['Retry-After' => (string) max(1, $retryAfter)],
        );
    }

    /**
     * Responde con los errores producidos por la validación de entrada.
     *
     * @param  array<string, list<string>>  $errors
     */
    public static function validation(array $errors): JsonResponse
    {
        return self::make(
            code: 'validation_error',
            message: 'Los parámetros enviados no son válidos.',
            details: $errors,
            status: 422,
        );
    }

    /**
     * Responde cuando una ruta o un recurso de la API no existe.
     */
    public static function notFound(): JsonResponse
    {
        return self::make(
            code: 'resource_not_found',
            message: 'El recurso solicitado no existe.',
            details: [],
            status: 404,
        );
    }

    /**
     * Responde cuando la ruta no admite el método HTTP utilizado.
     */
    public static function methodNotAllowed(): JsonResponse
    {
        return self::make(
            code: 'method_not_allowed',
            message: 'El método HTTP no está permitido para este recurso.',
            details: [],
            status: 405,
        );
    }

    /**
     * Construye la envoltura estable de cualquier error conocido de la API.
     *
     * @param  array<string, mixed>  $details
     * @param  array<string, string>  $headers
     */
    private static function make(
        string $code,
        string $message,
        array $details,
        int $status,
        array $headers = [],
    ): JsonResponse {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $status, $headers);
    }
}
