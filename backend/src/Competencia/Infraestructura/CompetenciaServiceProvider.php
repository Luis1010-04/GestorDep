<?php

namespace Src\Competencia\Infraestructura;

use Illuminate\Support\ServiceProvider;
use Src\Competencia\Aplicacion\ConsultaDeAsistencia;
use Src\Competencia\Aplicacion\VisibilidadDeJugador;
use Src\Competencia\Dominio\RepositorioDeAsistenciasDeEntrenamiento;
use Src\Competencia\Dominio\RepositorioDeEntrenamientos;
use Src\Competencia\Dominio\RepositorioDeLugares;
use Src\Competencia\Dominio\RepositorioDeRivales;
use Src\Competencia\Dominio\RepositorioDeTemporadas;
use Src\Competencia\Dominio\RepositorioDeTorneos;

class CompetenciaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ConsultaDeAsistencia::class, ConsultaDeAsistenciaSql::class);
        $this->app->bind(RepositorioDeAsistenciasDeEntrenamiento::class, RepositorioDeAsistenciasDeEntrenamientoEloquent::class);
        $this->app->bind(RepositorioDeEntrenamientos::class, RepositorioDeEntrenamientosEloquent::class);
        $this->app->bind(RepositorioDeLugares::class, RepositorioDeLugaresEloquent::class);
        $this->app->bind(RepositorioDeRivales::class, RepositorioDeRivalesEloquent::class);
        $this->app->bind(RepositorioDeTemporadas::class, RepositorioDeTemporadasEloquent::class);
        $this->app->bind(RepositorioDeTorneos::class, RepositorioDeTorneosEloquent::class);
        $this->app->bind(VisibilidadDeJugador::class, VisibilidadDeJugadorSql::class);
    }
}
