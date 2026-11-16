<?php

namespace Src\Consultas\Infraestructura;

use Illuminate\Support\ServiceProvider;
use Src\Compartido\Aplicacion\AlcanceDeJugadores;
use Src\Consultas\Aplicacion\ConsultaDeAlertas;
use Src\Consultas\Aplicacion\ConsultaDeEstatus;
use Src\Consultas\Aplicacion\ConsultaDeGraficos;
use Src\Consultas\Aplicacion\ConsultaDePendientesDeRendimiento;
use Src\Consultas\Aplicacion\ConsultaDeVistaParaConvocar;
use Src\Consultas\Aplicacion\ParametroDeDesactualizacion;

class ConsultasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AlcanceDeJugadores::class, AlcanceDeJugadoresSql::class);
        $this->app->bind(ConsultaDeAlertas::class, ConsultaDeAlertasSql::class);
        $this->app->bind(ConsultaDeEstatus::class, ConsultaDeEstatusSql::class);
        $this->app->bind(ConsultaDeGraficos::class, ConsultaDeGraficosSql::class);
        $this->app->bind(ParametroDeDesactualizacion::class, ParametroDeDesactualizacionSql::class);
        $this->app->bind(ConsultaDePendientesDeRendimiento::class, ConsultaDePendientesDeRendimientoSql::class);
        $this->app->bind(ConsultaDeVistaParaConvocar::class, ConsultaDeVistaParaConvocarSql::class);
    }
}
