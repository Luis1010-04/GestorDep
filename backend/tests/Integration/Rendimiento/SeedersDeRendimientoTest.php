<?php

use Database\Seeders\MetricasSeeder;
use Database\Seeders\PlantillasDeFabricaSeeder;
use Database\Seeders\PosicionesSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    esquemaReiniciar();
    (new PosicionesSeeder)->run();
});

/** @return array<string, array{tipo_dato: string, minimo: string, maximo: string, activa: bool}> */
function seedersMetricas(): array
{
    return DB::table('metricas')->orderBy('codigo')->get()->mapWithKeys(fn (stdClass $fila) => [$fila->codigo => [
        'tipo_dato' => $fila->tipo_dato, 'minimo' => $fila->minimo, 'maximo' => $fila->maximo, 'activa' => $fila->activa,
    ]])->all();
}

/** @return array<string, list<array{0: string, 1: bool, 2: int}>> posición => [código de métrica, obligatoria, orden] */
function seedersMetricasPorPosicion(): array
{
    $filas = DB::table('metricas_posicion')
        ->join('metricas', 'metricas.id', '=', 'metricas_posicion.metrica_id')
        ->join('posiciones', 'posiciones.id', '=', 'metricas_posicion.posicion_id')
        ->orderBy('posiciones.codigo')->orderBy('metricas_posicion.orden')
        ->get(['posiciones.codigo as posicion', 'metricas.codigo as metrica', 'metricas_posicion.obligatoria', 'metricas_posicion.orden']);

    $porPosicion = [];
    foreach ($filas as $fila) {
        $porPosicion[$fila->posicion][] = [$fila->metrica, $fila->obligatoria, $fila->orden];
    }

    return $porPosicion;
}

test('HU-14 (DA-07): MetricasSeeder siembra las nueve métricas enteras, con mínimo 0 y máximo 99, salvo pases completados (300)', function () {
    (new MetricasSeeder)->run();

    $rango = fn (string $maximo) => ['tipo_dato' => 'entero', 'minimo' => '0.00', 'maximo' => $maximo, 'activa' => true];

    expect(seedersMetricas())->toBe([
        'asistencias' => $rango('99.00'),
        'atajadas' => $rango('99.00'),
        'balones_perdidos' => $rango('99.00'),
        'faltas_cometidas' => $rango('99.00'),
        'goles' => $rango('99.00'),
        'goles_recibidos' => $rango('99.00'),
        'pases_completados' => $rango('300.00'),
        'recuperaciones' => $rango('99.00'),
        'tiros_a_porteria' => $rango('99.00'),
    ]);
});

test('HU-14: goles y asistencias son obligatorias en las cuatro posiciones, más las métricas propias de cada una', function () {
    (new MetricasSeeder)->run();

    expect(seedersMetricasPorPosicion())->toBe([
        'defensa' => [['goles', true, 1], ['asistencias', true, 2], ['recuperaciones', true, 3], ['faltas_cometidas', true, 4]],
        'delantero' => [['goles', true, 1], ['asistencias', true, 2], ['tiros_a_porteria', true, 3]],
        'medio' => [['goles', true, 1], ['asistencias', true, 2], ['pases_completados', true, 3], ['balones_perdidos', true, 4]],
        'portero' => [['goles', true, 1], ['asistencias', true, 2], ['atajadas', true, 3], ['goles_recibidos', true, 4]],
    ]);
});

test('RNF-09 (DA-08): MetricasSeeder es idempotente y no deshace un ajuste hecho en la base', function () {
    (new MetricasSeeder)->run();
    $ids = DB::table('metricas')->orderBy('codigo')->pluck('id', 'codigo')->all();
    DB::table('metricas')->where('codigo', 'goles')->update(['maximo' => '50']);
    DB::table('metricas_posicion')->where('metrica_id', $ids['atajadas'])->update(['obligatoria' => false]);

    (new MetricasSeeder)->run();

    expect(DB::table('metricas')->orderBy('codigo')->pluck('id', 'codigo')->all())->toBe($ids)
        ->and(DB::table('metricas_posicion')->count())->toBe(15)
        ->and(DB::table('metricas')->where('codigo', 'goles')->value('maximo'))->toBe('50.00')
        ->and(DB::table('metricas_posicion')->where('metrica_id', $ids['atajadas'])->value('obligatoria'))->toBeFalse();
});

test('RN-07 (AB-01): PlantillasDeFabricaSeeder siembra una plantilla «Desarrollo» de fábrica por posición, con 6.00 y ventana de 5', function () {
    (new PlantillasDeFabricaSeeder)->run();

    $plantillas = DB::table('plantillas_evaluacion')
        ->join('posiciones', 'posiciones.id', '=', 'plantillas_evaluacion.posicion_id')
        ->orderBy('posiciones.codigo')
        ->get([
            'posiciones.codigo', 'plantillas_evaluacion.nombre', 'plantillas_evaluacion.calificacion_minima',
            'plantillas_evaluacion.ventana_partidos', 'plantillas_evaluacion.es_de_fabrica', 'plantillas_evaluacion.creada_por',
        ])
        ->map(fn (stdClass $fila) => (array) $fila)->all();

    $desarrollo = fn (string $posicion) => [
        'codigo' => $posicion, 'nombre' => 'Desarrollo', 'calificacion_minima' => '6.00', 'ventana_partidos' => 5, 'es_de_fabrica' => true, 'creada_por' => null,
    ];

    expect($plantillas)->toBe([$desarrollo('defensa'), $desarrollo('delantero'), $desarrollo('medio'), $desarrollo('portero')]);
});

test('RNF-09 (DA-08): PlantillasDeFabricaSeeder es idempotente y no deshace un ajuste hecho en la base', function () {
    (new PlantillasDeFabricaSeeder)->run();
    $ids = DB::table('plantillas_evaluacion')->orderBy('id')->pluck('id')->all();
    DB::table('plantillas_evaluacion')->where('id', $ids[0])->update(['calificacion_minima' => '6.50']);

    (new PlantillasDeFabricaSeeder)->run();

    expect(DB::table('plantillas_evaluacion')->orderBy('id')->pluck('id')->all())->toBe($ids)
        ->and(DB::table('plantillas_evaluacion')->where('id', $ids[0])->value('calificacion_minima'))->toBe('6.50');
});

test('RNF-09: DatabaseSeeder siembra métricas y plantillas de fábrica, y repetirlo no duplica nada', function () {
    esquemaReiniciar();

    consolaArtisan('db:seed', ['--force' => true])->assertSuccessful();
    consolaArtisan('db:seed', ['--force' => true])->assertSuccessful();

    expect(DB::table('metricas')->count())->toBe(9)
        ->and(DB::table('metricas_posicion')->count())->toBe(15)
        ->and(DB::table('plantillas_evaluacion')->where('es_de_fabrica', true)->count())->toBe(4);
});
