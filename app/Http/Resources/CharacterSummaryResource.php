<?php

declare(strict_types=1);

/**
 * Define la representación resumida de un personaje en listados.
 */

namespace App\Http\Resources;

use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expone atributos propios sin cargar relaciones de detalle.
 *
 * @mixin Character
 */
class CharacterSummaryResource extends JsonResource
{
    /**
     * Transforma un personaje al contrato resumido de la API.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->external_id,
            'name' => $this->name,
            'status' => $this->status,
            'species' => $this->species,
            'type' => $this->type,
            'gender' => $this->gender,
            'image_url' => $this->image_url,
        ];
    }
}
