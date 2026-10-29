<?php

namespace Src\Competencia\Infraestructura;

use Illuminate\Support\ServiceProvider;
use Src\Competencia\Dominio\RepositorioDeEntrenamientos;
use Src\Competencia\Dominio\RepositorioDeLugares;
use Src\Competencia\Dominio\RepositorioDeRivales;
use Src\Competencia\Dominio\RepositorioDeTemporadas;
use Src\Competencia\Dominio\RepositorioDeTorneos;

class CompetenciaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RepositorioDeEntrenamientos::class, RepositorioDeEntrenamientosEloquent::class);
        $this->app->bind(RepositorioDeLugares::class, RepositorioDeLugaresEloquent::class);
        $this->app->bind(RepositorioDeRivales::class, RepositorioDeRivalesEloquent::class);
        $this->app->bind(RepositorioDeTemporadas::class, RepositorioDeTemporadasEloquent::class);
        $this->app->bind(RepositorioDeTorneos::class, RepositorioDeTorneosEloquent::class);
    }
}
