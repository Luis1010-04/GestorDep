<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/** Convocatoria (HU-12, HU-29 criterio 3; docs/hitos/f1-h3.md §7.5 filas 1, 2, 3, 9 y 10, y §7.6 fila 3). */
beforeEach(function () {
    sesionesPrepararBase();
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-09-05 15:00:00', new DateTimeZone('UTC'))));
});

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function convocatoriaPeticion(TestCase $prueba, string $metodo, string $ruta, array $cuerpo = [], string $correo = 'e1@x.com'): TestResponse
{
    return sesionesPeticion($prueba, $metodo, '/api/v1'.$ruta, $cuerpo, sesionesIniciar($prueba, $correo));
}

/**
 * Partido programado de un equipo, en T1.
 *
 * @param  array<string, string>  $m
 * @param  array<string, mixed>  $atributos
 */
function convocatoriaPartido(array $m, string $equipo = 'a', array $atributos = []): string
{
    return mundoInsertar('partidos', [
        'equipo_id' => $m[$equipo], 'temporada_id' => $m['t1'], 'torneo_id' => null, 'rival_id' => $m['r1'],
        'lugar_id' => $m['l1'], 'inicia_en' => '2026-09-12 23:00:00+00', 'tipo' => 'amistoso', 'condicion' => 'local',
        'estado' => 'programado', ...$atributos,
    ]);
}

/** @param  array<string, mixed>  $atributos */
function convocatoriaDe(string $partido, string $jugador, array $atributos = []): string
{
    return mundoInsertar('convocatorias', ['partido_id' => $partido, 'jugador_id' => $jugador, ...$atributos]);
}

/** @return array{convocatorias: int, historial: int} */
function convocatoriaFilas(): array
{
    return [
        'convocatorias' => DB::table('convocatorias')->whereNull('eliminado_en')->count(),
        'historial' => DB::table('convocatorias_historial')->count(),
    ];
}

test('HU-12 (§7.5 fila 1): E1 convoca a J1 a un partido programado de A', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = convocatoriaPartido($m);

    $respuesta = convocatoriaPeticion($this, 'POST', "/partidos/$partido/convocatoria", ['jugador_id' => $m['j1']])->assertCreated();

    $id = $respuesta->json('datos.id');
    expect($respuesta->json())->toBe(['datos' => [
        'id' => $id, 'partido_id' => $partido, 'jugador_id' => $m['j1'], 'posicion_id' => null, 'rol_alineacion' => null,
        'actualizado_en' => '2026-09-05T15:00:00+00:00',
    ]]);
    $historial = DB::table('convocatorias_historial')->where('convocatoria_id', $id)->get();
    expect($historial)->toHaveCount(1)
        ->and($historial[0]->operacion)->toBe('crear')
        ->and($historial[0]->usuario_id)->toBe($m['e1'])
        ->and(json_decode($historial[0]->datos_nuevos, true))->toEqual(['partido_id' => $partido, 'jugador_id' => $m['j1'], 'posicion_id' => null, 'rol_alineacion' => null]);
});

test('HU-12 (§7.5 fila 2): convocar de nuevo a J1 da 409 jugador_ya_convocado en jugador_id', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = convocatoriaPartido($m);
    convocatoriaDe($partido, $m['j1']);

    convocatoriaPeticion($this, 'POST', "/partidos/$partido/convocatoria", ['jugador_id' => $m['j1']])
        ->assertStatus(409)
        ->assertExactJson([
            'codigo' => 'jugador_ya_convocado', 'mensaje' => 'El jugador ya está convocado al partido.',
            'errores' => [['campo' => 'jugador_id', 'codigo' => 'jugador_ya_convocado', 'mensaje' => 'El jugador ya está convocado al partido.']],
        ]);
    expect(convocatoriaFilas())->toBe(['convocatorias' => 1, 'historial' => 0]);
});

test('HU-12 (§7.5 fila 3): convocar a J5 (baja) da el mismo 404 que un jugador inexistente', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = convocatoriaPartido($m);
    $ruta = "/partidos/$partido/convocatoria";

    $baja = convocatoriaPeticion($this, 'POST', $ruta, ['jugador_id' => $m['j5']])->assertNotFound();
    $inexistente = convocatoriaPeticion($this, 'POST', $ruta, ['jugador_id' => Identificador::generar()->valor()])->assertNotFound();

    expect($baja->getContent())->toBe($inexistente->getContent())
        ->and($baja->json('codigo'))->toBe('recurso_no_encontrado')
        ->and(convocatoriaFilas())->toBe(['convocatorias' => 0, 'historial' => 0]);
});

test('HU-12 (DA-28): un jugador de otro equipo, con la membresía cerrada o con UUID v4 da 404, también para E3', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = convocatoriaPartido($m);
    $salio = mundoJugador('M-6', 'Seis', nombres: 'J6');
    mundoMembresia($salio, $m['a'], 'medio', '2026-06-30');
    $esperado = convocatoriaPeticion($this, 'POST', "/partidos/$partido/convocatoria", ['jugador_id' => Identificador::generar()->valor()], 'e3@x.com')->getContent();

    foreach ([$m['j4'], $salio, '123e4567-e89b-42d3-a456-426614174000'] as $jugador) {
        $respuesta = convocatoriaPeticion($this, 'POST', "/partidos/$partido/convocatoria", ['jugador_id' => $jugador], 'e3@x.com')->assertNotFound();
        expect($respuesta->getContent())->toBe($esperado);
    }
    convocatoriaPeticion($this, 'POST', "/partidos/$partido/convocatoria", ['jugador_id' => $m['j3']], 'e3@x.com')->assertCreated();
    expect(convocatoriaFilas())->toBe(['convocatorias' => 1, 'historial' => 1]);
});

test('HU-29 (§7.5 fila 9): J5, dado de baja después de convocarlo, no aparece en la convocatoria; J1 sí', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = convocatoriaPartido($m);
    $j1 = convocatoriaDe($partido, $m['j1']);
    convocatoriaDe($partido, $m['j5']);

    $respuesta = convocatoriaPeticion($this, 'GET', "/partidos/$partido/convocatoria")->assertOk();

    expect($respuesta->json('datos.*.id'))->toBe([$j1])
        ->and($respuesta->json('datos.0.jugador_id'))->toBe($m['j1'])
        ->and($respuesta->json('meta.total'))->toBe(1);
});

test('HU-12 (§7.5 fila 10): E1 quita a J1 de la convocatoria; el historial registra el borrado y su alineación desaparece', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = convocatoriaPartido($m);
    $posicion = (string) DB::table('posiciones')->where('codigo', 'defensa')->value('id');
    $id = convocatoriaDe($partido, $m['j1'], ['posicion_id' => $posicion, 'rol_alineacion' => 'titular']);

    convocatoriaPeticion($this, 'DELETE', "/convocatorias/$id")->assertNoContent();

    $fila = DB::table('convocatorias')->where('id', $id)->first();
    $historial = DB::table('convocatorias_historial')->where('convocatoria_id', $id)->get();
    expect($fila->eliminado_en)->toBe('2026-09-05 15:00:00+00')
        ->and($fila->eliminado_por)->toBe($m['e1'])
        ->and($historial)->toHaveCount(1)
        ->and($historial[0]->operacion)->toBe('eliminar')
        ->and($historial[0]->usuario_id)->toBe($m['e1'])
        ->and(json_decode($historial[0]->datos_anteriores, true))->toMatchArray(['jugador_id' => $m['j1'], 'posicion_id' => $posicion, 'rol_alineacion' => 'titular'])
        ->and($historial[0]->datos_nuevos)->toBeNull()
        ->and(convocatoriaPeticion($this, 'GET', "/partidos/$partido/convocatoria")->json('datos'))->toBe([]);

    $nueva = convocatoriaPeticion($this, 'POST', "/partidos/$partido/convocatoria", ['jugador_id' => $m['j1']])->assertCreated();
    expect($nueva->json('datos.posicion_id'))->toBeNull()->and($nueva->json('datos.rol_alineacion'))->toBeNull();
    convocatoriaPeticion($this, 'DELETE', "/convocatorias/$id")->assertNotFound();
});

test('HU-12: la convocatoria lista en orden de convocatoria, excluye las eliminadas y la ve E3', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = convocatoriaPartido($m);
    $primera = convocatoriaPeticion($this, 'POST', "/partidos/$partido/convocatoria", ['jugador_id' => $m['j2']])->json('datos.id');
    $segunda = convocatoriaPeticion($this, 'POST', "/partidos/$partido/convocatoria", ['jugador_id' => $m['j1']])->json('datos.id');
    convocatoriaDe($partido, $m['j3'], ['eliminado_en' => '2026-09-04 10:00:00+00']);
    convocatoriaDe(convocatoriaPartido($m, atributos: ['inicia_en' => '2026-09-19 23:00:00+00']), $m['j3']);

    expect(convocatoriaPeticion($this, 'GET', "/partidos/$partido/convocatoria", correo: 'e3@x.com')->assertOk()->json('datos.*.id'))->toBe([$primera, $segunda])
        ->and(convocatoriaPeticion($this, 'GET', "/partidos/$partido/convocatoria?por_pagina=1&pagina=2")->json('datos.*.id'))->toBe([$segunda]);
    convocatoriaPeticion($this, 'GET', "/partidos/$partido/convocatoria?por_pagina=101")->assertUnprocessable();
});

test('HU-12 (§3.4): en un partido cancelado o jugado no se convoca ni se quita a nadie', function (array $atributos) {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = convocatoriaPartido($m, atributos: $atributos);
    $id = convocatoriaDe($partido, $m['j1']);

    convocatoriaPeticion($this, 'POST', "/partidos/$partido/convocatoria", ['jugador_id' => $m['j2']])
        ->assertStatus(409)->assertExactJson(['codigo' => 'partido_no_programado', 'mensaje' => 'El partido ya no está programado.', 'errores' => []]);
    convocatoriaPeticion($this, 'DELETE', "/convocatorias/$id")->assertStatus(409)->assertJsonPath('codigo', 'partido_no_programado');
    expect(convocatoriaFilas())->toBe(['convocatorias' => 1, 'historial' => 0]);
})->with([
    'cancelado' => [['estado' => 'cancelado']],
    'jugado' => [['estado' => 'jugado', 'goles_favor' => 1, 'goles_contra' => 0]],
]);

test('HU-12: un jugador_id faltante, que no es UUID o un campo no admitido dan 422', function (array $cuerpo, string $campo) {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();

    convocatoriaPeticion($this, 'POST', '/partidos/'.convocatoriaPartido($m).'/convocatoria', $cuerpo)
        ->assertUnprocessable()->assertJsonPath('codigo', 'validacion_fallida')->assertJsonPath('errores.0.campo', $campo);
    expect(convocatoriaFilas())->toBe(['convocatorias' => 0, 'historial' => 0]);
})->with([
    'vacío' => [[], 'jugador_id'],
    'no uuid' => [['jugador_id' => 'x'], 'jugador_id'],
    'posición' => [['jugador_id' => '01929f3c-0000-7000-8000-000000000000', 'rol_alineacion' => 'titular'], 'rol_alineacion'],
]);

test('RNF-01 (§7.6 fila 3): E2 intenta convocar a J1 a un partido de A y recibe 404', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = convocatoriaPartido($m);

    $ajeno = convocatoriaPeticion($this, 'POST', "/partidos/$partido/convocatoria", ['jugador_id' => $m['j1']], 'e2@x.com')->assertNotFound();
    $inexistente = convocatoriaPeticion($this, 'POST', '/partidos/'.Identificador::generar()->valor().'/convocatoria', ['jugador_id' => $m['j1']], 'e2@x.com')->assertNotFound();

    expect($ajeno->getContent())->toBe($inexistente->getContent())
        ->and(convocatoriaFilas())->toBe(['convocatorias' => 0, 'historial' => 0]);
});

test('RNF-01: la convocatoria de un equipo ajeno da 404 byte a byte por cualquier método y cuerpo, antes que el 422 y el 409, sin cambios', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = convocatoriaPartido($m);
    $cancelado = convocatoriaPartido($m, atributos: ['estado' => 'cancelado', 'inicia_en' => '2026-09-19 23:00:00+00']);
    $id = convocatoriaDe($partido, $m['j3']);
    $otro = Identificador::generar()->valor();
    $pares = [
        ['GET', "/partidos/$partido/convocatoria", "/partidos/$otro/convocatoria", []],
        ['GET', "/partidos/$partido/convocatoria?por_pagina=0", "/partidos/$otro/convocatoria?por_pagina=0", []],
        ['POST', "/partidos/$partido/convocatoria", "/partidos/$otro/convocatoria", ['jugador_id' => $m['j3']]],
        ['POST', "/partidos/$partido/convocatoria", "/partidos/$otro/convocatoria", ['jugador_id' => 'x', 'otro' => 1]],
        ['POST', "/partidos/$cancelado/convocatoria", "/partidos/$otro/convocatoria", ['jugador_id' => $m['j3']]],
        ['DELETE', "/convocatorias/$id", "/convocatorias/$otro", []],
        ['DELETE', "/convocatorias/$id", '/convocatorias/123e4567-e89b-42d3-a456-426614174000', []],
    ];

    foreach ($pares as [$metodo, $ajena, $inexistente, $cuerpo]) {
        $respuestaAjena = convocatoriaPeticion($this, $metodo, $ajena, $cuerpo, 'e2@x.com')->assertNotFound();
        expect($respuestaAjena->getContent())->toBe(convocatoriaPeticion($this, $metodo, $inexistente, $cuerpo, 'e2@x.com')->assertNotFound()->getContent(), "$metodo $ajena");
    }
    expect(convocatoriaFilas())->toBe(['convocatorias' => 1, 'historial' => 0]);
});

test('HU-12: un docente recibe 403 y sin sesión 401 en las rutas de convocatoria', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = convocatoriaPartido($m);
    $id = convocatoriaDe($partido, $m['j1']);
    $rutas = [['GET', "/partidos/$partido/convocatoria"], ['POST', "/partidos/$partido/convocatoria"], ['DELETE', "/convocatorias/$id"]];

    foreach ($rutas as [$metodo, $ruta]) {
        convocatoriaPeticion($this, $metodo, $ruta, ['jugador_id' => $m['j2']], 'd@x.com')->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
        sesionesPeticion($this, $metodo, '/api/v1'.$ruta, ['jugador_id' => $m['j2']])->assertUnauthorized();
    }
});
