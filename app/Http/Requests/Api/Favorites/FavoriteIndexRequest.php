<?php

declare(strict_types=1);

/**
 * Valida la paginación del listado privado de favoritos.
 */

namespace App\Http\Requests\Api\Favorites;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Restringe los límites de página antes de consultar la base de datos.
 */
final class FavoriteIndexRequest extends FormRequest
{
    /**
     * Delega la autorización en el middleware de token aplicado a la ruta.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define páginas positivas y un tamaño máximo razonable.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * Obtiene la página validada sin depender del resolvedor global de paginación.
     */
    public function page(): int
    {
        return (int) $this->validated('page', 1);
    }

    /**
     * Devuelve el tamaño validado o el valor predeterminado del contrato.
     */
    public function perPage(): int
    {
        return (int) $this->validated('per_page', 20);
    }
}
