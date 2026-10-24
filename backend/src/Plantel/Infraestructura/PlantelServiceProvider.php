<?php

namespace Src\Plantel\Infraestructura;

use Illuminate\Support\ServiceProvider;
use Src\Plantel\Dominio\RepositorioDeCategorias;
use Src\Plantel\Dominio\RepositorioDeEquipos;
use Src\Plantel\Dominio\RepositorioDeJugadores;
use Src\Plantel\Dominio\RepositorioDePosiciones;

class PlantelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RepositorioDeCategorias::class, RepositorioDeCategoriasEloquent::class);
        $this->app->bind(RepositorioDeEquipos::class, RepositorioDeEquiposEloquent::class);
        $this->app->bind(RepositorioDeJugadores::class, RepositorioDeJugadoresEloquent::class);
        $this->app->bind(RepositorioDePosiciones::class, RepositorioDePosicionesEloquent::class);
    }
}
