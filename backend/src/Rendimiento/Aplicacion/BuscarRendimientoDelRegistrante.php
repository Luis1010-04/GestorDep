<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Compartido\Aplicacion\EquiposDelEntrenador;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Rendimiento\Dominio\RendimientoDePartido;
use Src\Rendimiento\Dominio\RepositorioDeRendimientos;

/**
 * Un rendimiento que el usuario registró, de un equipo que entrena y de un jugador en su alcance (con bajas, DA-12). El
 * alcance se aplica antes que `registrado_por` (§3.3). Si falla cualquiera, el mismo 404 que uno inexistente (RN-14).
 */
final class BuscarRendimientoDelRegistrante
{
    public function __construct(
        private readonly RepositorioDeRendimientos $rendimientos,
        private readonly EquiposDelEntrenador $equipos,
        private readonly VisibilidadDeJugador $visibilidad,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    /** @throws RecursoNoEncontrado */
    public function ejecutar(Identificador $id): RendimientoDePartido
    {
        $rendimiento = $this->rendimientos->buscar($id) ?? throw new RecursoNoEncontrado;
        $usuario = $this->usuarioActual->identificador();

        if ($usuario === null
            || ! $this->equipos->entrena($usuario, $rendimiento->equipoId())
            || ! $this->visibilidad->esVisibleParaEntrenador($rendimiento->jugadorId(), incluirBajas: true)
            || ! $rendimiento->registradoPor()->igualA($usuario)) {
            throw new RecursoNoEncontrado;
        }

        return $rendimiento;
    }
}
