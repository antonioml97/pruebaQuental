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
     */
    private static function make(string $code, string $message, array $details, int $status): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $status);
    }
}
