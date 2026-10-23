<?php

namespace Src\Plantel\Infraestructura;

use Illuminate\Support\ServiceProvider;
use Src\Plantel\Dominio\RepositorioDeCategorias;
use Src\Plantel\Dominio\RepositorioDeEquipos;

class PlantelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RepositorioDeCategorias::class, RepositorioDeCategoriasEloquent::class);
        $this->app->bind(RepositorioDeEquipos::class, RepositorioDeEquiposEloquent::class);
    }
}
