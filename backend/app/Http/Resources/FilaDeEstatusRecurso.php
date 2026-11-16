<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Academico\Aplicacion\SituacionAcademica;
use Src\Consultas\Aplicacion\AlertaVisible;
use Src\Consultas\Aplicacion\FilaDeEstatus;
use Src\Salud\Aplicacion\EstadoVisible;

/**
 * Una fila del listado de estatus (HU-23, DA-105). Una forma por rol (T04, opción B): todos reciben el jugador, el estado
 * oficial, el indicador de aviso pendiente y las alertas que pueden ver; el entrenador, además, el resumen académico
 * simplificado (RN-13), y el docente, el detalle de sus materias. Un campo que el usuario no puede ver se omite.
 *
 * @property FilaDeEstatus $resource
 */
class FilaDeEstatusRecurso extends JsonResource
{
    public function __construct(
        FilaDeEstatus $fila,
        private readonly EstadoVisible $estado,
        private readonly ?SituacionAcademica $situacion,
        private readonly bool $comoEntrenador,
        private readonly bool $comoDocente,
    ) {
        parent::__construct($fila);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $fila = $this->resource;
        $datos = [
            'jugador' => [
                'id' => $fila->jugadorId->valor(),
                'nombres' => $fila->nombres,
                'apellidos' => $fila->apellidos,
                'matricula' => $fila->matricula,
            ],
            'estado_oficial' => (new EstadoDeDisponibilidadRecurso($this->estado))->resolve($request),
            'aviso_pendiente' => $fila->avisoPendiente,
            'alertas' => array_map(fn (AlertaVisible $alerta): array => (new AlertaRecurso($alerta))->resolve($request), $fila->alertas),
        ];

        $situacion = $this->situacion === null ? null : (new SituacionAcademicaRecurso($this->situacion))->resolve($request);

        if ($this->comoEntrenador) {
            if ($situacion !== null) {
                unset($situacion['materias']);
            }
            $datos['resumen_academico'] = $situacion;
        }

        if ($this->comoDocente) {
            $datos['materias'] = $situacion['materias'] ?? [];
        }

        return $datos;
    }
}
