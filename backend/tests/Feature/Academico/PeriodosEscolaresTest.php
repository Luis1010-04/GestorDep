<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Src\Academico\Aplicacion\PeriodoDuplicado;
use Src\Academico\Aplicacion\PeriodoVigente;
use Src\Academico\Dominio\PeriodoEscolar;
use Src\Academico\Dominio\RepositorioDePeriodos;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

beforeEach(function () {
    sesionesPrepararBase();
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-03-10 12:00:00', new DateTimeZone('UTC'))));
    sesionesCrearUsuario('d1@x.com', ['docente']);
    sesionesCrearUsuario('a@x.com', ['entrenador']);
    sesionesCrearUsuario('de@x.com', ['docente', 'entrenador']);
});

/** @return TestResponse<Response> */
function periodosCrear(TestCase $prueba, string $nombre, string $inicio, string $fin, string $correo = 'd1@x.com'): TestResponse
{
    return sesionesPeticion($prueba, 'POST', '/api/v1/periodos-escolares', ['nombre' => $nombre, 'fecha_inicio' => $inicio, 'fecha_fin' => $fin], sesionesIniciar($prueba, $correo));
}

/**
 * P1 vigente, P0 pasado y P2 futuro de docs/hitos/f1-h2.md §7.0.
 *
 * @return array{p1: string, p0: string, p2: string}
 */
function periodosDePartida(): array
{
    return [
        'p1' => mundoInsertar('periodos_escolares', ['nombre' => '2026-1', 'fecha_inicio' => '2026-01-12', 'fecha_fin' => '2026-06-30']),
        'p0' => mundoInsertar('periodos_escolares', ['nombre' => '2025-2', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-12-20']),
        'p2' => mundoInsertar('periodos_escolares', ['nombre' => '2026-2', 'fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-12-18']),
    ];
}

test('HU-28 (§7.1 fila 1): un docente crea un periodo futuro, no vigente, con el instante del reloj', function () {
    /** @var TestCase $this */
    $respuesta = periodosCrear($this, '2026-2', '2026-08-03', '2026-12-18')->assertCreated();

    expect($respuesta->json('datos'))->toMatchArray([
        'nombre' => '2026-2', 'fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-12-18', 'vigente' => false,
        'actualizado_en' => '2026-03-10T12:00:00+00:00',
    ])->and(array_keys($respuesta->json('datos')))->toBe(['id', 'nombre', 'fecha_inicio', 'fecha_fin', 'vigente', 'actualizado_en']);
    $fila = DB::table('periodos_escolares')->where('id', $respuesta->json('datos.id'))->first();
    expect($fila->creado_por)->toBe(DB::table('usuarios')->where('correo', 'd1@x.com')->value('id'));
});

test('HU-28 (§7.1 fila 2): un periodo que contiene hoy se crea vigente', function () {
    /** @var TestCase $this */
    periodosCrear($this, '2026-1', '2026-01-12', '2026-06-30')->assertCreated()->assertJsonPath('datos.vigente', true);
});

test('HU-28 (§7.1 fila 3): repetir el nombre con otras mayúsculas o espacios da 409 periodo_duplicado', function () {
    /** @var TestCase $this */
    periodosCrear($this, '2026-A', '2026-08-03', '2026-12-18')->assertCreated();

    periodosCrear($this, '  2026-a  ', '2027-01-01', '2027-06-01')
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'periodo_duplicado', 'mensaje' => 'Ya existe un periodo escolar con ese nombre.', 'errores' => []]);
    expect(DB::table('periodos_escolares')->count())->toBe(1);
});

test('HU-28 (§7.1 fila 4): una fecha_fin igual o anterior a fecha_inicio da 422 rango_de_fechas_invalido', function () {
    /** @var TestCase $this */
    periodosCrear($this, 'X', '2026-08-03', '2026-08-03')->assertUnprocessable()->assertJsonPath('codigo', 'rango_de_fechas_invalido');
    periodosCrear($this, 'X', '2026-08-03', '2026-08-02')
        ->assertUnprocessable()
        ->assertExactJson(['codigo' => 'rango_de_fechas_invalido', 'mensaje' => 'La fecha de fin debe ser posterior a la fecha de inicio.', 'errores' => []]);
    expect(DB::table('periodos_escolares')->count())->toBe(0);
});

test('HU-28 (§7.1 fila 5): un campo faltante o una fecha con formato inválido da 422 con el campo señalado', function (array $cuerpo, string $campo) {
    /** @var TestCase $this */
    sesionesPeticion($this, 'POST', '/api/v1/periodos-escolares', $cuerpo, sesionesIniciar($this, 'd1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', $campo);
})->with([
    'sin nombre' => [['fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-12-18'], 'nombre'],
    'sin fecha de inicio' => [['nombre' => 'X', 'fecha_fin' => '2026-12-18'], 'fecha_inicio'],
    'sin fecha de fin' => [['nombre' => 'X', 'fecha_inicio' => '2026-08-03'], 'fecha_fin'],
    'fecha con formato inválido' => [['nombre' => 'X', 'fecha_inicio' => '03/08/2026', 'fecha_fin' => '2026-12-18'], 'fecha_inicio'],
    'fecha inexistente' => [['nombre' => 'X', 'fecha_inicio' => '2026-02-30', 'fecha_fin' => '2026-12-18'], 'fecha_inicio'],
    'nombre de más de 100 caracteres' => [['nombre' => str_repeat('a', 101), 'fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-12-18'], 'nombre'],
]);

test('HU-28 (§7.1 fila 6): el listado va por -fecha_inicio y solo el periodo que contiene hoy es vigente', function () {
    /** @var TestCase $this */
    $ids = periodosDePartida();

    $respuesta = sesionesPeticion($this, 'GET', '/api/v1/periodos-escolares', idDeSesion: sesionesIniciar($this, 'd1@x.com'))->assertOk();

    expect(array_column($respuesta->json('datos'), 'id'))->toBe([$ids['p2'], $ids['p1'], $ids['p0']])
        ->and(array_column($respuesta->json('datos'), 'vigente'))->toBe([false, true, false])
        ->and($respuesta->json('meta'))->toBe(['pagina' => 1, 'por_pagina' => 15, 'total' => 3, 'total_paginas' => 1]);
});

test('HU-28: el listado admite orden ascendente, paginación y rechaza parámetros inválidos', function () {
    /** @var TestCase $this */
    $ids = periodosDePartida();
    $sesion = sesionesIniciar($this, 'd1@x.com');

    sesionesPeticion($this, 'GET', '/api/v1/periodos-escolares?orden=fecha_inicio', idDeSesion: $sesion)
        ->assertJsonPath('datos.0.id', $ids['p0']);
    sesionesPeticion($this, 'GET', '/api/v1/periodos-escolares?por_pagina=1&pagina=2', idDeSesion: $sesion)
        ->assertJsonPath('datos.0.id', $ids['p1'])
        ->assertJsonPath('meta', ['pagina' => 2, 'por_pagina' => 1, 'total' => 3, 'total_paginas' => 3]);
    sesionesPeticion($this, 'GET', '/api/v1/periodos-escolares?orden=nombre', idDeSesion: $sesion)->assertUnprocessable();
    sesionesPeticion($this, 'GET', '/api/v1/periodos-escolares?por_pagina=101', idDeSesion: $sesion)->assertUnprocessable();
});

test('DD-45 (§7.1 fila 7): con dos periodos que contienen hoy, solo el de fecha_inicio más reciente es vigente', function () {
    /** @var TestCase $this */
    $largo = mundoInsertar('periodos_escolares', ['nombre' => 'Anual', 'fecha_inicio' => '2026-01-05', 'fecha_fin' => '2026-12-18']);
    $corto = mundoInsertar('periodos_escolares', ['nombre' => 'Semestre', 'fecha_inicio' => '2026-02-01', 'fecha_fin' => '2026-07-15']);

    $respuesta = sesionesPeticion($this, 'GET', '/api/v1/periodos-escolares', idDeSesion: sesionesIniciar($this, 'd1@x.com'))->assertOk();

    expect(array_column($respuesta->json('datos'), 'id'))->toBe([$corto, $largo])
        ->and(array_column($respuesta->json('datos'), 'vigente'))->toBe([true, false]);
    expect(app(PeriodoVigente::class)->identificador()?->valor())->toBe($corto);
});

test('DD-45: los extremos del rango cuentan como vigentes y sin periodo que contenga hoy no hay vigente', function () {
    /** @var TestCase $this */
    $ultimoDia = mundoInsertar('periodos_escolares', ['nombre' => 'Termina hoy', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-03-10']);
    expect(app(PeriodoVigente::class)->identificador()?->valor())->toBe($ultimoDia);

    DB::connection('propietario')->table('periodos_escolares')->delete();
    $primerDia = mundoInsertar('periodos_escolares', ['nombre' => 'Empieza hoy', 'fecha_inicio' => '2026-03-10', 'fecha_fin' => '2026-06-30']);
    expect(app(PeriodoVigente::class)->identificador()?->valor())->toBe($primerDia);

    DB::connection('propietario')->table('periodos_escolares')->delete();
    mundoInsertar('periodos_escolares', ['nombre' => 'Pasado', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-03-09']);
    mundoInsertar('periodos_escolares', ['nombre' => 'Futuro', 'fecha_inicio' => '2026-03-11', 'fecha_fin' => '2026-12-18']);
    expect(app(PeriodoVigente::class)->identificador())->toBeNull();
});

test('DD-45: un periodo eliminado no puede ser el vigente', function () {
    /** @var TestCase $this */
    $ids = periodosDePartida();
    sesionesPeticion($this, 'DELETE', '/api/v1/periodos-escolares/'.$ids['p1'], idDeSesion: sesionesIniciar($this, 'd1@x.com'))->assertNoContent();

    expect(app(PeriodoVigente::class)->identificador())->toBeNull();
});

test('HU-28 (§7.1 fila 8): borrar P0 da 204, deja de listarse y una materia que lo usa lo sigue mostrando', function () {
    /** @var TestCase $this */
    $ids = periodosDePartida();
    $docente = DB::table('usuarios')->where('correo', 'd1@x.com')->value('id');
    $asignatura = mundoInsertar('asignaturas', ['clave' => 'MAT-101', 'nombre' => 'Matemáticas', 'grado' => 1]);
    mundoInsertar('materias', ['asignatura_id' => $asignatura, 'periodo_id' => $ids['p0'], 'docente_id' => $docente]);
    $sesion = sesionesIniciar($this, 'd1@x.com');

    sesionesPeticion($this, 'DELETE', '/api/v1/periodos-escolares/'.$ids['p0'], idDeSesion: $sesion)->assertNoContent();

    sesionesPeticion($this, 'GET', '/api/v1/periodos-escolares', idDeSesion: $sesion)->assertJsonPath('meta.total', 2);
    $fila = DB::table('periodos_escolares')->where('id', $ids['p0'])->first();
    expect($fila->eliminado_en)->not->toBeNull()
        ->and($fila->eliminado_por)->toBe($docente)
        ->and(DB::table('materias')->join('periodos_escolares', 'periodos_escolares.id', '=', 'materias.periodo_id')->value('periodos_escolares.nombre'))->toBe('2025-2');
});

test('HU-28 (§7.1 fila 9): tras borrar un periodo se puede crear otro con el mismo nombre', function () {
    /** @var TestCase $this */
    $id = periodosCrear($this, '2026-A', '2026-08-03', '2026-12-18')->json('datos.id');
    sesionesPeticion($this, 'DELETE', "/api/v1/periodos-escolares/$id", idDeSesion: sesionesIniciar($this, 'd1@x.com'))->assertNoContent();

    $nuevo = periodosCrear($this, '2026-A', '2026-08-03', '2026-12-18')->assertCreated();

    expect($nuevo->json('datos.id'))->not->toBe($id)->and(DB::table('periodos_escolares')->count())->toBe(2);
});

test('HU-28 (§7.1 fila 10): borrar un id inexistente, ya eliminado o que no es v7 da 404', function () {
    /** @var TestCase $this */
    $sesion = sesionesIniciar($this, 'd1@x.com');
    $id = periodosCrear($this, 'X', '2026-08-03', '2026-12-18')->json('datos.id');

    sesionesPeticion($this, 'DELETE', '/api/v1/periodos-escolares/'.Identificador::generar()->valor(), idDeSesion: $sesion)
        ->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');
    sesionesPeticion($this, 'DELETE', "/api/v1/periodos-escolares/$id", idDeSesion: $sesion)->assertNoContent();
    sesionesPeticion($this, 'DELETE', "/api/v1/periodos-escolares/$id", idDeSesion: $sesion)->assertNotFound();
    sesionesPeticion($this, 'DELETE', '/api/v1/periodos-escolares/123e4567-e89b-42d3-a456-426614174000', idDeSesion: $sesion)
        ->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');
    sesionesPeticion($this, 'DELETE', '/api/v1/periodos-escolares/no-es-uuid', idDeSesion: $sesion)->assertNotFound();
});

test('DD-49 (§7.1 fila 11): un entrenador sin el rol docente recibe 403 en las tres rutas', function () {
    /** @var TestCase $this */
    $sesion = sesionesIniciar($this, 'a@x.com');
    $id = Identificador::generar()->valor();

    sesionesPeticion($this, 'GET', '/api/v1/periodos-escolares', idDeSesion: $sesion)->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    sesionesPeticion($this, 'POST', '/api/v1/periodos-escolares', ['nombre' => 'X', 'fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-12-18'], $sesion)->assertForbidden();
    sesionesPeticion($this, 'DELETE', "/api/v1/periodos-escolares/$id", idDeSesion: $sesion)->assertForbidden();
    expect(DB::table('periodos_escolares')->count())->toBe(0);
});

test('DD-49 (§7.1 fila 12): un usuario con los roles docente y entrenador crea un periodo', function () {
    /** @var TestCase $this */
    periodosCrear($this, 'X', '2026-08-03', '2026-12-18', 'de@x.com')->assertCreated();
});

test('HU-28 (§7.1 fila 13): sin sesión las tres rutas responden 401', function () {
    /** @var TestCase $this */
    $id = Identificador::generar()->valor();

    sesionesPeticion($this, 'GET', '/api/v1/periodos-escolares')->assertUnauthorized();
    sesionesPeticion($this, 'POST', '/api/v1/periodos-escolares', ['nombre' => 'X'])->assertUnauthorized();
    sesionesPeticion($this, 'DELETE', "/api/v1/periodos-escolares/$id")->assertUnauthorized();
});

test('HU-28: si otra petición ganó la carrera por el mismo nombre, el índice único también responde periodo_duplicado', function () {
    /** @var TestCase $this */
    $repositorio = app(RepositorioDePeriodos::class);
    $inicio = new DateTimeImmutable('2026-08-03', new DateTimeZone('UTC'));
    $fin = new DateTimeImmutable('2026-12-18', new DateTimeZone('UTC'));
    $repositorio->guardarNuevo(new PeriodoEscolar(Identificador::generar(), '2026-A', $inicio, $fin, app(Reloj::class)->ahora()));

    expect(fn () => $repositorio->guardarNuevo(new PeriodoEscolar(Identificador::generar(), '2026-a', $inicio, $fin, app(Reloj::class)->ahora())))
        ->toThrow(PeriodoDuplicado::class);
    expect(DB::table('periodos_escolares')->count())->toBe(1);
});
