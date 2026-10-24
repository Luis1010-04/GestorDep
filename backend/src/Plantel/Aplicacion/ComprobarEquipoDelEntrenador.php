<?php

namespace Src\Plantel\Aplicacion;

use LogicException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Plantel\Dominio\RepositorioDeEquipos;

/** Primera comprobación de las rutas de un equipo: un equipo ajeno o inexistente es 404, nunca 403. */
final class ComprobarEquipoDelEntrenador
{
    public function __construct(
        private readonly RepositorioDeEquipos $equipos,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    /** @throws EquipoNoEncontrado */
    public function ejecutar(Identificador $equipoId): void
    {
        $entrenador = $this->usuarioActual->identificador() ?? throw new LogicException('usuario_no_identificado');

        $this->equipos->buscarDelEntrenador($equipoId, $entrenador) ?? throw new EquipoNoEncontrado;
    }
}
