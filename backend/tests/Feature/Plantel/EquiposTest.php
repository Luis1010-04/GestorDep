<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Src\Plantel\Aplicacion\CategoriaInexistente;
use Src\Plantel\Aplicacion\CrearEquipo;
use Src\Plantel\Aplicacion\EquipoDuplicado;
use Src\Plantel\Aplicacion\ListarEquipos;
use Src\Plantel\Dominio\Equipo;
use Src\Plantel\Dominio\RepositorioDeEquipos;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

beforeEach(function () {
    sesionesPrepararBase();
    sesionesCrearUsuario('a@x.com', ['entrenador']);
    sesionesCrearUsuario('b@x.com', ['entrenador']);
    sesionesCrearUsuario('d@x.com', ['docente']);
    sesionesCrearUsuario('ad@x.com', ['docente', 'entrenador']);
});

/** @return TestResponse<Response> */
function equiposCrearCategoria(TestCase $prueba, string $nombre): TestResponse
{
    return sesionesPeticion($prueba, 'POST', '/api/v1/categorias', ['nombre' => $nombre], sesionesIniciar($prueba, 'a@x.com'));
}

/** @return TestResponse<Response> */
function equiposCrear(TestCase $prueba, string $correo, string $nombre, string $categoriaId): TestResponse
{
    return sesionesPeticion($prueba, 'POST', '/api/v1/equipos', ['nombre' => $nombre, 'categoria_id' => $categoriaId], sesionesIniciar($prueba, $correo));
}

test('RN-22: un docente recibe 403 al crear un equipo y una categoría', function () {
    /** @var TestCase $this */
    $categoriaId = equiposCrearCategoria($this, 'Sub-17')->json('datos.id');
    $sesion = sesionesIniciar($this, 'd@x.com');

    sesionesPeticion($this, 'POST', '/api/v1/equipos', ['nombre' => 'Tigres', 'categoria_id' => $categoriaId], $sesion)
        ->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    sesionesPeticion($this, 'POST', '/api/v1/categorias', ['nombre' => 'Sub-19'], $sesion)
        ->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    sesionesPeticion($this, 'GET', '/api/v1/equipos', idDeSesion: $sesion)->assertForbidden();
    expect(DB::table('equipos')->count())->toBe(0);
});

test('RN-22: un usuario con los dos roles crea un equipo', function () {
    /** @var TestCase $this */
    $categoriaId = equiposCrearCategoria($this, 'Sub-17')->json('datos.id');

    equiposCrear($this, 'ad@x.com', 'Tigres', $categoriaId)->assertCreated();
});

test('RN-22: quien crea un equipo queda asignado con una fila vigente y su historial', function () {
    /** @var TestCase $this */
    $categoriaId = equiposCrearCategoria($this, 'Sub-17')->json('datos.id');

    $respuesta = equiposCrear($this, 'a@x.com', 'Tigres', $categoriaId)->assertCreated();

    expect(array_keys($respuesta->json('datos')))->toBe(['id', 'nombre', 'categoria', 'actualizado_en'])
        ->and($respuesta->json('datos.categoria'))->toBe(['id' => $categoriaId, 'nombre' => 'Sub-17']);

    $equipoId = $respuesta->json('datos.id');
    $usuarioId = DB::table('usuarios')->where('correo', 'a@x.com')->value('id');
    $asignacion = DB::table('entrenador_equipo')->where('equipo_id', $equipoId)->get();
    expect($asignacion)->toHaveCount(1)
        ->and($asignacion[0]->usuario_id)->toBe($usuarioId)
        ->and($asignacion[0]->hasta)->toBeNull()
        ->and($asignacion[0]->creado_por)->toBe($usuarioId);

    $historialAsignacion = DB::table('entrenador_equipo_historial')->where('entrenador_equipo_id', $asignacion[0]->id)->get();
    expect($historialAsignacion)->toHaveCount(1)
        ->and($historialAsignacion[0]->operacion)->toBe('crear')
        ->and($historialAsignacion[0]->usuario_id)->toBe($usuarioId)
        ->and($historialAsignacion[0]->datos_anteriores)->toBeNull();

    $historialEquipo = DB::table('equipos_historial')->where('equipo_id', $equipoId)->get();
    expect($historialEquipo)->toHaveCount(1)
        ->and($historialEquipo[0]->operacion)->toBe('crear')
        ->and(json_decode($historialEquipo[0]->datos_nuevos, true))->toMatchArray(['nombre' => 'Tigres', 'categoria_id' => $categoriaId]);
});

test('RN-22: la asignación empieza el día de hoy en America/Mexico_City', function () {
    /** @var TestCase $this */
    // 2026-03-10 03:30 UTC sigue siendo el 9 en Ciudad de México.
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-03-10 03:30:00', new DateTimeZone('UTC'))));
    $categoriaId = equiposCrearCategoria($this, 'Sub-17')->json('datos.id');

    $equipoId = equiposCrear($this, 'a@x.com', 'Tigres', $categoriaId)->assertCreated()->json('datos.id');

    expect(DB::table('entrenador_equipo')->where('equipo_id', $equipoId)->value('desde'))->toBe('2026-03-09');
});

test('RN-22: el mismo nombre y categoría da 409 equipo_duplicado', function () {
    /** @var TestCase $this */
    $categoriaId = equiposCrearCategoria($this, 'Sub-17')->json('datos.id');
    equiposCrear($this, 'a@x.com', 'Tigres', $categoriaId)->assertCreated();

    equiposCrear($this, 'b@x.com', 'Tigres', $categoriaId)
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'equipo_duplicado', 'mensaje' => 'Ya existe un equipo con ese nombre en la categoría.', 'errores' => []]);
    expect(DB::table('equipos')->count())->toBe(1)->and(DB::table('entrenador_equipo')->count())->toBe(1);

    $otraCategoria = equiposCrearCategoria($this, 'Sub-15')->json('datos.id');
    equiposCrear($this, 'b@x.com', 'Tigres', $otraCategoria)->assertCreated();
});

test('RN-22: una categoría inexistente o eliminada da 422 categoria_inexistente', function () {
    /** @var TestCase $this */
    $eliminada = equiposCrearCategoria($this, 'Sub-17')->json('datos.id');
    sesionesPeticion($this, 'DELETE', "/api/v1/categorias/$eliminada", idDeSesion: sesionesIniciar($this, 'a@x.com'))->assertNoContent();

    foreach ([$eliminada, Identificador::generar()->valor()] as $categoriaId) {
        equiposCrear($this, 'a@x.com', 'Tigres', $categoriaId)
            ->assertUnprocessable()
            ->assertJsonPath('codigo', 'categoria_inexistente')
            ->assertJsonPath('mensaje', 'La categoría indicada no existe.');
    }
    expect(DB::table('equipos')->count())->toBe(0);
});

test('RN-22: GET /equipos devuelve solo los equipos del entrenador que consulta', function () {
    /** @var TestCase $this */
    $categoriaId = equiposCrearCategoria($this, 'Sub-17')->json('datos.id');
    $ea = equiposCrear($this, 'a@x.com', 'EA', $categoriaId)->json('datos.id');
    $propio = equiposCrear($this, 'a@x.com', 'Otro de A', $categoriaId)->json('datos.id');
    $eb = equiposCrear($this, 'b@x.com', 'EB', $categoriaId)->json('datos.id');

    $deA = sesionesPeticion($this, 'GET', '/api/v1/equipos', idDeSesion: sesionesIniciar($this, 'a@x.com'))->assertOk();
    expect(array_column($deA->json('datos'), 'id'))->toBe([$ea, $propio])->and($deA->json('meta.total'))->toBe(2);

    $deB = sesionesPeticion($this, 'GET', '/api/v1/equipos', idDeSesion: sesionesIniciar($this, 'b@x.com'))->assertOk();
    expect(array_column($deB->json('datos'), 'id'))->toBe([$eb]);

    sesionesPeticion($this, 'GET', '/api/v1/equipos', idDeSesion: sesionesIniciar($this, 'ad@x.com'))
        ->assertOk()->assertJsonPath('datos', [])->assertJsonPath('meta.total', 0);
});

test('RN-22: una asignación cerrada o un equipo eliminado no aparecen en GET /equipos', function () {
    /** @var TestCase $this */
    $categoriaId = equiposCrearCategoria($this, 'Sub-17')->json('datos.id');
    $cerrado = equiposCrear($this, 'a@x.com', 'Cerrado', $categoriaId)->json('datos.id');
    $eliminado = equiposCrear($this, 'a@x.com', 'Eliminado', $categoriaId)->json('datos.id');
    $vigente = equiposCrear($this, 'a@x.com', 'Vigente', $categoriaId)->json('datos.id');
    DB::connection('propietario')->table('entrenador_equipo')->where('equipo_id', $cerrado)->update(['hasta' => '2026-01-01']);
    DB::connection('propietario')->table('equipos')->where('id', $eliminado)->update(['eliminado_en' => now()]);

    $respuesta = sesionesPeticion($this, 'GET', '/api/v1/equipos', idDeSesion: sesionesIniciar($this, 'a@x.com'))->assertOk();

    expect(array_column($respuesta->json('datos'), 'id'))->toBe([$vigente]);
});

test('RN-22: sin sesión las dos rutas de equipos responden 401', function () {
    /** @var TestCase $this */
    sesionesPeticion($this, 'GET', '/api/v1/equipos')->assertUnauthorized();
    sesionesPeticion($this, 'POST', '/api/v1/equipos', ['nombre' => 'Tigres', 'categoria_id' => Identificador::generar()->valor()])->assertUnauthorized();
});

test('RN-22: el listado va por nombre y admite orden descendente y paginación', function () {
    /** @var TestCase $this */
    $categoriaId = equiposCrearCategoria($this, 'Sub-17')->json('datos.id');
    foreach (['Tigres', 'Aguilas', 'Leones'] as $nombre) {
        equiposCrear($this, 'a@x.com', $nombre, $categoriaId)->assertCreated();
    }
    $sesion = sesionesIniciar($this, 'a@x.com');

    sesionesPeticion($this, 'GET', '/api/v1/equipos', idDeSesion: $sesion)
        ->assertJsonPath('datos.0.nombre', 'Aguilas')->assertJsonPath('datos.2.nombre', 'Tigres')->assertJsonPath('meta.por_pagina', 15);
    sesionesPeticion($this, 'GET', '/api/v1/equipos?orden=-nombre&por_pagina=2&pagina=2', idDeSesion: $sesion)
        ->assertJsonPath('datos.0.nombre', 'Aguilas')
        ->assertJsonPath('meta', ['pagina' => 2, 'por_pagina' => 2, 'total' => 3, 'total_paginas' => 2]);
    sesionesPeticion($this, 'GET', '/api/v1/equipos?por_pagina=101', idDeSesion: $sesion)->assertUnprocessable();
});

test('RN-22: el nombre se recorta y el vacío, el largo o la categoría mal formada dan 422', function () {
    /** @var TestCase $this */
    $categoriaId = equiposCrearCategoria($this, 'Sub-17')->json('datos.id');
    equiposCrear($this, 'a@x.com', '  Tigres ', $categoriaId)->assertCreated()->assertJsonPath('datos.nombre', 'Tigres');

    equiposCrear($this, 'a@x.com', '   ', $categoriaId)->assertUnprocessable()->assertJsonPath('errores.0.campo', 'nombre');
    equiposCrear($this, 'a@x.com', str_repeat('a', 101), $categoriaId)->assertUnprocessable()->assertJsonPath('errores.0.campo', 'nombre');
    equiposCrear($this, 'a@x.com', str_repeat('a', 100), $categoriaId)->assertCreated();
    equiposCrear($this, 'a@x.com', 'Otro', 'no-es-uuid')->assertUnprocessable()->assertJsonPath('errores.0.campo', 'categoria_id');
    equiposCrear($this, 'a@x.com', 'Otro', '3f8c2a52-6f3c-4a43-9d8f-0b1d2f4e5a6b')->assertUnprocessable()->assertJsonPath('errores.0.campo', 'categoria_id');
    sesionesPeticion($this, 'POST', '/api/v1/equipos', ['nombre' => 'Otro'], sesionesIniciar($this, 'a@x.com'))
        ->assertUnprocessable()->assertJsonPath('errores.0.campo', 'categoria_id');
});

test('HU-28: un equipo cuya categoría fue eliminada sigue mostrándola en GET /equipos', function () {
    /** @var TestCase $this */
    $categoriaId = equiposCrearCategoria($this, 'Sub-17')->json('datos.id');
    equiposCrear($this, 'a@x.com', 'Tigres', $categoriaId)->assertCreated();
    $sesion = sesionesIniciar($this, 'a@x.com');

    sesionesPeticion($this, 'DELETE', "/api/v1/categorias/$categoriaId", idDeSesion: $sesion)->assertNoContent();

    sesionesPeticion($this, 'GET', '/api/v1/equipos', idDeSesion: $sesion)
        ->assertOk()
        ->assertJsonPath('datos.0.categoria', ['id' => $categoriaId, 'nombre' => 'Sub-17']);
});

test('RNF-02: si otra petición ganó la carrera por el mismo equipo, el índice único también responde equipo_duplicado', function () {
    /** @var TestCase $this */
    $categoriaId = Identificador::desdeTexto(equiposCrearCategoria($this, 'Sub-17')->json('datos.id'));
    $entrenador = Identificador::desdeTexto(DB::table('usuarios')->where('correo', 'a@x.com')->value('id'));
    $repositorio = app(RepositorioDeEquipos::class);
    $ahora = app(Reloj::class)->ahora();
    $repositorio->guardarNuevo(new Equipo(Identificador::generar(), 'Tigres', $categoriaId, $ahora), $entrenador);

    expect(fn () => $repositorio->guardarNuevo(new Equipo(Identificador::generar(), 'Tigres', $categoriaId, $ahora), $entrenador))
        ->toThrow(EquipoDuplicado::class);
    expect(DB::table('equipos')->count())->toBe(1)->and(DB::table('entrenador_equipo')->count())->toBe(1);
});

test('RNF-02: si la categoría se elimina justo antes de escribir, la clave foránea responde categoria_inexistente', function () {
    /** @var TestCase $this */
    $entrenador = Identificador::desdeTexto(DB::table('usuarios')->where('correo', 'a@x.com')->value('id'));

    expect(fn () => app(RepositorioDeEquipos::class)->guardarNuevo(
        new Equipo(Identificador::generar(), 'Tigres', Identificador::generar(), app(Reloj::class)->ahora()),
        $entrenador,
    ))->toThrow(CategoriaInexistente::class);
    expect(DB::table('equipos')->count())->toBe(0);
});

test('RN-22: crear o listar equipos sin usuario identificado falla', function () {
    /** @var TestCase $this */
    expect(fn () => app(CrearEquipo::class)->ejecutar('Tigres', Identificador::generar()))->toThrow(LogicException::class, 'usuario_no_identificado');
    expect(fn () => app(ListarEquipos::class)->ejecutar(1, 15, false))->toThrow(LogicException::class, 'usuario_no_identificado');
});

test('RNF-02: si falla el historial, el equipo y la asignación tampoco se guardan', function (string $tablaQueFalla) {
    /** @var TestCase $this */
    $categoriaId = equiposCrearCategoria($this, 'Sub-17')->json('datos.id');
    $sesion = sesionesIniciar($this, 'a@x.com');
    $propietario = DB::connection('propietario');
    esquemaMarcarSucio();
    $propietario->unprepared("create or replace function falla_historial_de_prueba() returns trigger language plpgsql as \$\$ begin raise exception 'historial_de_prueba_falla'; end \$\$");
    $propietario->unprepared("create trigger tg_falla_de_prueba before insert on {$tablaQueFalla} for each row execute function falla_historial_de_prueba()");

    try {
        sesionesPeticion($this, 'POST', '/api/v1/equipos', ['nombre' => 'Tigres', 'categoria_id' => $categoriaId], $sesion)
            ->assertStatus(500)->assertJsonPath('codigo', 'error_interno');

        foreach (['equipos', 'equipos_historial', 'entrenador_equipo', 'entrenador_equipo_historial'] as $tabla) {
            expect(DB::table($tabla)->count())->toBe(0, $tabla);
        }
    } finally {
        $propietario->unprepared("drop trigger if exists tg_falla_de_prueba on {$tablaQueFalla}");
        $propietario->unprepared('drop function if exists falla_historial_de_prueba()');
    }
})->with(['equipos_historial', 'entrenador_equipo_historial']);
