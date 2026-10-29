<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/** Catálogos de Competencia (HU-28, docs/hitos/f1-h3.md §7.1). E1 y E2 son entrenadores; D es docente. */
beforeEach(function () {
    sesionesPrepararBase();
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-09-01 15:00:00', new DateTimeZone('UTC'))));
    sesionesCrearUsuario('e1@x.com', ['entrenador']);
    sesionesCrearUsuario('e2@x.com', ['entrenador']);
    sesionesCrearUsuario('d@x.com', ['docente']);
});

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function catalogoPeticion(TestCase $prueba, string $metodo, string $ruta, array $cuerpo = [], string $correo = 'e1@x.com'): TestResponse
{
    return sesionesPeticion($prueba, $metodo, '/api/v1'.$ruta, $cuerpo, sesionesIniciar($prueba, $correo));
}

/**
 * L1, R1, T1 y TO1 de §7.0, insertados directo en la base.
 *
 * @return array{l1: string, r1: string, t1: string, to1: string}
 */
function catalogosDePartida(): array
{
    $t1 = mundoInsertar('temporadas', ['nombre' => 'T1', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2026-12-15']);

    return [
        'l1' => mundoInsertar('lugares', ['nombre' => 'Cancha norte']),
        'r1' => mundoInsertar('rivales', ['nombre' => 'R1']),
        't1' => $t1,
        'to1' => mundoInsertar('torneos', ['nombre' => 'TO1', 'temporada_id' => $t1]),
    ];
}

function catalogoUsuario(string $correo): string
{
    return (string) DB::table('usuarios')->where('correo', $correo)->value('id');
}

test('HU-28 (§7.1 fila 1): E1 crea el lugar L2 y E2 lo ve en el listado', function () {
    /** @var TestCase $this */
    catalogosDePartida();

    $creado = catalogoPeticion($this, 'POST', '/lugares', ['nombre' => 'Cancha sur'])->assertCreated();

    expect($creado->json('datos'))->toBe([
        'id' => $creado->json('datos.id'), 'nombre' => 'Cancha sur', 'actualizado_en' => '2026-09-01T15:00:00+00:00',
    ]);
    $listado = catalogoPeticion($this, 'GET', '/lugares', correo: 'e2@x.com')->assertOk();
    expect($listado->json('datos.*.nombre'))->toBe(['Cancha norte', 'Cancha sur'])
        ->and($listado->json('meta'))->toBe(['pagina' => 1, 'por_pagina' => 15, 'total' => 2, 'total_paginas' => 1]);
});

test('HU-28 (§7.1 fila 2): E1 crea un lugar con el nombre de L1 y recibe 409', function () {
    /** @var TestCase $this */
    catalogosDePartida();

    catalogoPeticion($this, 'POST', '/lugares', ['nombre' => '  CANCHA NORTE '])
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'lugar_duplicado', 'mensaje' => 'Ya existe un lugar con ese nombre.', 'errores' => []]);
    expect(DB::table('lugares')->count())->toBe(1)
        ->and(DB::table('lugares_historial')->count())->toBe(0);
});

test('HU-28 (§7.1 fila 3): E1 crea la temporada T2 que se cruza con T1 y recibe 409 temporada_solapada', function () {
    /** @var TestCase $this */
    catalogosDePartida();

    catalogoPeticion($this, 'POST', '/temporadas', ['nombre' => 'T2', 'fecha_inicio' => '2026-12-10', 'fecha_fin' => '2026-12-20'])
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'temporada_solapada', 'mensaje' => 'Las fechas se cruzan con otra temporada.', 'errores' => []]);
    expect(DB::table('temporadas')->count())->toBe(1);
});

test('DD-07: una temporada que empieza el día en que termina otra se cruza; la del día siguiente no', function () {
    /** @var TestCase $this */
    catalogosDePartida();

    catalogoPeticion($this, 'POST', '/temporadas', ['nombre' => 'T2', 'fecha_inicio' => '2026-12-15', 'fecha_fin' => '2027-06-30'])
        ->assertStatus(409)->assertJsonPath('codigo', 'temporada_solapada');
    catalogoPeticion($this, 'POST', '/temporadas', ['nombre' => 'T0', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-08-01'])
        ->assertStatus(409)->assertJsonPath('codigo', 'temporada_solapada');
    catalogoPeticion($this, 'POST', '/temporadas', ['nombre' => 'T3', 'fecha_inicio' => '2026-09-01', 'fecha_fin' => '2026-09-30'])
        ->assertStatus(409)->assertJsonPath('codigo', 'temporada_solapada');
    catalogoPeticion($this, 'POST', '/temporadas', ['nombre' => 'T4', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2027-12-31'])
        ->assertStatus(409)->assertJsonPath('codigo', 'temporada_solapada');
    catalogoPeticion($this, 'POST', '/temporadas', ['nombre' => 'T2', 'fecha_inicio' => '2026-12-16', 'fecha_fin' => '2027-06-30'])->assertCreated();
    catalogoPeticion($this, 'POST', '/temporadas', ['nombre' => 'T0', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-07-31'])->assertCreated();
});

test('DD-07: una temporada eliminada no cuenta para el cruce', function () {
    /** @var TestCase $this */
    $c = catalogosDePartida();

    catalogoPeticion($this, 'DELETE', '/temporadas/'.$c['t1'])->assertNoContent();

    catalogoPeticion($this, 'POST', '/temporadas', ['nombre' => 'T2', 'fecha_inicio' => '2026-12-10', 'fecha_fin' => '2026-12-20'])->assertCreated();
});

test('HU-28 (§7.1 fila 4): una temporada con fecha_fin anterior a fecha_inicio da 422 en fecha_fin', function () {
    /** @var TestCase $this */
    $finesNoPosteriores = ['2027-01-31', '2027-02-01'];

    foreach ($finesNoPosteriores as $fin) {
        $respuesta = catalogoPeticion($this, 'POST', '/temporadas', ['nombre' => 'T5', 'fecha_inicio' => '2027-02-01', 'fecha_fin' => $fin])
            ->assertUnprocessable()
            ->assertJsonPath('codigo', 'validacion_fallida');

        expect($respuesta->json('errores.*.campo'))->toBe(['fecha_fin']);
    }
    expect(DB::table('temporadas')->count())->toBe(0);
});

test('HU-28 (§7.1 fila 5): E1 crea un torneo en T1', function () {
    /** @var TestCase $this */
    $c = catalogosDePartida();

    $creado = catalogoPeticion($this, 'POST', '/torneos', ['nombre' => 'Copa de otoño', 'temporada_id' => $c['t1']])->assertCreated();

    expect($creado->json('datos'))->toBe([
        'id' => $creado->json('datos.id'), 'nombre' => 'Copa de otoño', 'temporada_id' => $c['t1'], 'actualizado_en' => '2026-09-01T15:00:00+00:00',
    ]);
});

test('HU-28 (§7.1 fila 6): un docente que crea un lugar recibe 403 sin_permiso', function () {
    /** @var TestCase $this */
    catalogoPeticion($this, 'POST', '/lugares', ['nombre' => 'Cancha sur'], 'd@x.com')
        ->assertForbidden()
        ->assertJsonPath('codigo', 'sin_permiso');

    expect(DB::table('lugares')->count())->toBe(0);
});

test('HU-28: un docente recibe 403 en todas las rutas de catálogos de Competencia', function (string $metodo, string $ruta) {
    /** @var TestCase $this */
    $c = catalogosDePartida();

    catalogoPeticion($this, $metodo, str_replace(['{l}', '{r}', '{t}', '{to}'], [$c['l1'], $c['r1'], $c['t1'], $c['to1']], $ruta), ['nombre' => 'X'], 'd@x.com')
        ->assertForbidden()
        ->assertJsonPath('codigo', 'sin_permiso');
})->with([
    ['GET', '/lugares'], ['DELETE', '/lugares/{l}'],
    ['GET', '/rivales'], ['POST', '/rivales'], ['DELETE', '/rivales/{r}'],
    ['GET', '/temporadas'], ['POST', '/temporadas'], ['DELETE', '/temporadas/{t}'],
    ['GET', '/torneos'], ['POST', '/torneos'], ['DELETE', '/torneos/{to}'],
]);

test('HU-28: sin sesión las rutas de catálogos responden 401', function (string $metodo, string $ruta) {
    /** @var TestCase $this */
    sesionesPeticion($this, $metodo, '/api/v1'.str_replace('{id}', Identificador::generar()->valor(), $ruta), ['nombre' => 'X'])
        ->assertUnauthorized()
        ->assertJsonPath('codigo', 'no_autenticado');
})->with([
    ['GET', '/lugares'], ['POST', '/lugares'], ['DELETE', '/lugares/{id}'],
    ['GET', '/rivales'], ['POST', '/rivales'], ['DELETE', '/rivales/{id}'],
    ['GET', '/temporadas'], ['POST', '/temporadas'], ['DELETE', '/temporadas/{id}'],
    ['GET', '/torneos'], ['POST', '/torneos'], ['DELETE', '/torneos/{id}'],
]);

test('HU-28 (§7.1 fila 7): E1 borra L1, que tiene un partido; el partido sigue mostrando L1', function () {
    /** @var TestCase $this */
    $c = catalogosDePartida();
    $e1 = catalogoUsuario('e1@x.com');
    $categoria = mundoInsertar('categorias', ['nombre' => 'Sub-17']);
    $partido = mundoInsertar('partidos', [
        'equipo_id' => mundoEquipo('A', $categoria, $e1), 'temporada_id' => $c['t1'], 'torneo_id' => $c['to1'], 'rival_id' => $c['r1'],
        'lugar_id' => $c['l1'], 'inicia_en' => '2026-09-12 17:00:00+00', 'tipo' => 'oficial', 'condicion' => 'local', 'estado' => 'programado',
    ]);

    catalogoPeticion($this, 'DELETE', '/lugares/'.$c['l1'])->assertNoContent();

    $lugar = DB::table('lugares')->where('id', $c['l1'])->first();
    expect(DB::table('partidos')->where('id', $partido)->value('lugar_id'))->toBe($c['l1'])
        ->and($lugar->nombre)->toBe('Cancha norte')
        ->and($lugar->eliminado_en)->toBe('2026-09-01 15:00:00+00')
        ->and($lugar->eliminado_por)->toBe($e1)
        ->and(catalogoPeticion($this, 'GET', '/lugares')->json('datos'))->toBe([]);
});

test('HU-28: tras el borrado lógico el nombre puede darse de alta de nuevo, y un segundo borrado da 404', function (string $ruta, array $cuerpo) {
    /** @var TestCase $this */
    $id = catalogoPeticion($this, 'POST', $ruta, $cuerpo)->assertCreated()->json('datos.id');

    catalogoPeticion($this, 'DELETE', "$ruta/$id")->assertNoContent();
    catalogoPeticion($this, 'DELETE', "$ruta/$id")->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');
    catalogoPeticion($this, 'POST', $ruta, $cuerpo)->assertCreated();
})->with([
    'lugares' => ['/lugares', ['nombre' => 'Cancha sur']],
    'rivales' => ['/rivales', ['nombre' => 'Halcones']],
    'temporadas' => ['/temporadas', ['nombre' => 'T9', 'fecha_inicio' => '2028-01-01', 'fecha_fin' => '2028-06-30']],
]);

test('HU-28: un rival, una temporada o un torneo repetidos con otras mayúsculas dan 409', function () {
    /** @var TestCase $this */
    $c = catalogosDePartida();

    catalogoPeticion($this, 'POST', '/rivales', ['nombre' => 'r1'])->assertStatus(409)
        ->assertExactJson(['codigo' => 'rival_duplicado', 'mensaje' => 'Ya existe un rival con ese nombre.', 'errores' => []]);
    catalogoPeticion($this, 'POST', '/temporadas', ['nombre' => 't1', 'fecha_inicio' => '2030-01-01', 'fecha_fin' => '2030-06-30'])->assertStatus(409)
        ->assertExactJson(['codigo' => 'temporada_duplicada', 'mensaje' => 'Ya existe una temporada con ese nombre.', 'errores' => []]);
    catalogoPeticion($this, 'POST', '/torneos', ['nombre' => 'to1', 'temporada_id' => $c['t1']])->assertStatus(409)
        ->assertExactJson(['codigo' => 'torneo_duplicado', 'mensaje' => 'Ya existe un torneo con ese nombre en la temporada.', 'errores' => []]);
});

test('HU-28: el mismo nombre de torneo se permite en otra temporada', function () {
    /** @var TestCase $this */
    catalogosDePartida();
    $t2 = mundoInsertar('temporadas', ['nombre' => 'T2', 'fecha_inicio' => '2027-01-01', 'fecha_fin' => '2027-06-30']);

    catalogoPeticion($this, 'POST', '/torneos', ['nombre' => 'TO1', 'temporada_id' => $t2])->assertCreated();
});

test('HU-28: un torneo en una temporada inexistente, eliminada o con UUID que no es v7 da 422 temporada_inexistente', function () {
    /** @var TestCase $this */
    $c = catalogosDePartida();
    DB::connection('propietario')->table('temporadas')->where('id', $c['t1'])->update(['eliminado_en' => '2026-08-20 10:00:00+00']);

    foreach ([$c['t1'], Identificador::generar()->valor(), '123e4567-e89b-42d3-a456-426614174000'] as $temporada) {
        catalogoPeticion($this, 'POST', '/torneos', ['nombre' => 'Copa', 'temporada_id' => $temporada])
            ->assertUnprocessable()
            ->assertExactJson(['codigo' => 'temporada_inexistente', 'mensaje' => 'La temporada indicada no existe.', 'errores' => []]);
    }
    expect(DB::table('torneos')->count())->toBe(1);
});

test('HU-28: un campo faltante, demasiado largo o con formato inválido da 422 con el campo señalado', function (string $ruta, array $cuerpo, array $campos) {
    /** @var TestCase $this */
    $respuesta = catalogoPeticion($this, 'POST', $ruta, $cuerpo)->assertUnprocessable()->assertJsonPath('codigo', 'validacion_fallida');

    expect($respuesta->json('errores.*.campo'))->toBe($campos);
})->with([
    'lugar sin nombre' => ['/lugares', [], ['nombre']],
    'lugar de 101' => ['/lugares', ['nombre' => str_repeat('a', 101)], ['nombre']],
    'rival vacío' => ['/rivales', ['nombre' => '   '], ['nombre']],
    'temporada sin fechas' => ['/temporadas', ['nombre' => 'T'], ['fecha_inicio', 'fecha_fin']],
    'temporada con hora' => ['/temporadas', ['nombre' => 'T', 'fecha_inicio' => '2027-01-01T00:00:00Z', 'fecha_fin' => '2027-02-01'], ['fecha_inicio']],
    'torneo sin temporada' => ['/torneos', ['nombre' => 'Copa'], ['temporada_id']],
    'torneo con temporada no uuid' => ['/torneos', ['nombre' => 'Copa', 'temporada_id' => 'x'], ['temporada_id']],
]);

test('HU-28: los listados excluyen los eliminados y ordenan por nombre, o temporadas por -fecha_inicio', function () {
    /** @var TestCase $this */
    $c = catalogosDePartida();
    mundoInsertar('lugares', ['nombre' => 'Alberca']);
    mundoInsertar('rivales', ['nombre' => 'Águilas', 'eliminado_en' => '2026-08-20 10:00:00+00']);
    mundoInsertar('rivales', ['nombre' => 'Búhos']);
    mundoInsertar('temporadas', ['nombre' => 'T0', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-06-30']);
    mundoInsertar('torneos', ['nombre' => 'Copa', 'temporada_id' => $c['t1']]);

    expect(catalogoPeticion($this, 'GET', '/lugares')->json('datos.*.nombre'))->toBe(['Alberca', 'Cancha norte'])
        ->and(catalogoPeticion($this, 'GET', '/lugares?orden=-nombre')->json('datos.*.nombre'))->toBe(['Cancha norte', 'Alberca'])
        ->and(catalogoPeticion($this, 'GET', '/rivales')->json('datos.*.nombre'))->toBe(['Búhos', 'R1'])
        ->and(catalogoPeticion($this, 'GET', '/temporadas')->json('datos.*.nombre'))->toBe(['T1', 'T0'])
        ->and(catalogoPeticion($this, 'GET', '/temporadas?orden=fecha_inicio')->json('datos.*.nombre'))->toBe(['T0', 'T1'])
        ->and(catalogoPeticion($this, 'GET', '/torneos')->json('datos.*.nombre'))->toBe(['Copa', 'TO1'])
        ->and(catalogoPeticion($this, 'GET', '/temporadas')->json('datos.0'))->toBe([
            'id' => $c['t1'], 'nombre' => 'T1', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2026-12-15',
            'actualizado_en' => (new DateTimeImmutable((string) DB::table('temporadas')->where('id', $c['t1'])->value('actualizado_en')))->setTimezone(new DateTimeZone('UTC'))->format(DATE_ATOM),
        ]);
    catalogoPeticion($this, 'GET', '/lugares?orden=fecha_inicio')->assertUnprocessable();
    catalogoPeticion($this, 'GET', '/torneos?por_pagina=101')->assertUnprocessable();
});

test('HU-28: el listado pagina con pagina y por_pagina', function () {
    /** @var TestCase $this */
    $nombres = ['A', 'B', 'C'];

    foreach ($nombres as $nombre) {
        mundoInsertar('rivales', ['nombre' => $nombre]);
    }

    $respuesta = catalogoPeticion($this, 'GET', '/rivales?pagina=2&por_pagina=2')->assertOk();

    expect($respuesta->json('datos.*.nombre'))->toBe(['C'])
        ->and($respuesta->json('meta'))->toBe(['pagina' => 2, 'por_pagina' => 2, 'total' => 3, 'total_paginas' => 2]);
});

test('RNF-02: cada alta y cada borrado de catálogo escriben su historial con autor e instante', function (string $ruta, array $cuerpo, string $tabla, string $columna) {
    /** @var TestCase $this */
    $temporada = mundoInsertar('temporadas', ['nombre' => 'T1', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2026-12-15']);
    $cuerpo = array_map(fn ($valor) => $valor === '{t}' ? $temporada : $valor, $cuerpo);
    $e1 = catalogoUsuario('e1@x.com');

    $id = catalogoPeticion($this, 'POST', $ruta, $cuerpo)->assertCreated()->json('datos.id');
    catalogoPeticion($this, 'DELETE', "$ruta/$id")->assertNoContent();

    $filas = DB::table("{$tabla}_historial")->where($columna, $id)->orderBy('version')->get();
    expect($filas)->toHaveCount(2)
        ->and($filas[0]->operacion)->toBe('crear')
        ->and($filas[0]->datos_anteriores)->toBeNull()
        ->and(json_decode($filas[0]->datos_nuevos, true))->toMatchArray($cuerpo)
        ->and($filas[1]->operacion)->toBe('eliminar')
        ->and(json_decode($filas[1]->datos_anteriores, true))->toMatchArray($cuerpo)
        ->and($filas[1]->datos_nuevos)->toBeNull();
    foreach ($filas as $fila) {
        expect($fila->usuario_id)->toBe($e1)
            ->and($fila->ocurrido_en)->toBe('2026-09-01 15:00:00+00');
    }
})->with([
    'lugares' => ['/lugares', ['nombre' => 'Cancha sur'], 'lugares', 'lugar_id'],
    'rivales' => ['/rivales', ['nombre' => 'Halcones'], 'rivales', 'rival_id'],
    'temporadas' => ['/temporadas', ['nombre' => 'T2', 'fecha_inicio' => '2027-01-01', 'fecha_fin' => '2027-06-30'], 'temporadas', 'temporada_id'],
    'torneos' => ['/torneos', ['nombre' => 'Copa', 'temporada_id' => '{t}'], 'torneos', 'torneo_id'],
]);

test('HU-28: borrar un id inexistente o un UUID que no es v7 da el mismo 404', function (string $ruta) {
    /** @var TestCase $this */
    $v7 = catalogoPeticion($this, 'DELETE', "$ruta/".Identificador::generar()->valor())->assertNotFound();
    $v4 = catalogoPeticion($this, 'DELETE', "$ruta/123e4567-e89b-42d3-a456-426614174000")->assertNotFound();

    expect($v4->getContent())->toBe($v7->getContent())
        ->and($v7->json('codigo'))->toBe('recurso_no_encontrado');
    catalogoPeticion($this, 'DELETE', "$ruta/no-es-uuid")->assertNotFound();
})->with(['/lugares', '/rivales', '/temporadas', '/torneos']);
