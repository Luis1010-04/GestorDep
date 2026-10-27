<?php

use Database\Seeders\CriterioGeneralSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Datos de partida de docs/hitos/f1-h2.md §7.0 que usan las materias: docentes D1 y D2, entrenador A, periodos P1 y
 * P0, asignaturas S1 y S2, materias M1 (D1) y M2 (D2).
 *
 * @param  array<string, string>|null  $nuevo
 * @return array{d1: string, d2: string, a: string, p1: string, p0: string, s1: string, s2: string, m1: string, m2: string}
 */
function materiasMundo(?array $nuevo = null): array
{
    static $mundo;
    $mundo = $nuevo ?? $mundo;

    return $mundo;
}

beforeEach(function () {
    sesionesPrepararBase();
    (new CriterioGeneralSeeder)->run();
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-03-10 12:00:00', new DateTimeZone('UTC'))));

    $d1 = sesionesCrearUsuario('d1@x.com', ['docente']);
    $d2 = sesionesCrearUsuario('d2@x.com', ['docente']);
    $a = sesionesCrearUsuario('a@x.com', ['entrenador']);
    $p1 = mundoInsertar('periodos_escolares', ['nombre' => '2026-1', 'fecha_inicio' => '2026-01-12', 'fecha_fin' => '2026-06-30']);
    $p0 = mundoInsertar('periodos_escolares', ['nombre' => '2025-2', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-12-20']);
    $s1 = mundoInsertar('asignaturas', ['clave' => 'MAT-101', 'nombre' => 'Matemáticas', 'grado' => 1]);
    $s2 = mundoInsertar('asignaturas', ['clave' => 'FIS-101', 'nombre' => 'Física', 'grado' => 1]);

    materiasMundo([
        'd1' => $d1, 'd2' => $d2, 'a' => $a, 'p1' => $p1, 'p0' => $p0, 's1' => $s1, 's2' => $s2,
        'm1' => mundoInsertar('materias', ['asignatura_id' => $s1, 'periodo_id' => $p1, 'docente_id' => $d1]),
        'm2' => mundoInsertar('materias', ['asignatura_id' => $s2, 'periodo_id' => $p1, 'docente_id' => $d2]),
    ]);
});

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function materiasCrear(TestCase $prueba, array $cuerpo, string $correo = 'd1@x.com'): TestResponse
{
    return sesionesPeticion($prueba, 'POST', '/api/v1/materias', $cuerpo, sesionesIniciar($prueba, $correo));
}

test('HU-06 (§7.3 fila 1): D1 crea una materia para S2 y P1 y queda como su docente, con historial y criterio general', function () {
    /** @var TestCase $this */
    $m = materiasMundo();

    $respuesta = materiasCrear($this, ['asignatura_id' => $m['s2'], 'periodo_id' => $m['p1']])->assertCreated();

    expect($respuesta->json('datos'))->toMatchArray([
        'asignatura' => ['id' => $m['s2'], 'clave' => 'FIS-101', 'nombre' => 'Física'],
        'periodo' => ['id' => $m['p1'], 'nombre' => '2026-1'],
        'criterio_aplicado' => ['ambito' => 'general', 'calificacion_minima' => '7.00'],
        'actualizado_en' => '2026-03-10T12:00:00+00:00',
    ])->and(array_keys($respuesta->json('datos')))->toBe(['id', 'asignatura', 'periodo', 'criterio_aplicado', 'actualizado_en']);

    $id = $respuesta->json('datos.id');
    $fila = DB::table('materias')->where('id', $id)->first();
    expect($fila->docente_id)->toBe($m['d1'])->and($fila->creado_por)->toBe($m['d1']);

    $historial = DB::table('materias_historial')->where('materia_id', $id)->get();
    expect($historial)->toHaveCount(1)
        ->and($historial[0]->operacion)->toBe('crear')
        ->and($historial[0]->version)->toBe(1)
        ->and($historial[0]->usuario_id)->toBe($m['d1'])
        ->and($historial[0]->datos_anteriores)->toBeNull()
        ->and(json_decode($historial[0]->datos_nuevos, true))->toEqual(['asignatura_id' => $m['s2'], 'periodo_id' => $m['p1'], 'docente_id' => $m['d1']]);
});

test('HU-06 (§7.3 fila 2): repetir asignatura y periodo da 409 materia_duplicada y no deja historial', function () {
    /** @var TestCase $this */
    $m = materiasMundo();

    materiasCrear($this, ['asignatura_id' => $m['s1'], 'periodo_id' => $m['p1']])
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'materia_duplicada', 'mensaje' => 'Ya tiene una materia con esa asignatura en ese periodo.', 'errores' => []]);
    expect(DB::table('materias')->count())->toBe(2)->and(DB::table('materias_historial')->count())->toBe(0);
});

test('HU-06 (§7.3 fila 3): otro docente crea la misma asignatura y periodo', function () {
    /** @var TestCase $this */
    $m = materiasMundo();

    $id = materiasCrear($this, ['asignatura_id' => $m['s1'], 'periodo_id' => $m['p1']], 'd2@x.com')->assertCreated()->json('datos.id');

    expect(DB::table('materias')->where('id', $id)->value('docente_id'))->toBe($m['d2']);
});

test('HU-06 (§7.3 fila 4): una asignatura o un periodo inexistente o eliminado da 422 con su código', function () {
    /** @var TestCase $this */
    $m = materiasMundo();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    $v7Inexistente = Identificador::generar()->valor();
    $noV7 = '123e4567-e89b-42d3-a456-426614174000';
    sesionesPeticion($this, 'DELETE', '/api/v1/asignaturas/'.$m['s2'], idDeSesion: $sesion)->assertNoContent();
    sesionesPeticion($this, 'DELETE', '/api/v1/periodos-escolares/'.$m['p0'], idDeSesion: $sesion)->assertNoContent();

    foreach ([$v7Inexistente, $noV7, $m['s2']] as $asignatura) {
        sesionesPeticion($this, 'POST', '/api/v1/materias', ['asignatura_id' => $asignatura, 'periodo_id' => $m['p1']], $sesion)
            ->assertUnprocessable()
            ->assertExactJson(['codigo' => 'asignatura_inexistente', 'mensaje' => 'La asignatura indicada no existe.', 'errores' => []]);
    }
    foreach ([$v7Inexistente, $noV7, $m['p0']] as $periodo) {
        sesionesPeticion($this, 'POST', '/api/v1/materias', ['asignatura_id' => $m['s1'], 'periodo_id' => $periodo], $sesion)
            ->assertUnprocessable()
            ->assertExactJson(['codigo' => 'periodo_inexistente', 'mensaje' => 'El periodo escolar indicado no existe.', 'errores' => []]);
    }
    expect(DB::table('materias')->count())->toBe(2);
});

test('HU-06 (§7.3 fila 5): un campo faltante o un UUID mal formado da 422 validacion_fallida', function (array $cuerpo, string $campo) {
    /** @var TestCase $this */
    $m = materiasMundo();
    $cuerpo = array_map(fn ($v) => $v === '{s1}' ? $m['s1'] : ($v === '{p1}' ? $m['p1'] : $v), $cuerpo);

    sesionesPeticion($this, 'POST', '/api/v1/materias', $cuerpo, sesionesIniciar($this, 'd1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', $campo);
})->with([
    'sin asignatura' => [['periodo_id' => '{p1}'], 'asignatura_id'],
    'sin periodo' => [['asignatura_id' => '{s1}'], 'periodo_id'],
    'asignatura mal formada' => [['asignatura_id' => 'no-es-uuid', 'periodo_id' => '{p1}'], 'asignatura_id'],
    'periodo mal formado' => [['asignatura_id' => '{s1}', 'periodo_id' => '123'], 'periodo_id'],
    'asignatura numérica' => [['asignatura_id' => 5, 'periodo_id' => '{p1}'], 'asignatura_id'],
]);

test('HU-06 (§7.3 fila 6): GET /materias solo muestra las de D1 y filtra por periodo', function () {
    /** @var TestCase $this */
    $m = materiasMundo();
    $otra = mundoInsertar('materias', ['asignatura_id' => $m['s2'], 'periodo_id' => $m['p0'], 'docente_id' => $m['d1']]);
    $sesion = sesionesIniciar($this, 'd1@x.com');

    $todas = sesionesPeticion($this, 'GET', '/api/v1/materias', idDeSesion: $sesion)->assertOk();
    expect(array_column($todas->json('datos'), 'id'))->toBe([$otra, $m['m1']])
        ->and($todas->json('meta'))->toBe(['pagina' => 1, 'por_pagina' => 15, 'total' => 2, 'total_paginas' => 1]);

    $delPeriodo = sesionesPeticion($this, 'GET', '/api/v1/materias?filtro[periodo_id]='.$m['p0'], idDeSesion: $sesion)->assertOk();
    expect(array_column($delPeriodo->json('datos'), 'id'))->toBe([$otra])->and($delPeriodo->json('meta.total'))->toBe(1);

    sesionesPeticion($this, 'GET', '/api/v1/materias?filtro[periodo_id]='.Identificador::generar()->valor(), idDeSesion: $sesion)
        ->assertOk()->assertJsonPath('datos', []);
    sesionesPeticion($this, 'GET', '/api/v1/materias?filtro[periodo_id]=no-es-uuid', idDeSesion: $sesion)->assertUnprocessable();
    sesionesPeticion($this, 'GET', '/api/v1/materias?por_pagina=1&pagina=2', idDeSesion: $sesion)
        ->assertJsonPath('datos.0.id', $m['m1'])
        ->assertJsonPath('meta', ['pagina' => 2, 'por_pagina' => 1, 'total' => 2, 'total_paginas' => 2]);
});

test('RNF-02 (§7.3 fila 7): D1 consulta M2 (de D2) y recibe un 404 idéntico al de un id inexistente', function () {
    /** @var TestCase $this */
    $m = materiasMundo();
    $sesion = sesionesIniciar($this, 'd1@x.com');

    $ajena = sesionesPeticion($this, 'GET', '/api/v1/materias/'.$m['m2'], idDeSesion: $sesion)->assertNotFound();
    $inexistente = sesionesPeticion($this, 'GET', '/api/v1/materias/'.Identificador::generar()->valor(), idDeSesion: $sesion)->assertNotFound();
    $noV7 = sesionesPeticion($this, 'GET', '/api/v1/materias/123e4567-e89b-42d3-a456-426614174000', idDeSesion: $sesion)->assertNotFound();

    expect($ajena->getContent())->toBe($inexistente->getContent())->and($noV7->getContent())->toBe($inexistente->getContent())
        ->and($inexistente->json())->toBe(['codigo' => 'recurso_no_encontrado', 'mensaje' => 'El recurso solicitado no existe.', 'errores' => []]);
});

test('HU-06: el dueño consulta su materia con el criterio general', function () {
    /** @var TestCase $this */
    $m = materiasMundo();

    $respuesta = sesionesPeticion($this, 'GET', '/api/v1/materias/'.$m['m1'], idDeSesion: sesionesIniciar($this, 'd1@x.com'))->assertOk();

    expect($respuesta->json('datos.id'))->toBe($m['m1'])
        ->and($respuesta->json('datos.asignatura.clave'))->toBe('MAT-101')
        ->and($respuesta->json('datos.criterio_aplicado'))->toBe(['ambito' => 'general', 'calificacion_minima' => '7.00']);
});

test('HU-06 (§7.3 fila 8): si se elimina la asignatura (y el periodo) de M1, la consulta sigue mostrándolos', function () {
    /** @var TestCase $this */
    $m = materiasMundo();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    sesionesPeticion($this, 'DELETE', '/api/v1/asignaturas/'.$m['s1'], idDeSesion: sesionesIniciar($this, 'd1@x.com'))->assertNoContent();
    sesionesPeticion($this, 'DELETE', '/api/v1/periodos-escolares/'.$m['p1'], idDeSesion: $sesion)->assertNoContent();

    sesionesPeticion($this, 'GET', '/api/v1/materias/'.$m['m1'], idDeSesion: $sesion)
        ->assertOk()
        ->assertJsonPath('datos.asignatura.clave', 'MAT-101')
        ->assertJsonPath('datos.periodo.nombre', '2026-1');
    sesionesPeticion($this, 'GET', '/api/v1/materias', idDeSesion: $sesion)->assertJsonPath('meta.total', 1)->assertJsonPath('datos.0.asignatura.nombre', 'Matemáticas');
});

test('HU-06 (§7.3 fila 9): un entrenador sin el rol docente recibe 403 en las tres rutas', function () {
    /** @var TestCase $this */
    $m = materiasMundo();
    $sesion = sesionesIniciar($this, 'a@x.com');

    sesionesPeticion($this, 'GET', '/api/v1/materias', idDeSesion: $sesion)->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    sesionesPeticion($this, 'POST', '/api/v1/materias', ['asignatura_id' => $m['s1'], 'periodo_id' => $m['p0']], $sesion)->assertForbidden();
    sesionesPeticion($this, 'GET', '/api/v1/materias/'.$m['m1'], idDeSesion: $sesion)->assertForbidden();
    expect(DB::table('materias')->count())->toBe(2);
});

test('HU-06 (§7.3 fila 10): sin sesión las tres rutas responden 401', function () {
    /** @var TestCase $this */
    $m = materiasMundo();

    sesionesPeticion($this, 'GET', '/api/v1/materias')->assertUnauthorized();
    sesionesPeticion($this, 'POST', '/api/v1/materias', ['asignatura_id' => $m['s1']])->assertUnauthorized();
    sesionesPeticion($this, 'GET', '/api/v1/materias/'.$m['m1'])->assertUnauthorized();
});

test('RNF-02: si falla el historial no queda la materia (misma transacción)', function () {
    /** @var TestCase $this */
    $m = materiasMundo();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    esquemaMarcarSucio();
    DB::connection('propietario')->statement('alter table materias_historial add constraint ck_prueba_fallo check (false)');

    sesionesPeticion($this, 'POST', '/api/v1/materias', ['asignatura_id' => $m['s1'], 'periodo_id' => $m['p0']], $sesion)->assertStatus(500);

    expect(DB::table('materias')->count())->toBe(2);
});

test('RN-01: sin criterio general sembrado, consultar una materia responde 500 y no inventa un mínimo', function () {
    /** @var TestCase $this */
    $m = materiasMundo();
    DB::connection('propietario')->table('criterios_aprobacion')->delete();

    sesionesPeticion($this, 'GET', '/api/v1/materias/'.$m['m1'], idDeSesion: sesionesIniciar($this, 'd1@x.com'))
        ->assertStatus(500)->assertJsonPath('codigo', 'error_interno');
});
