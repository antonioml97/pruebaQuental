<?php

declare(strict_types=1);

/**
 * Valida y normaliza una petición pública de registro.
 */

namespace App\Http\Requests\Api\Authentication;

use App\Domain\Authentication\DTO\RegistrationData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Convierte la entrada HTTP de registro en datos tipados del dominio.
 */
final class RegisterRequest extends FormRequest
{
    /**
     * Permite el registro de visitantes no autenticados.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define unicidad, formato y fortaleza mínima de las credenciales.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'max:72', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ];
    }

    /**
     * Construye los datos tipados después de validar la petición.
     */
    public function registration(): RegistrationData
    {
        /** @var array{name: string, email: string, password: string} $validated */
        $validated = $this->validated();

        return new RegistrationData(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
        );
    }

    /**
     * Normaliza espacios y correo antes de aplicar las reglas.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'email' => is_string($this->input('email'))
                ? mb_strtolower(trim($this->input('email')))
                : $this->input('email'),
        ]);
    }
}
