<?php

namespace Src\Plantel\Aplicacion;

use DateTimeImmutable;
use LogicException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Plantel\Dominio\Jugador;
use Src\Plantel\Dominio\RepositorioDeEquipos;
use Src\Plantel\Dominio\RepositorioDeJugadores;
use Src\Plantel\Dominio\RepositorioDePosiciones;

/** Alta de un jugador en un equipo del entrenador (HU-03); la categoría sale del equipo. */
final class RegistrarJugador
{
    public function __construct(
        private readonly RepositorioDeJugadores $jugadores,
        private readonly RepositorioDeEquipos $equipos,
        private readonly RepositorioDePosiciones $posiciones,
        private readonly UsuarioActual $usuarioActual,
        private readonly Reloj $reloj,
    ) {}

    /** @throws EquipoNoEncontrado|PosicionInvalida|MatriculaDuplicada */
    public function ejecutar(
        string $nombres,
        string $apellidos,
        DateTimeImmutable $fechaNacimiento,
        string $matricula,
        Identificador $equipoId,
        string $posicion,
    ): JugadorRegistrado {
        $entrenador = $this->usuarioActual->identificador() ?? throw new LogicException('usuario_no_identificado');

        $equipo = $this->equipos->buscarDelEntrenador($equipoId, $entrenador) ?? throw new EquipoNoEncontrado;
        $posicionId = $this->posiciones->idPorCodigo($posicion) ?? throw new PosicionInvalida;

        $matricula = trim($matricula);
        if ($this->jugadores->existeMatricula($matricula)) {
            throw new MatriculaDuplicada;
        }

        $jugador = new Jugador(Identificador::generar(), $nombres, $apellidos, $fechaNacimiento, $matricula, $this->reloj->ahora());
        $this->jugadores->guardarNuevo($jugador, $equipoId, $posicionId);

        return new JugadorRegistrado($jugador, $equipo, $posicion);
    }
}
