<?php

declare(strict_types=1);

/**
 * Valida filtros y paginación del listado público de personajes.
 */

namespace App\Http\Requests\Api;

use App\Domain\Characters\DTO\CharacterFiltersData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Convierte parámetros HTTP válidos en criterios de consulta del dominio.
 */
final class CharacterIndexRequest extends FormRequest
{
    /**
     * Permite consultar personajes sin autenticación en este caso de uso público.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define los filtros y límites admitidos por el listado.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'string', Rule::in(['Alive', 'Dead', 'unknown'])],
            'species' => ['sometimes', 'nullable', 'string', 'max:255'],
            'gender' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(['Female', 'Male', 'Genderless', 'unknown']),
            ],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * Construye filtros tipados a partir de los parámetros validados.
     */
    public function filters(): CharacterFiltersData
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return new CharacterFiltersData(
            name: isset($validated['name']) ? (string) $validated['name'] : null,
            status: isset($validated['status']) ? (string) $validated['status'] : null,
            species: isset($validated['species']) ? (string) $validated['species'] : null,
            gender: isset($validated['gender']) ? (string) $validated['gender'] : null,
            perPage: isset($validated['per_page']) ? (int) $validated['per_page'] : 20,
        );
    }
}
