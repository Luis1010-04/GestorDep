<?php

use Database\Seeders\CriterioGeneralSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Src\Academico\Aplicacion\AsignaturaDuplicada;
use Src\Academico\Dominio\Asignatura;
use Src\Academico\Dominio\RepositorioDeAsignaturas;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

beforeEach(function () {
    sesionesPrepararBase();
    sesionesCrearUsuario('d1@x.com', ['docente']);
    sesionesCrearUsuario('a@x.com', ['entrenador']);
});

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function asignaturasCrear(TestCase $prueba, array $cuerpo, string $correo = 'd1@x.com'): TestResponse
{
    return sesionesPeticion($prueba, 'POST', '/api/v1/asignaturas', $cuerpo, sesionesIniciar($prueba, $correo));
}

test('HU-28 (§7.2 fila 1): un docente crea MAT-101 Matemáticas grado 1', function () {
    /** @var TestCase $this */
    $respuesta = asignaturasCrear($this, ['clave' => 'MAT-101', 'nombre' => 'Matemáticas', 'grado' => 1])->assertCreated();

    expect($respuesta->json('datos'))->toMatchArray(['clave' => 'MAT-101', 'nombre' => 'Matemáticas', 'grado' => 1])
        ->and(array_keys($respuesta->json('datos')))->toBe(['id', 'clave', 'nombre', 'grado', 'actualizado_en']);
    $fila = DB::table('asignaturas')->where('id', $respuesta->json('datos.id'))->first();
    expect($fila->creado_por)->toBe(DB::table('usuarios')->where('correo', 'd1@x.com')->value('id'));
});

test('HU-28 (§7.2 fila 2): repetir la clave con otras mayúsculas o espacios da 409 asignatura_duplicada', function () {
    /** @var TestCase $this */
    asignaturasCrear($this, ['clave' => 'MAT-101', 'nombre' => 'Matemáticas', 'grado' => 1])->assertCreated();

    asignaturasCrear($this, ['clave' => '  mat-101 ', 'nombre' => 'Otra', 'grado' => 2])
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'asignatura_duplicada', 'mensaje' => 'Ya existe una asignatura con esa clave.', 'errores' => []]);
    expect(DB::table('asignaturas')->count())->toBe(1);
});

test('HU-28 (§7.2 fila 3): un grado de 0, 13, texto o decimal da 422 con grado señalado', function (mixed $grado) {
    /** @var TestCase $this */
    asignaturasCrear($this, ['clave' => 'MAT-101', 'nombre' => 'Matemáticas', 'grado' => $grado])
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', 'grado');
})->with([
    'cero' => [0],
    'trece' => [13],
    'texto' => ['uno'],
    'decimal' => [1.5],
    'ausente' => [null],
]);

test('HU-28: los grados 1 y 12 son válidos', function () {
    /** @var TestCase $this */
    asignaturasCrear($this, ['clave' => 'A-1', 'nombre' => 'Uno', 'grado' => 1])->assertCreated();
    asignaturasCrear($this, ['clave' => 'A-12', 'nombre' => 'Doce', 'grado' => 12])->assertCreated();
});

test('HU-28 (§7.2 fila 4): clave de más de 20, nombre vacío o de más de 150 dan 422 con el campo señalado', function (array $cuerpo, string $campo) {
    /** @var TestCase $this */
    asignaturasCrear($this, $cuerpo)->assertUnprocessable()->assertJsonPath('codigo', 'validacion_fallida')->assertJsonPath('errores.0.campo', $campo);
})->with([
    'clave de 21' => [['clave' => str_repeat('a', 21), 'nombre' => 'X', 'grado' => 1], 'clave'],
    'clave vacía' => [['clave' => '   ', 'nombre' => 'X', 'grado' => 1], 'clave'],
    'nombre vacío' => [['clave' => 'X-1', 'nombre' => '   ', 'grado' => 1], 'nombre'],
    'nombre de 151' => [['clave' => 'X-1', 'nombre' => str_repeat('a', 151), 'grado' => 1], 'nombre'],
]);

test('HU-28: una clave de 20 y un nombre de 150 caracteres son válidos', function () {
    /** @var TestCase $this */
    asignaturasCrear($this, ['clave' => str_repeat('a', 20), 'nombre' => str_repeat('b', 150), 'grado' => 3])->assertCreated();
});

test('HU-28 (§7.2 fila 5): el listado va ordenado por clave y paginado', function () {
    /** @var TestCase $this */
    asignaturasCrear($this, ['clave' => 'MAT-101', 'nombre' => 'Matemáticas', 'grado' => 1])->assertCreated();
    asignaturasCrear($this, ['clave' => 'FIS-101', 'nombre' => 'Física', 'grado' => 1])->assertCreated();
    asignaturasCrear($this, ['clave' => 'QUI-101', 'nombre' => 'Química', 'grado' => 1])->assertCreated();
    $sesion = sesionesIniciar($this, 'd1@x.com');

    $lista = sesionesPeticion($this, 'GET', '/api/v1/asignaturas', idDeSesion: $sesion)->assertOk();
    expect(array_column($lista->json('datos'), 'clave'))->toBe(['FIS-101', 'MAT-101', 'QUI-101'])
        ->and($lista->json('meta'))->toBe(['pagina' => 1, 'por_pagina' => 15, 'total' => 3, 'total_paginas' => 1]);

    sesionesPeticion($this, 'GET', '/api/v1/asignaturas?orden=-clave&por_pagina=2&pagina=2', idDeSesion: $sesion)
        ->assertJsonPath('datos.0.clave', 'FIS-101')
        ->assertJsonPath('meta', ['pagina' => 2, 'por_pagina' => 2, 'total' => 3, 'total_paginas' => 2]);
    sesionesPeticion($this, 'GET', '/api/v1/asignaturas?orden=nombre', idDeSesion: $sesion)->assertUnprocessable();
});

test('HU-28 (§7.2 fila 6): borrar da 204, se puede crear la misma clave y una materia que la usa la sigue mostrando', function () {
    /** @var TestCase $this */
    $id = asignaturasCrear($this, ['clave' => 'MAT-101', 'nombre' => 'Matemáticas', 'grado' => 1])->json('datos.id');
    (new CriterioGeneralSeeder)->run();
    $docente = DB::table('usuarios')->where('correo', 'd1@x.com')->value('id');
    $periodo = mundoInsertar('periodos_escolares', ['nombre' => '2026-1', 'fecha_inicio' => '2026-01-12', 'fecha_fin' => '2026-06-30']);
    $materia = mundoInsertar('materias', ['asignatura_id' => $id, 'periodo_id' => $periodo, 'docente_id' => $docente]);
    $sesion = sesionesIniciar($this, 'd1@x.com');

    sesionesPeticion($this, 'DELETE', "/api/v1/asignaturas/$id", idDeSesion: $sesion)->assertNoContent();

    sesionesPeticion($this, 'GET', '/api/v1/asignaturas', idDeSesion: $sesion)->assertJsonPath('meta.total', 0);
    $fila = DB::table('asignaturas')->where('id', $id)->first();
    expect($fila->eliminado_en)->not->toBeNull()->and($fila->eliminado_por)->toBe($docente)
        ->and(DB::table('materias')->join('asignaturas', 'asignaturas.id', '=', 'materias.asignatura_id')->value('asignaturas.clave'))->toBe('MAT-101');
    // La materia que la usa la sigue mostrando por la API (auditoría de C2, M-3).
    sesionesPeticion($this, 'GET', "/api/v1/materias/$materia", idDeSesion: $sesion)
        ->assertOk()
        ->assertJsonPath('datos.asignatura', ['id' => $id, 'clave' => 'MAT-101', 'nombre' => 'Matemáticas']);
    sesionesPeticion($this, 'GET', '/api/v1/materias', idDeSesion: $sesion)
        ->assertJsonPath('datos.0.asignatura', ['id' => $id, 'clave' => 'MAT-101', 'nombre' => 'Matemáticas']);
    asignaturasCrear($this, ['clave' => 'MAT-101', 'nombre' => 'Matemáticas', 'grado' => 1])->assertCreated();
});

test('HU-28: borrar una asignatura inexistente, ya eliminada o con id que no es v7 da 404', function () {
    /** @var TestCase $this */
    $sesion = sesionesIniciar($this, 'd1@x.com');
    $id = asignaturasCrear($this, ['clave' => 'MAT-101', 'nombre' => 'Matemáticas', 'grado' => 1])->json('datos.id');

    sesionesPeticion($this, 'DELETE', '/api/v1/asignaturas/'.Identificador::generar()->valor(), idDeSesion: $sesion)
        ->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');
    sesionesPeticion($this, 'DELETE', "/api/v1/asignaturas/$id", idDeSesion: $sesion)->assertNoContent();
    sesionesPeticion($this, 'DELETE', "/api/v1/asignaturas/$id", idDeSesion: $sesion)->assertNotFound();
    sesionesPeticion($this, 'DELETE', '/api/v1/asignaturas/123e4567-e89b-42d3-a456-426614174000', idDeSesion: $sesion)->assertNotFound();
});

test('HU-28 (§7.2 fila 7): un entrenador sin el rol docente recibe 403 en las tres rutas', function () {
    /** @var TestCase $this */
    $sesion = sesionesIniciar($this, 'a@x.com');
    $id = Identificador::generar()->valor();

    sesionesPeticion($this, 'GET', '/api/v1/asignaturas', idDeSesion: $sesion)->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    sesionesPeticion($this, 'POST', '/api/v1/asignaturas', ['clave' => 'X', 'nombre' => 'X', 'grado' => 1], $sesion)->assertForbidden();
    sesionesPeticion($this, 'DELETE', "/api/v1/asignaturas/$id", idDeSesion: $sesion)->assertForbidden();
    expect(DB::table('asignaturas')->count())->toBe(0);
});

test('HU-28 (§7.2 fila 8): sin sesión las tres rutas responden 401', function () {
    /** @var TestCase $this */
    $id = Identificador::generar()->valor();

    sesionesPeticion($this, 'GET', '/api/v1/asignaturas')->assertUnauthorized();
    sesionesPeticion($this, 'POST', '/api/v1/asignaturas', ['clave' => 'X'])->assertUnauthorized();
    sesionesPeticion($this, 'DELETE', "/api/v1/asignaturas/$id")->assertUnauthorized();
});

test('HU-28: si otra petición ganó la carrera por la misma clave, el índice único también responde asignatura_duplicada', function () {
    /** @var TestCase $this */
    $repositorio = app(RepositorioDeAsignaturas::class);
    $repositorio->guardarNueva(new Asignatura(Identificador::generar(), 'MAT-101', 'Matemáticas', 1, app(Reloj::class)->ahora()));

    expect(fn () => $repositorio->guardarNueva(new Asignatura(Identificador::generar(), 'mat-101', 'Otra', 2, app(Reloj::class)->ahora())))
        ->toThrow(AsignaturaDuplicada::class);
    expect(DB::table('asignaturas')->count())->toBe(1);
});
