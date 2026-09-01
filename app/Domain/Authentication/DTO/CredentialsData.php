<?php

declare(strict_types=1);

/**
 * Contiene credenciales validadas para iniciar sesión.
 */

namespace App\Domain\Authentication\DTO;

/**
 * Transporta credenciales sin acoplar el servicio de autenticación a HTTP.
 */
final readonly class CredentialsData
{
    /** Correo normalizado presentado por el usuario. */
    public string $email;

    /** Contraseña presentada para su comprobación inmediata. */
    public string $password;

    /**
     * Crea unas credenciales ya validadas estructuralmente.
     */
    public function __construct(string $email, string $password)
    {
        $this->email = $email;
        $this->password = $password;
    }
}
