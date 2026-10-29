<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/** Partidos (HU-12 criterio 1, docs/hitos/f1-h3.md §7.4). La hora local es la de America/Mexico_City (UTC-6). */
beforeEach(function () {
    sesionesPrepararBase();
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-09-01 15:00:00', new DateTimeZone('UTC'))));
});

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function partidoPeticion(TestCase $prueba, string $metodo, string $ruta, array $cuerpo = [], string $correo = 'e1@x.com'): TestResponse
{
    return sesionesPeticion($prueba, $metodo, '/api/v1'.$ruta, $cuerpo, sesionesIniciar($prueba, $correo));
}

/**
 * Partido oficial de A en T1 con TO1, el 12 de septiembre a las 17:00 locales.
 *
 * @param  array<string, string>  $m
 * @param  array<string, mixed>  $extra
 * @return array<string, mixed>
 */
function partidoCuerpo(array $m, array $extra = []): array
{
    return [
        'inicia_en' => '2026-09-12T17:00:00-06:00', 'lugar_id' => $m['l1'], 'rival_id' => $m['r1'],
        'torneo_id' => $m['to1'], 'tipo' => 'oficial', 'condicion' => 'local', ...$extra,
    ];
}

/**
 * @param  array<string, string>  $m
 * @param  array<string, mixed>  $atributos
 */
function partidoDe(array $m, string $equipo = 'a', array $atributos = []): string
{
    return mundoInsertar('partidos', [
        'equipo_id' => $m[$equipo], 'temporada_id' => $m['t1'], 'torneo_id' => $m['to1'], 'rival_id' => $m['r1'],
        'lugar_id' => $m['l1'], 'inicia_en' => '2026-09-12 23:00:00+00', 'tipo' => 'oficial', 'condicion' => 'local',
        'estado' => 'programado', ...$atributos,
    ]);
}

/**
 * T2 del 10 de enero al 30 de junio de 2027, con su torneo TO2.
 *
 * @param  array<string, string>  $m
 * @return array<string, string>
 */
function partidoConT2(array $m): array
{
    $t2 = mundoInsertar('temporadas', ['nombre' => 'T2', 'fecha_inicio' => '2027-01-10', 'fecha_fin' => '2027-06-30']);

    return [...$m, 't2' => $t2, 'to2' => mundoInsertar('torneos', ['nombre' => 'TO2', 'temporada_id' => $t2])];
}

test('HU-12 (§7.4 fila 1): E1 crea un partido oficial en T1 con TO1; temporada_id = T1 y la categoría no aparece como campo', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();

    $respuesta = partidoPeticion($this, 'POST', "/equipos/{$m['a']}/partidos", partidoCuerpo($m))->assertCreated();

    $id = $respuesta->json('datos.id');
    expect($respuesta->json())->toBe(['datos' => [
        'id' => $id, 'equipo_id' => $m['a'], 'temporada_id' => $m['t1'], 'torneo_id' => $m['to1'], 'rival_id' => $m['r1'],
        'lugar_id' => $m['l1'], 'inicia_en' => '2026-09-12T23:00:00+00:00', 'tipo' => 'oficial', 'condicion' => 'local',
        'estado' => 'programado', 'goles_favor' => null, 'goles_contra' => null, 'actualizado_en' => '2026-09-01T15:00:00+00:00',
    ]]);
    $historial = DB::table('partidos_historial')->where('partido_id', $id)->get();
    expect($historial)->toHaveCount(1)
        ->and($historial[0]->operacion)->toBe('crear')
        ->and($historial[0]->usuario_id)->toBe($m['e1'])
        ->and(json_decode($historial[0]->datos_nuevos, true))->toMatchArray(['temporada_id' => $m['t1'], 'estado' => 'programado', 'tipo' => 'oficial']);
});

test('HU-12 (§7.4 fila 2): sin temporada que contenga la fecha, crear un partido da 422 sin_temporada en inicia_en', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();

    partidoPeticion($this, 'POST', "/equipos/{$m['a']}/partidos", partidoCuerpo($m, ['inicia_en' => '2027-03-01T17:00:00-06:00', 'tipo' => 'amistoso', 'torneo_id' => null]))
        ->assertUnprocessable()
        ->assertExactJson([
            'codigo' => 'sin_temporada', 'mensaje' => 'Ninguna temporada contiene la fecha del partido.',
            'errores' => [['campo' => 'inicia_en', 'codigo' => 'sin_temporada', 'mensaje' => 'Ninguna temporada contiene la fecha del partido.']],
        ]);
    expect(DB::table('partidos')->count())->toBe(0);
});

test('HU-12 (§7.4 fila 3): un partido oficial sin torneo da 422 torneo_obligatorio; uno de torneo, también', function (string $tipo) {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();

    partidoPeticion($this, 'POST', "/equipos/{$m['a']}/partidos", partidoCuerpo($m, ['tipo' => $tipo, 'torneo_id' => null]))
        ->assertUnprocessable()
        ->assertExactJson([
            'codigo' => 'torneo_obligatorio', 'mensaje' => 'El partido oficial o de torneo debe indicar el torneo.',
            'errores' => [['campo' => 'torneo_id', 'codigo' => 'torneo_obligatorio', 'mensaje' => 'El partido oficial o de torneo debe indicar el torneo.']],
        ]);
    expect(DB::table('partidos')->count())->toBe(0);
})->with(['oficial', 'torneo']);

test('HU-12 (§7.4 fila 4): un partido amistoso sin torneo se crea; uno interno, también', function (string $tipo) {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $cuerpo = partidoCuerpo($m, ['tipo' => $tipo]);
    unset($cuerpo['torneo_id']);

    partidoPeticion($this, 'POST', "/equipos/{$m['a']}/partidos", $cuerpo)
        ->assertCreated()->assertJsonPath('datos.torneo_id', null)->assertJsonPath('datos.tipo', $tipo)->assertJsonPath('datos.temporada_id', $m['t1']);
})->with(['amistoso', 'interno']);

test('HU-12 (§7.4 fila 5): un partido en fecha de otra temporada con TO1 da 422 torneo_de_otra_temporada', function () {
    /** @var TestCase $this */
    $m = partidoConT2(mundoDeCompetencia());

    partidoPeticion($this, 'POST', "/equipos/{$m['a']}/partidos", partidoCuerpo($m, ['inicia_en' => '2027-02-01T17:00:00-06:00']))
        ->assertUnprocessable()
        ->assertExactJson([
            'codigo' => 'torneo_de_otra_temporada', 'mensaje' => 'El torneo no es de la temporada del partido.',
            'errores' => [['campo' => 'torneo_id', 'codigo' => 'torneo_de_otra_temporada', 'mensaje' => 'El torneo no es de la temporada del partido.']],
        ]);
    partidoPeticion($this, 'POST', "/equipos/{$m['a']}/partidos", partidoCuerpo($m, ['inicia_en' => '2027-02-01T17:00:00-06:00', 'torneo_id' => $m['to2']]))
        ->assertCreated()->assertJsonPath('datos.temporada_id', $m['t2']);
});

test('HU-12 (§7.4 fila 6, parcial hasta T07): E1 cancela un partido de A; el historial lo registra y ya no se modifica', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = partidoDe($m);

    partidoPeticion($this, 'PATCH', "/partidos/$id", ['estado' => 'cancelado'])->assertOk()->assertJsonPath('datos.estado', 'cancelado');

    $fila = DB::table('partidos_historial')->where('partido_id', $id)->first();
    expect($fila->operacion)->toBe('actualizar')
        ->and($fila->usuario_id)->toBe($m['e1'])
        ->and(json_decode($fila->datos_anteriores, true)['estado'])->toBe('programado')
        ->and(json_decode($fila->datos_nuevos, true)['estado'])->toBe('cancelado');
    partidoPeticion($this, 'PATCH', "/partidos/$id", ['condicion' => 'visitante'])
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'partido_no_programado', 'mensaje' => 'El partido ya no está programado.', 'errores' => []]);
});

test('HU-12 (§7.4 fila 7): E1 consulta un partido de B y recibe el mismo 404 que con uno inexistente', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $deB = partidoDe($m, 'b');

    $ajeno = partidoPeticion($this, 'GET', "/partidos/$deB")->assertNotFound();
    $inexistente = partidoPeticion($this, 'GET', '/partidos/'.Identificador::generar()->valor())->assertNotFound();
    $v4 = partidoPeticion($this, 'GET', '/partidos/123e4567-e89b-42d3-a456-426614174000')->assertNotFound();

    expect($ajeno->getContent())->toBe($inexistente->getContent())
        ->and($v4->getContent())->toBe($inexistente->getContent())
        ->and($ajeno->json('codigo'))->toBe('recurso_no_encontrado');
});

test('HU-12 (§7.4 fila 8): a las 00:30 UTC del 16 de diciembre (el 15 en America/Mexico_City) se asigna T1 por la fecha local', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();

    partidoPeticion($this, 'POST', "/equipos/{$m['a']}/partidos", partidoCuerpo($m, ['inicia_en' => '2026-12-16T00:30:00Z']))
        ->assertCreated()->assertJsonPath('datos.temporada_id', $m['t1'])->assertJsonPath('datos.inicia_en', '2026-12-16T00:30:00+00:00');
});

test('HU-12 (DD-07): la frontera del día local decide la temporada en los dos extremos', function (string $iniciaEn, ?string $temporada) {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $cuerpo = partidoCuerpo($m, ['inicia_en' => $iniciaEn, 'tipo' => 'amistoso', 'torneo_id' => null]);

    $respuesta = partidoPeticion($this, 'POST', "/equipos/{$m['a']}/partidos", $cuerpo);

    if ($temporada === null) {
        $respuesta->assertUnprocessable()->assertJsonPath('codigo', 'sin_temporada');
    } else {
        $respuesta->assertCreated()->assertJsonPath('datos.temporada_id', $m[$temporada]);
    }
})->with([
    '15 de diciembre 23:59:59 local' => ['2026-12-16T05:59:59Z', 't1'],
    '16 de diciembre 00:00 local' => ['2026-12-16T06:00:00Z', null],
    '31 de julio 23:59:59 local' => ['2026-08-01T05:59:59Z', null],
    '1 de agosto 00:00 local' => ['2026-08-01T06:00:00Z', 't1'],
    'con desplazamiento explícito' => ['2026-08-01T00:00:00-06:00', 't1'],
]);

test('HU-12 (DA-25): mover la fecha a otra temporada vuelve a derivarla y exige un torneo de esa temporada', function () {
    /** @var TestCase $this */
    $m = partidoConT2(mundoDeCompetencia());
    $id = partidoDe($m);
    $ruta = "/partidos/$id";

    partidoPeticion($this, 'PATCH', $ruta, ['inicia_en' => '2027-02-01T17:00:00-06:00'])->assertUnprocessable()->assertJsonPath('codigo', 'torneo_de_otra_temporada');
    partidoPeticion($this, 'PATCH', $ruta, ['inicia_en' => '2027-08-01T17:00:00-06:00'])->assertUnprocessable()->assertJsonPath('errores.0.campo', 'inicia_en');
    partidoPeticion($this, 'PATCH', $ruta, ['inicia_en' => '2027-02-01T17:00:00-06:00', 'torneo_id' => $m['to2']])
        ->assertOk()->assertJsonPath('datos.temporada_id', $m['t2'])->assertJsonPath('datos.torneo_id', $m['to2']);
    expect(DB::table('partidos_historial')->where('partido_id', $id)->count())->toBe(1);
});

test('HU-12 (DA-25): cambiar el tipo o quitar el torneo reevalúa torneo_obligatorio sobre el resultado final', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $amistoso = partidoDe($m, atributos: ['tipo' => 'amistoso', 'torneo_id' => null]);
    $oficial = partidoDe($m);

    partidoPeticion($this, 'PATCH', "/partidos/$amistoso", ['tipo' => 'oficial'])->assertUnprocessable()->assertJsonPath('codigo', 'torneo_obligatorio');
    partidoPeticion($this, 'PATCH', "/partidos/$oficial", ['torneo_id' => null])->assertUnprocessable()->assertJsonPath('codigo', 'torneo_obligatorio');
    partidoPeticion($this, 'PATCH', "/partidos/$oficial", ['tipo' => 'amistoso', 'torneo_id' => null])->assertOk()->assertJsonPath('datos.torneo_id', null);
    partidoPeticion($this, 'PATCH', "/partidos/$amistoso", ['tipo' => 'torneo', 'torneo_id' => $m['to1']])->assertOk()->assertJsonPath('datos.tipo', 'torneo');
});

test('HU-12: un partido conserva su temporada eliminada mientras no cambie la fecha', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = partidoDe($m, atributos: ['tipo' => 'amistoso', 'torneo_id' => null]);
    DB::connection('propietario')->table('temporadas')->where('id', $m['t1'])->update(['eliminado_en' => '2026-08-20 10:00:00+00']);

    partidoPeticion($this, 'PATCH', "/partidos/$id", ['condicion' => 'visitante'])->assertOk()->assertJsonPath('datos.temporada_id', $m['t1']);
    partidoPeticion($this, 'PATCH', "/partidos/$id", ['inicia_en' => '2026-09-13T17:00:00-06:00'])->assertUnprocessable()->assertJsonPath('codigo', 'sin_temporada');
    partidoPeticion($this, 'GET', "/partidos/$id")->assertOk()->assertJsonPath('datos.temporada_id', $m['t1']);
});

test('HU-12 (DA-24): un lugar, un rival o un torneo inexistente o eliminado da 422 con su código', function (string $campo, string $codigo) {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $eliminados = [
        'lugar_id' => mundoInsertar('lugares', ['nombre' => 'Vieja', 'eliminado_en' => '2026-08-01 10:00:00+00']),
        'rival_id' => mundoInsertar('rivales', ['nombre' => 'Viejo', 'eliminado_en' => '2026-08-01 10:00:00+00']),
        'torneo_id' => mundoInsertar('torneos', ['nombre' => 'Viejo', 'temporada_id' => $m['t1'], 'eliminado_en' => '2026-08-01 10:00:00+00']),
    ];

    foreach ([$eliminados[$campo], Identificador::generar()->valor(), '123e4567-e89b-42d3-a456-426614174000'] as $valor) {
        partidoPeticion($this, 'POST', "/equipos/{$m['a']}/partidos", partidoCuerpo($m, [$campo => $valor]))
            ->assertUnprocessable()->assertJsonPath('codigo', $codigo)->assertJsonPath('errores', []);
    }
    partidoPeticion($this, 'PATCH', '/partidos/'.partidoDe($m), [$campo => $eliminados[$campo]])->assertUnprocessable()->assertJsonPath('codigo', $codigo);
    expect(DB::table('partidos_historial')->count())->toBe(0);
})->with([
    'lugar' => ['lugar_id', 'lugar_inexistente'],
    'rival' => ['rival_id', 'rival_inexistente'],
    'torneo' => ['torneo_id', 'torneo_inexistente'],
]);

test('HU-12: cancelar un partido jugado o ya cancelado da 409 transicion_no_permitida en estado (§3.4)', function (array $atributos) {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = partidoDe($m, atributos: $atributos);

    partidoPeticion($this, 'PATCH', "/partidos/$id", ['estado' => 'cancelado'])
        ->assertStatus(409)->assertJsonPath('codigo', 'transicion_no_permitida')->assertJsonPath('errores.0.campo', 'estado');
})->with([
    'jugado' => [['estado' => 'jugado', 'goles_favor' => 3, 'goles_contra' => 1]],
    'cancelado' => [['estado' => 'cancelado']],
]);

test('HU-12: datos y cancelación en la misma petición quedan en una sola fila de historial', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = partidoDe($m);

    partidoPeticion($this, 'PATCH', "/partidos/$id", ['condicion' => 'visitante', 'estado' => 'cancelado'])
        ->assertOk()->assertJsonPath('datos.condicion', 'visitante')->assertJsonPath('datos.estado', 'cancelado');
    expect(DB::table('partidos_historial')->where('partido_id', $id)->count())->toBe(1);
});

test('HU-12: un PATCH vacío, con campos no admitidos o con un estado distinto de cancelado da 422', function (array $cuerpo, string $campo) {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();

    partidoPeticion($this, 'PATCH', '/partidos/'.partidoDe($m), $cuerpo)->assertUnprocessable()->assertJsonPath('errores.0.campo', $campo);
})->with([
    'vacío' => [[], 'cuerpo'],
    'temporada' => [['temporada_id' => '01929f3c-0000-7000-8000-000000000000'], 'temporada_id'],
    'goles' => [['goles_favor' => 1], 'goles_favor'],
    'jugado' => [['estado' => 'jugado'], 'estado'],
    'tipo inválido' => [['tipo' => 'liga'], 'tipo'],
]);

test('HU-12: los campos faltantes o mal formados del alta dan 422 con el campo señalado', function (array $cuerpo, array $campos) {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();

    expect(partidoPeticion($this, 'POST', "/equipos/{$m['a']}/partidos", $cuerpo)->assertUnprocessable()->json('errores.*.campo'))->toBe($campos);
})->with([
    'vacío' => [[], ['inicia_en', 'lugar_id', 'rival_id', 'tipo', 'condicion']],
    'sin zona y valores cerrados inválidos' => [['inicia_en' => '2026-09-12T17:00:00', 'lugar_id' => 'x', 'rival_id' => 'x', 'tipo' => 'liga', 'condicion' => 'neutral'], ['inicia_en', 'lugar_id', 'rival_id', 'tipo', 'condicion']],
    'temporada enviada' => [['inicia_en' => '2026-09-12T17:00:00Z', 'lugar_id' => '01929f3c-0000-7000-8000-000000000000', 'rival_id' => '01929f3c-0000-7000-8000-000000000000', 'tipo' => 'amistoso', 'condicion' => 'local', 'temporada_id' => '01929f3c-0000-7000-8000-000000000000'], ['temporada_id']],
]);

test('HU-12 (DA-26): el listado de un equipo filtra por temporada y estado y ordena por inicia_en', function () {
    /** @var TestCase $this */
    $m = partidoConT2(mundoDeCompetencia());
    $primero = partidoDe($m, atributos: ['inicia_en' => '2026-09-12 23:00:00+00']);
    $cancelado = partidoDe($m, atributos: ['inicia_en' => '2026-10-12 23:00:00+00', 'estado' => 'cancelado']);
    $deT2 = partidoDe($m, atributos: ['inicia_en' => '2027-02-01 23:00:00+00', 'temporada_id' => $m['t2'], 'torneo_id' => $m['to2']]);
    partidoDe($m, 'b');
    $ruta = "/equipos/{$m['a']}/partidos";

    expect(partidoPeticion($this, 'GET', $ruta)->assertOk()->json('datos.*.id'))->toBe([$primero, $cancelado, $deT2])
        ->and(partidoPeticion($this, 'GET', "$ruta?orden=-inicia_en")->json('datos.*.id'))->toBe([$deT2, $cancelado, $primero])
        ->and(partidoPeticion($this, 'GET', "$ruta?filtro[temporada_id]={$m['t1']}")->json('datos.*.id'))->toBe([$primero, $cancelado])
        ->and(partidoPeticion($this, 'GET', "$ruta?filtro[estado]=cancelado")->json('datos.*.id'))->toBe([$cancelado])
        ->and(partidoPeticion($this, 'GET', "$ruta?filtro[temporada_id]={$m['t1']}&filtro[estado]=programado")->json('datos.*.id'))->toBe([$primero])
        ->and(partidoPeticion($this, 'GET', "$ruta?filtro[temporada_id]=".Identificador::generar()->valor())->assertOk()->json('datos'))->toBe([]);
    partidoPeticion($this, 'GET', "$ruta?filtro[temporada_id]=x")->assertUnprocessable()->assertJsonPath('errores.0.campo', 'filtro.temporada_id');
    partidoPeticion($this, 'GET', "$ruta?filtro[estado]=suspendido")->assertUnprocessable()->assertJsonPath('errores.0.campo', 'filtro.estado');
});

test('RNF-01: un partido o el listado de un equipo ajeno dan 404 byte a byte por cualquier método, antes que el 422, sin cambios', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = partidoDe($m);
    $otro = Identificador::generar()->valor();
    $pares = [
        ['GET', "/partidos/$id", "/partidos/$otro", []],
        ['PATCH', "/partidos/$id", "/partidos/$otro", ['estado' => 'cancelado']],
        ['PATCH', "/partidos/$id", "/partidos/$otro", ['estado' => 'x', 'goles_favor' => 1]],
        ['GET', "/equipos/{$m['a']}/partidos", "/equipos/$otro/partidos", []],
        ['GET', "/equipos/{$m['a']}/partidos?filtro[estado]=x", "/equipos/$otro/partidos?filtro[estado]=x", []],
        ['POST', "/equipos/{$m['a']}/partidos", "/equipos/$otro/partidos", partidoCuerpo($m)],
        ['POST', "/equipos/{$m['a']}/partidos", "/equipos/$otro/partidos", []],
    ];

    foreach ($pares as [$metodo, $ajena, $inexistente, $cuerpo]) {
        $respuestaAjena = partidoPeticion($this, $metodo, $ajena, $cuerpo, 'e2@x.com')->assertNotFound();
        expect($respuestaAjena->getContent())->toBe(partidoPeticion($this, $metodo, $inexistente, $cuerpo, 'e2@x.com')->assertNotFound()->getContent(), "$metodo $ajena");
    }
    expect(DB::table('partidos')->count())->toBe(1)
        ->and(DB::table('partidos')->value('estado'))->toBe('programado')
        ->and(DB::table('partidos_historial')->count())->toBe(0);
});

test('RN-22: E3, que entrena A y B, consulta y modifica un partido de A', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = partidoDe($m);

    partidoPeticion($this, 'GET', "/partidos/$id", correo: 'e3@x.com')->assertOk()->assertJsonPath('datos.id', $id);
    partidoPeticion($this, 'PATCH', "/partidos/$id", ['condicion' => 'visitante'], 'e3@x.com')->assertOk();
});

test('HU-12: un docente recibe 403 y sin sesión 401 en las rutas de partidos', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = partidoDe($m);
    $rutas = [['GET', "/equipos/{$m['a']}/partidos"], ['POST', "/equipos/{$m['a']}/partidos"], ['GET', "/partidos/$id"], ['PATCH', "/partidos/$id"]];

    foreach ($rutas as [$metodo, $ruta]) {
        partidoPeticion($this, $metodo, $ruta, partidoCuerpo($m), 'd@x.com')->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
        sesionesPeticion($this, $metodo, '/api/v1'.$ruta, partidoCuerpo($m))->assertUnauthorized();
    }
});
