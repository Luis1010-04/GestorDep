<?php

namespace Src\Academico\Infraestructura;

use Illuminate\Support\ServiceProvider;
use Src\Academico\Dominio\RepositorioDeAsignaturas;
use Src\Academico\Dominio\RepositorioDePeriodos;

class AcademicoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RepositorioDeAsignaturas::class, RepositorioDeAsignaturasEloquent::class);
        $this->app->bind(RepositorioDePeriodos::class, RepositorioDePeriodosEloquent::class);
    }
}
