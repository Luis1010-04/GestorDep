<?php

namespace Src\Academico\Infraestructura;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Src\Academico\Aplicacion\ConsultaDeMaterias;
use Src\Academico\Aplicacion\FuenteDeDatosAcademicos;
use Src\Academico\Dominio\RepositorioDeAsignaturas;
use Src\Academico\Dominio\RepositorioDeCriterios;
use Src\Academico\Dominio\RepositorioDeMaterias;
use Src\Academico\Dominio\RepositorioDeParametros;
use Src\Academico\Dominio\RepositorioDePeriodos;
use Src\Academico\Dominio\RepositorioDeResumenes;
use Src\Academico\Infraestructura\Consola\ConciliarResumenesComando;
use Src\Academico\Infraestructura\Escuchas\RecalcularResumenAcademico;
use Src\Compartido\Dominio\Eventos\AsistenciaDeClaseRegistrada;
use Src\Compartido\Dominio\Eventos\CalificacionRegistrada;
use Src\Compartido\Dominio\Eventos\CriterioDeMateriaModificado;
use Src\Compartido\Dominio\Eventos\JugadorInscrito;

class AcademicoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ConsultaDeMaterias::class, ConsultaDeMateriasSql::class);
        $this->app->bind(FuenteDeDatosAcademicos::class, FuenteDeDatosAcademicosSql::class);
        $this->app->bind(RepositorioDeAsignaturas::class, RepositorioDeAsignaturasEloquent::class);
        $this->app->bind(RepositorioDeCriterios::class, RepositorioDeCriteriosEloquent::class);
        $this->app->bind(RepositorioDeMaterias::class, RepositorioDeMateriasEloquent::class);
        $this->app->bind(RepositorioDeParametros::class, RepositorioDeParametrosEloquent::class);
        $this->app->bind(RepositorioDePeriodos::class, RepositorioDePeriodosEloquent::class);
        $this->app->bind(RepositorioDeResumenes::class, RepositorioDeResumenesEloquent::class);
    }

    public function boot(): void
    {
        Event::listen(
            [CalificacionRegistrada::class, AsistenciaDeClaseRegistrada::class, JugadorInscrito::class, CriterioDeMateriaModificado::class],
            RecalcularResumenAcademico::class,
        );

        if ($this->app->runningInConsole()) {
            $this->commands([ConciliarResumenesComando::class]);
        }
    }
}
