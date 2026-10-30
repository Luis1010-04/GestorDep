<?php

namespace Src\Plantel\Infraestructura;

use Illuminate\Support\ServiceProvider;
use Src\Compartido\Aplicacion\EquiposDelEntrenador;
use Src\Compartido\Aplicacion\JugadoresDelEquipo;
use Src\Compartido\Aplicacion\LocalizadorDeJugadores;
use Src\Plantel\Aplicacion\BuscadorDeJugadorPorMatricula;
use Src\Plantel\Aplicacion\ConsultaDeJugadores;
use Src\Plantel\Dominio\RepositorioDeCategorias;
use Src\Plantel\Dominio\RepositorioDeEquipos;
use Src\Plantel\Dominio\RepositorioDeJugadores;
use Src\Plantel\Dominio\RepositorioDeMembresias;
use Src\Plantel\Dominio\RepositorioDePosiciones;

class PlantelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BuscadorDeJugadorPorMatricula::class, BusquedaDeJugadorPorMatricula::class);
        $this->app->bind(ConsultaDeJugadores::class, ConsultaDeJugadoresSql::class);
        $this->app->bind(EquiposDelEntrenador::class, EquiposDelEntrenadorPlantel::class);
        $this->app->bind(JugadoresDelEquipo::class, JugadoresDelEquipoPlantel::class);
        $this->app->bind(LocalizadorDeJugadores::class, LocalizadorDeJugadoresPlantel::class);
        $this->app->bind(RepositorioDeCategorias::class, RepositorioDeCategoriasEloquent::class);
        $this->app->bind(RepositorioDeEquipos::class, RepositorioDeEquiposEloquent::class);
        $this->app->bind(RepositorioDeJugadores::class, RepositorioDeJugadoresEloquent::class);
        $this->app->bind(RepositorioDeMembresias::class, RepositorioDeMembresiasEloquent::class);
        $this->app->bind(RepositorioDePosiciones::class, RepositorioDePosicionesEloquent::class);
    }
}
