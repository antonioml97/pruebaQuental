<?php

declare(strict_types=1);

/**
 * Registra los servicios propios de la aplicación.
 */

namespace App\Providers;

use App\Domain\RickAndMorty\Contracts\RickAndMortyCatalogFetcherInterface;
use App\Domain\RickAndMorty\Contracts\RickAndMortyCatalogPersisterInterface;
use App\Domain\RickAndMorty\Contracts\RickAndMortyClientInterface;
use App\Services\RickAndMorty\EloquentRickAndMortyCatalogPersister;
use App\Services\RickAndMorty\RickAndMortyCatalogFetcher;
use App\Services\RickAndMorty\RickAndMortyClient;
use Illuminate\Support\ServiceProvider;

/**
 * Configura las dependencias compartidas por los casos de uso de la aplicación.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra las implementaciones de los contratos de la aplicación.
     */
    public function register(): void
    {
        $this->app->bind(RickAndMortyClientInterface::class, RickAndMortyClient::class);
        $this->app->bind(RickAndMortyCatalogFetcherInterface::class, RickAndMortyCatalogFetcher::class);
        $this->app->bind(
            RickAndMortyCatalogPersisterInterface::class,
            EloquentRickAndMortyCatalogPersister::class,
        );
    }

    /**
     * Inicializa los servicios que requieren la aplicación ya registrada.
     */
    public function boot(): void
    {
        // No se requiere inicialización adicional por el momento.
    }
}
