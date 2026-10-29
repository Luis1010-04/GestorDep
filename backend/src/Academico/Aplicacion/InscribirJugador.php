<?php

namespace Src\Academico\Aplicacion;

use LogicException;
use Src\Academico\Dominio\Inscripcion;
use Src\Academico\Dominio\RepositorioDeInscripciones;
use Src\Compartido\Aplicacion\LocalizadorDeJugadores;
use Src\Compartido\Aplicacion\PublicadorDeEventos;
use Src\Compartido\Dominio\Eventos\JugadorInscrito;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;

/**
 * Inscribe a un jugador, localizado por matrícula, en una materia del docente (HU-06, RN-20, DD-37, DD-52). Inscribirlo
 * otorga al docente el alcance sobre él. Es el único uso del `LocalizadorDeJugadores`. La pertenencia de la materia al
 * docente la comprueba la solicitud HTTP antes de llegar aquí.
 */
final class InscribirJugador
{
    public function __construct(
        private readonly LocalizadorDeJugadores $localizador,
        private readonly RepositorioDeInscripciones $inscripciones,
        private readonly ConsultaDeInscripciones $consulta,
        private readonly PublicadorDeEventos $eventos,
        private readonly Reloj $reloj,
    ) {}

    /** @throws JugadorNoRegistrado|JugadorEnBajaNoInscribible|JugadorYaInscrito */
    public function ejecutar(Identificador $materiaId, string $matricula): InscripcionConJugador
    {
        $jugador = $this->localizador->porMatricula(trim($matricula)) ?? throw new JugadorNoRegistrado;
        if ($jugador->enBaja) {
            throw new JugadorEnBajaNoInscribible;
        }
        if ($this->inscripciones->existe($materiaId, $jugador->id)) {
            throw new JugadorYaInscrito;
        }

        $inscripcion = new Inscripcion(Identificador::generar(), $materiaId, $jugador->id, Instante::truncadoASegundos($this->reloj->ahora()));
        $this->inscripciones->guardarNueva($inscripcion);
        $this->eventos->publicar(new JugadorInscrito($inscripcion->id()->valor(), $materiaId->valor(), $jugador->id->valor()));

        return $this->consulta->buscar($inscripcion->id()) ?? throw new LogicException('jugador_sin_alcance_tras_inscribir');
    }
}
