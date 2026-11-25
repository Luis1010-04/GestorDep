<?php

namespace Src\Plantel\Aplicacion;

use LogicException;
use Src\Compartido\Aplicacion\CandadoDeJugadores;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Plantel\Dominio\RepositorioDeEquipos;
use Src\Plantel\Dominio\RepositorioDeJugadores;
use Src\Plantel\Dominio\RepositorioDeMembresias;
use Src\Plantel\Dominio\RepositorioDePosiciones;

/**
 * Asigna un jugador existente a un equipo del entrenador, localizado por matrícula (HU-04, RN-20, DD-31).
 * Asignar otorga el alcance: la respuesta ya se lee con `AlcanceDeJugadores`.
 *
 * La membresía se crea con la fila del jugador bloqueada (`CandadoDeJugadores`): una baja simultánea se serializa con la
 * asignación, y una asignación que llega durante la baja responde 409 `jugador_en_baja` (AB-20, DA-127).
 */
final class AsignarJugadorAEquipo
{
    public function __construct(
        private readonly RepositorioDeEquipos $equipos,
        private readonly RepositorioDePosiciones $posiciones,
        private readonly RepositorioDeJugadores $jugadores,
        private readonly RepositorioDeMembresias $membresias,
        private readonly ConsultaDeJugadores $consulta,
        private readonly UsuarioActual $usuarioActual,
        private readonly CandadoDeJugadores $candado,
    ) {}

    /** @throws EquipoNoEncontrado|PosicionInvalida|JugadorNoEncontrado|JugadorEnBaja|JugadorYaEnEquipo */
    public function ejecutar(Identificador $equipoId, string $matricula, string $posicion): JugadorConEquipos
    {
        $entrenador = $this->usuarioActual->identificador() ?? throw new LogicException('usuario_no_identificado');

        $this->equipos->buscarDelEntrenador($equipoId, $entrenador) ?? throw new EquipoNoEncontrado;
        $posicionId = $this->posiciones->idPorCodigo($posicion) ?? throw new PosicionInvalida;

        $jugador = $this->jugadores->buscarParaAsignar(trim($matricula)) ?? throw new JugadorNoEncontrado;
        if ($jugador->enBaja) {
            throw new JugadorEnBaja;
        }

        $this->candado->conJugadoresActivos(
            [$jugador->id],
            fn (): JugadorEnBaja => new JugadorEnBaja,
            fn () => $this->membresias->asignar($jugador->id, $equipoId, $posicionId),
        );

        return $this->consulta->buscarParaEscribir($jugador->id) ?? throw new LogicException('jugador_sin_alcance_tras_asignar');
    }
}
