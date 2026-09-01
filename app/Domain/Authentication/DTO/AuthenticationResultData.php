<?php

declare(strict_types=1);

/**
 * Representa el resultado seguro de emitir una sesión autenticada.
 */

namespace App\Domain\Authentication\DTO;

use DateTimeImmutable;

/**
 * Transporta el usuario público y el token que solo consumirá la cookie HTTP.
 */
final readonly class AuthenticationResultData
{
    /** Identificador local del usuario. */
    public int $userId;

    /** Nombre público del usuario. */
    public string $name;

    /** Correo del usuario autenticado. */
    public string $email;

    /** Token opaco que nunca debe formar parte del cuerpo JSON. */
    public string $plainTextToken;

    /** Momento en que la sesión dejará de ser válida. */
    public DateTimeImmutable $expiresAt;

    /**
     * Crea el resultado de una emisión de token satisfactoria.
     */
    public function __construct(
        int $userId,
        string $name,
        string $email,
        string $plainTextToken,
        DateTimeImmutable $expiresAt,
    ) {
        $this->userId = $userId;
        $this->name = $name;
        $this->email = $email;
        $this->plainTextToken = $plainTextToken;
        $this->expiresAt = $expiresAt;
    }
}
