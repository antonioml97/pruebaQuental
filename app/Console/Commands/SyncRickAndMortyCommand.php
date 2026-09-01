<?php

declare(strict_types=1);

/**
 * Expone la sincronización de Rick and Morty mediante Artisan.
 */

namespace App\Console\Commands;

use App\Domain\RickAndMorty\DTO\RickAndMortySyncResultData;
use App\Domain\RickAndMorty\Exceptions\RickAndMortySynchronizationException;
use App\Services\RickAndMorty\RickAndMortySyncService;
use Illuminate\Console\Command;

/**
 * Ejecuta el servicio de sincronización y presenta un resultado útil al operador.
 */
final class SyncRickAndMortyCommand extends Command
{
    /** @var string Nombre y argumentos admitidos por el comando. */
    protected $signature = 'rick-and-morty:sync';

    /** @var string Descripción mostrada en el listado de Artisan. */
    protected $description = 'Sincroniza el catálogo de Rick and Morty con la base de datos local';

    /**
     * Ejecuta la sincronización y devuelve un código de salida coherente.
     */
    public function handle(RickAndMortySyncService $service): int
    {
        $result = null;

        /** Ejecuta la operación que el componente visual representa como una tarea. */
        $synchronize = function () use ($service, &$result): bool {
            $result = $service->synchronize();

            return true;
        };

        try {
            $this->components->task('Sincronizando Rick and Morty', $synchronize);
        } catch (RickAndMortySynchronizationException $exception) {
            $this->displayFailure($exception);

            return self::FAILURE;
        }

        if (! $result instanceof RickAndMortySyncResultData) {
            $this->components->error('La sincronización no produjo un resultado válido.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->success('Sincronización completada.');
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Localizaciones procesadas', $result->locationsProcessed],
                ['Episodios procesados', $result->episodesProcessed],
                ['Personajes procesados', $result->charactersProcessed],
                ['Relaciones procesadas', $result->relationsProcessed],
                ['Registros creados', $result->createdRecords],
                ['Registros actualizados', $result->updatedRecords],
                ['Registros sin cambios', $result->unchangedRecords],
            ],
        );

        return self::SUCCESS;
    }

    /**
     * Muestra el contexto del error sin exponer la respuesta ni detalles internos.
     */
    private function displayFailure(RickAndMortySynchronizationException $exception): void
    {
        $this->components->error('No se pudo completar la sincronización.');
        $this->line("Etapa: {$exception->stage}");

        if ($exception->resource !== null) {
            $this->line("Recurso: {$exception->resource}");
        }

        if ($exception->page !== null) {
            $this->line("Página: {$exception->page}");
        }
    }
}
