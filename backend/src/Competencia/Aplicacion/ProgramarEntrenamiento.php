<?php

namespace Src\Competencia\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Aplicacion\JugadoresDelEquipo;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;
use Src\Compartido\Dominio\Reloj;
use Src\Competencia\Dominio\Entrenamiento;
use Src\Competencia\Dominio\EstadoDeEntrenamiento;
use Src\Competencia\Dominio\RepositorioDeEntrenamientos;

/**
 * Alta de un entrenamiento programado (HU-10). Un empalme de lugar no impide el alta: se informa como advertencia.
 * La pertenencia del equipo se comprobó antes, en la solicitud (404 antes que 422).
 */
final class ProgramarEntrenamiento
{
    public function __construct(
        private readonly RepositorioDeEntrenamientos $entrenamientos,
        private readonly ComprobarEquipoEntrenado $comprobarEquipo,
        private readonly ReferenciasDeEntrenamiento $referencias,
        private readonly JugadoresDelEquipo $jugadores,
        private readonly Reloj $reloj,
    ) {}

    /** @throws RecursoNoEncontrado|LugarInexistente|ResponsableNoAsignado|ReglaDeNegocioViolada */
    public function ejecutar(Identificador $equipoId, DateTimeImmutable $iniciaEn, DateTimeImmutable $terminaEn, string $lugarId, string $responsableId): EntrenamientoConJugadores
    {
        $this->comprobarEquipo->ejecutar($equipoId);

        $entrenamiento = new Entrenamiento(
            Identificador::generar(),
            $equipoId,
            $this->referencias->lugar($lugarId),
            $iniciaEn,
            $terminaEn,
            $this->referencias->responsable($responsableId, $equipoId),
            EstadoDeEntrenamiento::Programado,
            Instante::truncadoASegundos($this->reloj->ahora()),
        );
        $this->entrenamientos->guardarNuevo($entrenamiento);

        return new EntrenamientoConJugadores($entrenamiento, $this->jugadores->de($equipoId), $this->entrenamientos->hayEmpalme($entrenamiento));
    }
}
