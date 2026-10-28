<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Src\Plantel\Aplicacion\AsignarJugadorAEquipo;
use Src\Plantel\Aplicacion\CambiarPosicionDeJugador;
use Src\Plantel\Aplicacion\JugadorYaEnEquipo;
use Src\Plantel\Dominio\RepositorioDeMembresias;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function asignacionAsignar(TestCase $prueba, string $correo, string $equipoId, array $cuerpo): TestResponse
{
    return sesionesPeticion($prueba, 'POST', "/api/v1/equipos/{$equipoId}/jugadores", $cuerpo, sesionesIniciar($prueba, $correo));
}

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function asignacionCambiar(TestCase $prueba, string $correo, string $equipoId, string $jugadorId, array $cuerpo): TestResponse
{
    return sesionesPeticion($prueba, 'PATCH', "/api/v1/equipos/{$equipoId}/jugadores/{$jugadorId}", $cuerpo, sesionesIniciar($prueba, $correo));
}

/** @return list<string> */
function asignacionEquiposVistos(TestCase $prueba, string $correo, string $jugadorId): array
{
    $jugadores = sesionesPeticion($prueba, 'GET', '/api/v1/jugadores', [], sesionesIniciar($prueba, $correo))->assertOk()->json('datos');

    foreach ($jugadores as $jugador) {
        if ($jugador['id'] === $jugadorId) {
            return array_column($jugador['equipos'], 'nombre');
        }
    }

    return [];
}

/** @return array<string, int> */
function asignacionConteos(): array
{
    return ['jugador_equipo' => DB::table('jugador_equipo')->count(), 'jugador_equipo_historial' => DB::table('jugador_equipo_historial')->count()];
}

test('HU-04: B asigna a J1 a EB por matrícula, con posición, y obtiene alcance', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    expect(asignacionEquiposVistos($this, 'b@x.com', $m['j1']))->toBe([]);

    $respuesta = asignacionAsignar($this, 'b@x.com', $m['eb'], ['matricula' => 'M-1', 'posicion' => 'delantero'])->assertCreated();

    $datos = $respuesta->json('datos');
    expect(array_keys($datos))->toBe(['id', 'nombres', 'apellidos', 'fecha_nacimiento', 'matricula', 'estatus_academico', 'estatus_deportivo', 'equipos', 'actualizado_en'])
        ->and($datos['id'])->toBe($m['j1'])
        ->and($datos['equipos'])->toBe([['equipo_id' => $m['eb'], 'nombre' => 'EB', 'categoria' => ['id' => $m['categoria'], 'nombre' => 'Sub-17'], 'posicion' => 'delantero']]);

    expect(DB::table('jugador_equipo')->where('jugador_id', $m['j1'])->whereNull('hasta')->pluck('equipo_id')->sort()->values()->all())
        ->toBe(collect([$m['ea'], $m['eb']])->sort()->values()->all());
    expect(asignacionEquiposVistos($this, 'b@x.com', $m['j1']))->toBe(['EB'])
        ->and(asignacionEquiposVistos($this, 'a@x.com', $m['j1']))->toBe(['EA']);
});

test('HU-04: la asignación escribe la membresía y su historial crear con el mismo instante, y desde es el día de México', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    // 2026-03-10 03:30 UTC sigue siendo el 9 en Ciudad de México.
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-03-10 03:30:45', new DateTimeZone('UTC'))));

    asignacionAsignar($this, 'b@x.com', $m['eb'], ['matricula' => 'M-1', 'posicion' => 'defensa'])->assertCreated();

    $usuarioId = DB::table('usuarios')->where('correo', 'b@x.com')->value('id');
    $membresia = DB::table('jugador_equipo')->where('jugador_id', $m['j1'])->where('equipo_id', $m['eb'])->first();
    expect($membresia->desde)->toBe('2026-03-09')
        ->and($membresia->hasta)->toBeNull()
        ->and($membresia->posicion_id)->toBe(DB::table('posiciones')->where('codigo', 'defensa')->value('id'))
        ->and($membresia->creado_por)->toBe($usuarioId);

    $historial = DB::table('jugador_equipo_historial')->where('jugador_equipo_id', $membresia->id)->get();
    expect($historial)->toHaveCount(1)
        ->and($historial[0]->operacion)->toBe('crear')
        ->and($historial[0]->version)->toBe(1)
        ->and($historial[0]->usuario_id)->toBe($usuarioId)
        ->and($historial[0]->datos_anteriores)->toBeNull()
        ->and(json_decode($historial[0]->datos_nuevos, true))->toEqual([
            'jugador_id' => $m['j1'], 'equipo_id' => $m['eb'], 'posicion_id' => $membresia->posicion_id, 'desde' => '2026-03-09', 'hasta' => null,
        ])
        ->and((new DateTimeImmutable($historial[0]->ocurrido_en))->getTimestamp())->toBe((new DateTimeImmutable($membresia->creado_en))->getTimestamp());
});

test('RN-20: la matrícula se busca sin distinguir mayúsculas ni espacios alrededor', function (string $matricula) {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    asignacionAsignar($this, 'b@x.com', $m['eb'], ['matricula' => $matricula, 'posicion' => 'medio'])->assertCreated()->assertJsonPath('datos.id', $m['j1']);
})->with(['m-1', 'M-1', '  M-1 ', ' m-1']);

test('HU-04: asignar de nuevo al mismo jugador al mismo equipo da 409 jugador_ya_en_equipo', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    asignacionAsignar($this, 'b@x.com', $m['eb'], ['matricula' => 'M-1', 'posicion' => 'medio'])->assertCreated();
    $antes = asignacionConteos();

    asignacionAsignar($this, 'b@x.com', $m['eb'], ['matricula' => 'M-1', 'posicion' => 'medio'])
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'jugador_ya_en_equipo', 'mensaje' => 'El jugador ya pertenece a ese equipo.', 'errores' => []]);

    expect(asignacionConteos())->toBe($antes);
});

test('HU-04: una membresía cerrada no impide asignar de nuevo', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    mundoMembresia($m['j1'], $m['eb'], 'medio', '2026-01-31');

    asignacionAsignar($this, 'b@x.com', $m['eb'], ['matricula' => 'M-1', 'posicion' => 'medio'])->assertCreated();

    expect(DB::table('jugador_equipo')->where('jugador_id', $m['j1'])->where('equipo_id', $m['eb'])->count())->toBe(2);
});

test('HU-04: si otra petición ganó la carrera por la membresía vigente, el índice único responde jugador_ya_en_equipo', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $repositorio = app(RepositorioDeMembresias::class);
    $posicionId = Identificador::desdeTexto(DB::table('posiciones')->where('codigo', 'medio')->value('id'));
    $repositorio->asignar(Identificador::desdeTexto($m['j1']), Identificador::desdeTexto($m['eb']), $posicionId);
    $antes = asignacionConteos();

    expect(fn () => $repositorio->asignar(Identificador::desdeTexto($m['j1']), Identificador::desdeTexto($m['eb']), $posicionId))
        ->toThrow(JugadorYaEnEquipo::class);
    expect(asignacionConteos())->toBe($antes);
});

test('HU-04: una matrícula inexistente da 404', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $antes = asignacionConteos();

    asignacionAsignar($this, 'b@x.com', $m['eb'], ['matricula' => 'NO-EXISTE', 'posicion' => 'medio'])
        ->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');

    expect(asignacionConteos())->toBe($antes);
});

test('DD-30: asignar a un jugador en baja da 409 jugador_en_baja', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $antes = asignacionConteos();

    asignacionAsignar($this, 'b@x.com', $m['eb'], ['matricula' => 'M-5', 'posicion' => 'medio'])
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'jugador_en_baja', 'mensaje' => 'El jugador está en baja: no se puede asignar ni inscribir.', 'errores' => []]);

    expect(asignacionConteos())->toBe($antes);
});

test('RN-20: un equipo que no es del entrenador da 404 sin importar la matrícula, la posición ni los campos', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $antes = asignacionConteos();
    $inexistente = asignacionAsignar($this, 'a@x.com', (string) Str::uuid7(), ['matricula' => 'M-4', 'posicion' => 'medio'])->assertNotFound();

    foreach ([
        ['matricula' => 'M-2', 'posicion' => 'medio'],
        ['matricula' => 'M-4', 'posicion' => 'medio'],
        ['matricula' => 'NO-EXISTE', 'posicion' => 'medio'],
        ['matricula' => 'M-5', 'posicion' => 'medio'],
        ['matricula' => 'M-2', 'posicion' => 'arquero'],
        [],
    ] as $cuerpo) {
        $respuesta = asignacionAsignar($this, 'a@x.com', $m['eb'], $cuerpo)->assertNotFound();

        expect($respuesta->getContent())->toBe($inexistente->getContent());
    }
    expect(asignacionConteos())->toBe($antes);
});

test('HU-04: un identificador de equipo que no es UUID v7 da 404, no 403 ni 500', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    asignacionAsignar($this, 'a@x.com', '3f8c2a52-6f3c-4a43-9d8f-0b1d2f4e5a6b', ['matricula' => 'M-4', 'posicion' => 'medio'])->assertNotFound();
    asignacionCambiar($this, 'a@x.com', '3f8c2a52-6f3c-4a43-9d8f-0b1d2f4e5a6b', $m['j1'], ['posicion' => 'defensa'])->assertNotFound();
    asignacionCambiar($this, 'a@x.com', $m['ea'], '3f8c2a52-6f3c-4a43-9d8f-0b1d2f4e5a6b', ['posicion' => 'defensa'])->assertNotFound();
});

test('HU-04: una posición desconocida da 422 posicion_invalida y un campo ausente o mal formado da 422 validacion_fallida', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $antes = asignacionConteos();

    asignacionAsignar($this, 'b@x.com', $m['eb'], ['matricula' => 'M-1', 'posicion' => 'arquero'])
        ->assertUnprocessable()->assertJsonPath('codigo', 'posicion_invalida');
    foreach ([
        'matricula' => [['posicion' => 'medio'], ['matricula' => '   ', 'posicion' => 'medio'], ['matricula' => str_repeat('a', 31), 'posicion' => 'medio'], ['matricula' => ['M-1'], 'posicion' => 'medio']],
        'posicion' => [['matricula' => 'M-1'], ['matricula' => 'M-1', 'posicion' => ['medio']]],
    ] as $campo => $cuerpos) {
        foreach ($cuerpos as $cuerpo) {
            asignacionAsignar($this, 'b@x.com', $m['eb'], $cuerpo)
                ->assertUnprocessable()->assertJsonPath('codigo', 'validacion_fallida')->assertJsonPath('errores.0.campo', $campo);
        }
    }

    expect(asignacionConteos())->toBe($antes);
});

test('HU-01: un docente recibe 403 sin_permiso en ambas rutas y sin sesión 401', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    asignacionAsignar($this, 'd@x.com', $m['eb'], ['matricula' => 'M-1', 'posicion' => 'medio'])->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    asignacionCambiar($this, 'd@x.com', $m['ea'], $m['j1'], ['posicion' => 'defensa'])->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    sesionesPeticion($this, 'POST', "/api/v1/equipos/{$m['eb']}/jugadores", ['matricula' => 'M-1', 'posicion' => 'medio'])->assertUnauthorized();
    sesionesPeticion($this, 'PATCH', "/api/v1/equipos/{$m['ea']}/jugadores/{$m['j1']}", ['posicion' => 'defensa'])->assertUnauthorized();
});

test('HU-04: un usuario con los dos roles asigna a un equipo propio', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $equipo = mundoEquipo('EAD', $m['categoria'], $m['ad']);

    asignacionAsignar($this, 'ad@x.com', $equipo, ['matricula' => 'M-4', 'posicion' => 'portero'])->assertCreated();
});

test('HU-05: A cambia la posición de J1 en EA de medio a defensa y el historial conserva la anterior', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $usuarioId = DB::table('usuarios')->where('correo', 'a@x.com')->value('id');
    $membresia = DB::table('jugador_equipo')->where('jugador_id', $m['j1'])->where('equipo_id', $m['ea'])->first();
    $medio = DB::table('posiciones')->where('codigo', 'medio')->value('id');
    $defensa = DB::table('posiciones')->where('codigo', 'defensa')->value('id');

    $respuesta = asignacionCambiar($this, 'a@x.com', $m['ea'], $m['j1'], ['posicion' => 'defensa'])->assertOk();

    expect($respuesta->json('datos.id'))->toBe($m['j1'])
        ->and($respuesta->json('datos.equipos'))->toBe([['equipo_id' => $m['ea'], 'nombre' => 'EA', 'categoria' => ['id' => $m['categoria'], 'nombre' => 'Sub-17'], 'posicion' => 'defensa']]);
    $actual = DB::table('jugador_equipo')->where('id', $membresia->id)->first();
    expect($actual->posicion_id)->toBe($defensa)
        ->and($actual->actualizado_por)->toBe($usuarioId);

    $historial = DB::table('jugador_equipo_historial')->where('jugador_equipo_id', $membresia->id)->get();
    expect($historial)->toHaveCount(1)
        ->and($historial[0]->operacion)->toBe('actualizar')
        ->and($historial[0]->usuario_id)->toBe($usuarioId)
        ->and(json_decode($historial[0]->datos_anteriores, true))->toEqual(['jugador_id' => $m['j1'], 'equipo_id' => $m['ea'], 'posicion_id' => $medio, 'desde' => '2026-01-01', 'hasta' => null])
        ->and(json_decode($historial[0]->datos_nuevos, true))->toEqual(['jugador_id' => $m['j1'], 'equipo_id' => $m['ea'], 'posicion_id' => $defensa, 'desde' => '2026-01-01', 'hasta' => null]);
});

test('HU-05: dos cambios seguidos dejan versiones consecutivas', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $membresia = DB::table('jugador_equipo')->where('jugador_id', $m['j1'])->value('id');

    asignacionCambiar($this, 'a@x.com', $m['ea'], $m['j1'], ['posicion' => 'defensa'])->assertOk();
    asignacionCambiar($this, 'a@x.com', $m['ea'], $m['j1'], ['posicion' => 'portero'])->assertOk();

    expect(DB::table('jugador_equipo_historial')->where('jugador_equipo_id', $membresia)->orderBy('version')->pluck('version')->all())->toBe([1, 2]);
});

test('HU-05: la posición es de cada equipo: cambiar la de J3 en EA no toca su membresía en EB', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $enEb = DB::table('jugador_equipo')->where('jugador_id', $m['j3'])->where('equipo_id', $m['eb'])->first();

    asignacionCambiar($this, 'a@x.com', $m['ea'], $m['j3'], ['posicion' => 'medio'])->assertOk();

    expect(DB::table('jugador_equipo')->where('id', $enEb->id)->first())->toEqual($enEb)
        ->and(DB::table('jugador_equipo_historial')->where('jugador_equipo_id', $enEb->id)->count())->toBe(0);
    expect(DB::table('jugador_equipo')->where('jugador_id', $m['j3'])->where('equipo_id', $m['ea'])->value('posicion_id'))
        ->toBe(DB::table('posiciones')->where('codigo', 'medio')->value('id'));
});

test('HU-05: un cambio sin diferencia real responde 200 sin escribir historial ni tocar la fila', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $antes = DB::table('jugador_equipo')->where('jugador_id', $m['j1'])->first();

    asignacionCambiar($this, 'a@x.com', $m['ea'], $m['j1'], ['posicion' => 'medio'])->assertOk()->assertJsonPath('datos.equipos.0.posicion', 'medio');

    expect(DB::table('jugador_equipo_historial')->count())->toBe(0)
        ->and(DB::table('jugador_equipo')->where('id', $antes->id)->first())->toEqual($antes);
});

test('HU-05: cambiar la posición de un jugador en baja es válido', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    asignacionCambiar($this, 'a@x.com', $m['ea'], $m['j5'], ['posicion' => 'delantero'])->assertOk()->assertJsonPath('datos.estatus_deportivo', 'baja');
});

test('HU-05: un jugador que no está vigente en ese equipo da 404', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    mundoMembresia($m['j4'], $m['ea'], 'medio', '2026-02-01');
    $antes = asignacionConteos();

    foreach (['j2', 'j4'] as $jugador) {
        asignacionCambiar($this, 'a@x.com', $m['ea'], $m[$jugador], ['posicion' => 'defensa'])->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');
    }
    asignacionCambiar($this, 'a@x.com', $m['ea'], (string) Str::uuid7(), ['posicion' => 'defensa'])->assertNotFound();

    expect(asignacionConteos())->toBe($antes);
});

test('HU-05: un equipo ajeno da 404 y no 403, también con una posición inválida', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $antes = asignacionConteos();
    $posicionAntes = DB::table('jugador_equipo')->where('jugador_id', $m['j2'])->value('posicion_id');

    foreach (['defensa', 'arquero'] as $posicion) {
        asignacionCambiar($this, 'a@x.com', $m['eb'], $m['j2'], ['posicion' => $posicion])->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');
    }
    asignacionCambiar($this, 'a@x.com', $m['eb'], $m['j2'], [])->assertNotFound();

    expect(asignacionConteos())->toBe($antes)
        ->and(DB::table('jugador_equipo')->where('jugador_id', $m['j2'])->value('posicion_id'))->toBe($posicionAntes);
});

test('HU-05: una posición desconocida da 422 posicion_invalida y un campo ausente o mal formado 422 validacion_fallida', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    asignacionCambiar($this, 'a@x.com', $m['ea'], $m['j1'], ['posicion' => 'arquero'])->assertUnprocessable()->assertJsonPath('codigo', 'posicion_invalida');
    foreach ([[], ['posicion' => '  '], ['posicion' => ['medio']]] as $cuerpo) {
        asignacionCambiar($this, 'a@x.com', $m['ea'], $m['j1'], $cuerpo)
            ->assertUnprocessable()->assertJsonPath('codigo', 'validacion_fallida')->assertJsonPath('errores.0.campo', 'posicion');
    }

    expect(DB::table('jugador_equipo_historial')->count())->toBe(0);
});

test('HU-05: los campos desconocidos del cuerpo se ignoran', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    asignacionCambiar($this, 'a@x.com', $m['ea'], $m['j1'], ['posicion' => 'defensa', 'equipo_id' => $m['eb'], 'matricula' => 'X'])->assertOk();

    expect(DB::table('jugadores')->where('id', $m['j1'])->value('matricula'))->toBe('M-1');
});

test('HU-04: los casos de uso comprueban el equipo del entrenador y exigen usuario identificado', function () {
    /** @var TestCase $this */
    expect(fn () => app(AsignarJugadorAEquipo::class)->ejecutar(Identificador::generar(), 'M-1', 'medio'))->toThrow(LogicException::class, 'usuario_no_identificado');
    expect(fn () => app(CambiarPosicionDeJugador::class)->ejecutar(Identificador::generar(), Identificador::generar(), 'medio'))->toThrow(LogicException::class, 'usuario_no_identificado');
});

test('RNF-02: si falla el historial, la asignación no deja la membresía ni el historial', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $sesion = sesionesIniciar($this, 'b@x.com');
    $propietario = DB::connection('propietario');
    esquemaMarcarSucio();
    $propietario->unprepared("create or replace function falla_historial_de_prueba() returns trigger language plpgsql as \$\$ begin raise exception 'historial_de_prueba_falla'; end \$\$");
    $propietario->unprepared('create trigger tg_falla_de_prueba before insert on jugador_equipo_historial for each row execute function falla_historial_de_prueba()');
    $antes = asignacionConteos();

    try {
        sesionesPeticion($this, 'POST', "/api/v1/equipos/{$m['eb']}/jugadores", ['matricula' => 'M-1', 'posicion' => 'medio'], $sesion)
            ->assertStatus(500)->assertJsonPath('codigo', 'error_interno');

        expect(asignacionConteos())->toBe($antes);
    } finally {
        $propietario->unprepared('drop trigger if exists tg_falla_de_prueba on jugador_equipo_historial');
        $propietario->unprepared('drop function if exists falla_historial_de_prueba()');
    }
});

test('RNF-02: si falla el historial, el cambio de posición no se guarda', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $sesion = sesionesIniciar($this, 'a@x.com');
    $antes = DB::table('jugador_equipo')->where('jugador_id', $m['j1'])->first();
    $propietario = DB::connection('propietario');
    esquemaMarcarSucio();
    $propietario->unprepared("create or replace function falla_historial_de_prueba() returns trigger language plpgsql as \$\$ begin raise exception 'historial_de_prueba_falla'; end \$\$");
    $propietario->unprepared('create trigger tg_falla_de_prueba before insert on jugador_equipo_historial for each row execute function falla_historial_de_prueba()');

    try {
        sesionesPeticion($this, 'PATCH', "/api/v1/equipos/{$m['ea']}/jugadores/{$m['j1']}", ['posicion' => 'defensa'], $sesion)
            ->assertStatus(500)->assertJsonPath('codigo', 'error_interno');

        expect(DB::table('jugador_equipo')->where('id', $antes->id)->first())->toEqual($antes)
            ->and(DB::table('jugador_equipo_historial')->count())->toBe(0);
    } finally {
        $propietario->unprepared('drop trigger if exists tg_falla_de_prueba on jugador_equipo_historial');
        $propietario->unprepared('drop function if exists falla_historial_de_prueba()');
    }
});
