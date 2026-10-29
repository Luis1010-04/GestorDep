<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Aplicacion\EquiposDelEntrenador;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;

/** Primera comprobación de las rutas de un equipo: uno ajeno o inexistente es 404, antes de validar el cuerpo (§6.1). */
final class ComprobarEquipoEntrenado
{
    public function __construct(
        private readonly EquiposDelEntrenador $equipos,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    /** @throws RecursoNoEncontrado */
    public function ejecutar(Identificador $equipoId): void
    {
        $usuario = $this->usuarioActual->identificador();
        if ($usuario === null || ! $this->equipos->entrena($usuario, $equipoId)) {
            throw new RecursoNoEncontrado;
        }
    }
}
