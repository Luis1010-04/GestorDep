<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/** Alineación y resultado (HU-13; docs/hitos/f1-h3.md §7.5 filas 4 a 8 y §7.4 fila 6). */
beforeEach(function () {
    sesionesPrepararBase();
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-09-13 01:00:00', new DateTimeZone('UTC'))));
});

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function alineacionPeticion(TestCase $prueba, string $metodo, string $ruta, array $cuerpo = [], string $correo = 'e1@x.com'): TestResponse
{
    return sesionesPeticion($prueba, $metodo, '/api/v1'.$ruta, $cuerpo, sesionesIniciar($prueba, $correo));
}

/**
 * @param  array<string, string>  $m
 * @param  array<string, mixed>  $atributos
 */
function alineacionPartido(array $m, string $equipo = 'a', array $atributos = []): string
{
    return mundoInsertar('partidos', [
        'equipo_id' => $m[$equipo], 'temporada_id' => $m['t1'], 'rival_id' => $m['r1'], 'lugar_id' => $m['l1'],
        'inicia_en' => '2026-09-12 23:00:00+00', 'tipo' => 'amistoso', 'condicion' => 'local', 'estado' => 'programado', ...$atributos,
    ]);
}

function alineacionPosicion(string $codigo): string
{
    return (string) DB::table('posiciones')->where('codigo', $codigo)->value('id');
}

/** @param  array<string, mixed>  $atributos */
function alineacionConvocar(string $partido, string $jugador, array $atributos = []): string
{
    return mundoInsertar('convocatorias', ['partido_id' => $partido, 'jugador_id' => $jugador, ...$atributos]);
}

test('HU-13 (§7.5 fila 4): con J1 convocado, E1 lo define titular en la posición defensa', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = alineacionPartido($m);
    $convocatoria = alineacionConvocar($partido, $m['j1']);
    $defensa = alineacionPosicion('defensa');

    $respuesta = alineacionPeticion($this, 'PUT', "/partidos/$partido/alineacion", ['alineacion' => [
        ['jugador_id' => $m['j1'], 'posicion_id' => $defensa, 'rol_alineacion' => 'titular'],
    ]])->assertOk();

    expect($respuesta->json('datos'))->toBe([[
        'id' => $convocatoria, 'partido_id' => $partido, 'jugador_id' => $m['j1'], 'posicion_id' => $defensa,
        'rol_alineacion' => 'titular', 'actualizado_en' => '2026-09-13T01:00:00+00:00',
    ]]);
    $fila = DB::table('convocatorias_historial')->where('convocatoria_id', $convocatoria)->first();
    expect($fila->operacion)->toBe('actualizar')
        ->and($fila->usuario_id)->toBe($m['e1'])
        ->and(json_decode($fila->datos_anteriores, true))->toMatchArray(['posicion_id' => null, 'rol_alineacion' => null])
        ->and(json_decode($fila->datos_nuevos, true))->toMatchArray(['posicion_id' => $defensa, 'rol_alineacion' => 'titular']);
});

test('HU-13 (§7.5 fila 5): incluir a J2, que no está convocado, da 422 jugador_no_convocado en jugador_id y no cambia nada', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = alineacionPartido($m);
    alineacionConvocar($partido, $m['j1']);

    alineacionPeticion($this, 'PUT', "/partidos/$partido/alineacion", ['alineacion' => [
        ['jugador_id' => $m['j1'], 'posicion_id' => alineacionPosicion('defensa'), 'rol_alineacion' => 'titular'],
        ['jugador_id' => $m['j2'], 'posicion_id' => alineacionPosicion('medio'), 'rol_alineacion' => 'suplente'],
    ]])
        ->assertUnprocessable()
        ->assertExactJson([
            'codigo' => 'jugador_no_convocado', 'mensaje' => 'El jugador no está convocado al partido.',
            'errores' => [['campo' => 'alineacion.1.jugador_id', 'codigo' => 'jugador_no_convocado', 'mensaje' => 'El jugador no está convocado al partido.']],
        ]);
    expect(DB::table('convocatorias')->whereNotNull('rol_alineacion')->count())->toBe(0)
        ->and(DB::table('convocatorias_historial')->count())->toBe(0);
});

test('HU-13 (§7.5 fila 6): cambiar la alineación de un partido jugado da 409 partido_no_programado', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = alineacionPartido($m, atributos: ['estado' => 'jugado', 'goles_favor' => 2, 'goles_contra' => 0]);
    alineacionConvocar($partido, $m['j1'], ['posicion_id' => alineacionPosicion('defensa'), 'rol_alineacion' => 'titular']);

    alineacionPeticion($this, 'PUT', "/partidos/$partido/alineacion", ['alineacion' => [
        ['jugador_id' => $m['j1'], 'posicion_id' => alineacionPosicion('medio'), 'rol_alineacion' => 'suplente'],
    ]])
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'partido_no_programado', 'mensaje' => 'El partido ya no está programado.', 'errores' => []]);
    expect(DB::table('convocatorias')->value('rol_alineacion'))->toBe('titular');
});

test('HU-13 (§7.5 fila 7): E1 captura el resultado 3-1 de un partido programado y queda jugado', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = alineacionPartido($m);

    $respuesta = alineacionPeticion($this, 'PUT', "/partidos/$partido/resultado", ['goles_favor' => 3, 'goles_contra' => 1])->assertOk();

    expect($respuesta->json('datos'))->toMatchArray([
        'id' => $partido, 'estado' => 'jugado', 'goles_favor' => 3, 'goles_contra' => 1, 'actualizado_en' => '2026-09-13T01:00:00+00:00',
    ]);
    $fila = DB::table('partidos_historial')->where('partido_id', $partido)->first();
    expect($fila->operacion)->toBe('actualizar')
        ->and($fila->usuario_id)->toBe($m['e1'])
        ->and(json_decode($fila->datos_anteriores, true))->toMatchArray(['estado' => 'programado', 'goles_favor' => null])
        ->and(json_decode($fila->datos_nuevos, true))->toMatchArray(['estado' => 'jugado', 'goles_favor' => 3, 'goles_contra' => 1]);
});

test('HU-13 (§7.5 fila 8): goles de 100 dan 422 valor_fuera_de_rango en goles_favor y no cambian el partido', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = alineacionPartido($m);

    alineacionPeticion($this, 'PUT', "/partidos/$partido/resultado", ['goles_favor' => 100, 'goles_contra' => 1])
        ->assertUnprocessable()
        ->assertExactJson([
            'codigo' => 'valor_fuera_de_rango', 'mensaje' => 'Los goles deben estar entre 0 y 99.',
            'errores' => [['campo' => 'goles_favor', 'codigo' => 'valor_fuera_de_rango', 'mensaje' => 'Los goles deben estar entre 0 y 99.']],
        ]);
    expect(DB::table('partidos')->where('id', $partido)->value('estado'))->toBe('programado')
        ->and(DB::table('partidos_historial')->count())->toBe(0);
});

test('HU-12 (§7.4 fila 6): un partido cancelado no admite resultado: PUT /partidos/{partido}/resultado da 409 partido_no_programado', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = alineacionPartido($m);
    alineacionPeticion($this, 'PATCH', "/partidos/$partido", ['estado' => 'cancelado'])->assertOk();

    alineacionPeticion($this, 'PUT', "/partidos/$partido/resultado", ['goles_favor' => 3, 'goles_contra' => 1])
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'partido_no_programado', 'mensaje' => 'El partido ya no está programado.', 'errores' => []]);
    expect(DB::table('partidos')->where('id', $partido)->first())->toMatchArray(['estado' => 'cancelado', 'goles_favor' => null, 'goles_contra' => null]);
});

test('HU-13 (§2.3): el resultado de un partido jugado no se corrige', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = alineacionPartido($m);
    alineacionPeticion($this, 'PUT', "/partidos/$partido/resultado", ['goles_favor' => 3, 'goles_contra' => 1])->assertOk();

    alineacionPeticion($this, 'PUT', "/partidos/$partido/resultado", ['goles_favor' => 2, 'goles_contra' => 2])->assertStatus(409)->assertJsonPath('codigo', 'partido_no_programado');
    expect(DB::table('partidos')->where('id', $partido)->value('goles_favor'))->toBe(3);
});

test('HU-13 (DA-33): goles fuera de rango, negativos o con forma inválida', function (array $cuerpo, int $estado, string $codigo, string $campo) {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();

    alineacionPeticion($this, 'PUT', '/partidos/'.alineacionPartido($m).'/resultado', $cuerpo)
        ->assertStatus($estado)->assertJsonPath('codigo', $codigo)->assertJsonPath('errores.0.campo', $campo);
})->with([
    'goles_contra de 100' => [['goles_favor' => 0, 'goles_contra' => 100], 422, 'valor_fuera_de_rango', 'goles_contra'],
    'los dos de 100: primero goles_favor' => [['goles_favor' => 100, 'goles_contra' => 100], 422, 'valor_fuera_de_rango', 'goles_favor'],
    'negativo' => [['goles_favor' => -1, 'goles_contra' => 0], 422, 'valor_fuera_de_rango', 'goles_favor'],
    'decimal' => [['goles_favor' => 3.5, 'goles_contra' => 0], 422, 'validacion_fallida', 'goles_favor'],
    'cadena' => [['goles_favor' => '3', 'goles_contra' => 0], 422, 'validacion_fallida', 'goles_favor'],
    'faltante' => [['goles_favor' => 3], 422, 'validacion_fallida', 'goles_contra'],
    'campo no admitido' => [['goles_favor' => 3, 'goles_contra' => 1, 'estado' => 'jugado'], 422, 'validacion_fallida', 'estado'],
]);

test('HU-13: los límites 0 y 99 se aceptan', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();

    alineacionPeticion($this, 'PUT', '/partidos/'.alineacionPartido($m).'/resultado', ['goles_favor' => 0, 'goles_contra' => 99])
        ->assertOk()->assertJsonPath('datos.goles_favor', 0)->assertJsonPath('datos.goles_contra', 99);
});

test('HU-13 (§3.4): la alineación reemplaza la anterior: quien sale queda sin posición ni rol, y solo las filas que cambian escriben historial', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = alineacionPartido($m);
    $j1 = alineacionConvocar($partido, $m['j1'], ['posicion_id' => alineacionPosicion('defensa'), 'rol_alineacion' => 'titular']);
    $j2 = alineacionConvocar($partido, $m['j2'], ['posicion_id' => alineacionPosicion('medio'), 'rol_alineacion' => 'suplente']);
    $j3 = alineacionConvocar($partido, $m['j3']);

    $respuesta = alineacionPeticion($this, 'PUT', "/partidos/$partido/alineacion", ['alineacion' => [
        ['jugador_id' => $m['j1'], 'posicion_id' => alineacionPosicion('defensa'), 'rol_alineacion' => 'titular'],
        ['jugador_id' => $m['j3'], 'posicion_id' => alineacionPosicion('delantero'), 'rol_alineacion' => 'suplente'],
    ]])->assertOk();

    /** @var list<array{id: string, posicion_id: string|null, rol_alineacion: string|null}> $datos */
    $datos = $respuesta->json('datos');
    $porId = array_column($datos, null, 'id');
    expect($porId[$j1]['rol_alineacion'])->toBe('titular')
        ->and($porId[$j2]['posicion_id'])->toBeNull()
        ->and($porId[$j2]['rol_alineacion'])->toBeNull()
        ->and($porId[$j3]['rol_alineacion'])->toBe('suplente')
        ->and(DB::table('convocatorias_historial')->pluck('convocatoria_id')->sort()->values()->all())->toBe(collect([$j2, $j3])->sort()->values()->all());

    alineacionPeticion($this, 'PUT', "/partidos/$partido/alineacion", ['alineacion' => []])->assertOk();
    expect(DB::table('convocatorias')->whereNotNull('rol_alineacion')->count())->toBe(0)
        ->and(DB::table('convocatorias')->whereNotNull('posicion_id')->count())->toBe(0);
});

test('HU-13 (DA-34): un jugador visible cuya convocatoria se quitó da 422 jugador_no_convocado', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = alineacionPartido($m);
    alineacionConvocar($partido, $m['j2'], ['eliminado_en' => '2026-09-10 10:00:00+00']);

    alineacionPeticion($this, 'PUT', "/partidos/$partido/alineacion", ['alineacion' => [
        ['jugador_id' => $m['j2'], 'posicion_id' => alineacionPosicion('medio'), 'rol_alineacion' => 'titular'],
    ]])->assertUnprocessable()->assertJsonPath('codigo', 'jugador_no_convocado')->assertJsonPath('errores.0.campo', 'alineacion.0.jugador_id');
});

test('RNF-01 (DA-34): un jugador fuera del alcance en la alineación, también uno en baja que estuvo convocado, da el mismo 404 que uno inexistente', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = alineacionPartido($m);
    alineacionConvocar($partido, $m['j1']);
    alineacionConvocar($partido, $m['j5']);
    $entrada = fn (string $jugador) => ['alineacion' => [
        ['jugador_id' => $m['j1'], 'posicion_id' => alineacionPosicion('defensa'), 'rol_alineacion' => 'titular'],
        ['jugador_id' => $jugador, 'posicion_id' => alineacionPosicion('medio'), 'rol_alineacion' => 'suplente'],
    ]];
    $inexistente = alineacionPeticion($this, 'PUT', "/partidos/$partido/alineacion", $entrada(Identificador::generar()->valor()))->assertNotFound();

    foreach (['baja convocado' => $m['j5'], 'de otro equipo' => $m['j4'], 'v4' => '123e4567-e89b-42d3-a456-426614174000'] as $caso => $jugador) {
        $respuesta = alineacionPeticion($this, 'PUT', "/partidos/$partido/alineacion", $entrada($jugador))->assertNotFound();
        expect($respuesta->getContent())->toBe($inexistente->getContent(), $caso);
    }
    expect($inexistente->json('codigo'))->toBe('recurso_no_encontrado')
        ->and(DB::table('convocatorias')->whereNotNull('rol_alineacion')->count())->toBe(0)
        ->and(DB::table('convocatorias_historial')->count())->toBe(0);
});

test('HU-13 (DA-34): para E3, que entrena A y B, J4 es visible y no está convocado: 422', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = alineacionPartido($m);

    alineacionPeticion($this, 'PUT', "/partidos/$partido/alineacion", ['alineacion' => [
        ['jugador_id' => $m['j4'], 'posicion_id' => alineacionPosicion('portero'), 'rol_alineacion' => 'titular'],
    ]], 'e3@x.com')->assertUnprocessable()->assertJsonPath('codigo', 'jugador_no_convocado');
});

test('HU-13 (DA-31): una posición inexistente o con UUID que no es v7 da 422 posicion_inexistente y no cambia nada', function (string $posicion) {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = alineacionPartido($m);
    alineacionConvocar($partido, $m['j1']);
    alineacionConvocar($partido, $m['j2']);

    alineacionPeticion($this, 'PUT', "/partidos/$partido/alineacion", ['alineacion' => [
        ['jugador_id' => $m['j1'], 'posicion_id' => alineacionPosicion('defensa'), 'rol_alineacion' => 'titular'],
        ['jugador_id' => $m['j2'], 'posicion_id' => $posicion, 'rol_alineacion' => 'suplente'],
    ]])
        ->assertUnprocessable()
        ->assertExactJson(['codigo' => 'posicion_inexistente', 'mensaje' => 'La posición indicada no existe.', 'errores' => []]);
    expect(DB::table('convocatorias')->whereNotNull('rol_alineacion')->count())->toBe(0)
        ->and(DB::table('convocatorias_historial')->count())->toBe(0);
})->with([
    'v7 inexistente' => fn () => Identificador::generar()->valor(),
    'v4' => '123e4567-e89b-42d3-a456-426614174000',
]);

test('HU-13: la alineación con forma inválida da 422', function (array $cuerpo, string $campo) {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = alineacionPartido($m);
    alineacionConvocar($partido, $m['j1']);
    $cuerpo = json_decode(str_replace(['{j1}', '{defensa}'], [$m['j1'], alineacionPosicion('defensa')], (string) json_encode($cuerpo)), true);

    alineacionPeticion($this, 'PUT', "/partidos/$partido/alineacion", $cuerpo)
        ->assertUnprocessable()->assertJsonPath('codigo', 'validacion_fallida')->assertJsonPath('errores.0.campo', $campo);
})->with([
    'sin alineación' => [[], 'alineacion'],
    'rol inválido' => [['alineacion' => [['jugador_id' => '{j1}', 'posicion_id' => '{defensa}', 'rol_alineacion' => 'banca']]], 'alineacion.0.rol_alineacion'],
    'sin posición' => [['alineacion' => [['jugador_id' => '{j1}', 'rol_alineacion' => 'titular']]], 'alineacion.0.posicion_id'],
    'repetido' => [['alineacion' => [
        ['jugador_id' => '{j1}', 'posicion_id' => '{defensa}', 'rol_alineacion' => 'titular'],
        ['jugador_id' => '{j1}', 'posicion_id' => '{defensa}', 'rol_alineacion' => 'suplente'],
    ]], 'alineacion.0.jugador_id'],
]);

test('RNF-01: la alineación y el resultado de un partido ajeno dan 404 byte a byte con cuerpo válido e inválido, antes que el 422 y el 409, sin cambios', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = alineacionPartido($m);
    $jugado = alineacionPartido($m, atributos: ['estado' => 'jugado', 'goles_favor' => 1, 'goles_contra' => 1, 'inicia_en' => '2026-09-05 23:00:00+00']);
    alineacionConvocar($partido, $m['j3']);
    $otro = Identificador::generar()->valor();
    $alineacion = ['alineacion' => [['jugador_id' => $m['j3'], 'posicion_id' => alineacionPosicion('medio'), 'rol_alineacion' => 'titular']]];
    $pares = [
        ["/partidos/$partido/alineacion", "/partidos/$otro/alineacion", $alineacion],
        ["/partidos/$partido/alineacion", "/partidos/$otro/alineacion", ['alineacion' => 'x']],
        ["/partidos/$jugado/alineacion", "/partidos/$otro/alineacion", $alineacion],
        ["/partidos/$partido/resultado", "/partidos/$otro/resultado", ['goles_favor' => 2, 'goles_contra' => 0]],
        ["/partidos/$partido/resultado", "/partidos/$otro/resultado", ['goles_favor' => 100, 'goles_contra' => 'x']],
        ["/partidos/$jugado/resultado", '/partidos/123e4567-e89b-42d3-a456-426614174000/resultado', ['goles_favor' => 2, 'goles_contra' => 0]],
    ];

    foreach ($pares as [$ajena, $inexistente, $cuerpo]) {
        $respuestaAjena = alineacionPeticion($this, 'PUT', $ajena, $cuerpo, 'e2@x.com')->assertNotFound();
        expect($respuestaAjena->getContent())->toBe(alineacionPeticion($this, 'PUT', $inexistente, $cuerpo, 'e2@x.com')->assertNotFound()->getContent(), $ajena);
    }
    expect(DB::table('partidos')->where('id', $partido)->value('estado'))->toBe('programado')
        ->and(DB::table('convocatorias')->whereNotNull('rol_alineacion')->count())->toBe(0)
        ->and(DB::table('partidos_historial')->count() + DB::table('convocatorias_historial')->count())->toBe(0);
});

test('RN-22: E3, que entrena A y B, define la alineación y captura el resultado de un partido de A', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = alineacionPartido($m);
    alineacionConvocar($partido, $m['j3']);

    alineacionPeticion($this, 'PUT', "/partidos/$partido/alineacion", ['alineacion' => [
        ['jugador_id' => $m['j3'], 'posicion_id' => alineacionPosicion('medio'), 'rol_alineacion' => 'titular'],
    ]], 'e3@x.com')->assertOk();
    alineacionPeticion($this, 'PUT', "/partidos/$partido/resultado", ['goles_favor' => 1, 'goles_contra' => 0], 'e3@x.com')->assertOk();
});

test('HU-13: un docente recibe 403 y sin sesión 401 en la alineación y el resultado', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $partido = alineacionPartido($m);

    foreach (["/partidos/$partido/alineacion", "/partidos/$partido/resultado"] as $ruta) {
        alineacionPeticion($this, 'PUT', $ruta, ['goles_favor' => 1, 'goles_contra' => 0], 'd@x.com')->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
        sesionesPeticion($this, 'PUT', '/api/v1'.$ruta, ['goles_favor' => 1, 'goles_contra' => 0])->assertUnauthorized();
    }
});
