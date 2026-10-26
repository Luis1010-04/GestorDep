<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Plantel\Aplicacion\CategoriaDuplicada;
use Src\Plantel\Dominio\Categoria;
use Src\Plantel\Dominio\RepositorioDeCategorias;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

beforeEach(function () {
    sesionesPrepararBase();
    sesionesCrearUsuario('a@x.com', ['entrenador']);
    sesionesCrearUsuario('b@x.com', ['entrenador']);
    sesionesCrearUsuario('d@x.com', ['docente']);
});

/** @return TestResponse<Response> */
function categoriasCrear(TestCase $prueba, string $correo, string $nombre): TestResponse
{
    return sesionesPeticion($prueba, 'POST', '/api/v1/categorias', ['nombre' => $nombre], sesionesIniciar($prueba, $correo));
}

test('HU-28: un entrenador crea "Sub-17" y cualquier entrenador la ve', function () {
    /** @var TestCase $this */
    $respuesta = categoriasCrear($this, 'a@x.com', 'Sub-17')->assertCreated();
    $respuesta->assertJsonPath('datos.nombre', 'Sub-17')->assertJsonStructure(['datos' => ['id', 'nombre', 'actualizado_en']]);
    expect(array_keys($respuesta->json('datos')))->toBe(['id', 'nombre', 'actualizado_en']);

    $sesionB = sesionesIniciar($this, 'b@x.com');
    sesionesPeticion($this, 'GET', '/api/v1/categorias', idDeSesion: $sesionB)
        ->assertOk()
        ->assertJsonPath('datos.0.id', $respuesta->json('datos.id'))
        ->assertJsonPath('meta.total', 1);

    $fila = DB::table('categorias')->where('id', $respuesta->json('datos.id'))->first();
    expect($fila->creado_por)->toBe(DB::table('usuarios')->where('correo', 'a@x.com')->value('id'))->and($fila->eliminado_en)->toBeNull();
});

test('HU-28: otro entrenador crea "sub-17" y recibe 409 categoria_duplicada', function () {
    /** @var TestCase $this */
    categoriasCrear($this, 'a@x.com', 'Sub-17')->assertCreated();

    categoriasCrear($this, 'b@x.com', 'sub-17')
        ->assertStatus(409)
        ->assertExactJson([
            'codigo' => 'categoria_duplicada',
            'mensaje' => 'Ya existe una categoría con ese nombre.',
            'errores' => [],
        ]);
    expect(DB::table('categorias')->count())->toBe(1);
});

test('HU-28: un docente recibe 403 al crear y al listar', function () {
    /** @var TestCase $this */
    $sesion = sesionesIniciar($this, 'd@x.com');

    sesionesPeticion($this, 'POST', '/api/v1/categorias', ['nombre' => 'Sub-17'], $sesion)->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    sesionesPeticion($this, 'GET', '/api/v1/categorias', idDeSesion: $sesion)->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    expect(DB::table('categorias')->count())->toBe(0);
});

test('HU-28: borrar una categoría sin equipos da 204 y deja de listarse', function () {
    /** @var TestCase $this */
    $id = categoriasCrear($this, 'a@x.com', 'Sub-17')->json('datos.id');
    $sesionB = sesionesIniciar($this, 'b@x.com');

    sesionesPeticion($this, 'DELETE', "/api/v1/categorias/$id", idDeSesion: $sesionB)->assertNoContent();

    sesionesPeticion($this, 'GET', '/api/v1/categorias', idDeSesion: $sesionB)->assertOk()->assertJsonPath('meta.total', 0)->assertJsonPath('datos', []);
    $fila = DB::table('categorias')->where('id', $id)->first();
    expect($fila)->not->toBeNull()
        ->and($fila->eliminado_en)->not->toBeNull()
        ->and($fila->eliminado_por)->toBe(DB::table('usuarios')->where('correo', 'b@x.com')->value('id'));
});

test('HU-28: borrar una categoría con equipos da 204 y el equipo conserva su categoría', function () {
    /** @var TestCase $this */
    $id = categoriasCrear($this, 'a@x.com', 'Sub-17')->json('datos.id');
    $equipoId = Identificador::generar()->valor();
    DB::connection('propietario')->table('equipos')->insert(['id' => $equipoId, 'nombre' => 'Aguilas', 'categoria_id' => $id]);

    sesionesPeticion($this, 'DELETE', "/api/v1/categorias/$id", idDeSesion: sesionesIniciar($this, 'a@x.com'))->assertNoContent();

    // Aquí se comprueba en la base que la categoría sigue resoluble desde el equipo; la lectura por la API (GET /equipos) la cubre EquiposTest.
    $categoria = DB::table('equipos')->join('categorias', 'categorias.id', '=', 'equipos.categoria_id')
        ->where('equipos.id', $equipoId)->value('categorias.nombre');
    expect($categoria)->toBe('Sub-17');
});

test('HU-28: se puede crear de nuevo el nombre de una categoría borrada', function () {
    /** @var TestCase $this */
    $id = categoriasCrear($this, 'a@x.com', 'Sub-17')->json('datos.id');
    sesionesPeticion($this, 'DELETE', "/api/v1/categorias/$id", idDeSesion: sesionesIniciar($this, 'a@x.com'))->assertNoContent();

    $nueva = categoriasCrear($this, 'b@x.com', 'Sub-17')->assertCreated();

    expect($nueva->json('datos.id'))->not->toBe($id)->and(DB::table('categorias')->count())->toBe(2);
});

test('HU-28: borrar una categoría inexistente o ya borrada da 404', function () {
    /** @var TestCase $this */
    $sesion = sesionesIniciar($this, 'a@x.com');
    $id = categoriasCrear($this, 'a@x.com', 'Sub-17')->json('datos.id');

    sesionesPeticion($this, 'DELETE', '/api/v1/categorias/'.Identificador::generar()->valor(), idDeSesion: $sesion)
        ->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');

    sesionesPeticion($this, 'DELETE', "/api/v1/categorias/$id", idDeSesion: $sesion)->assertNoContent();
    sesionesPeticion($this, 'DELETE', "/api/v1/categorias/$id", idDeSesion: $sesion)
        ->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');
});

test('HU-28: sin sesión las tres rutas responden 401', function () {
    /** @var TestCase $this */
    $id = Identificador::generar()->valor();

    sesionesPeticion($this, 'GET', '/api/v1/categorias')->assertUnauthorized();
    sesionesPeticion($this, 'POST', '/api/v1/categorias', ['nombre' => 'Sub-17'])->assertUnauthorized();
    sesionesPeticion($this, 'DELETE', "/api/v1/categorias/$id")->assertUnauthorized();
});

test('HU-28: el nombre se recorta y el vacío o de más de 100 caracteres da 422', function () {
    /** @var TestCase $this */
    categoriasCrear($this, 'a@x.com', '   Sub-17  ')->assertCreated()->assertJsonPath('datos.nombre', 'Sub-17');

    categoriasCrear($this, 'a@x.com', '   ')->assertUnprocessable()->assertJsonPath('errores.0.campo', 'nombre');
    categoriasCrear($this, 'a@x.com', str_repeat('a', 101))->assertUnprocessable()->assertJsonPath('errores.0.campo', 'nombre');
    categoriasCrear($this, 'a@x.com', str_repeat('a', 100))->assertCreated();
});

test('HU-28: el listado va por nombre y admite orden descendente y paginación', function () {
    /** @var TestCase $this */
    categoriasCrear($this, 'a@x.com', 'Sub-17')->assertCreated();
    categoriasCrear($this, 'a@x.com', 'Sub-13')->assertCreated();
    categoriasCrear($this, 'a@x.com', 'Sub-15')->assertCreated();
    $sesion = sesionesIniciar($this, 'b@x.com');

    sesionesPeticion($this, 'GET', '/api/v1/categorias', idDeSesion: $sesion)
        ->assertJsonPath('datos.0.nombre', 'Sub-13')->assertJsonPath('datos.2.nombre', 'Sub-17');
    sesionesPeticion($this, 'GET', '/api/v1/categorias?orden=-nombre&por_pagina=2&pagina=2', idDeSesion: $sesion)
        ->assertJsonPath('datos.0.nombre', 'Sub-13')
        ->assertJsonPath('meta', ['pagina' => 2, 'por_pagina' => 2, 'total' => 3, 'total_paginas' => 2]);
});

test('HU-28: si otra petición ganó la carrera por el mismo nombre, el índice único también responde categoria_duplicada', function () {
    /** @var TestCase $this */
    $repositorio = app(RepositorioDeCategorias::class);
    $repositorio->guardarNueva(new Categoria(Identificador::generar(), 'Sub-17', app(Reloj::class)->ahora()));

    expect(fn () => $repositorio->guardarNueva(new Categoria(Identificador::generar(), 'SUB-17', app(Reloj::class)->ahora())))
        ->toThrow(CategoriaDuplicada::class);
    expect(DB::table('categorias')->count())->toBe(1);
});
