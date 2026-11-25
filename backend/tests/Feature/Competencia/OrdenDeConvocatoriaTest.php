<?php

/*
 * Desempate del orden de la convocatoria (docs/hitos/f1-h7.md v0.6 §8, T07; §3.1): `GET /partidos/{partido}/convocatoria`
 * y `GET /partidos/{partido}/convocatoria/vista` ordenan por `creado_en` y después por `id`. Los identificadores se fijan
 * en la prueba y se insertan al revés de su orden, y los apellidos van en otro orden: si la lista saliera por inserción o
 * por apellidos, la prueba fallaría. Mundo: `convocarMundo()` (E1 entrena A, con D1 «Defensa» y M1 «Medio»).
 */

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

test('Orden: con el mismo creado_en, la convocatoria sale por id', function () {
    /** @var TestCase $this */
    $m = convocarMundo();
    $partido = mundoInsertar('partidos', [
        'equipo_id' => $m['a'], 'temporada_id' => (string) DB::table('temporadas')->value('id'), 'rival_id' => (string) DB::table('rivales')->value('id'),
        'lugar_id' => $m['lugar'], 'inicia_en' => '2026-10-10 23:00:00+00', 'tipo' => 'amistoso', 'condicion' => 'local', 'estado' => 'programado',
    ]);
    $primera = '01936b8e-0000-7000-8000-000000000001';
    $segunda = '01936b8e-0000-7000-8000-000000000002';
    foreach ([$segunda => $m['d1'], $primera => $m['m1']] as $id => $jugador) {
        DB::connection('propietario')->table('convocatorias')->insert(['id' => $id, 'partido_id' => $partido, 'jugador_id' => $jugador, 'creado_en' => '2026-10-01 15:00:00+00']);
    }

    expect(convocarPedir($this, "/api/v1/partidos/$partido/convocatoria")->assertOk()->json('datos.*.id'))->toBe([$primera, $segunda])
        ->and(convocarPedir($this, "/api/v1/partidos/$partido/convocatoria/vista")->assertOk()->json('datos.*.jugador.id'))->toBe([$m['m1'], $m['d1']]);
});
