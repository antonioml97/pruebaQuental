<?php

declare(strict_types=1);

/**
 * Define la representación JSON de una localización sincronizada.
 */

namespace App\Http\Resources;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expone únicamente los datos públicos de una localización.
 *
 * @mixin Location
 */
final class LocationResource extends JsonResource
{
    /**
     * Transforma una localización al contrato público de la API.
     *
     * @param  Request  $request  Contexto HTTP recibido por Laravel durante la serialización del recurso.
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->external_id,
            'name' => $this->name,
            'type' => $this->type,
            'dimension' => $this->dimension,
        ];
    }
}
