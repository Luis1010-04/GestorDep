<?php

namespace Src\Alertas\Infraestructura;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Src\Alertas\Aplicacion\RepositorioDeAlertas;
use Src\Alertas\Aplicacion\RepositorioDeParametrosDeAlertas;
use Src\Alertas\Aplicacion\Transaccion;
use Src\Alertas\Infraestructura\Consola\RecalcularAlertasComando;
use Src\Alertas\Infraestructura\Escuchas\RecalcularAlertaAcademica;
use Src\Compartido\Dominio\Eventos\AsistenciaDeClaseRegistrada;
use Src\Compartido\Dominio\Eventos\CalificacionRegistrada;
use Src\Compartido\Dominio\Eventos\CriterioDeMateriaModificado;
use Src\Compartido\Dominio\Eventos\JugadorInscrito;

class AlertasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RepositorioDeAlertas::class, RepositorioDeAlertasSql::class);
        $this->app->bind(RepositorioDeParametrosDeAlertas::class, RepositorioDeParametrosDeAlertasSql::class);
        $this->app->bind(Transaccion::class, TransaccionSql::class);
    }

    public function boot(): void
    {
        Event::listen(
            [CalificacionRegistrada::class, AsistenciaDeClaseRegistrada::class, JugadorInscrito::class, CriterioDeMateriaModificado::class],
            RecalcularAlertaAcademica::class,
        );

        if ($this->app->runningInConsole()) {
            $this->commands([RecalcularAlertasComando::class]);
        }
    }
}
