<?php

declare(strict_types=1);

/**
 * Define la representación JSON de un episodio sincronizado.
 */

namespace App\Http\Resources;

use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expone los datos públicos de un episodio con fecha normalizada.
 *
 * @mixin Episode
 */
final class EpisodeResource extends JsonResource
{
    /**
     * Transforma un episodio al contrato público de la API.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->external_id,
            'name' => $this->name,
            'air_date' => $this->air_date->toDateString(),
            'code' => $this->code,
        ];
    }
}
