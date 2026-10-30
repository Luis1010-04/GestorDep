<?php

namespace Src\Rendimiento\Infraestructura;

use Illuminate\Support\ServiceProvider;
use Src\Rendimiento\Aplicacion\ConsultaDeMetricas;
use Src\Rendimiento\Aplicacion\ConsultaDeRendimientos;
use Src\Rendimiento\Aplicacion\VisibilidadDeJugador;
use Src\Rendimiento\Dominio\CatalogoDeMetricas;
use Src\Rendimiento\Dominio\RepositorioDeRendimientos;

class RendimientoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CatalogoDeMetricas::class, CatalogoDeMetricasSql::class);
        $this->app->bind(ConsultaDeMetricas::class, CatalogoDeMetricasSql::class);
        $this->app->bind(ConsultaDeRendimientos::class, ConsultaDeRendimientosSql::class);
        $this->app->bind(RepositorioDeRendimientos::class, RepositorioDeRendimientosEloquent::class);
        $this->app->bind(VisibilidadDeJugador::class, VisibilidadDeJugadorSql::class);
    }
}
