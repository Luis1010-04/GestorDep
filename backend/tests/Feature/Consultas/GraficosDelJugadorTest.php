<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/**
 * HU-25 y §7.7 (docs/hitos/f1-h6.md): los gráficos de historial de un jugador, por rol (DA-97, DA-105). Mundo de partida:
 * `graficosMundo()`; hoy es 2026-11-15.
 */
/**
 * @param  array<string, mixed>  $respuesta  el objeto `datos` de la respuesta
 * @return list<array<string, mixed>> los puntos de una de sus series
 */
function graficosPuntos(array $respuesta, string $serie): array
{
    /** @var array{serie: list<array<string, mixed>>} $objeto */
    $objeto = $respuesta[$serie];

    return $objeto['serie'];
}

test('HU-25 c1 (RN-14, DA-97): el entrenador recibe el rendimiento en partidos, la asistencia a entrenamientos, las mediciones y el resumen académico, solo de lo que él registró', function () {
    /** @var TestCase $this */
    $m = graficosMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    $datos = sesionesPeticion($this, 'GET', graficosJugadorRuta($m['f1']), idDeSesion: $sesion)->assertOk()->json('datos');
    $imc = fn (string $fecha): string => (string) DB::table('mediciones_corporales')->where('jugador_id', $m['f1'])->where('fecha', $fecha)->value('imc');

    expect(array_keys($datos))->toBe(['rendimiento_partidos', 'asistencia_entrenamientos', 'mediciones', 'resumen_academico'])
        ->and($datos['rendimiento_partidos']['serie'])->toBe([['fecha' => '2026-10-10', 'valor' => 7], ['fecha' => '2026-10-24', 'valor' => 8]])
        ->and($datos['rendimiento_partidos']['actualizado_en'])->toBe('2026-11-10T10:00:00+00:00')
        ->and($datos['rendimiento_partidos']['posiblemente_desactualizado'])->toBeFalse()
        ->and($datos['asistencia_entrenamientos']['serie'])->toBe([['periodo' => '2026-10', 'valor' => '33.33'], ['periodo' => '2026-11', 'valor' => '100.00']])
        ->and($datos['asistencia_entrenamientos']['actualizado_en'])->toBe('2026-11-10T18:00:00+00:00')
        ->and(array_keys($datos['mediciones']))->toBe(['peso_kg', 'estatura_cm', 'imc'])
        ->and($datos['mediciones']['peso_kg']['serie'])->toBe([['periodo' => '2026-09', 'valor' => '70.00'], ['periodo' => '2026-10', 'valor' => '71.50']])
        ->and($datos['mediciones']['estatura_cm']['serie'])->toBe([['periodo' => '2026-09', 'valor' => '175.0'], ['periodo' => '2026-10', 'valor' => '175.5']])
        ->and($datos['mediciones']['imc']['serie'])->toBe([['periodo' => '2026-09', 'valor' => $imc('2026-09-05')], ['periodo' => '2026-10', 'valor' => $imc('2026-10-05')]])
        ->and($datos['resumen_academico']['serie'])->toBe([
            ['periodo' => ['id' => $m['periodo_anterior'], 'nombre' => '2026-1'], 'valor' => '7.50'],
            ['periodo' => ['id' => $m['periodo'], 'nombre' => '2026-2'], 'valor' => '8.00'],
        ])
        ->and($datos['resumen_academico']['actualizado_en'])->toBe('2026-11-10T12:00:00+00:00');
});

test('HU-25 c3 (RN-14, §7.7): un jugador en dos equipos con dos entrenadores: la serie de cada uno incluye solo los datos que él registró', function () {
    /** @var TestCase $this */
    $m = graficosMundo();
    $rendimientos = function (string $correo) use ($m): array {
        $datos = sesionesPeticion($this, 'GET', graficosJugadorRuta($m['f1']), idDeSesion: sesionesIniciar($this, $correo))->assertOk()->json('datos');

        return graficosPuntos($datos, 'rendimiento_partidos');
    };

    expect($rendimientos('e1@x.com'))->toBe([['fecha' => '2026-10-10', 'valor' => 7], ['fecha' => '2026-10-24', 'valor' => 8]])
        ->and($rendimientos('e2@x.com'))->toBe([['fecha' => '2026-10-17', 'valor' => 9]])
        ->and($rendimientos('e3@x.com'))->toBe([['fecha' => '2026-10-10', 'valor' => 5], ['fecha' => '2026-10-17', 'valor' => 6]]);
});

test('HU-25 c3 (RN-14): las mediciones y la asistencia del entrenador de otro equipo no salen en la serie de E1, y E2 solo ve las suyas y las de su equipo', function () {
    /** @var TestCase $this */
    $m = graficosMundo();
    $datos = sesionesPeticion($this, 'GET', graficosJugadorRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'e2@x.com'))->assertOk()->json('datos');

    expect(graficosPuntos($datos['mediciones'], 'peso_kg'))->toBe([['periodo' => '2026-10', 'valor' => '90.00']])
        ->and(graficosPuntos($datos, 'asistencia_entrenamientos'))->toBe([['periodo' => '2026-10', 'valor' => '100.00']]);
});

test('HU-25 c1, §7.7 fila 5 (RN-13): el docente recibe las calificaciones y la asistencia a clases de sus materias, y no las mediciones ni el resumen', function () {
    /** @var TestCase $this */
    $m = graficosMundo();
    $sesion = sesionesIniciar($this, 'd1@x.com');

    $datos = sesionesPeticion($this, 'GET', graficosJugadorRuta($m['f1']), idDeSesion: $sesion)->assertOk()->json('datos');

    expect(array_keys($datos))->toBe(['calificaciones', 'asistencia_clases'])
        ->and($datos['calificaciones'])->toHaveCount(1)
        ->and($datos['calificaciones'][0]['materia_id'])->toBe($m['materia'])
        ->and($datos['calificaciones'][0]['asignatura'])->toBe(['clave' => 'MAT-101', 'nombre' => 'Matemáticas'])
        ->and($datos['calificaciones'][0]['serie']['serie'])->toBe([
            ['fecha' => '2026-09-15', 'valor' => '8.00'], ['fecha' => '2026-10-15', 'valor' => '9.00'], ['fecha' => '2026-11-10', 'valor' => '8.50'],
        ])
        ->and($datos['calificaciones'][0]['serie']['actualizado_en'])->toBe('2026-11-10T12:00:00+00:00')
        ->and($datos['asistencia_clases'][0]['materia_id'])->toBe($m['materia'])
        // La asistencia a clases cuenta la falta justificada como presencia (RN-05): (1 + 1) / 3 en octubre.
        ->and($datos['asistencia_clases'][0]['serie']['serie'])->toBe([['periodo' => '2026-10', 'valor' => '66.67'], ['periodo' => '2026-11', 'valor' => '100.00']]);
});

test('HU-25 (RN-13): el docente solo recibe sus materias, no las de otro docente que también tenga al jugador', function () {
    /** @var TestCase $this */
    $m = graficosMundo();
    $otraMateria = mundoInsertar('materias', ['asignatura_id' => mundoInsertar('asignaturas', ['clave' => 'FIS-101', 'nombre' => 'Física', 'grado' => 1]), 'periodo_id' => $m['periodo'], 'docente_id' => $m['d2']]);
    $inscripcion = mundoInscribir($otraMateria, $m['f1']);
    mundoCalificar($inscripcion, 'final', '6.00');

    $deD1 = sesionesPeticion($this, 'GET', graficosJugadorRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'd1@x.com'))->assertOk()->json('datos');
    $deD2 = sesionesPeticion($this, 'GET', graficosJugadorRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'd2@x.com'))->assertOk()->json('datos');

    expect(array_column($deD1['calificaciones'], 'materia_id'))->toBe([$m['materia']])
        ->and(array_column($deD2['calificaciones'], 'materia_id'))->toBe([$otraMateria])
        ->and($deD2['calificaciones'][0]['serie']['serie'])->toBe([['fecha' => '2026-03-10', 'valor' => '6.00']]);
});

test('RNF-04, §7.7 filas 1 y 2 (DD-15): la serie se marca como posiblemente desactualizada tras 30 días sin captura: a los 31, sí; a los 30, no', function () {
    /** @var TestCase $this */
    $m = graficosMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');
    $marca = function (string $ultimaMedicion) use ($m, $sesion): bool {
        DB::connection('propietario')->table('mediciones_corporales')->where('registrado_por', $m['e1'])->update(['actualizado_en' => $ultimaMedicion]);
        $datos = sesionesPeticion($this, 'GET', graficosJugadorRuta($m['f1']), idDeSesion: $sesion)->assertOk()->json('datos');

        return $datos['mediciones']['peso_kg']['posiblemente_desactualizado'];
    };

    // Hoy de negocio: 2026-11-15, en America/Mexico_City.
    expect($marca('2026-10-15 18:00:00+00'))->toBeTrue()
        ->and($marca('2026-10-16 18:00:00+00'))->toBeFalse();
});

test('RNF-09 (DD-15): los días para marcar un dato como desactualizado salen de parametros_institucionales, no del código', function () {
    /** @var TestCase $this */
    $m = graficosMundo();
    DB::connection('propietario')->table('parametros_institucionales')->where('clave', 'desactualizacion')->update(['valor' => json_encode(['dias' => 60])]);
    $sesion = sesionesIniciar($this, 'e1@x.com');

    $datos = sesionesPeticion($this, 'GET', graficosJugadorRuta($m['f1']), idDeSesion: $sesion)->assertOk()->json('datos');

    // La última medición tiene 41 días: con 30 está desactualizada, con 60 no.
    expect($datos['mediciones']['peso_kg']['posiblemente_desactualizado'])->toBeFalse();
});

test('HU-25, RNF-04: una serie sin datos va vacía, con actualizado_en nulo y sin marca de desactualizada', function () {
    /** @var TestCase $this */
    $m = graficosMundo();
    $sesion = sesionesIniciar($this, 'e3@x.com');

    $datos = sesionesPeticion($this, 'GET', graficosJugadorRuta($m['j1']), idDeSesion: $sesion)->assertOk()->json('datos');

    expect($datos['rendimiento_partidos'])->toBe(['serie' => [], 'actualizado_en' => null, 'posiblemente_desactualizado' => false])
        ->and($datos['mediciones']['peso_kg'])->toBe(['serie' => [], 'actualizado_en' => null, 'posiblemente_desactualizado' => false])
        ->and($datos['resumen_academico']['serie'])->toBe([]);
});

test('RN-13 (RN-14): un usuario con los dos roles recibe lo del entrenador porque entrena al jugador, y lo del docente solo de sus materias, vacío si no tiene ninguna con él', function () {
    /** @var TestCase $this */
    $m = graficosMundo();
    $sesion = sesionesIniciar($this, 'de@x.com');

    $datos = sesionesPeticion($this, 'GET', graficosJugadorRuta($m['f1']), idDeSesion: $sesion)->assertOk()->json('datos');

    expect(array_keys($datos))->toBe(['rendimiento_partidos', 'asistencia_entrenamientos', 'mediciones', 'resumen_academico', 'calificaciones', 'asistencia_clases'])
        ->and($datos['rendimiento_partidos']['serie'])->toBe([])
        ->and($datos['calificaciones'])->toBe([])
        ->and($datos['asistencia_clases'])->toBe([]);
});

test('RNF-01, RN-23: un jugador fuera del alcance, en baja o inexistente responde el mismo 404, y el docente sin materia con él también', function () {
    /** @var TestCase $this */
    $m = graficosMundo();
    $inexistente = Identificador::generar()->valor();

    foreach ([['e2@x.com', $m['j1']], ['d1@x.com', $m['j1']], ['d2@x.com', $m['f1']], ['e1@x.com', $m['jb']], ['d1@x.com', $m['jb']]] as [$correo, $jugador]) {
        $sesion = sesionesIniciar($this, $correo);
        $ajeno = sesionesPeticion($this, 'GET', graficosJugadorRuta($jugador), idDeSesion: $sesion)->assertNotFound();
        $referencia = sesionesPeticion($this, 'GET', graficosJugadorRuta($inexistente), idDeSesion: $sesion)->assertNotFound();

        expect($ajeno->getContent())->toBe($referencia->getContent(), "$correo $jugador");
    }
});

test('HU-25: sin sesión responde 401, y con un id que no es un UUID, 404', function () {
    /** @var TestCase $this */
    $m = graficosMundo();

    sesionesPeticion($this, 'GET', graficosJugadorRuta($m['f1']))->assertUnauthorized();
    sesionesPeticion($this, 'GET', '/api/v1/jugadores/no-es-un-uuid/graficos', idDeSesion: sesionesIniciar($this, 'e1@x.com'))->assertNotFound();
});
