<?php

namespace Src\Compartido\Infraestructura;

use Illuminate\Support\ServiceProvider;
use Src\Compartido\Dominio\Reloj;

class CompartidoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Reloj::class, RelojDelSistema::class);
    }
}
