<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Eventos\JugadorInscrito;
use Src\Compartido\Dominio\Identificador;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/** Inscripciones (HU-06 criterios 2 y 3, RN-20, DD-37, DD-52). Datos de partida de docs/hitos/f1-h2.md §7.0. */

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function inscripcionesCrear(TestCase $prueba, string $materiaId, array $cuerpo, string $correo = 'd1@x.com'): TestResponse
{
    return sesionesPeticion($prueba, 'POST', "/api/v1/materias/$materiaId/inscripciones", $cuerpo, sesionesIniciar($prueba, $correo));
}

test('HU-06 (§7.5 fila 1): D1 inscribe a J4 por matrícula, queda el historial crear y D1 ya lo ve en GET /jugadores', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'd1@x.com');

    $respuesta = sesionesPeticion($this, 'POST', '/api/v1/materias/'.$m['m1'].'/inscripciones', ['matricula' => 'M-4'], $sesion)->assertCreated();

    expect($respuesta->json('datos'))->toMatchArray([
        'materia_id' => $m['m1'],
        'jugador' => ['id' => $m['j4'], 'nombres' => 'Cuatro', 'apellidos' => 'Dominguez', 'matricula' => 'M-4'],
    ])->and(array_keys($respuesta->json('datos')))->toBe(['id', 'materia_id', 'jugador', 'actualizado_en'])
        ->and(array_keys($respuesta->json('datos.jugador')))->toBe(['id', 'nombres', 'apellidos', 'matricula']);
    $id = $respuesta->json('datos.id');
    $historial = DB::table('inscripciones_historial')->where('inscripcion_id', $id)->get();
    expect($historial)->toHaveCount(1)
        ->and($historial[0]->operacion)->toBe('crear')
        ->and($historial[0]->usuario_id)->toBe($m['d1'])
        ->and(json_decode($historial[0]->datos_nuevos, true))->toEqual(['materia_id' => $m['m1'], 'jugador_id' => $m['j4']]);
    $lista = sesionesPeticion($this, 'GET', '/api/v1/jugadores', idDeSesion: $sesion)->assertOk();
    expect(array_column($lista->json('datos'), 'id'))->toBe([$m['j1'], $m['j3'], $m['j4']]);
});

test('DD-37 (§7.5 fila 2): la matrícula con otras mayúsculas o espacios resuelve al mismo jugador', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    inscripcionesCrear($this, $m['m1'], ['matricula' => '  m-4  '])->assertCreated()->assertJsonPath('datos.jugador.id', $m['j4']);
    inscripcionesCrear($this, $m['m1'], ['matricula' => 'M-4'])
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'jugador_ya_inscrito', 'mensaje' => 'El jugador ya está inscrito en esa materia.', 'errores' => []]);
    expect(DB::table('inscripciones')->where('jugador_id', $m['j4'])->count())->toBe(1);
});

test('HU-06 (§7.5 fila 3): una matrícula sin expediente da 404 jugador_no_registrado sin datos de nadie', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    $respuesta = inscripcionesCrear($this, $m['m1'], ['matricula' => 'NO-EXISTE'])->assertNotFound();

    expect($respuesta->json())->toBe([
        'codigo' => 'jugador_no_registrado',
        'mensaje' => 'No existe un expediente con esa matrícula: el jugador debe tener un expediente.',
        'errores' => [],
    ]);
    expect(DB::table('inscripciones')->count())->toBe(5)->and(DB::table('inscripciones_historial')->count())->toBe(0);
});

test('HU-06 (§7.5 fila 4): inscribir a quien ya está inscrito da 409 jugador_ya_inscrito', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    inscripcionesCrear($this, $m['m1'], ['matricula' => 'M-1'])->assertStatus(409)->assertJsonPath('codigo', 'jugador_ya_inscrito');
    expect(DB::table('inscripciones_historial')->count())->toBe(0);
});

test('DD-52 (§7.5 fila 5): inscribir a un jugador en baja da 409 jugador_en_baja con el mismo cuerpo que la asignación de H1', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $baja = mundoJugador('M-9', 'Zamora', enBaja: true);

    inscripcionesCrear($this, $m['m1'], ['matricula' => 'M-9'])
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'jugador_en_baja', 'mensaje' => 'El jugador está en baja: no se puede asignar ni inscribir.', 'errores' => []]);
    expect(DB::table('inscripciones')->where('jugador_id', $baja)->count())->toBe(0);
});

test('RNF-01 (§7.5 fila 6): D1 inscribe en M2 (de D2) y recibe un 404 idéntico al de una materia inexistente, con cualquier cuerpo', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    $inexistente = sesionesPeticion($this, 'POST', '/api/v1/materias/'.Identificador::generar()->valor().'/inscripciones', ['matricula' => 'M-4'], $sesion)->assertNotFound();

    foreach ([['matricula' => 'M-4'], ['matricula' => 'NO-EXISTE'], ['matricula' => 'M-1'], []] as $cuerpo) {
        $respuesta = sesionesPeticion($this, 'POST', '/api/v1/materias/'.$m['m2'].'/inscripciones', $cuerpo, $sesion)->assertNotFound();
        expect($respuesta->getContent())->toBe($inexistente->getContent());
    }
    $noV7 = sesionesPeticion($this, 'POST', '/api/v1/materias/123e4567-e89b-42d3-a456-426614174000/inscripciones', ['matricula' => 'M-4'], $sesion)->assertNotFound();
    expect($noV7->getContent())->toBe($inexistente->getContent());

    $listadoInexistente = sesionesPeticion($this, 'GET', '/api/v1/materias/'.Identificador::generar()->valor().'/inscripciones', idDeSesion: $sesion)->assertNotFound();
    expect($listadoInexistente->getContent())->toBe($inexistente->getContent());
    foreach (['', '?por_pagina=abc', '?pagina=-1'] as $consulta) {
        $listado = sesionesPeticion($this, 'GET', '/api/v1/materias/'.$m['m2'].'/inscripciones'.$consulta, idDeSesion: $sesion)->assertNotFound();
        expect($listado->getContent())->toBe($listadoInexistente->getContent());
    }
    expect(DB::table('inscripciones')->where('jugador_id', $m['j4'])->count())->toBe(0);
});

test('HU-01 (§7.5 fila 7): un entrenador sin el rol docente recibe 403 en POST y en GET de inscripciones', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'a@x.com');

    sesionesPeticion($this, 'POST', '/api/v1/materias/'.$m['m1'].'/inscripciones', ['matricula' => 'M-4'], $sesion)->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    sesionesPeticion($this, 'GET', '/api/v1/materias/'.$m['m1'].'/inscripciones', idDeSesion: $sesion)->assertForbidden();
});

test('HU-06 (§7.5 fila 8): GET inscripciones lista J1 y J3 por apellidos y nombres, sin J5 (baja)', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    $respuesta = sesionesPeticion($this, 'GET', '/api/v1/materias/'.$m['m1'].'/inscripciones', idDeSesion: sesionesIniciar($this, 'd1@x.com'))->assertOk();

    expect(array_column(array_column($respuesta->json('datos'), 'jugador'), 'id'))->toBe([$m['j1'], $m['j3']])
        ->and(array_column($respuesta->json('datos'), 'id'))->toBe([$m['i1'], $m['i3a']])
        ->and($respuesta->json('meta'))->toBe(['pagina' => 1, 'por_pagina' => 15, 'total' => 2, 'total_paginas' => 1]);
    sesionesPeticion($this, 'GET', '/api/v1/materias/'.$m['m1'].'/inscripciones?por_pagina=1&pagina=2', idDeSesion: sesionesIniciar($this, 'd1@x.com'))
        ->assertJsonPath('datos.0.jugador.id', $m['j3'])
        ->assertJsonPath('meta', ['pagina' => 2, 'por_pagina' => 1, 'total' => 2, 'total_paginas' => 2]);
});

test('RNF-01 (§7.5 fila 9): D1 inscribe al mismo jugador en una segunda materia propia y GET /jugadores lo devuelve una vez', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $otra = mundoInsertar('materias', ['asignatura_id' => $m['s2'], 'periodo_id' => $m['p1'], 'docente_id' => $m['d1']]);

    inscripcionesCrear($this, $m['m1'], ['matricula' => 'M-4'])->assertCreated();
    inscripcionesCrear($this, $otra, ['matricula' => 'M-4'])->assertCreated();

    $lista = sesionesPeticion($this, 'GET', '/api/v1/jugadores', idDeSesion: sesionesIniciar($this, 'd1@x.com'))->assertOk();
    expect(array_count_values(array_column($lista->json('datos'), 'id'))[$m['j4']])->toBe(1)
        ->and($lista->json('meta.total'))->toBe(3);
});

test('RN-14 (§7.5 fila 10): tras inscribir a J4, su detalle para D1 no trae nada deportivo', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $ed = mundoEquipo('ED', $m['categoria'], $m['a']);
    mundoMembresia($m['j4'], $ed, 'portero');
    $sesion = sesionesIniciar($this, 'd1@x.com');
    sesionesPeticion($this, 'POST', '/api/v1/materias/'.$m['m1'].'/inscripciones', ['matricula' => 'M-4'], $sesion)->assertCreated();

    sesionesPeticion($this, 'GET', '/api/v1/jugadores/'.$m['j4'], idDeSesion: $sesion)->assertOk()->assertJsonPath('datos.equipos', []);
});

test('HU-06 (§7.5 fila 11): matrícula ausente, vacía, de solo espacios o de más de 30 caracteres da 422 validacion_fallida', function (array $cuerpo) {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    inscripcionesCrear($this, $m['m1'], $cuerpo)
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', 'matricula');
})->with([
    'ausente' => [[]],
    'vacía' => [['matricula' => '']],
    'solo espacios' => [['matricula' => '    ']],
    'de 31 caracteres' => [['matricula' => str_repeat('a', 31)]],
    'número' => [['matricula' => 5]],
]);

test('HU-06: una matrícula de 30 caracteres se acepta y se busca (404 si no existe)', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    inscripcionesCrear($this, $m['m1'], ['matricula' => str_repeat('a', 30)])->assertNotFound()->assertJsonPath('codigo', 'jugador_no_registrado');
});

test('HU-01 (§7.5 fila 12): sin sesión las rutas de inscripciones responden 401', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    sesionesPeticion($this, 'POST', '/api/v1/materias/'.$m['m1'].'/inscripciones', ['matricula' => 'M-4'])->assertUnauthorized();
    sesionesPeticion($this, 'GET', '/api/v1/materias/'.$m['m1'].'/inscripciones')->assertUnauthorized();
});

test('HU-06 (§7.10 fila 23): al inscribir a J4 se publica JugadorInscrito con los identificadores de la inscripción', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    Event::fake([JugadorInscrito::class]);

    $id = inscripcionesCrear($this, $m['m1'], ['matricula' => 'M-4'])->assertCreated()->json('datos.id');

    Event::assertDispatchedTimes(JugadorInscrito::class, 1);
    Event::assertDispatched(JugadorInscrito::class, fn (JugadorInscrito $e) => $e->inscripcionId === $id && $e->materiaId === $m['m1'] && $e->jugadorId === $m['j4']);
    expect(DB::table('resumenes_academicos')->where('jugador_id', $m['j4'])->count())->toBe(0);
});

test('HU-06 (§7.10 fila 23): con el despachador real, el resumen de J4 aparece con materias_sin_calificar 1 y dato incompleto', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    inscripcionesCrear($this, $m['m1'], ['matricula' => 'M-4'])->assertCreated();

    expect(DB::table('resumenes_academicos')->where('jugador_id', $m['j4'])->first())
        ->toMatchObject(['periodo_id' => $m['p1'], 'promedio' => null, 'materias_sin_calificar' => 1, 'materias_reprobadas' => 0, 'nivel_riesgo' => null]);
});

test('RNF-02: si falla el historial no queda la inscripción ni se publica el evento', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    esquemaMarcarSucio();
    DB::connection('propietario')->statement('alter table inscripciones_historial add constraint ck_prueba_fallo check (false)');
    Event::fake([JugadorInscrito::class]);

    sesionesPeticion($this, 'POST', '/api/v1/materias/'.$m['m1'].'/inscripciones', ['matricula' => 'M-4'], $sesion)
        ->assertStatus(500)->assertJsonPath('codigo', 'error_interno');

    expect(DB::table('inscripciones')->where('jugador_id', $m['j4'])->count())->toBe(0);
    Event::assertNotDispatched(JugadorInscrito::class);
});

test('RNF-01: una inscripción eliminada no se lista ni otorga alcance, y una nueva inscripción la reemplaza', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    DB::connection('propietario')->table('inscripciones')->where('id', $m['i1'])->update(['eliminado_en' => '2026-03-01 00:00:00+00', 'eliminado_por' => $m['d1']]);

    $lista = sesionesPeticion($this, 'GET', '/api/v1/materias/'.$m['m1'].'/inscripciones', idDeSesion: sesionesIniciar($this, 'd1@x.com'))->assertOk();
    expect(array_column(array_column($lista->json('datos'), 'jugador'), 'id'))->toBe([$m['j3']]);

    inscripcionesCrear($this, $m['m1'], ['matricula' => 'M-1'])->assertCreated();
});
