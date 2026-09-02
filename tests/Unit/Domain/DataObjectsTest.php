<?php

declare(strict_types=1);

/**
 * Verifica que los DTOs promovidos conservan sus datos e inmutabilidad.
 */

namespace Tests\Unit\Domain;

use App\Domain\Authentication\DTO\AuthenticationResultData;
use App\Domain\Authentication\DTO\CredentialsData;
use App\Domain\Authentication\DTO\RegistrationData;
use App\Domain\Characters\DTO\CharacterData;
use App\Domain\Characters\DTO\CharacterFiltersData;
use App\Domain\Episodes\DTO\EpisodeData;
use App\Domain\Locations\DTO\LocationData;
use App\Domain\RickAndMorty\DTO\PaginatedResponseData;
use App\Domain\RickAndMorty\DTO\RickAndMortyCatalogData;
use App\Domain\RickAndMorty\DTO\RickAndMortySyncResultData;
use DateTimeImmutable;
use Error;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Comprueba el contrato de datos sin acoplarlo a mappers, HTTP o Eloquent. */
final class DataObjectsTest extends TestCase
{
    /**
     * Conserva nombres, valores y prohibición de escritura después de construir el DTO.
     *
     * @param  class-string  $dataClass  Clase del DTO cuya firma pública debe conservarse.
     * @param  array<string, mixed>  $expected  Argumentos con nombre y propiedades esperadas.
     */
    #[DataProvider('dataObjects')]
    public function test_it_preserves_data_and_immutability(string $dataClass, array $expected): void
    {
        $data = new $dataClass(...$expected);

        $this->assertSame($expected, get_object_vars($data));

        $property = array_key_first($expected);
        $this->expectException(Error::class);

        $data->{$property} = $expected[$property];
    }

    /**
     * Aporta ejemplos de todos los DTOs, incluidas referencias nulas y colecciones.
     *
     * @return iterable<string, array{class-string, array<string, mixed>}>
     */
    public static function dataObjects(): iterable
    {
        $date = new DateTimeImmutable('2026-09-02 12:00:00');
        $location = new LocationData(20, 'Earth', 'Planet', 'C-137');
        $episode = new EpisodeData(1, 'Pilot', $date, 'S01E01');
        $character = new CharacterData(1, 'Rick', 'Alive', 'Human', '', 'Male', 'https://example.test/rick.png', null, 20, [1]);

        yield 'credenciales' => [CredentialsData::class, ['email' => 'rick@example.test', 'password' => 'Example123']];
        yield 'registro' => [RegistrationData::class, ['name' => 'Rick', 'email' => 'rick@example.test', 'password' => 'Example123']];
        yield 'autenticación' => [AuthenticationResultData::class, [
            'userId' => 1, 'name' => 'Rick', 'email' => 'rick@example.test', 'plainTextToken' => '1|example', 'expiresAt' => $date,
        ]];
        yield 'filtros' => [CharacterFiltersData::class, [
            'name' => null, 'status' => 'Alive', 'species' => 'Human', 'gender' => null, 'perPage' => 10,
        ]];
        yield 'personaje' => [CharacterData::class, get_object_vars($character)];
        yield 'episodio' => [EpisodeData::class, get_object_vars($episode)];
        yield 'localización' => [LocationData::class, get_object_vars($location)];
        yield 'catálogo' => [RickAndMortyCatalogData::class, ['locations' => [$location], 'episodes' => [$episode], 'characters' => [$character]]];
        yield 'página' => [PaginatedResponseData::class, [
            'currentPage' => 1, 'totalPages' => 1, 'totalItems' => 1, 'nextPage' => null, 'previousPage' => null, 'items' => [$character],
        ]];
        yield 'resumen' => [RickAndMortySyncResultData::class, [
            'locationsProcessed' => 1, 'episodesProcessed' => 1, 'charactersProcessed' => 1, 'relationsProcessed' => 1,
            'createdRecords' => 1, 'updatedRecords' => 1, 'unchangedRecords' => 1,
        ]];
    }
}
