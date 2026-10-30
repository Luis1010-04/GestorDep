<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Compartido\Aplicacion\PosicionEnEquipo;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Rendimiento\Dominio\CalculoDeDesempeno;
use Src\Rendimiento\Dominio\EvaluacionDeRendimiento;
use Src\Rendimiento\Dominio\RepositorioDeEvaluaciones;
use Src\Rendimiento\Dominio\RepositorioDePlantillas;
use Src\Rendimiento\Dominio\RepositorioDeUmbrales;

/**
 * Evalúa a un jugador en un equipo para un entrenador (RN-08, HU-16). El criterio es su umbral vigente o, si no tiene,
 * la plantilla de fábrica de la posición del jugador en el equipo; si el jugador ya no está en el equipo, la del
 * `posicion_id` guardado en el rendimiento (v1.0.5). Las calificaciones son solo las del entrenador en ese equipo.
 */
final class RecalcularEvaluacion
{
    public function __construct(
        private readonly RepositorioDeUmbrales $umbrales,
        private readonly RepositorioDePlantillas $plantillas,
        private readonly PosicionEnEquipo $posiciones,
        private readonly CalificacionesDelEntrenador $calificaciones,
        private readonly RepositorioDeEvaluaciones $evaluaciones,
        private readonly Reloj $reloj,
    ) {}

    /** Calcula e inserta la evaluación. Sin criterio que aplicar no inserta nada; la lectura responde sin_umbral_aplicable (DA-33). */
    public function ejecutar(Identificador $jugador, Identificador $equipo, Identificador $entrenador, ?Identificador $posicionGuardada = null): void
    {
        $evaluacion = $this->calcular($jugador, $equipo, $entrenador, $posicionGuardada);
        if ($evaluacion !== null) {
            $this->evaluaciones->insertar($evaluacion);
        }
    }

    /** La evaluación de este instante, sin guardarla; nula si no hay umbral ni plantilla de fábrica que aplicar. */
    public function calcular(Identificador $jugador, Identificador $equipo, Identificador $entrenador, ?Identificador $posicionGuardada = null): ?EvaluacionDeRendimiento
    {
        $umbral = $this->umbrales->vigenteDe($jugador, $equipo, $entrenador);
        if ($umbral !== null) {
            [$umbralId, $plantillaId, $criterio] = [$umbral->id(), $umbral->plantillaId(), $umbral->criterio()];
        } else {
            $posicion = $this->posiciones->de($jugador, $equipo) ?? $posicionGuardada;
            $plantilla = $posicion === null ? null : $this->plantillas->deFabricaDe($posicion);
            if ($plantilla === null) {
                return null;
            }
            [$umbralId, $plantillaId, $criterio] = [null, $plantilla->id(), $plantilla->criterio()];
        }

        return new EvaluacionDeRendimiento(
            Identificador::generar(), $jugador, $equipo, $entrenador, $umbralId, $plantillaId, $criterio,
            CalculoDeDesempeno::evaluar($criterio, $this->calificaciones->de($jugador, $equipo, $entrenador)),
            Instante::truncadoASegundos($this->reloj->ahora()),
        );
    }
}
