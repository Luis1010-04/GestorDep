<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Aplicacion\AsistenciaPorMes;
use Src\Compartido\Aplicacion\PorcentajeDeAsistenciaAEntrenamientos;
use Src\Compartido\Dominio\Identificador;

/**
 * DA-109, P-14 (HU-25): el puerto de Compartido que da el porcentaje de asistencia a entrenamientos por mes, con la regla de
 * Competencia (DD-51: la falta justificada cuenta en el denominador y no como presencia). El mes es el de la zona de
 * presentación (America/Mexico_City), no el de UTC.
 */
function asistenciaAEntrenamientosPuerto(): PorcentajeDeAsistenciaAEntrenamientos
{
    return app(PorcentajeDeAsistenciaAEntrenamientos::class);
}

/** @return array<string, string> */
function asistenciaDeEntrenamientosComoTexto(AsistenciaPorMes $asistencia): array
{
    return array_map(fn ($porcentaje): string => $porcentaje->comoCadena(2), $asistencia->porcentajes);
}

/**
 * Un entrenamiento del equipo con la asistencia de cada jugador (estado por identificador de jugador); devuelve su id.
 *
 * @param  array<string, string>  $asistencias
 */
function entrenamientoConAsistencia(string $equipo, string $lugar, string $responsable, string $iniciaEn, array $asistencias, string $estado = 'realizado'): string
{
    $termina = (new DateTimeImmutable($iniciaEn))->modify('+90 minutes')->format('Y-m-d H:i:sP');
    $id = mundoEntrenamiento($equipo, $lugar, $responsable, $iniciaEn, $termina, ['estado' => $estado]);
    foreach ($asistencias as $jugador => $resultado) {
        mundoInsertar('asistencias_entrenamiento', ['entrenamiento_id' => $id, 'jugador_id' => $jugador, 'estado' => $resultado, 'actualizado_en' => $iniciaEn]);
    }

    return $id;
}

beforeEach(function () {
    sesionesPrepararBase();
});

test('HU-25 (DD-51): el porcentaje de cada mes es presentes sobre todos los registros, con la falta justificada solo en el denominador', function () {
    $m = mundoDeCompetencia();
    entrenamientoConAsistencia($m['a'], $m['l1'], $m['e1'], '2026-10-03 18:00:00+00', [$m['j1'] => 'presente']);
    entrenamientoConAsistencia($m['a'], $m['l1'], $m['e1'], '2026-10-10 18:00:00+00', [$m['j1'] => 'falta']);
    entrenamientoConAsistencia($m['a'], $m['l1'], $m['e1'], '2026-10-17 18:00:00+00', [$m['j1'] => 'falta_justificada']);
    entrenamientoConAsistencia($m['a'], $m['l1'], $m['e1'], '2026-11-07 18:00:00+00', [$m['j1'] => 'presente']);

    $asistencia = asistenciaAEntrenamientosPuerto()->delJugadorEnEquipos(Identificador::desdeTexto($m['j1']), [Identificador::desdeTexto($m['a'])]);

    expect(asistenciaDeEntrenamientosComoTexto($asistencia))->toBe(['2026-10' => '33.33', '2026-11' => '100.00'])
        ->and($asistencia->actualizadoEn?->format('Y-m-d H:i:s'))->toBe('2026-11-07 18:00:00');
});

test('HU-25: el mes se cuenta en America/Mexico_City: a las 23:30 del 31 de octubre en México sigue siendo octubre, aunque en UTC ya sea noviembre', function () {
    $m = mundoDeCompetencia();
    entrenamientoConAsistencia($m['a'], $m['l1'], $m['e1'], '2026-11-01 05:30:00+00', [$m['j1'] => 'presente']);
    entrenamientoConAsistencia($m['a'], $m['l1'], $m['e1'], '2026-11-01 06:30:00+00', [$m['j1'] => 'falta']);

    $asistencia = asistenciaAEntrenamientosPuerto()->delJugadorEnEquipos(Identificador::desdeTexto($m['j1']), [Identificador::desdeTexto($m['a'])]);

    expect(asistenciaDeEntrenamientosComoTexto($asistencia))->toBe(['2026-10' => '100.00', '2026-11' => '0.00']);
});

test('HU-25: solo cuentan los entrenamientos realizados y los registros no borrados', function () {
    $m = mundoDeCompetencia();
    entrenamientoConAsistencia($m['a'], $m['l1'], $m['e1'], '2026-10-03 18:00:00+00', [$m['j1'] => 'presente']);
    entrenamientoConAsistencia($m['a'], $m['l1'], $m['e1'], '2026-10-10 18:00:00+00', [$m['j1'] => 'falta'], 'programado');
    entrenamientoConAsistencia($m['a'], $m['l1'], $m['e1'], '2026-10-11 18:00:00+00', [$m['j1'] => 'falta'], 'cancelado');
    $borrado = entrenamientoConAsistencia($m['a'], $m['l1'], $m['e1'], '2026-10-17 18:00:00+00', [$m['j1'] => 'falta']);
    DB::connection('propietario')->table('asistencias_entrenamiento')->where('entrenamiento_id', $borrado)->update(['eliminado_en' => '2026-10-18 10:00:00+00']);

    $asistencia = asistenciaAEntrenamientosPuerto()->delJugadorEnEquipos(Identificador::desdeTexto($m['j1']), [Identificador::desdeTexto($m['a'])]);

    expect(asistenciaDeEntrenamientosComoTexto($asistencia))->toBe(['2026-10' => '100.00']);
});

test('RN-14 (HU-25): el jugador de dos equipos cuenta solo en los equipos pedidos', function () {
    $m = mundoDeCompetencia();
    entrenamientoConAsistencia($m['a'], $m['l1'], $m['e1'], '2026-10-03 18:00:00+00', [$m['j3'] => 'presente']);
    entrenamientoConAsistencia($m['b'], $m['l1'], $m['e2'], '2026-10-10 18:00:00+00', [$m['j3'] => 'falta']);
    $puerto = asistenciaAEntrenamientosPuerto();
    $j3 = Identificador::desdeTexto($m['j3']);
    $a = Identificador::desdeTexto($m['a']);
    $b = Identificador::desdeTexto($m['b']);

    expect(asistenciaDeEntrenamientosComoTexto($puerto->delJugadorEnEquipos($j3, [$a])))->toBe(['2026-10' => '100.00'])
        ->and(asistenciaDeEntrenamientosComoTexto($puerto->delJugadorEnEquipos($j3, [$b])))->toBe(['2026-10' => '0.00'])
        ->and(asistenciaDeEntrenamientosComoTexto($puerto->delJugadorEnEquipos($j3, [$a, $b])))->toBe(['2026-10' => '50.00'])
        ->and($puerto->delJugadorEnEquipos($j3, [])->porcentajes)->toBe([]);
});

test('HU-25 c2: el porcentaje del equipo cuenta los registros de todos sus jugadores', function () {
    $m = mundoDeCompetencia();
    entrenamientoConAsistencia($m['a'], $m['l1'], $m['e1'], '2026-10-03 18:00:00+00', [$m['j1'] => 'presente', $m['j2'] => 'presente', $m['j3'] => 'falta']);
    entrenamientoConAsistencia($m['a'], $m['l1'], $m['e1'], '2026-10-10 18:00:00+00', [$m['j1'] => 'falta', $m['j2'] => 'falta_justificada']);
    entrenamientoConAsistencia($m['b'], $m['l1'], $m['e2'], '2026-10-17 18:00:00+00', [$m['j4'] => 'presente']);

    $puerto = asistenciaAEntrenamientosPuerto();

    // 2 presentes de 5 registros del equipo A; el entrenamiento de B no cuenta.
    expect(asistenciaDeEntrenamientosComoTexto($puerto->delEquipo(Identificador::desdeTexto($m['a']))))->toBe(['2026-10' => '40.00'])
        ->and(asistenciaDeEntrenamientosComoTexto($puerto->delEquipo(Identificador::desdeTexto($m['b']))))->toBe(['2026-10' => '100.00']);
});

test('HU-25: sin ningún registro no hay meses ni fecha de actualización', function () {
    $m = mundoDeCompetencia();
    $puerto = asistenciaAEntrenamientosPuerto();

    $delEquipo = $puerto->delEquipo(Identificador::desdeTexto($m['a']));
    $delJugador = $puerto->delJugadorEnEquipos(Identificador::desdeTexto($m['j1']), [Identificador::desdeTexto($m['a'])]);

    expect($delEquipo->porcentajes)->toBe([])->and($delEquipo->actualizadoEn)->toBeNull()
        ->and($delJugador->porcentajes)->toBe([])->and($delJugador->actualizadoEn)->toBeNull();
});
