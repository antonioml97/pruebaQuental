<?php

declare(strict_types=1);

/**
 * Persiste una localización por identificador externo dentro de la transacción del catálogo.
 */

namespace App\Services\Locations;

use App\Domain\Locations\DTO\LocationData;
use App\Models\Location;

/**
 * Persiste una localización por identificador externo dentro de la transacción del catálogo.
 */
final class LocationPersister
{
    /**
     * Guarda los atributos del recurso sin gestionar la transacción global.
     *
     * @param  LocationData  $data  Datos externos de la localización que se identificarán por external_id.
     */
    public function persist(LocationData $data): Location
    {
        $location = Location::query()->updateOrCreate(
            ['external_id' => $data->externalId],
            [
                'name' => $data->name,
                'type' => $data->type,
                'dimension' => $data->dimension,
            ],
        );

        return $location;
    }
}
