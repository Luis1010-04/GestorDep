<?php

namespace App\Http\Resources;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Consultas\Aplicacion\PorcentajeDeAsistencia;
use Src\Consultas\Aplicacion\PuntoDeSerie;
use Src\Consultas\Aplicacion\VistaParaConvocar;

/**
 * La vista para convocar de un jugador (HU-24, DD-15, DA-94, DA-111; docs/hitos/f1-h6.md §6.2). Cada dimensión lleva
 * `actualizado_en` (o nulo si no hay dato) y `posiblemente_desactualizado`.
 *
 * @property VistaParaConvocar $resource
 */
class VistaParaConvocarRecurso extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $vista = $this->resource;
        $rendimiento = $vista->rendimiento;
        $situacion = $vista->situacion;

        return [
            'jugador' => [
                'id' => $vista->jugador->id->valor(),
                'nombres' => $vista->jugador->nombres,
                'apellidos' => $vista->jugador->apellidos,
                'matricula' => $vista->jugador->matricula,
            ],
            'disponibilidad' => [
                'estado' => $vista->disponibilidad->estado,
                'fecha_estimada_regreso' => $vista->disponibilidad->fechaEstimadaRegreso,
                'aviso_pendiente' => $vista->disponibilidad->avisoPendiente,
                'actualizado_en' => self::instante($vista->disponibilidad->actualizadoEn),
                'posiblemente_desactualizado' => $vista->disponibilidadDesactualizada,
            ],
            'rendimiento_reciente' => [
                'promedio' => $rendimiento?->promedio?->comoCadena(2),
                'resultado' => $rendimiento->resultado ?? 'datos_insuficientes',
                'actualizado_en' => self::instante($rendimiento?->calculadoEn),
                'posiblemente_desactualizado' => $vista->rendimientoDesactualizado,
            ],
            'situacion_academica' => [
                'periodo' => $situacion->periodo,
                'promedio' => $situacion->promedio,
                'materias_reprobadas' => $situacion->materiasReprobadas,
                'materias_sin_calificar' => $situacion->materiasSinCalificar,
                'dato_incompleto' => $situacion->datoIncompleto,
                'porcentaje_asistencia' => $situacion->porcentajeAsistencia,
                'nivel_riesgo' => $situacion->nivelRiesgo,
                'calculado_en' => self::instante($situacion->calculadoEn),
                'actualizado_en' => self::instante($situacion->calculadoEn),
                'posiblemente_desactualizado' => $vista->situacionDesactualizada,
            ],
            'asistencia' => [
                'equipo' => self::asistencia($vista->asistencia->equipo, $vista->asistenciaDelEquipoDesactualizada),
                'de_mis_capturas' => self::asistencia($vista->asistencia->deMisCapturas, $vista->asistenciaDeMisCapturasDesactualizada),
            ],
            'estado_fisico' => [
                'ultima_medicion' => $vista->estadoFisico->ultimaMedicion,
                'pruebas' => $vista->estadoFisico->pruebas,
                'actualizado_en' => self::instante($vista->estadoFisico->actualizadoEn),
                'posiblemente_desactualizado' => $vista->estadoFisicoDesactualizado,
            ],
            'progreso' => [
                'primero' => self::punto($vista->progreso->puntos[0] ?? null),
                'ultimo' => self::punto($vista->progreso->puntos === [] ? null : $vista->progreso->puntos[array_key_last($vista->progreso->puntos)]),
                'actualizado_en' => self::instante($vista->progreso->actualizadoEn),
                'posiblemente_desactualizado' => $vista->progresoDesactualizado,
            ],
        ];
    }

    /** @return array{porcentaje: string|null, actualizado_en: string|null, posiblemente_desactualizado: bool} */
    private static function asistencia(PorcentajeDeAsistencia $asistencia, bool $desactualizada): array
    {
        return [
            'porcentaje' => $asistencia->porcentaje instanceof NumeroDecimal ? $asistencia->porcentaje->comoCadena(2) : null,
            'actualizado_en' => self::instante($asistencia->actualizadoEn),
            'posiblemente_desactualizado' => $desactualizada,
        ];
    }

    /** @return array{fecha: string|array{id: string, nombre: string}, valor: string|int}|null */
    private static function punto(?PuntoDeSerie $punto): ?array
    {
        return $punto === null ? null : ['fecha' => $punto->referencia, 'valor' => $punto->valor];
    }

    private static function instante(?DateTimeImmutable $instante): ?string
    {
        return $instante?->format(DateTimeInterface::ATOM);
    }
}
