<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Aplicacion\AsistenciaPorMes;
use Src\Compartido\Aplicacion\PorcentajeDeAsistenciaAClases;
use Src\Compartido\Dominio\Identificador;

/**
 * DA-109, P-14 (HU-25): el puerto de Compartido que da el porcentaje de asistencia a clases por mes de un jugador en una
 * materia, con la regla de Académico (RN-05: la falta justificada cuenta como presencia).
 */
function asistenciaAClasesDe(string $jugador, string $materia): AsistenciaPorMes
{
    return app(PorcentajeDeAsistenciaAClases::class)->delJugadorEnLaMateria(Identificador::desdeTexto($jugador), Identificador::desdeTexto($materia));
}

/** @return array<string, string> */
function asistenciaPorcentajes(AsistenciaPorMes $asistencia): array
{
    return array_map(fn ($porcentaje): string => $porcentaje->comoCadena(2), $asistencia->porcentajes);
}

test('HU-25 (RN-05): el porcentaje de cada mes cuenta presentes y faltas justificadas sobre todos los registros, y los meses salen en orden', function () {
    $m = mundoDeDocentes();
    mundoAsistir($m['i1'], '2026-11-03', 'presente');
    mundoAsistir($m['i1'], '2026-10-05', 'presente');
    mundoAsistir($m['i1'], '2026-10-06', 'falta_justificada');
    mundoAsistir($m['i1'], '2026-10-07', 'falta');
    mundoAsistir($m['i1'], '2026-09-30', 'falta');

    expect(asistenciaPorcentajes(asistenciaAClasesDe($m['j1'], $m['m1'])))->toBe(['2026-09' => '0.00', '2026-10' => '66.67', '2026-11' => '100.00']);
});

test('HU-25: solo cuenta la materia pedida y el jugador pedido, no otras materias ni otros jugadores', function () {
    $m = mundoDeDocentes();
    mundoAsistir($m['i3a'], '2026-10-05', 'presente');
    mundoAsistir($m['i3b'], '2026-10-05', 'falta');
    mundoAsistir($m['i1'], '2026-10-05', 'falta');

    expect(asistenciaPorcentajes(asistenciaAClasesDe($m['j3'], $m['m1'])))->toBe(['2026-10' => '100.00'])
        ->and(asistenciaPorcentajes(asistenciaAClasesDe($m['j3'], $m['m2'])))->toBe(['2026-10' => '0.00']);
});

test('HU-25: un registro borrado no cuenta', function () {
    $m = mundoDeDocentes();
    mundoAsistir($m['i1'], '2026-10-05', 'presente');
    $borrado = mundoAsistir($m['i1'], '2026-10-06', 'falta');
    DB::connection('propietario')->table('asistencias_clase')->where('id', $borrado)->update(['eliminado_en' => '2026-10-07 10:00:00+00']);

    expect(asistenciaPorcentajes(asistenciaAClasesDe($m['j1'], $m['m1'])))->toBe(['2026-10' => '100.00']);
});

test('RNF-04: actualizado_en es la última actualización de los registros contados', function () {
    $m = mundoDeDocentes();
    $primero = mundoAsistir($m['i1'], '2026-10-05', 'presente');
    $segundo = mundoAsistir($m['i1'], '2026-11-03', 'presente');
    DB::connection('propietario')->table('asistencias_clase')->where('id', $primero)->update(['actualizado_en' => '2026-10-05 12:00:00+00']);
    DB::connection('propietario')->table('asistencias_clase')->where('id', $segundo)->update(['actualizado_en' => '2026-11-03 12:30:00+00']);

    $asistencia = asistenciaAClasesDe($m['j1'], $m['m1']);

    expect($asistencia->actualizadoEn?->format('Y-m-d H:i:s'))->toBe('2026-11-03 12:30:00')
        ->and($asistencia->actualizadoEn?->getTimezone()->getName())->toBe('UTC');
});

test('HU-25: sin ningún registro no hay meses ni fecha de actualización', function () {
    $m = mundoDeDocentes();

    $asistencia = asistenciaAClasesDe($m['j1'], $m['m1']);

    expect($asistencia->porcentajes)->toBe([])->and($asistencia->actualizadoEn)->toBeNull();
});
