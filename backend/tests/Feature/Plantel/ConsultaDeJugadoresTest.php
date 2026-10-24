<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * @param  array<string, mixed>  $consulta
 * @return TestResponse<Response>
 */
function consultaComo(TestCase $prueba, ?string $correo, string $ruta, array $consulta = []): TestResponse
{
    $sesion = $correo === null ? null : sesionesIniciar($prueba, $correo);

    return sesionesPeticion($prueba, 'GET', $ruta.($consulta === [] ? '' : '?'.http_build_query($consulta)), [], $sesion);
}

/**
 * @param  TestResponse<Response>  $respuesta
 * @return list<string>
 */
function consultaMatriculas(TestResponse $respuesta): array
{
    return array_column($respuesta->json('datos'), 'matricula');
}

test('HU-02: un entrenador ve en el listado solo a los jugadores de sus equipos, sin bajas', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $respuesta = consultaComo($this, 'a@x.com', '/api/v1/jugadores')->assertOk();

    expect(consultaMatriculas($respuesta))->toBe(['M-1', 'M-3'])
        ->and($respuesta->json('meta'))->toBe(['pagina' => 1, 'por_pagina' => 15, 'total' => 2, 'total_paginas' => 1]);
});

test('RN-14: el detalle de un jugador en dos equipos muestra solo el equipo del entrenador que consulta', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    consultaComo($this, 'a@x.com', "/api/v1/jugadores/{$m['j3']}")
        ->assertOk()
        ->assertJsonPath('datos.id', $m['j3'])
        ->assertJsonPath('datos.equipos', [
            ['equipo_id' => $m['ea'], 'nombre' => 'EA', 'categoria' => ['id' => $m['categoria'], 'nombre' => 'Sub-17'], 'posicion' => 'portero'],
        ]);
    consultaComo($this, 'b@x.com', "/api/v1/jugadores/{$m['j3']}")
        ->assertOk()
        ->assertJsonPath('datos.equipos', [
            ['equipo_id' => $m['eb'], 'nombre' => 'EB', 'categoria' => ['id' => $m['categoria'], 'nombre' => 'Sub-17'], 'posicion' => 'delantero'],
        ]);
});

test('RNF-01: un jugador de un equipo ajeno responde 404 en el detalle', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    consultaComo($this, 'a@x.com', "/api/v1/jugadores/{$m['j2']}")->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');
});

test('RNF-01: un jugador sin equipo responde 404 en el detalle', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    consultaComo($this, 'a@x.com', "/api/v1/jugadores/{$m['j4']}")->assertNotFound();
});

test('RNF-01: el entrenador de otro equipo recibe 404 por un jugador que no es suyo', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    consultaComo($this, 'b@x.com', "/api/v1/jugadores/{$m['j1']}")->assertNotFound();
});

test('RN-14: un jugador en baja se puede consultar por quien ya lo tenía asignado (DD-30)', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    consultaComo($this, 'a@x.com', "/api/v1/jugadores/{$m['j5']}")
        ->assertOk()
        ->assertJsonPath('datos.estatus_academico', 'baja')
        ->assertJsonPath('datos.estatus_deportivo', 'baja');
});

test('RN-14: filtrar por el equipo de otro entrenador devuelve lista vacía con total 0', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $respuesta = consultaComo($this, 'a@x.com', '/api/v1/jugadores', ['filtro' => ['equipo_id' => $m['eb']]])->assertOk();

    expect($respuesta->json('datos'))->toBe([])
        ->and($respuesta->json('meta.total'))->toBe(0);
});

test('RN-14: el filtro no revela que un jugador propio también está en el equipo de otro (J3)', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    foreach (['a@x.com' => 'eb', 'b@x.com' => 'ea'] as $correo => $ajeno) {
        $respuesta = consultaComo($this, $correo, '/api/v1/jugadores', ['filtro' => ['equipo_id' => $m[$ajeno]]])->assertOk();

        expect($respuesta->json('datos'))->toBe([])
            ->and($respuesta->json('meta.total'))->toBe(0);
    }
});

test('HU-02: filtrar por un equipo propio devuelve a sus jugadores vigentes', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $respuesta = consultaComo($this, 'b@x.com', '/api/v1/jugadores', ['filtro' => ['equipo_id' => $m['eb']]])->assertOk();

    expect(consultaMatriculas($respuesta))->toBe(['M-2', 'M-3'])
        ->and($respuesta->json('meta.total'))->toBe(2);
});

test('HU-02: un filtro por equipo mal formado responde 422 validacion_fallida', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    consultaComo($this, 'a@x.com', '/api/v1/jugadores', ['filtro' => ['equipo_id' => 'no-es-uuid']])
        ->assertStatus(422)
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', 'filtro.equipo_id');
});

test('RN-14: el arreglo equipos solo incluye membresías vigentes; una cerrada no aparece', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $segundoEquipo = mundoEquipo('EA2', $m['categoria'], $m['a']);
    mundoMembresia($m['j1'], $segundoEquipo, 'delantero', hasta: '2026-02-01');

    $respuesta = consultaComo($this, 'a@x.com', "/api/v1/jugadores/{$m['j1']}")->assertOk();

    expect(array_column($respuesta->json('datos.equipos'), 'equipo_id'))->toBe([$m['ea']]);
});

test('RN-14: una membresía cerrada deja al jugador fuera del alcance', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    DB::connection('propietario')->table('jugador_equipo')->where('jugador_id', $m['j1'])->update(['hasta' => '2026-02-01']);

    consultaComo($this, 'a@x.com', "/api/v1/jugadores/{$m['j1']}")->assertNotFound();
});

test('RN-14: en el listado, un jugador en dos equipos propios sale una vez con ambos equipos', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $segundoEquipo = mundoEquipo('EA2', $m['categoria'], $m['a']);
    mundoMembresia($m['j3'], $segundoEquipo, 'defensa');

    $respuesta = consultaComo($this, 'a@x.com', '/api/v1/jugadores')->assertOk();
    $j3 = array_values(array_filter($respuesta->json('datos'), fn (array $jugador) => $jugador['id'] === $m['j3']))[0];

    expect(consultaMatriculas($respuesta))->toBe(['M-1', 'M-3'])
        ->and($respuesta->json('meta.total'))->toBe(2)
        ->and(array_column($j3['equipos'], 'nombre'))->toEqualCanonicalizing(['EA', 'EA2']);
});

test('HU-02: un docente recibe lista vacía en el listado', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $respuesta = consultaComo($this, 'd@x.com', '/api/v1/jugadores')->assertOk();

    expect($respuesta->json('datos'))->toBe([])->and($respuesta->json('meta.total'))->toBe(0);
});

test('HU-02: un docente recibe 404 en el detalle de un jugador', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    consultaComo($this, 'd@x.com', "/api/v1/jugadores/{$m['j1']}")->assertNotFound();
});

test('HU-02: un usuario con los dos roles y sin equipos recibe lista vacía', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    expect(consultaComo($this, 'ad@x.com', '/api/v1/jugadores')->assertOk()->json('datos'))->toBe([]);
});

test('RN-20: la búsqueda por matrícula de un jugador no asignado devuelve exactamente nombres, apellidos y matrícula', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    consultaComo($this, 'a@x.com', '/api/v1/jugadores/busqueda', ['matricula' => 'M-4'])
        ->assertOk()
        ->assertExactJson(['datos' => ['nombres' => 'Cuatro', 'apellidos' => 'Dominguez', 'matricula' => 'M-4']]);
});

test('RN-20: un docente también puede buscar por matrícula', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    consultaComo($this, 'd@x.com', '/api/v1/jugadores/busqueda', ['matricula' => 'M-4'])
        ->assertOk()
        ->assertExactJson(['datos' => ['nombres' => 'Cuatro', 'apellidos' => 'Dominguez', 'matricula' => 'M-4']]);
});

test('RN-20: una matrícula inexistente responde 404', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    consultaComo($this, 'a@x.com', '/api/v1/jugadores/busqueda', ['matricula' => 'NO-EXISTE'])->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');
});

test('RN-20: la búsqueda no distingue mayúsculas y recorta espacios (DD-37)', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    consultaComo($this, 'a@x.com', '/api/v1/jugadores/busqueda', ['matricula' => '  m-4 '])
        ->assertOk()
        ->assertJsonPath('datos.matricula', 'M-4');
});

test('RN-20: la búsqueda encuentra a un jugador en baja, con los mismos tres campos (DD-38)', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    consultaComo($this, 'b@x.com', '/api/v1/jugadores/busqueda', ['matricula' => 'M-5'])
        ->assertOk()
        ->assertExactJson(['datos' => ['nombres' => 'Cinco', 'apellidos' => 'Estrada', 'matricula' => 'M-5']]);
});

test('RN-20: la búsqueda no encuentra a un jugador eliminado', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    DB::connection('propietario')->table('jugadores')->where('id', $m['j4'])->update(['eliminado_en' => now()]);

    consultaComo($this, 'a@x.com', '/api/v1/jugadores/busqueda', ['matricula' => 'M-4'])->assertNotFound();
});

test('RN-20: la búsqueda sin matrícula o con una de más de 30 caracteres responde 422', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    consultaComo($this, 'a@x.com', '/api/v1/jugadores/busqueda')->assertStatus(422)->assertJsonPath('errores.0.campo', 'matricula');
    consultaComo($this, 'a@x.com', '/api/v1/jugadores/busqueda', ['matricula' => str_repeat('x', 31)])->assertStatus(422);
});

test('RN-20: la búsqueda no otorga alcance sobre el jugador encontrado', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    consultaComo($this, 'a@x.com', '/api/v1/jugadores/busqueda', ['matricula' => 'M-4'])->assertOk();

    consultaComo($this, 'a@x.com', "/api/v1/jugadores/{$m['j4']}")->assertNotFound();
    expect(in_array('M-4', consultaMatriculas(consultaComo($this, 'a@x.com', '/api/v1/jugadores')), true))->toBeFalse();
});

test('RNF-01: sin sesión, las tres rutas de jugadores responden 401', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    foreach (['/api/v1/jugadores', '/api/v1/jugadores/busqueda?matricula=M-4', "/api/v1/jugadores/{$m['j1']}"] as $ruta) {
        consultaComo($this, null, $ruta)->assertUnauthorized()->assertJsonPath('codigo', 'no_autenticado');
    }
});

test('HU-02: el listado se ordena por apellidos y nombres, y admite orden descendente', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    mundoJugador('M-6', 'Alvarez', [$m['ea'] => 'medio'], nombres: 'Abel');

    expect(consultaMatriculas(consultaComo($this, 'a@x.com', '/api/v1/jugadores')))->toBe(['M-6', 'M-1', 'M-3'])
        ->and(consultaMatriculas(consultaComo($this, 'a@x.com', '/api/v1/jugadores', ['orden' => '-apellidos'])))->toBe(['M-3', 'M-1', 'M-6'])
        ->and(consultaMatriculas(consultaComo($this, 'a@x.com', '/api/v1/jugadores', ['orden' => 'nombres'])))->toBe(['M-6', 'M-3', 'M-1']);
});

test('HU-02: un orden no permitido responde 422', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    consultaComo($this, 'a@x.com', '/api/v1/jugadores', ['orden' => 'matricula'])->assertStatus(422)->assertJsonPath('codigo', 'validacion_fallida');
});

test('HU-02: la paginación recorta la página y meta.total cuenta con el mismo alcance y filtros', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    foreach (range(10, 14) as $n) {
        mundoJugador("M-$n", "Zeta$n", [$m['ea'] => 'medio']);
    }
    mundoJugador('M-20', 'Zeta20', [$m['eb'] => 'medio']);

    $pagina = consultaComo($this, 'a@x.com', '/api/v1/jugadores', ['pagina' => 2, 'por_pagina' => 3])->assertOk();

    expect($pagina->json('meta'))->toBe(['pagina' => 2, 'por_pagina' => 3, 'total' => 7, 'total_paginas' => 3])
        ->and(consultaMatriculas($pagina))->toBe(['M-11', 'M-12', 'M-13']);

    $filtrado = consultaComo($this, 'a@x.com', '/api/v1/jugadores', ['filtro' => ['equipo_id' => $m['ea']], 'por_pagina' => 100])->assertOk();
    expect($filtrado->json('meta.total'))->toBe(7)->and($filtrado->json('datos'))->toHaveCount(7);
});

test('HU-02: los parámetros de paginación fuera de rango responden 422', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    consultaComo($this, 'a@x.com', '/api/v1/jugadores', ['por_pagina' => 101])->assertStatus(422);
    consultaComo($this, 'a@x.com', '/api/v1/jugadores', ['pagina' => 0])->assertStatus(422);
});

test('RNF-01: el listado hace el mismo número de consultas con 2 jugadores que con 10', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $sesion = sesionesIniciar($this, 'a@x.com');
    $contar = function () use ($sesion): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        sesionesPeticion($this, 'GET', '/api/v1/jugadores', [], $sesion)->assertOk();
        $total = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $total;
    };

    $conDos = $contar();
    foreach (range(10, 17) as $n) {
        mundoJugador("M-$n", "Zeta$n", [$m['ea'] => 'medio']);
    }

    expect(sesionesPeticion($this, 'GET', '/api/v1/jugadores', [], $sesion)->json('meta.total'))->toBe(10)
        ->and($contar())->toBe($conDos);
});

test('HU-02: el detalle y el 201 de POST /jugadores tienen exactamente el mismo conjunto de claves', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $creado = sesionesPeticion($this, 'POST', '/api/v1/jugadores', [
        'nombres' => 'Nuevo', 'apellidos' => 'Jugador', 'fecha_nacimiento' => '2010-01-01',
        'matricula' => 'M-99', 'equipo_id' => $m['ea'], 'posicion' => 'medio',
    ], sesionesIniciar($this, 'a@x.com'))->assertCreated();

    $detalle = consultaComo($this, 'a@x.com', '/api/v1/jugadores/'.$creado->json('datos.id'))->assertOk();

    expect(array_keys($detalle->json('datos')))->toBe(array_keys($creado->json('datos')))
        ->and(array_keys($detalle->json('datos.equipos.0')))->toBe(array_keys($creado->json('datos.equipos.0')))
        ->and($detalle->json('datos'))->toBe($creado->json('datos'))
        ->and($detalle->json('datos.actualizado_en'))->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/');
});
