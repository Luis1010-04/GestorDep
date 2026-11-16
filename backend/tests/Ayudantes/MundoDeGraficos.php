<?php

/*
 * Datos de partida de las pruebas de gráficos de historial (HU-25, docs/hitos/f1-h6.md §7.7). Parte de `estatusMundo()`
 * (E1 entrena A, E2 entrena B, E3 entrena A y B; D1 da una materia del periodo vigente con F1 y JB inscritos; DE es docente
 * y entrena A; F1 está en A y B; J1, solo en A; hoy es 2026-11-15) y le agrega, con la conexión del propietario:
 *
 * - Partidos jugados: PA1 (2026-10-10) y PA2 (2026-10-24) de A, y PB1 (2026-10-17) de B.
 * - Calificaciones de partido de F1: E1 7 y 8 en PA1 y PA2; E3 5 en PA1 y 6 en PB1; E2 9 en PB1. De J1, E1: 9 y 6.
 * - Mediciones de F1: E1 en 2026-09 y 2026-10; E2 en 2026-10 (de otro equipo, nunca debe verlas E1).
 * - Entrenamientos realizados de A: 10-03, 10-10 y 10-17 (F1 presente, falta y falta justificada; J1 presente en los dos
 *   primeros) y 11-07 y 11-10 (F1 presente); y uno de B el 10-17 (F1 presente).
 * - Materia de D1: parciales de F1 (2026-09-15 y 2026-10-15), la final (2026-11-10) y asistencia a clases de octubre y
 *   noviembre.
 * - Resúmenes académicos de F1 del periodo anterior (7.50) y del vigente (8.00).
 */

use Database\Seeders\ParametrosInstitucionalesSeeder;
use Illuminate\Support\Facades\DB;

/** @return array<string, string> */
function graficosMundo(): array
{
    $m = estatusMundo();
    (new ParametrosInstitucionalesSeeder)->run();

    $temporada = mundoInsertar('temporadas', ['nombre' => 'T1', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2026-12-15']);
    $rival = mundoInsertar('rivales', ['nombre' => 'R1']);
    $lugar = mundoInsertar('lugares', ['nombre' => 'Cancha norte']);
    $partido = fn (string $equipo, string $iniciaEn): string => mundoInsertar('partidos', [
        'equipo_id' => $equipo, 'temporada_id' => $temporada, 'rival_id' => $rival, 'lugar_id' => $lugar,
        'inicia_en' => $iniciaEn, 'tipo' => 'amistoso', 'condicion' => 'local', 'estado' => 'jugado', 'goles_favor' => 1, 'goles_contra' => 0,
    ]);
    $m['pa1'] = $partido($m['a'], '2026-10-10 23:00:00+00');
    $m['pa2'] = $partido($m['a'], '2026-10-24 23:00:00+00');
    $m['pb1'] = $partido($m['b'], '2026-10-17 23:00:00+00');

    graficosRendimiento($m['pa1'], $m['f1'], $m['a'], $m['e1'], 7);
    graficosRendimiento($m['pa2'], $m['f1'], $m['a'], $m['e1'], 8);
    graficosRendimiento($m['pa1'], $m['j1'], $m['a'], $m['e1'], 9, 'medio');
    graficosRendimiento($m['pa2'], $m['j1'], $m['a'], $m['e1'], 6, 'medio');
    graficosRendimiento($m['pa1'], $m['f1'], $m['a'], $m['e3'], 5);
    graficosRendimiento($m['pb1'], $m['f1'], $m['b'], $m['e3'], 6);
    graficosRendimiento($m['pb1'], $m['f1'], $m['b'], $m['e2'], 9);

    medicionInsertar($m['f1'], $m['a'], $m['e1'], '2026-09-05', ['peso_kg' => '70.00', 'estatura_cm' => '175.0', 'actualizado_en' => '2026-09-05 10:00:00+00']);
    medicionInsertar($m['f1'], $m['a'], $m['e1'], '2026-10-05', ['peso_kg' => '71.50', 'estatura_cm' => '175.5', 'actualizado_en' => '2026-10-05 10:00:00+00']);
    medicionInsertar($m['f1'], $m['b'], $m['e2'], '2026-10-06', ['peso_kg' => '90.00', 'estatura_cm' => '180.0', 'actualizado_en' => '2026-10-06 10:00:00+00']);

    graficosEntrenamiento($m['a'], $lugar, $m['e1'], '2026-10-03 18:00:00+00', [$m['f1'] => 'presente', $m['j1'] => 'presente']);
    graficosEntrenamiento($m['a'], $lugar, $m['e1'], '2026-10-10 18:00:00+00', [$m['f1'] => 'falta', $m['j1'] => 'presente']);
    graficosEntrenamiento($m['a'], $lugar, $m['e1'], '2026-10-17 18:00:00+00', [$m['f1'] => 'falta_justificada']);
    graficosEntrenamiento($m['a'], $lugar, $m['e1'], '2026-11-07 18:00:00+00', [$m['f1'] => 'presente']);
    graficosEntrenamiento($m['a'], $lugar, $m['e1'], '2026-11-10 18:00:00+00', [$m['f1'] => 'presente']);
    graficosEntrenamiento($m['b'], $lugar, $m['e2'], '2026-10-17 18:00:00+00', [$m['f1'] => 'presente']);

    $criterio = (string) DB::table('criterios_aprobacion')->where('ambito', 'general')->whereNull('vigente_hasta')->value('id');
    foreach ([['parcial', 1, '8.00', '2026-09-15'], ['parcial', 2, '9.00', '2026-10-15'], ['final', null, '8.50', '2026-11-10']] as [$tipo, $numero, $valor, $fecha]) {
        mundoInsertar('calificaciones', [
            'inscripcion_id' => $m['inscripcion_f1'], 'tipo' => $tipo, 'numero_parcial' => $numero, 'valor' => $valor,
            'criterio_id' => $criterio, 'fecha' => $fecha, 'actualizado_en' => "$fecha 12:00:00+00",
        ]);
    }
    foreach ([['2026-10-05', 'presente'], ['2026-10-06', 'falta_justificada'], ['2026-10-07', 'falta'], ['2026-11-03', 'presente']] as [$fecha, $estado]) {
        mundoInsertar('asistencias_clase', ['inscripcion_id' => $m['inscripcion_f1'], 'fecha' => $fecha, 'estado' => $estado, 'actualizado_en' => "$fecha 12:00:00+00"]);
    }

    $m['periodo_anterior'] = mundoInsertar('periodos_escolares', ['nombre' => '2026-1', 'fecha_inicio' => '2026-01-12', 'fecha_fin' => '2026-06-30']);
    foreach ([[$m['periodo_anterior'], '7.50', '2026-06-30 12:00:00+00'], [$m['periodo'], '8.00', '2026-11-10 12:00:00+00']] as [$periodo, $promedio, $calculadoEn]) {
        mundoInsertar('resumenes_academicos', [
            'jugador_id' => $m['f1'], 'periodo_id' => $periodo, 'promedio' => $promedio, 'materias_reprobadas' => 0,
            'materias_sin_calificar' => 0, 'porcentaje_asistencia' => '90.00', 'nivel_riesgo' => 'sin_riesgo', 'calculado_en' => $calculadoEn,
        ]);
    }

    return $m;
}

function graficosRendimiento(string $partido, string $jugador, string $equipo, string $registradoPor, int $calificacion, string $posicion = 'delantero'): string
{
    return mundoInsertar('rendimientos_partido', [
        'partido_id' => $partido, 'jugador_id' => $jugador, 'equipo_id' => $equipo, 'registrado_por' => $registradoPor,
        'posicion_id' => DB::table('posiciones')->where('codigo', $posicion)->value('id'),
        'minutos' => 90, 'fue_titular' => true, 'tarjetas_amarillas' => 0, 'tarjetas_rojas' => 0, 'calificacion_general' => $calificacion,
        'creado_en' => '2026-11-10 10:00:00+00', 'actualizado_en' => '2026-11-10 10:00:00+00',
    ]);
}

/**
 * Un entrenamiento realizado con la asistencia de cada jugador (estado por identificador de jugador). Cada registro de
 * asistencia se actualizó el día del entrenamiento.
 *
 * @param  array<string, string>  $asistencias
 */
function graficosEntrenamiento(string $equipo, string $lugar, string $responsable, string $iniciaEn, array $asistencias): string
{
    $termina = (new DateTimeImmutable($iniciaEn))->modify('+90 minutes')->format('Y-m-d H:i:sP');
    $id = mundoEntrenamiento($equipo, $lugar, $responsable, $iniciaEn, $termina, ['estado' => 'realizado']);
    foreach ($asistencias as $jugador => $estado) {
        mundoInsertar('asistencias_entrenamiento', ['entrenamiento_id' => $id, 'jugador_id' => $jugador, 'estado' => $estado, 'actualizado_en' => $iniciaEn]);
    }

    return $id;
}

function graficosJugadorRuta(string $jugador, string $consulta = ''): string
{
    return "/api/v1/jugadores/$jugador/graficos".($consulta === '' ? '' : "?$consulta");
}

function graficosEquipoRuta(string $equipo, string $consulta = ''): string
{
    return "/api/v1/equipos/$equipo/graficos".($consulta === '' ? '' : "?$consulta");
}
