<?php

namespace Src\Identidad\Infraestructura;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\ServiceProvider;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Reloj;
use Src\Identidad\Aplicacion\CierreDeSesiones;
use Src\Identidad\Aplicacion\IniciarSesion;
use Src\Identidad\Dominio\PoliticaDeContrasena;
use Src\Identidad\Dominio\RepositorioDeUsuarios;
use Src\Identidad\Infraestructura\Consola\CrearUsuarioComando;

class IdentidadServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UsuarioActual::class, UsuarioActualDeSesion::class);
        $this->app->bind(RepositorioDeUsuarios::class, RepositorioDeUsuariosEloquent::class);
        $this->app->bind(CierreDeSesiones::class, CierreDeSesionesEnBaseDeDatos::class);
        $this->app->bind(
            PoliticaDeContrasena::class,
            fn () => new PoliticaDeContrasena((int) config('identidad.longitud_minima_contrasena')),
        );
        $this->app->bind(
            IniciarSesion::class,
            fn ($app) => new IniciarSesion(
                $app->make(RepositorioDeUsuarios::class),
                $app->make(Hasher::class),
                $app->make(Reloj::class),
                (int) config('identidad.intentos_maximos'),
                (int) config('identidad.minutos_de_bloqueo'),
            ),
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([CrearUsuarioComando::class]);
        }
    }
}
