<?php

declare(strict_types=1);

/**
 * Valida y normaliza una petición pública de inicio de sesión.
 */

namespace App\Http\Requests\Api\Authentication;

use App\Domain\Authentication\DTO\CredentialsData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Convierte la entrada HTTP en credenciales tipadas del dominio.
 */
final class LoginRequest extends FormRequest
{
    /**
     * Permite presentar credenciales sin autenticación previa.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define la estructura mínima admitida para las credenciales.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'max:72'],
        ];
    }

    /**
     * Construye credenciales tipadas después de validar la petición.
     */
    public function credentials(): CredentialsData
    {
        /** @var array{email: string, password: string} $validated */
        $validated = $this->validated();

        return new CredentialsData($validated['email'], $validated['password']);
    }

    /**
     * Normaliza el correo sin transformar la contraseña presentada.
     */
    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        $this->merge([
            'email' => is_string($email) ? mb_strtolower(trim($email)) : $email,
        ]);
    }
}
