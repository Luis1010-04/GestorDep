<?php

namespace Src\Consultas\Infraestructura;

use Illuminate\Support\ServiceProvider;
use Src\Compartido\Aplicacion\AlcanceDeJugadores;
use Src\Consultas\Aplicacion\ConsultaDePendientesDeRendimiento;

class ConsultasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AlcanceDeJugadores::class, AlcanceDeJugadoresSql::class);
        $this->app->bind(ConsultaDePendientesDeRendimiento::class, ConsultaDePendientesDeRendimientoSql::class);
    }
}
