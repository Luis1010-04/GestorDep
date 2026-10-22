<?php

namespace Src\Identidad\Infraestructura;

use Illuminate\Support\ServiceProvider;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Identidad\Dominio\PoliticaDeContrasena;
use Src\Identidad\Dominio\RepositorioDeUsuarios;
use Src\Identidad\Infraestructura\Consola\CrearUsuarioComando;

class IdentidadServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UsuarioActual::class, UsuarioActualDeSesion::class);
        $this->app->bind(RepositorioDeUsuarios::class, RepositorioDeUsuariosEloquent::class);
        $this->app->bind(
            PoliticaDeContrasena::class,
            fn () => new PoliticaDeContrasena((int) config('identidad.longitud_minima_contrasena')),
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([CrearUsuarioComando::class]);
        }
    }
}
