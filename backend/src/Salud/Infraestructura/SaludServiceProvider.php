<?php

namespace Src\Salud\Infraestructura;

use Illuminate\Support\ServiceProvider;
use Src\Salud\Aplicacion\Transaccion;
use Src\Salud\Aplicacion\VisibilidadDeJugador;
use Src\Salud\Dominio\RepositorioDeAvisos;

class SaludServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RepositorioDeAvisos::class, RepositorioDeAvisosSql::class);
        $this->app->bind(Transaccion::class, TransaccionSql::class);
        $this->app->bind(VisibilidadDeJugador::class, VisibilidadDeJugadorSql::class);
    }
}
