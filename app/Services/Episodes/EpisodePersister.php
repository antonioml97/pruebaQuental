<?php

declare(strict_types=1);

/**
 * Persiste un episodio por identificador externo dentro de la transacción del catálogo.
 */

namespace App\Services\Episodes;

use App\Domain\Episodes\DTO\EpisodeData;
use App\Models\Episode;

/**
 * Persiste un episodio por identificador externo dentro de la transacción del catálogo.
 */
final class EpisodePersister
{
    /**
     * Guarda los atributos del recurso sin gestionar la transacción global.
     *
     * @param  EpisodeData  $data  Datos externos del episodio con la fecha de emisión normalizada.
     */
    public function persist(EpisodeData $data): Episode
    {
        $episode = Episode::query()->updateOrCreate(
            ['external_id' => $data->externalId],
            [
                'name' => $data->name,
                'air_date' => $data->airDate,
                'code' => $data->code,
            ],
        );

        return $episode;
    }
}
