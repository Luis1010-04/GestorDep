<?php

namespace Src\Identidad\Infraestructura\Consola;

use Illuminate\Console\Command;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;
use Src\Identidad\Aplicacion\CrearUsuario;

class CrearUsuarioComando extends Command
{
    protected $signature = 'usuarios:crear {correo} {nombre} {--rol=*} {--contrasena=}';

    public function __construct()
    {
        parent::__construct();

        $this->setDescription(__('identidad.consola.descripcion'));
    }

    public function handle(CrearUsuario $crearUsuario): int
    {
        $contrasena = (string) $this->option('contrasena');
        if ($contrasena === '') {
            $contrasena = (string) $this->secret(__('identidad.consola.pedir_contrasena'));
        }

        /** @var list<string> $roles */
        $roles = $this->option('rol');

        try {
            $usuario = $crearUsuario->ejecutar((string) $this->argument('correo'), (string) $this->argument('nombre'), $roles, $contrasena);
        } catch (ReglaDeNegocioViolada $e) {
            $this->error(__('errores.reglas.'.$e->codigo()));

            return self::FAILURE;
        }

        $this->info(__('identidad.consola.creado', ['correo' => $usuario->correo()]));

        return self::SUCCESS;
    }
}
