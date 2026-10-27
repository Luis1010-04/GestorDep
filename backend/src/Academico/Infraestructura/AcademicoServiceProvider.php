<?php

namespace Src\Academico\Infraestructura;

use Illuminate\Support\ServiceProvider;
use Src\Academico\Aplicacion\ConsultaDeMaterias;
use Src\Academico\Dominio\RepositorioDeAsignaturas;
use Src\Academico\Dominio\RepositorioDeCriterios;
use Src\Academico\Dominio\RepositorioDeMaterias;
use Src\Academico\Dominio\RepositorioDePeriodos;

class AcademicoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ConsultaDeMaterias::class, ConsultaDeMateriasSql::class);
        $this->app->bind(RepositorioDeAsignaturas::class, RepositorioDeAsignaturasEloquent::class);
        $this->app->bind(RepositorioDeCriterios::class, RepositorioDeCriteriosEloquent::class);
        $this->app->bind(RepositorioDeMaterias::class, RepositorioDeMateriasEloquent::class);
        $this->app->bind(RepositorioDePeriodos::class, RepositorioDePeriodosEloquent::class);
    }
}
