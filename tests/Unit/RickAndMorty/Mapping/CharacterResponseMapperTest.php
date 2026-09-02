<?php

declare(strict_types=1);

/**
 * Verifica exclusivamente personajes, sus campos y sus referencias externas.
 */

namespace Tests\Unit\RickAndMorty\Mapping;

use App\Domain\Characters\DTO\CharacterData;
use App\Domain\RickAndMorty\Exceptions\InvalidRickAndMortyResponseException;
use App\Services\RickAndMorty\Mapping\CharacterResponseMapper;
use App\Services\RickAndMorty\Mapping\ResponsePayloadReader;
use PHPUnit\Framework\TestCase;
use Tests\Support\RickAndMortyPayloads;

/** Verifica exclusivamente personajes, sus campos y sus referencias externas. */
final class CharacterResponseMapperTest extends TestCase
{
    /** Transformador bajo prueba, sin acceso HTTP ni persistencia. */
    private CharacterResponseMapper $characters;

    /** Construye el transformador con el lector común de campos externos. */
    protected function setUp(): void
    {
        parent::setUp();

        $this->characters = new CharacterResponseMapper(new ResponsePayloadReader);
    }

    /** Verifica que un personaje completo del proveedor se convierte en datos de dominio. */
    public function test_it_maps_a_character_response(): void
    {
        $character = $this->characters->map(RickAndMortyPayloads::character());

        $this->assertInstanceOf(CharacterData::class, $character);
        $this->assertSame(1, $character->externalId);
        $this->assertSame('Rick Sanchez', $character->name);
        $this->assertNull($character->originLocationExternalId);
        $this->assertSame(20, $character->currentLocationExternalId);
        $this->assertSame([1, 2], $character->episodeExternalIds);
    }

    /** Verifica que los campos externos obligatorios no pueden estar ausentes. */
    public function test_it_rejects_a_missing_required_field(): void
    {
        $payload = RickAndMortyPayloads::character();
        unset($payload['name']);

        $this->expectException(InvalidRickAndMortyResponseException::class);
        $this->expectExceptionMessage('[name]');

        $this->characters->map($payload);
    }

    /** Verifica que los campos externos deben tener el tipo primitivo esperado. */
    public function test_it_rejects_an_invalid_field_type(): void
    {
        $payload = RickAndMortyPayloads::character();
        $payload['id'] = '1';

        $this->expectException(InvalidRickAndMortyResponseException::class);
        $this->expectExceptionMessage('[id]');

        $this->characters->map($payload);
    }

    /** Verifica que una referencia no puede apuntar a un tipo de recurso diferente. */
    public function test_it_rejects_an_inconsistent_resource_reference(): void
    {
        $payload = RickAndMortyPayloads::character();
        $payload['episode'] = ['https://rickandmortyapi.com/api/location/1'];

        $this->expectException(InvalidRickAndMortyResponseException::class);
        $this->expectExceptionMessage('debe referenciar un recurso de tipo [episode]');

        $this->characters->map($payload);
    }

    /** Verifica que las referencias externas solo admiten protocolos HTTP compatibles. */
    public function test_it_rejects_a_non_http_resource_url(): void
    {
        $payload = RickAndMortyPayloads::character();
        $payload['episode'] = ['ftp://rickandmortyapi.com/api/episode/1'];

        $this->expectException(InvalidRickAndMortyResponseException::class);
        $this->expectExceptionMessage('[episode.0]');

        $this->characters->map($payload);
    }
}
