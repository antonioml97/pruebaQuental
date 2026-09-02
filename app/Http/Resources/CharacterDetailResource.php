<?php

declare(strict_types=1);

/**
 * Define la representación detallada de un personaje sincronizado.
 */

namespace App\Http\Resources;

use App\Models\Character;
use Illuminate\Http\Request;

/**
 * Añade localizaciones y episodios al contrato resumido del personaje.
 *
 * @mixin Character
 */
final class CharacterDetailResource extends CharacterSummaryResource
{
    /**
     * Transforma un personaje y sus relaciones al contrato de detalle.
     *
     * @param  Request  $request  Contexto HTTP recibido por Laravel durante la serialización del recurso.
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'origin' => LocationResource::make($this->whenLoaded('origin')),
            'current_location' => LocationResource::make($this->whenLoaded('currentLocation')),
            'episodes' => EpisodeResource::collection($this->whenLoaded('episodes')),
        ];
    }
}
