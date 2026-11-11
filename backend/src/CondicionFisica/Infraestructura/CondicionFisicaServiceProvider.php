<?php

namespace Src\CondicionFisica\Infraestructura;

use Illuminate\Support\ServiceProvider;
use Src\CondicionFisica\Aplicacion\Transaccion;
use Src\CondicionFisica\Aplicacion\VisibilidadDeJugador;
use Src\CondicionFisica\Dominio\CatalogoDeRangos;
use Src\CondicionFisica\Dominio\CatalogoDeTiposDePrueba;
use Src\CondicionFisica\Dominio\RepositorioDeMediciones;
use Src\CondicionFisica\Dominio\RepositorioDePruebasFisicas;

class CondicionFisicaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CatalogoDeRangos::class, CatalogoDeRangosSql::class);
        $this->app->bind(CatalogoDeTiposDePrueba::class, CatalogoDeTiposDePruebaSql::class);
        $this->app->bind(RepositorioDeMediciones::class, RepositorioDeMedicionesSql::class);
        $this->app->bind(RepositorioDePruebasFisicas::class, RepositorioDePruebasFisicasSql::class);
        $this->app->bind(Transaccion::class, TransaccionSql::class);
        $this->app->bind(VisibilidadDeJugador::class, VisibilidadDeJugadorSql::class);
    }
}
