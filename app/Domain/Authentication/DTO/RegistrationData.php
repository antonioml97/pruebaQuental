<?php

declare(strict_types=1);

/**
 * Contiene los datos validados necesarios para registrar un usuario.
 */

namespace App\Domain\Authentication\DTO;

/**
 * Transporta datos de registro independientes del protocolo HTTP.
 */
final readonly class RegistrationData
{
    /** Nombre público del usuario. */
    public string $name;

    /** Correo normalizado que identifica al usuario. */
    public string $email;

    /** Contraseña en claro que solo se conserva durante el registro. */
    public string $password;

    /**
     * Crea una solicitud de registro ya validada.
     */
    public function __construct(string $name, string $email, string $password)
    {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
    }
}
