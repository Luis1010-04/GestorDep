<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Captura de rendimiento (HU-14; docs/hitos/f1-h4.md §7.1 filas 1 a 11 y 13, y §7.4 filas 3 y 4).
 * Datos de partida de §7.0: E1 entrena A, E2 entrena B, E3 entrena A y B; P1 portero, D1 defensa, M1 medio y F1
 * delantero en A, F1 también en B; J2 en A sin convocar. PJ1 jugado de A con P1, D1, M1 y F1 convocados; PP1
 * programado de A.
 */
beforeEach(function () {
    sesionesPrepararBase();
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-09-20 15:00:00', new DateTimeZone('UTC'))));
});

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function capturaPeticion(TestCase $prueba, string $metodo, string $ruta, array $cuerpo = [], string $correo = 'e1@x.com'): TestResponse
{
    return sesionesPeticion($prueba, $metodo, '/api/v1'.$ruta, $cuerpo, sesionesIniciar($prueba, $correo));
}

/** @return array{rendimientos: int, valores: int, historial: int, historial_valores: int} */
function capturaFilas(): array
{
    return [
        'rendimientos' => DB::table('rendimientos_partido')->whereNull('eliminado_en')->count(),
        'valores' => DB::table('valores_metrica')->whereNull('eliminado_en')->count(),
        'historial' => DB::table('rendimientos_partido_historial')->count(),
        'historial_valores' => DB::table('valores_metrica_historial')->count(),
    ];
}

/** @param  array<string, string>  $m */
function capturaDeP1(TestCase $prueba, array $m): string
{
    return (string) capturaPeticion($prueba, 'POST', "/partidos/{$m['pj1']}/rendimientos", capturaCuerpo($m['p1'], 'portero'))->assertCreated()->json('datos.id');
}

test('HU-14 (§7.1 fila 1): E1 captura a P1 en PJ1 con sus métricas obligatorias, con historial', function () {
    /** @var TestCase $this */
    $m = capturaMundo();
    $metricas = capturaMetricasDe('portero');

    $respuesta = capturaPeticion($this, 'POST', "/partidos/{$m['pj1']}/rendimientos", capturaCuerpo($m['p1'], 'portero'))->assertCreated();

    $id = (string) $respuesta->json('datos.id');
    expect($respuesta->json('datos'))->toBe([
        'id' => $id, 'partido_id' => $m['pj1'], 'jugador_id' => $m['p1'], 'equipo_id' => $m['a'],
        'posicion_id' => (string) DB::table('posiciones')->where('codigo', 'portero')->value('id'),
        'minutos' => 90, 'fue_titular' => true, 'tarjetas_amarillas' => 0, 'tarjetas_rojas' => 0, 'calificacion_general' => 7,
        'observacion' => 'Buen partido', 'metricas' => $metricas, 'actualizado_en' => '2026-09-20T15:00:00+00:00',
    ])
        ->and(DB::table('rendimientos_partido')->where('id', $id)->value('registrado_por'))->toBe($m['e1'])
        ->and(capturaFilas())->toBe(['rendimientos' => 1, 'valores' => 4, 'historial' => 1, 'historial_valores' => 4])
        ->and(DB::table('rendimientos_partido_historial')->where('rendimiento_id', $id)->value('usuario_id'))->toBe($m['e1']);
});

test('HU-14 (§7.1 fila 2): una segunda captura de P1 en PJ1 da 409 rendimiento_duplicado y no escribe nada', function () {
    /** @var TestCase $this */
    $m = capturaMundo();
    capturaDeP1($this, $m);
    $antes = capturaFilas();

    capturaPeticion($this, 'POST', "/partidos/{$m['pj1']}/rendimientos", capturaCuerpo($m['p1'], 'portero'))
        ->assertStatus(409)
        ->assertJsonPath('codigo', 'rendimiento_duplicado')
        ->assertJsonPath('errores.0.campo', 'jugador_id');

    expect(capturaFilas())->toBe($antes);
});

test('HU-14 (§7.1 fila 3): capturar en un partido programado da 409 partido_no_jugado', function () {
    /** @var TestCase $this */
    $m = capturaMundo();

    capturaPeticion($this, 'POST', "/partidos/{$m['pp1']}/rendimientos", capturaCuerpo($m['p1'], 'portero'))
        ->assertStatus(409)
        ->assertJson(['codigo' => 'partido_no_jugado', 'errores' => []]);

    expect(capturaFilas()['rendimientos'])->toBe(0);
});

test('HU-14 (§7.1 fila 4): capturar a D1 sin recuperaciones da 422 metrica_obligatoria_faltante', function () {
    /** @var TestCase $this */
    $m = capturaMundo();
    $sinRecuperaciones = array_values(array_filter(capturaMetricasDe('defensa'), fn (array $metrica) => $metrica['metrica_id'] !== capturaMetrica('recuperaciones')));

    capturaPeticion($this, 'POST', "/partidos/{$m['pj1']}/rendimientos", capturaCuerpo($m['d1'], 'defensa', ['metricas' => $sinRecuperaciones]))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'metrica_obligatoria_faltante')
        ->assertJsonPath('errores.0.campo', 'metricas');

    expect(capturaFilas()['rendimientos'])->toBe(0);
});

test('HU-14 (§7.1 fila 5): capturar a D1 con tiros a portería da 422 metrica_no_aplica_a_posicion en su índice', function () {
    /** @var TestCase $this */
    $m = capturaMundo();
    $metricas = [...capturaMetricasDe('defensa'), ['metrica_id' => capturaMetrica('tiros_a_porteria'), 'valor' => 2]];

    capturaPeticion($this, 'POST', "/partidos/{$m['pj1']}/rendimientos", capturaCuerpo($m['d1'], 'defensa', ['metricas' => $metricas]))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'metrica_no_aplica_a_posicion')
        ->assertJsonPath('errores.0.campo', 'metricas.4.metrica_id');

    expect(capturaFilas()['rendimientos'])->toBe(0);
});

test('HU-14 (§7.1 fila 6): una calificación de 11 da 422 valor_fuera_de_rango en calificacion_general', function () {
    /** @var TestCase $this */
    $m = capturaMundo();

    capturaPeticion($this, 'POST', "/partidos/{$m['pj1']}/rendimientos", capturaCuerpo($m['f1'], 'delantero', ['calificacion_general' => 11]))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'valor_fuera_de_rango')
        ->assertJsonPath('errores.0.campo', 'calificacion_general');

    expect(capturaFilas()['rendimientos'])->toBe(0);
});

test('HU-14 (§7.1 fila 7): 151 minutos dan 422 valor_fuera_de_rango en minutos', function () {
    /** @var TestCase $this */
    $m = capturaMundo();

    capturaPeticion($this, 'POST', "/partidos/{$m['pj1']}/rendimientos", capturaCuerpo($m['m1'], 'medio', ['minutos' => 151]))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'valor_fuera_de_rango')
        ->assertJsonPath('errores.0.campo', 'minutos');

    expect(capturaFilas()['rendimientos'])->toBe(0);
});

test('HU-14 (§7.1 fila 8): capturar a J2, visible pero no convocado a PJ1, da 422 jugador_no_convocado', function () {
    /** @var TestCase $this */
    $m = capturaMundo();

    capturaPeticion($this, 'POST', "/partidos/{$m['pj1']}/rendimientos", capturaCuerpo($m['j2'], 'medio'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'jugador_no_convocado')
        ->assertJsonPath('errores.0.campo', 'jugador_id');

    expect(capturaFilas()['rendimientos'])->toBe(0);
});

test('HU-14 (§7.1 fila 9): PJ1 aparece en pendientes solo mientras le falte un convocado de A, y solo para sus entrenadores', function () {
    /** @var TestCase $this */
    $m = capturaMundo();
    $pendientes = fn (string $correo = 'e1@x.com') => capturaPeticion($this, 'GET', '/partidos/pendientes-de-rendimiento', [], $correo)->assertOk();

    expect($pendientes()->json('datos'))->toBe([[
        'partido_id' => $m['pj1'], 'equipo_id' => $m['a'], 'inicia_en' => '2026-09-12T23:00:00+00:00', 'convocados_sin_rendimiento' => 4,
    ]]);

    capturaDeP1($this, $m);

    expect($pendientes()->json('datos.0.convocados_sin_rendimiento'))->toBe(3)
        ->and($pendientes('e2@x.com')->json('datos'))->toBe([]);

    foreach (['d1' => 'defensa', 'm1' => 'medio', 'f1' => 'delantero'] as $jugador => $posicion) {
        capturaPeticion($this, 'POST', "/partidos/{$m['pj1']}/rendimientos", capturaCuerpo($m[$jugador], $posicion))->assertCreated();
    }

    expect($pendientes()->json('datos'))->toBe([])
        ->and($pendientes()->json('meta.total'))->toBe(0);
});

test('HU-14 (§7.1 fila 10): E2, sin relación con A, consulta o corrige el rendimiento de E1 y recibe el 404 de uno inexistente', function () {
    /** @var TestCase $this */
    $m = capturaMundo();
    $id = capturaDeP1($this, $m);
    $inexistente = capturaPeticion($this, 'PATCH', '/rendimientos/'.Identificador::generar()->valor(), ['calificacion_general' => 3], 'e2@x.com')->assertNotFound();

    $corregir = capturaPeticion($this, 'PATCH', "/rendimientos/$id", ['calificacion_general' => 3], 'e2@x.com')->assertNotFound();
    capturaPeticion($this, 'GET', "/partidos/{$m['pj1']}/rendimientos", [], 'e2@x.com')->assertNotFound();

    expect($corregir->getContent())->toBe($inexistente->getContent())
        ->and(DB::table('rendimientos_partido')->where('id', $id)->value('calificacion_general'))->toBe(7);
});

test('HU-14 (§7.1 fila 11): E1 corrige la calificación de su rendimiento y el historial guarda el valor anterior', function () {
    /** @var TestCase $this */
    $m = capturaMundo();
    $id = capturaDeP1($this, $m);

    capturaPeticion($this, 'PATCH', "/rendimientos/$id", ['calificacion_general' => 9])
        ->assertOk()
        ->assertJsonPath('datos.calificacion_general', 9)
        ->assertJsonPath('datos.minutos', 90);

    $historial = DB::table('rendimientos_partido_historial')->where('rendimiento_id', $id)->where('operacion', 'actualizar')->first();
    expect(DB::table('rendimientos_partido')->where('id', $id)->value('calificacion_general'))->toBe(9)
        ->and(json_decode($historial->datos_anteriores, true)['calificacion_general'])->toBe(7)
        ->and(json_decode($historial->datos_nuevos, true)['calificacion_general'])->toBe(9)
        ->and($historial->usuario_id)->toBe($m['e1']);
});

test('HU-14 (§7.1 fila 13, RN-14): E3, que entrena A, corrige el rendimiento de E1 y recibe el 404 de uno inexistente', function () {
    /** @var TestCase $this */
    $m = capturaMundo();
    $id = capturaDeP1($this, $m);
    $inexistente = capturaPeticion($this, 'PATCH', '/rendimientos/'.Identificador::generar()->valor(), ['calificacion_general' => 3], 'e3@x.com')->assertNotFound();

    $corregir = capturaPeticion($this, 'PATCH', "/rendimientos/$id", ['calificacion_general' => 3], 'e3@x.com')->assertNotFound();

    expect($corregir->getContent())->toBe($inexistente->getContent())
        ->and(DB::table('rendimientos_partido')->where('id', $id)->value('calificacion_general'))->toBe(7);
});

test('RN-14 (§7.4 fila 3): E3 lista los rendimientos de PJ1 y solo ve los que él registró, no el de E1', function () {
    /** @var TestCase $this */
    $m = capturaMundo();
    capturaDeP1($this, $m);
    $deE3 = (string) capturaPeticion($this, 'POST', "/partidos/{$m['pj1']}/rendimientos", capturaCuerpo($m['d1'], 'defensa'), 'e3@x.com')->assertCreated()->json('datos.id');

    $lista = capturaPeticion($this, 'GET', "/partidos/{$m['pj1']}/rendimientos", [], 'e3@x.com')->assertOk();

    expect(array_column($lista->json('datos'), 'id'))->toBe([$deE3])
        ->and($lista->json('meta.total'))->toBe(1)
        ->and(capturaPeticion($this, 'GET', "/partidos/{$m['pj1']}/rendimientos")->json('meta.total'))->toBe(1);
});

test('RNF-01 (§7.4 fila 4): un UUID válido que no es v7 en {rendimiento} da 404, nunca 500', function () {
    /** @var TestCase $this */
    capturaMundo();

    capturaPeticion($this, 'PATCH', '/rendimientos/0b7a6f2e-3c1d-4e5f-8a9b-1c2d3e4f5a6b', ['calificacion_general' => 3])
        ->assertNotFound()
        ->assertJsonPath('codigo', 'recurso_no_encontrado');
});

test('HU-14: E1 consulta el catálogo de métricas con sus posiciones, sin ver nombres de posición', function () {
    /** @var TestCase $this */
    capturaMundo();

    /** @var list<array<string, mixed>> $metricas */
    $metricas = capturaPeticion($this, 'GET', '/metricas')->assertOk()->json('datos');
    $goles = collect($metricas)->firstWhere('codigo', 'goles');
    $pases = collect($metricas)->firstWhere('codigo', 'pases_completados');

    expect(array_column($metricas, 'codigo'))->toBe(['asistencias', 'atajadas', 'balones_perdidos', 'faltas_cometidas', 'goles', 'goles_recibidos', 'pases_completados', 'recuperaciones', 'tiros_a_porteria'])
        ->and($goles['tipo_dato'])->toBe('entero')
        ->and($goles['minimo'])->toBe(0)
        ->and($goles['maximo'])->toBe(99)
        ->and($pases['maximo'])->toBe(300)
        ->and(count($goles['posiciones']))->toBe(4)
        ->and(array_keys($goles['posiciones'][0]))->toBe(['posicion_id', 'obligatoria', 'orden']);
});

test('HU-14: un docente recibe 403 en las rutas de captura, y sin sesión 401', function () {
    /** @var TestCase $this */
    $m = capturaMundo();
    $rutas = [
        ['GET', '/metricas'], ['GET', '/partidos/pendientes-de-rendimiento'], ['GET', "/partidos/{$m['pj1']}/rendimientos"],
        ['POST', "/partidos/{$m['pj1']}/rendimientos"], ['PATCH', '/rendimientos/'.Identificador::generar()->valor()],
    ];

    foreach ($rutas as [$metodo, $ruta]) {
        capturaPeticion($this, $metodo, $ruta, [], 'd@x.com')->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
        sesionesPeticion($this, $metodo, '/api/v1'.$ruta)->assertUnauthorized();
    }
});

test('HU-14 (DA-11): un jugador fuera del alcance de E1 da el 404 de uno inexistente, antes que jugador_no_convocado', function () {
    /** @var TestCase $this */
    $m = capturaMundo();
    $deB = mundoJugador('M-B', 'Ajeno', [$m['b'] => 'medio']);
    $ruta = "/partidos/{$m['pj1']}/rendimientos";

    $ajeno = capturaPeticion($this, 'POST', $ruta, capturaCuerpo($deB, 'medio'))->assertNotFound();
    $inexistente = capturaPeticion($this, 'POST', $ruta, capturaCuerpo(Identificador::generar()->valor(), 'medio'))->assertNotFound();

    expect($ajeno->getContent())->toBe($inexistente->getContent());
});

test('HU-14 (DA-11): un partido ajeno da 404 antes de validar el cuerpo', function () {
    /** @var TestCase $this */
    $m = capturaMundo();

    capturaPeticion($this, 'POST', "/partidos/{$m['pj1']}/rendimientos", [], 'e2@x.com')->assertNotFound();
});

test('HU-14 (DA-11): tarjetas, observación y valor de métrica fuera de rango dan valor_fuera_de_rango en su campo', function (array $cambios, string $campo) {
    /** @var TestCase $this */
    $m = capturaMundo();
    $cuerpo = capturaCuerpo($m['p1'], 'portero', $cambios);
    if ($campo === 'metricas.0.valor') {
        $cuerpo['metricas'][0]['valor'] = 100;
    }

    capturaPeticion($this, 'POST', "/partidos/{$m['pj1']}/rendimientos", $cuerpo)
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'valor_fuera_de_rango')
        ->assertJsonPath('errores.0.campo', $campo);
})->with([
    'tres amarillas' => [['tarjetas_amarillas' => 3], 'tarjetas_amarillas'],
    'dos rojas' => [['tarjetas_rojas' => 2], 'tarjetas_rojas'],
    'calificación 0' => [['calificacion_general' => 0], 'calificacion_general'],
    'minutos negativos' => [['minutos' => -1], 'minutos'],
    'observación de 501' => [['observacion' => str_repeat('a', 501)], 'observacion'],
    'goles 100' => [[], 'metricas.0.valor'],
]);

test('HU-14: la corrección reemplaza la lista de métricas y escribe el historial de cada valor', function () {
    /** @var TestCase $this */
    $m = capturaMundo();
    $id = capturaDeP1($this, $m);
    $metricas = capturaMetricasDe('portero');
    $metricas[2]['valor'] = 6;

    capturaPeticion($this, 'PATCH', "/rendimientos/$id", ['metricas' => $metricas])
        ->assertOk()
        ->assertJsonPath('datos.metricas.2.valor', 6);

    expect(DB::table('valores_metrica')->whereNull('eliminado_en')->where('rendimiento_id', $id)->count())->toBe(4)
        ->and(DB::table('valores_metrica_historial')->where('operacion', 'actualizar')->count())->toBe(1);

    capturaPeticion($this, 'PATCH', "/rendimientos/$id", ['metricas' => array_slice($metricas, 0, 3)])
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'metrica_obligatoria_faltante');
});

test('HU-14: la corrección rechaza un cuerpo vacío, jugador_id y un campo desconocido', function () {
    /** @var TestCase $this */
    $m = capturaMundo();
    $id = capturaDeP1($this, $m);

    capturaPeticion($this, 'PATCH', "/rendimientos/$id", [])->assertUnprocessable()->assertJsonPath('errores.0.campo', 'cuerpo');
    capturaPeticion($this, 'PATCH', "/rendimientos/$id", ['jugador_id' => $m['d1']])->assertUnprocessable()->assertJsonPath('errores.0.codigo', 'prohibited');
    capturaPeticion($this, 'PATCH', "/rendimientos/$id", ['minutos' => 151])->assertUnprocessable()->assertJsonPath('errores.0.campo', 'minutos');
});

test('HU-14: la captura valida tipos y rechaza campos desconocidos o métricas repetidas', function (array $cambios, string $campo) {
    /** @var TestCase $this */
    $m = capturaMundo();

    capturaPeticion($this, 'POST', "/partidos/{$m['pj1']}/rendimientos", capturaCuerpo($m['p1'], 'portero', $cambios))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', $campo);
})->with([
    'minutos como texto' => [['minutos' => '90'], 'minutos'],
    'titular como número' => [['fue_titular' => 1], 'fue_titular'],
    'campo desconocido' => [['goles' => 1], 'goles'],
    'métrica repetida' => [['metricas' => [['metrica_id' => '01a18cc6-821c-79f4-a0f5-780d70083654', 'valor' => 1], ['metrica_id' => '01a18cc6-821c-79f4-a0f5-780d70083654', 'valor' => 1]]], 'metricas.0.metrica_id'],
]);

test('HU-14 (DA-11): un jugador_id o un metrica_id con UUID válido que no es v7 no da 500', function () {
    /** @var TestCase $this */
    $m = capturaMundo();
    $ruta = "/partidos/{$m['pj1']}/rendimientos";
    $noV7 = '0b7a6f2e-3c1d-4e5f-8a9b-1c2d3e4f5a6b';
    $metricas = capturaMetricasDe('portero');
    $metricas[1]['metrica_id'] = $noV7;

    capturaPeticion($this, 'POST', $ruta, capturaCuerpo($noV7, 'portero'))->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');
    capturaPeticion($this, 'POST', $ruta, capturaCuerpo($m['p1'], 'portero', ['metricas' => $metricas]))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'metrica_no_aplica_a_posicion')
        ->assertJsonPath('errores.0.campo', 'metricas.1.metrica_id');
});

test('RNF-02: corregir quitando y volviendo a poner una métrica opcional borra lógicamente el valor y lo crea de nuevo, con historial', function () {
    /** @var TestCase $this */
    $m = capturaMundo();
    $portero = (string) DB::table('posiciones')->where('codigo', 'portero')->value('id');
    DB::connection('propietario')->table('metricas_posicion')->where('posicion_id', $portero)->where('metrica_id', capturaMetrica('goles_recibidos'))->update(['obligatoria' => false]);
    $id = capturaDeP1($this, $m);
    $completas = capturaMetricasDe('portero');

    capturaPeticion($this, 'PATCH', "/rendimientos/$id", ['metricas' => array_slice($completas, 0, 3)])->assertOk()->assertJsonCount(3, 'datos.metricas');

    $eliminado = DB::table('valores_metrica')->where('rendimiento_id', $id)->where('metrica_id', capturaMetrica('goles_recibidos'))->first();
    expect($eliminado->eliminado_en)->not->toBeNull()
        ->and($eliminado->eliminado_por)->toBe($m['e1'])
        ->and(DB::table('valores_metrica_historial')->where('valor_metrica_id', $eliminado->id)->where('operacion', 'eliminar')->count())->toBe(1);

    capturaPeticion($this, 'PATCH', "/rendimientos/$id", ['metricas' => $completas])->assertOk()->assertJsonCount(4, 'datos.metricas');

    expect(DB::table('valores_metrica')->whereNull('eliminado_en')->where('rendimiento_id', $id)->count())->toBe(4)
        ->and(DB::table('valores_metrica_historial')->where('operacion', 'crear')->count())->toBe(5);
});
