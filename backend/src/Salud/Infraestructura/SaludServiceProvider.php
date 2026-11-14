<?php

namespace Src\Salud\Infraestructura;

use Illuminate\Support\ServiceProvider;
use Src\Compartido\Aplicacion\EstadoDeDisponibilidadOficial;
use Src\Salud\Aplicacion\Transaccion;
use Src\Salud\Aplicacion\VisibilidadDeJugador;
use Src\Salud\Dominio\RepositorioDeAvisos;
use Src\Salud\Dominio\RepositorioDeEstados;
use Src\Salud\Dominio\RepositorioDeLesiones;

class SaludServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EstadoDeDisponibilidadOficial::class, EstadoDeDisponibilidadOficialSalud::class);
        $this->app->bind(RepositorioDeAvisos::class, RepositorioDeAvisosSql::class);
        $this->app->bind(RepositorioDeEstados::class, RepositorioDeEstadosSql::class);
        $this->app->bind(RepositorioDeLesiones::class, RepositorioDeLesionesSql::class);
        $this->app->bind(Transaccion::class, TransaccionSql::class);
        $this->app->bind(VisibilidadDeJugador::class, VisibilidadDeJugadorSql::class);
    }
}
