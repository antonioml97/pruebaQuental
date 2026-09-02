<?php

declare(strict_types=1);

/**
 * Define el contrato paginado común para listados de personajes.
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Elimina enlaces visuales redundantes y conserva navegación y totales útiles.
 */
final class PaginatedCharacterCollection extends ResourceCollection
{
    /** @var class-string<CharacterSummaryResource> Recurso aplicado a cada personaje. */
    public $collects = CharacterSummaryResource::class;

    /**
     * Reduce los metadatos predeterminados de Laravel al contrato público estable.
     *
     * @param  Request  $request  Contexto HTTP recibido por Laravel durante la serialización del recurso.
     * @param  array<string, mixed>  $paginated  Representación del paginador de Laravel con sus contadores y límites.
     * @param  array<string, mixed>  $default  Metadatos y enlaces predeterminados de Laravel antes de reducirlos al contrato.
     * @return array<string, mixed>
     */
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return [
            'links' => $default['links'],
            'meta' => [
                'current_page' => $paginated['current_page'],
                'last_page' => $paginated['last_page'],
                'per_page' => $paginated['per_page'],
                'total' => $paginated['total'],
            ],
        ];
    }
}
