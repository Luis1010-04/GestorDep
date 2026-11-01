<?php

namespace Src\Rendimiento\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Rendimiento\Dominio\CriterioDeEvaluacion;
use Src\Rendimiento\Dominio\EvaluacionCalculada;
use Src\Rendimiento\Dominio\EvaluacionDeRendimiento;
use Src\Rendimiento\Dominio\RepositorioDeEvaluaciones;
use Src\Rendimiento\Dominio\ResultadoDeRendimiento;

/** `evaluaciones_rendimiento` es de solo inserción y sin auditoría (§5.1): no hay historial ni actualización. */
final class RepositorioDeEvaluacionesSql implements RepositorioDeEvaluaciones
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function insertar(EvaluacionDeRendimiento $evaluacion): void
    {
        $calculada = $evaluacion->calculada();

        $this->conexion->table('evaluaciones_rendimiento')->insert([
            'id' => $evaluacion->id()->valor(),
            'jugador_id' => $evaluacion->jugadorId()->valor(),
            'equipo_id' => $evaluacion->equipoId()->valor(),
            'entrenador_id' => $evaluacion->entrenadorId()->valor(),
            'umbral_id' => $evaluacion->umbralId()?->valor(),
            'plantilla_id' => $evaluacion->plantillaId()?->valor(),
            'calificacion_minima' => $evaluacion->criterio()->calificacionMinima()->comoCadena(),
            'ventana_partidos' => $evaluacion->criterio()->ventanaPartidos(),
            'promedio' => $calculada->promedio()?->comoCadena(),
            'partidos_considerados' => $calculada->partidosConsiderados(),
            'resultado' => $calculada->resultado()->value,
            'calculado_en' => $evaluacion->calculadaEn()->format('Y-m-d H:i:sP'),
        ]);
    }

    public function ultimaDe(Identificador $jugador, Identificador $equipo, Identificador $entrenador): ?EvaluacionDeRendimiento
    {
        $fila = $this->conexion->table('evaluaciones_rendimiento')
            ->where('jugador_id', $jugador->valor())
            ->where('equipo_id', $equipo->valor())
            ->where('entrenador_id', $entrenador->valor())
            ->orderByDesc('calculado_en')
            ->orderByDesc('id')
            ->first();

        return $fila === null ? null : new EvaluacionDeRendimiento(
            Identificador::desdeTexto($fila->id),
            $jugador,
            $equipo,
            $entrenador,
            $fila->umbral_id === null ? null : Identificador::desdeTexto($fila->umbral_id),
            $fila->plantilla_id === null ? null : Identificador::desdeTexto($fila->plantilla_id),
            new CriterioDeEvaluacion(NumeroDecimal::desdeTexto($fila->calificacion_minima), $fila->ventana_partidos),
            new EvaluacionCalculada(
                ResultadoDeRendimiento::from($fila->resultado),
                $fila->promedio === null ? null : NumeroDecimal::desdeTexto($fila->promedio),
                $fila->partidos_considerados,
            ),
            (new DateTimeImmutable($fila->calculado_en))->setTimezone(new DateTimeZone('UTC')),
        );
    }
}
