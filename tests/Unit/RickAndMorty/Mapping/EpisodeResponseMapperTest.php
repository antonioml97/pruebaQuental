<?php

declare(strict_types=1);

/**
 * Verifica la traducción de episodios y la normalización de fechas.
 */

namespace Tests\Unit\RickAndMorty\Mapping;

use App\Domain\Episodes\DTO\EpisodeData;
use App\Domain\RickAndMorty\Exceptions\InvalidRickAndMortyResponseException;
use App\Services\RickAndMorty\Mapping\EpisodeResponseMapper;
use App\Services\RickAndMorty\Mapping\ResponsePayloadReader;
use PHPUnit\Framework\TestCase;
use Tests\Support\RickAndMortyPayloads;

/** Verifica la traducción de episodios y la normalización de fechas. */
final class EpisodeResponseMapperTest extends TestCase
{
    /** Transformador bajo prueba, sin acceso HTTP ni persistencia. */
    private EpisodeResponseMapper $episodes;

    /** Construye el transformador con el lector común de campos externos. */
    protected function setUp(): void
    {
        parent::setUp();

        $this->episodes = new EpisodeResponseMapper(new ResponsePayloadReader);
    }

    /** Conserva el código y convierte la fecha externa sin alterar el contrato. */
    public function test_it_maps_an_episode_response(): void
    {
        $episode = $this->episodes->map(RickAndMortyPayloads::episode());

        $this->assertInstanceOf(EpisodeData::class, $episode);
        $this->assertSame('S01E01', $episode->code);
        $this->assertSame('2013-12-02', $episode->airDate->format('Y-m-d'));
    }

    /** Verifica el formato de fecha del proveedor antes de acceder al dominio. */
    public function test_it_rejects_an_invalid_air_date(): void
    {
        $this->expectException(InvalidRickAndMortyResponseException::class);
        $this->expectExceptionMessage('[air_date]');

        $this->episodes->map(array_replace(RickAndMortyPayloads::episode(), ['air_date' => 'not-a-date']));
    }
}
