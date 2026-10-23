<?php

namespace Src\Plantel\Infraestructura;

use Illuminate\Support\ServiceProvider;
use Src\Plantel\Dominio\RepositorioDeCategorias;

class PlantelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RepositorioDeCategorias::class, RepositorioDeCategoriasEloquent::class);
    }
}
