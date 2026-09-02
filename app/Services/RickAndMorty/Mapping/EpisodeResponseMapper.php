<?php

declare(strict_types=1);

/**
 * Traduce episodios y valida sus códigos y fechas de emisión.
 */

namespace App\Services\RickAndMorty\Mapping;

use App\Domain\Episodes\DTO\EpisodeData;
use DateTimeImmutable;

/**
 * Traduce episodios y valida sus códigos y fechas de emisión.
 */
final class EpisodeResponseMapper
{
    /** Lector común de campos y referencias externas. */
    private readonly ResponsePayloadReader $payload;

    /**
     * Recibe el colaborador específico del caso de uso.
     *
     * @param  ResponsePayloadReader  $payload  Lector compartido que valida tipos, campos y referencias del proveedor.
     */
    public function __construct(ResponsePayloadReader $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Transforma la respuesta de un episodio.
     *
     * @param  array<string, mixed>  $payload  Objeto JSON externo todavía no validado; solo se aceptan los campos del contrato.
     */
    public function map(array $payload): EpisodeData
    {
        $code = $this->payload->requireString($payload, 'episode');

        if (preg_match('/^S\d+E\d+$/', $code) !== 1) {
            throw $this->payload->invalid('episode', 'debe contener un código de episodio válido');
        }

        return new EpisodeData(
            externalId: $this->payload->requirePositiveInt($payload, 'id'),
            name: $this->payload->requireString($payload, 'name'),
            airDate: $this->mapAirDate($payload),
            code: $code,
        );
    }

    /**
     * Normaliza la fecha de emisión del proveedor como un valor inmutable del dominio.
     *
     * @param  array<string, mixed>  $payload  Objeto JSON externo todavía no validado; solo se aceptan los campos del contrato.
     */
    private function mapAirDate(array $payload): DateTimeImmutable
    {
        $value = $this->payload->requireString($payload, 'air_date');
        $date = DateTimeImmutable::createFromFormat('!F j, Y', $value);
        $errors = DateTimeImmutable::getLastErrors();

        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw $this->payload->invalid('air_date', 'debe contener una fecha válida');
        }

        return $date;
    }
}
