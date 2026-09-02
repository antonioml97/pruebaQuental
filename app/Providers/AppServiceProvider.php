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
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('authentication',
            /**
             * Combina límites por IP y por correo normalizado e IP.
             *
             * @param  Request  $request  Petición de registro o acceso de la que se obtienen correo e IP.
             * @return list<Limit> Límites independientes que debe respetar la petición.
             */
            static function (Request $request): array {
                $email = mb_strtolower(trim((string) $request->input('email')));
                $ipAddress = $request->ip() ?? 'unknown';

                return [
                    Limit::perMinute(10)->by('authentication-ip|'.$ipAddress),
                    Limit::perMinute(5)->by('authentication-identity|'.$email.'|'.$ipAddress),
                ];
            });
    }
}
