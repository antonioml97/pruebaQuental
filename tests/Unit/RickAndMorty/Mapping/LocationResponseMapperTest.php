<?php

declare(strict_types=1);

/**
 * Verifica localizaciones y los campos descriptivos vacíos admitidos.
 */

namespace Tests\Unit\RickAndMorty\Mapping;

use App\Domain\Locations\DTO\LocationData;
use App\Domain\RickAndMorty\Exceptions\InvalidRickAndMortyResponseException;
use App\Services\RickAndMorty\Mapping\LocationResponseMapper;
use App\Services\RickAndMorty\Mapping\ResponsePayloadReader;
use PHPUnit\Framework\TestCase;
use Tests\Support\RickAndMortyPayloads;

/** Verifica localizaciones y los campos descriptivos vacíos admitidos. */
final class LocationResponseMapperTest extends TestCase
{
    /** Transformador bajo prueba, sin acceso HTTP ni persistencia. */
    private LocationResponseMapper $locations;

    /** Construye el transformador con el lector común de campos externos. */
    protected function setUp(): void
    {
        parent::setUp();

        $this->locations = new LocationResponseMapper(new ResponsePayloadReader);
    }

    /** Conserva el identificador externo y los datos descriptivos de la localización. */
    public function test_it_maps_a_location_response(): void
    {
        $location = $this->locations->map(RickAndMortyPayloads::location());

        $this->assertInstanceOf(LocationData::class, $location);
        $this->assertSame(20, $location->externalId);
        $this->assertSame('Planet', $location->type);
        $this->assertSame('Replacement Dimension', $location->dimension);
    }

    /** Verifica el tipo vacío que el proveedor utiliza en localizaciones conocidas. */
    public function test_it_maps_a_location_with_an_empty_type(): void
    {
        $location = $this->locations->map([
            'id' => 118,
            'name' => 'Space Tahoe',
            'type' => '',
            'dimension' => 'Replacement Dimension',
        ]);

        $this->assertSame('', $location->type);
    }

    /** Verifica que admitir texto vacío no permita otros tipos primitivos. */
    public function test_it_rejects_a_non_string_location_type(): void
    {
        $this->expectException(InvalidRickAndMortyResponseException::class);
        $this->expectExceptionMessage('[type]');

        $this->locations->map([
            'id' => 118,
            'name' => 'Space Tahoe',
            'type' => null,
            'dimension' => 'Replacement Dimension',
        ]);
    }

    /** Verifica las dimensiones vacías que el proveedor utiliza en localizaciones conocidas. */
    public function test_it_maps_a_location_with_an_empty_dimension(): void
    {
        $location = $this->locations->map([
            'id' => 123,
            'name' => 'Normal Size Bug Dimension',
            'type' => 'Dimension',
            'dimension' => '',
        ]);

        $this->assertSame('', $location->dimension);
    }

    /** Verifica que admitir una dimensión vacía no permita valores no textuales. */
    public function test_it_rejects_a_non_string_location_dimension(): void
    {
        $this->expectException(InvalidRickAndMortyResponseException::class);
        $this->expectExceptionMessage('[dimension]');

        $this->locations->map([
            'id' => 123,
            'name' => 'Normal Size Bug Dimension',
            'type' => 'Dimension',
            'dimension' => null,
        ]);
    }
}
