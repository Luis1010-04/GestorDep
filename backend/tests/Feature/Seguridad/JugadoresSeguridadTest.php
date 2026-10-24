<?php

use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function seguridadComo(TestCase $prueba, ?string $correo, string $metodo, string $ruta, array $cuerpo = []): TestResponse
{
    return sesionesPeticion($prueba, $metodo, $ruta, $cuerpo, $correo === null ? null : sesionesIniciar($prueba, $correo));
}

/**
 * @param  array<string, string>  $m  datos de mundoDeJugadores()
 * @return array<string, mixed>
 */
function seguridadAltaEn(array $m, string $equipo, string $matricula): array
{
    return [
        'nombres' => 'X', 'apellidos' => 'Y', 'fecha_nacimiento' => '2010-01-01',
        'matricula' => $matricula, 'equipo_id' => $m[$equipo], 'posicion' => 'medio',
    ];
}

test('RNF-01: con un usuario ajeno, ninguna ruta de jugadores revela a J2 ni a su equipo', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    seguridadComo($this, 'a@x.com', 'GET', "/api/v1/jugadores/{$m['j2']}")->assertNotFound();

    $listado = seguridadComo($this, 'a@x.com', 'GET', '/api/v1/jugadores')->assertOk();
    expect(str_contains((string) $listado->getContent(), $m['j2']) || str_contains((string) $listado->getContent(), 'M-2'))->toBeFalse();

    $alta = seguridadComo($this, 'a@x.com', 'POST', '/api/v1/jugadores', seguridadAltaEn($m, 'eb', 'M-77'))->assertNotFound();
    expect(str_contains((string) $alta->getContent(), 'EB'))->toBeFalse();
});

test('RNF-01: con un docente, las rutas de jugadores no revelan ningún expediente', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    expect(seguridadComo($this, 'd@x.com', 'GET', '/api/v1/jugadores')->assertOk()->json('datos'))->toBe([]);
    seguridadComo($this, 'd@x.com', 'GET', "/api/v1/jugadores/{$m['j1']}")->assertNotFound();
    seguridadComo($this, 'd@x.com', 'POST', '/api/v1/jugadores', seguridadAltaEn($m, 'ea', 'M-78'))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'sin_permiso');
});

test('RNF-01: sin sesión, todas las rutas de jugadores responden 401', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    foreach ([
        ['GET', '/api/v1/jugadores'],
        ['GET', '/api/v1/jugadores/busqueda?matricula=M-4'],
        ['GET', "/api/v1/jugadores/{$m['j1']}"],
        ['POST', '/api/v1/jugadores'],
    ] as [$metodo, $ruta]) {
        seguridadComo($this, null, $metodo, $ruta)->assertUnauthorized();
    }
});

test('RNF-01: el 404 por un jugador ajeno es idéntico al 404 por un id inexistente (no es un oráculo)', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $inexistente = seguridadComo($this, 'a@x.com', 'GET', '/api/v1/jugadores/'.Str::uuid7())->assertNotFound();

    foreach (['j2', 'j4'] as $ajeno) {
        $respuesta = seguridadComo($this, 'a@x.com', 'GET', "/api/v1/jugadores/{$m[$ajeno]}")->assertNotFound();

        expect($respuesta->getContent())->toBe($inexistente->getContent())
            ->and($respuesta->headers->get('Content-Type'))->toBe($inexistente->headers->get('Content-Type'));
    }
});

test('RNF-01: el 404 de un docente por un jugador ajeno es idéntico al de un id inexistente', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $inexistente = seguridadComo($this, 'd@x.com', 'GET', '/api/v1/jugadores/'.Str::uuid7())->assertNotFound();
    $ajeno = seguridadComo($this, 'd@x.com', 'GET', "/api/v1/jugadores/{$m['j1']}")->assertNotFound();

    expect($ajeno->getContent())->toBe($inexistente->getContent());
});

test('RN-14: filtrar por el equipo de otro no cambia la respuesta frente a filtrar por un equipo inexistente', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $filtro = fn (string $equipoId): string => '/api/v1/jugadores?'.http_build_query(['filtro' => ['equipo_id' => $equipoId]]);

    $inexistente = seguridadComo($this, 'a@x.com', 'GET', $filtro((string) Str::uuid7()))->assertOk();
    $ajeno = seguridadComo($this, 'a@x.com', 'GET', $filtro($m['eb']))->assertOk();

    expect($ajeno->getContent())->toBe($inexistente->getContent());
});

test('RN-20: la búsqueda nunca expone el id ni otros campos del expediente', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    foreach (['a@x.com', 'b@x.com', 'd@x.com'] as $correo) {
        $respuesta = seguridadComo($this, $correo, 'GET', '/api/v1/jugadores/busqueda?matricula=M-2')->assertOk();

        expect(array_keys($respuesta->json('datos')))->toBe(['nombres', 'apellidos', 'matricula'])
            ->and(str_contains((string) $respuesta->getContent(), $m['j2']) || str_contains((string) $respuesta->getContent(), $m['eb']))->toBeFalse();
    }
});

test('RNF-01: las rutas de equipos/jugadores con un usuario ajeno responden 404 idéntico al de un equipo inexistente y no revelan a J2', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $asignar = fn (string $equipo) => seguridadComo($this, 'a@x.com', 'POST', "/api/v1/equipos/{$equipo}/jugadores", ['matricula' => 'M-2', 'posicion' => 'medio']);
    $cambiar = fn (string $equipo) => seguridadComo($this, 'a@x.com', 'PATCH', "/api/v1/equipos/{$equipo}/jugadores/{$m['j2']}", ['posicion' => 'medio']);

    $inexistente = (string) Str::uuid7();
    foreach ([$asignar, $cambiar] as $peticion) {
        $ajeno = $peticion($m['eb'])->assertNotFound();

        expect($ajeno->getContent())->toBe($peticion($inexistente)->assertNotFound()->getContent())
            ->and(str_contains((string) $ajeno->getContent(), $m['j2']) || str_contains((string) $ajeno->getContent(), 'M-2') || str_contains((string) $ajeno->getContent(), 'EB'))->toBeFalse();
    }
});

test('RNF-01: con un docente, las rutas de equipos/jugadores responden 403 sin_permiso', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    seguridadComo($this, 'd@x.com', 'POST', "/api/v1/equipos/{$m['ea']}/jugadores", ['matricula' => 'M-4', 'posicion' => 'medio'])
        ->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    seguridadComo($this, 'd@x.com', 'PATCH', "/api/v1/equipos/{$m['ea']}/jugadores/{$m['j1']}", ['posicion' => 'medio'])
        ->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
});

test('RNF-01: sin sesión, las rutas de equipos/jugadores responden 401', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    seguridadComo($this, null, 'POST', "/api/v1/equipos/{$m['ea']}/jugadores", ['matricula' => 'M-4', 'posicion' => 'medio'])->assertUnauthorized();
    seguridadComo($this, null, 'PATCH', "/api/v1/equipos/{$m['ea']}/jugadores/{$m['j1']}", ['posicion' => 'medio'])->assertUnauthorized();
});

test('RN-20: asignar por matrícula no entrega el id ni datos del jugador ajeno antes de asignar', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    foreach (['NO-EXISTE', 'M-5'] as $matricula) {
        $respuesta = seguridadComo($this, 'a@x.com', 'POST', "/api/v1/equipos/{$m['ea']}/jugadores", ['matricula' => $matricula, 'posicion' => 'medio']);

        expect(str_contains((string) $respuesta->getContent(), $m['j5']) || str_contains((string) $respuesta->getContent(), 'Estrada'))->toBeFalse();
    }
});
