<?php

declare(strict_types=1);

/**
 * Adapta las consultas de personajes al transporte HTTP REST.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CharacterIndexRequest;
use App\Http\Resources\CharacterDetailResource;
use App\Http\Resources\PaginatedCharacterCollection;
use App\Services\Characters\CharacterQueryService;

/**
 * Expone listado y detalle sin contener consultas ni reglas de negocio.
 */
final class CharacterController extends Controller
{
    /**
     * Devuelve un listado paginado conforme a los filtros validados.
     *
     * @param  CharacterIndexRequest  $request  Petición con filtros y paginación ya validados.
     * @param  CharacterQueryService  $query  Consulta del catálogo local, sin peticiones al proveedor externo.
     */
    public function index(
        CharacterIndexRequest $request,
        CharacterQueryService $query,
    ): PaginatedCharacterCollection {
        return (new PaginatedCharacterCollection(
            $query->paginate(filters: $request->filters(), page: $request->page()),
        ))->preserveQuery();
    }

    /**
     * Devuelve el detalle identificado por el ID público del proveedor.
     *
     * @param  string  $externalId  Identificador público del proveedor, no la clave local de Eloquent.
     * @param  CharacterQueryService  $query  Consulta del catálogo local, sin peticiones al proveedor externo.
     */
    public function show(string $externalId, CharacterQueryService $query): CharacterDetailResource
    {
        return new CharacterDetailResource($query->findByExternalId((int) $externalId));
    }
}
