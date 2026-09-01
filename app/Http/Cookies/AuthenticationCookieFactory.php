<?php

declare(strict_types=1);

/**
 * Construye las cookies de autenticación y protección CSRF.
 */

namespace App\Http\Cookies;

use DateTimeInterface;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Centraliza nombres y atributos de seguridad para no duplicarlos en controladores.
 */
final class AuthenticationCookieFactory
{
    /**
     * Crea la cookie HttpOnly que transporta el token opaco.
     */
    public function accessToken(string $plainTextToken, DateTimeInterface $expiresAt): Cookie
    {
        return $this->make(
            name: $this->configuredString('auth_tokens.cookie.name'),
            value: $plainTextToken,
            expiresAt: $expiresAt,
            httpOnly: true,
        );
    }

    /**
     * Crea la cookie que Axios copiará en la cabecera de protección CSRF.
     */
    public function csrfToken(string $token, DateTimeInterface $expiresAt): Cookie
    {
        return $this->make(
            name: $this->configuredString('auth_tokens.csrf_cookie.name'),
            value: $token,
            expiresAt: $expiresAt,
            httpOnly: false,
        );
    }

    /**
     * Crea una cookie caducada para retirar la sesión del navegador.
     */
    public function forgetAccessToken(): Cookie
    {
        return $this->make(
            name: $this->configuredString('auth_tokens.cookie.name'),
            value: '',
            expiresAt: now()->subYear(),
            httpOnly: true,
        );
    }

    /**
     * Construye una cookie host-only con los atributos compartidos.
     */
    private function make(
        string $name,
        string $value,
        DateTimeInterface $expiresAt,
        bool $httpOnly,
    ): Cookie {
        return new Cookie(
            name: $name,
            value: $value,
            expire: $expiresAt,
            path: '/',
            domain: null,
            secure: (bool) config('auth_tokens.cookie.secure'),
            httpOnly: $httpOnly,
            raw: false,
            sameSite: $this->configuredString('auth_tokens.cookie.same_site'),
        );
    }

    /**
     * Obtiene una cadena de configuración obligatoria y no vacía.
     */
    private function configuredString(string $key): string
    {
        $value = config($key);

        if (! is_string($value) || $value === '') {
            throw new InvalidArgumentException("La configuración [$key] debe ser una cadena no vacía.");
        }

        return $value;
    }
}
