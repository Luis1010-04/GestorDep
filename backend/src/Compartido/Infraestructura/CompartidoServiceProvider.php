<?php

namespace Src\Compartido\Infraestructura;

use Illuminate\Support\ServiceProvider;
use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\Esquema\MacrosDeEsquema;

class CompartidoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Reloj::class, RelojDelSistema::class);
        // Sin singleton: lee el Reloj y la zona en cada resolución, para que una prueba pueda fijar ambos.
        $this->app->bind(HoyDeNegocio::class, fn ($app) => new HoyDeNegocioSegunReloj($app->make(Reloj::class), (string) config('app.zona_presentacion')));
    }

    public function boot(): void
    {
        MacrosDeEsquema::registrar();
    }
}
