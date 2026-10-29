<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/** Asistencia a entrenamientos y porcentajes (HU-11, docs/hitos/f1-h3.md §7.3 y §7.6 filas 1, 2 y 8). */
beforeEach(function () {
    sesionesPrepararBase();
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-09-20 15:00:00', new DateTimeZone('UTC'))));
});

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function asistenciaPeticion(TestCase $prueba, string $metodo, string $ruta, array $cuerpo = [], string $correo = 'e1@x.com'): TestResponse
{
    return sesionesPeticion($prueba, $metodo, '/api/v1'.$ruta, $cuerpo, sesionesIniciar($prueba, $correo));
}

/**
 * Entrenamiento de un equipo en L1; el día cambia con `$dia` para no empalmar.
 *
 * @param  array<string, string>  $m
 */
function asistenciaEntrenamiento(array $m, string $equipo = 'a', string $estado = 'realizado', int $dia = 10): string
{
    $responsable = $equipo === 'a' ? $m['e1'] : $m['e2'];

    return mundoEntrenamiento($m[$equipo], $m['l1'], $responsable, "2026-09-{$dia} 23:00:00+00", "2026-09-{$dia} 23:59:00+00", ['estado' => $estado]);
}

function asistenciaRegistrar(string $entrenamiento, string $jugador, string $estado): string
{
    return mundoInsertar('asistencias_entrenamiento', ['entrenamiento_id' => $entrenamiento, 'jugador_id' => $jugador, 'estado' => $estado]);
}

/** @return array{asistencias: int, historial: int} */
function asistenciaFilas(): array
{
    return [
        'asistencias' => DB::table('asistencias_entrenamiento')->count(),
        'historial' => DB::table('asistencias_entrenamiento_historial')->count(),
    ];
}

test('HU-11 (§7.3 fila 1): capturar asistencia en un entrenamiento programado da 409 entrenamiento_no_realizado', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = asistenciaEntrenamiento($m, estado: 'programado');

    asistenciaPeticion($this, 'POST', "/entrenamientos/$id/asistencias", ['registros' => [['jugador_id' => $m['j1'], 'estado' => 'presente']]])
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'entrenamiento_no_realizado', 'mensaje' => 'El entrenamiento no está realizado.', 'errores' => []]);
    expect(asistenciaFilas())->toBe(['asistencias' => 0, 'historial' => 0]);
});

test('HU-11 (§7.3 fila 1): un entrenamiento cancelado tampoco admite asistencia', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = asistenciaEntrenamiento($m, estado: 'cancelado');

    asistenciaPeticion($this, 'POST', "/entrenamientos/$id/asistencias", ['registros' => [['jugador_id' => $m['j1'], 'estado' => 'presente']]])
        ->assertStatus(409)->assertJsonPath('codigo', 'entrenamiento_no_realizado');
});

test('HU-11 (§7.3 fila 2): E1 captura J1 presente, J2 falta y J3 falta justificada; el porcentaje de J3 es sobre sus registros', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = asistenciaEntrenamiento($m);

    $respuesta = asistenciaPeticion($this, 'POST', "/entrenamientos/$id/asistencias", ['registros' => [
        ['jugador_id' => $m['j1'], 'estado' => 'presente'],
        ['jugador_id' => $m['j2'], 'estado' => 'falta'],
        ['jugador_id' => $m['j3'], 'estado' => 'falta_justificada'],
    ]])->assertCreated();

    expect($respuesta->json('datos.*.jugador_id'))->toBe([$m['j1'], $m['j2'], $m['j3']])
        ->and($respuesta->json('datos.*.estado'))->toBe(['presente', 'falta', 'falta_justificada'])
        ->and($respuesta->json('datos.0'))->toBe([
            'id' => $respuesta->json('datos.0.id'), 'entrenamiento_id' => $id, 'jugador_id' => $m['j1'],
            'estado' => 'presente', 'actualizado_en' => '2026-09-20T15:00:00+00:00',
        ])
        ->and(asistenciaFilas())->toBe(['asistencias' => 3, 'historial' => 3])
        ->and(DB::table('asistencias_entrenamiento_historial')->pluck('operacion')->unique()->all())->toBe(['crear'])
        ->and(DB::table('asistencias_entrenamiento_historial')->pluck('usuario_id')->unique()->all())->toBe([$m['e1']]);
    expect(asistenciaPeticion($this, 'GET', "/jugadores/{$m['j3']}/asistencia-entrenamientos")->assertOk()->json('datos'))->toBe([
        'jugador_id' => $m['j3'], 'porcentaje' => '0.00', 'presentes' => 0, 'faltas' => 0, 'faltas_justificadas' => 1,
    ]);
});

test('HU-11 (§7.3 fila 3): capturar de nuevo da 409 asistencia_ya_registrada y no guarda nada', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = asistenciaEntrenamiento($m);
    asistenciaRegistrar($id, $m['j1'], 'presente');

    asistenciaPeticion($this, 'POST', "/entrenamientos/$id/asistencias", ['registros' => [
        ['jugador_id' => $m['j2'], 'estado' => 'presente'],
        ['jugador_id' => $m['j1'], 'estado' => 'falta'],
    ]])
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'asistencia_ya_registrada', 'mensaje' => 'Ya hay asistencia registrada para ese jugador en el entrenamiento.', 'errores' => []]);
    expect(asistenciaFilas())->toBe(['asistencias' => 1, 'historial' => 0])
        ->and(DB::table('asistencias_entrenamiento')->value('estado'))->toBe('presente');
});

test('HU-11 (§7.3 fila 4): E1 corrige J2 a presente y el historial guarda la fila previa', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $asistencia = asistenciaRegistrar(asistenciaEntrenamiento($m), $m['j2'], 'falta');

    asistenciaPeticion($this, 'PATCH', "/asistencias-entrenamiento/$asistencia", ['estado' => 'presente'])
        ->assertOk()->assertJsonPath('datos.estado', 'presente')->assertJsonPath('datos.actualizado_en', '2026-09-20T15:00:00+00:00');

    $fila = DB::table('asistencias_entrenamiento_historial')->where('asistencia_entrenamiento_id', $asistencia)->first();
    expect($fila->operacion)->toBe('actualizar')
        ->and($fila->usuario_id)->toBe($m['e1'])
        ->and($fila->ocurrido_en)->toBe('2026-09-20 15:00:00+00')
        ->and(json_decode($fila->datos_anteriores, true))->toMatchArray(['jugador_id' => $m['j2'], 'estado' => 'falta'])
        ->and(json_decode($fila->datos_nuevos, true))->toMatchArray(['jugador_id' => $m['j2'], 'estado' => 'presente'])
        ->and(DB::table('asistencias_entrenamiento')->where('id', $asistencia)->value('actualizado_por'))->toBe($m['e1']);
});

test('HU-11 (§7.3 fila 5): una lista con J5 (baja) da 422 jugador_no_pertenece_al_equipo en jugador_id y no guarda nada', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = asistenciaEntrenamiento($m);

    asistenciaPeticion($this, 'POST', "/entrenamientos/$id/asistencias", ['registros' => [
        ['jugador_id' => $m['j1'], 'estado' => 'presente'],
        ['jugador_id' => $m['j5'], 'estado' => 'presente'],
    ]])
        ->assertUnprocessable()
        ->assertExactJson([
            'codigo' => 'jugador_no_pertenece_al_equipo', 'mensaje' => 'El jugador no pertenece al equipo o está dado de baja.',
            'errores' => [['campo' => 'registros.1.jugador_id', 'codigo' => 'jugador_no_pertenece_al_equipo', 'mensaje' => 'El jugador no pertenece al equipo o está dado de baja.']],
        ]);
    expect(asistenciaFilas())->toBe(['asistencias' => 0, 'historial' => 0]);
});

test('HU-11: un jugador de otro equipo, con la membresía cerrada o inexistente tampoco pertenece al equipo', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = asistenciaEntrenamiento($m);
    $salio = mundoJugador('M-6', 'Seis', nombres: 'J6');
    mundoMembresia($salio, $m['a'], 'medio', '2026-06-30');

    foreach ([$m['j4'], $salio, Identificador::generar()->valor(), '123e4567-e89b-42d3-a456-426614174000'] as $jugador) {
        asistenciaPeticion($this, 'POST', "/entrenamientos/$id/asistencias", ['registros' => [['jugador_id' => $jugador, 'estado' => 'presente']]])
            ->assertUnprocessable()->assertJsonPath('errores.0.campo', 'registros.0.jugador_id');
    }
    expect(asistenciaFilas())->toBe(['asistencias' => 0, 'historial' => 0]);
});

test('HU-11 (§7.3 fila 6): con 3 presentes, 1 falta y 1 justificada en entrenamientos realizados, el porcentaje de J1 es 60.00', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    foreach (['presente', 'presente', 'presente', 'falta', 'falta_justificada'] as $indice => $estado) {
        asistenciaRegistrar(asistenciaEntrenamiento($m, dia: 10 + $indice), $m['j1'], $estado);
    }
    asistenciaRegistrar(asistenciaEntrenamiento($m, estado: 'cancelado', dia: 20), $m['j1'], 'falta');
    asistenciaRegistrar(asistenciaEntrenamiento($m, estado: 'programado', dia: 21), $m['j1'], 'falta');

    expect(asistenciaPeticion($this, 'GET', "/jugadores/{$m['j1']}/asistencia-entrenamientos")->assertOk()->json('datos'))->toBe([
        'jugador_id' => $m['j1'], 'porcentaje' => '60.00', 'presentes' => 3, 'faltas' => 1, 'faltas_justificadas' => 1,
    ]);
});

test('HU-11 (§7.3 fila 7): sin entrenamientos realizados el porcentaje de J1 es null', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    asistenciaEntrenamiento($m, estado: 'programado');

    expect(asistenciaPeticion($this, 'GET', "/jugadores/{$m['j1']}/asistencia-entrenamientos")->assertOk()->json('datos'))->toBe([
        'jugador_id' => $m['j1'], 'porcentaje' => null, 'presentes' => 0, 'faltas' => 0, 'faltas_justificadas' => 0,
    ]);
});

test('HU-11 (§7.3 filas 8 y 9): el porcentaje de J3 solo cuenta los entrenamientos de los equipos de quien consulta', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    asistenciaRegistrar(asistenciaEntrenamiento($m, 'a', dia: 10), $m['j3'], 'presente');
    asistenciaRegistrar(asistenciaEntrenamiento($m, 'b', dia: 11), $m['j3'], 'falta');
    asistenciaRegistrar(asistenciaEntrenamiento($m, 'b', dia: 12), $m['j3'], 'falta');
    $ruta = "/jugadores/{$m['j3']}/asistencia-entrenamientos";

    expect(asistenciaPeticion($this, 'GET', $ruta)->assertOk()->json('datos'))
        ->toMatchArray(['porcentaje' => '100.00', 'presentes' => 1, 'faltas' => 0])
        ->and(asistenciaPeticion($this, 'GET', $ruta, correo: 'e2@x.com')->assertOk()->json('datos'))
        ->toMatchArray(['porcentaje' => '0.00', 'presentes' => 0, 'faltas' => 2])
        ->and(asistenciaPeticion($this, 'GET', $ruta, correo: 'e3@x.com')->assertOk()->json('datos'))
        ->toMatchArray(['porcentaje' => '33.33', 'presentes' => 1, 'faltas' => 2]);
});

test('HU-11 (§7.3 fila 10): con 2 presentes de 4 registros, el porcentaje del equipo A es 50.00', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = asistenciaEntrenamiento($m);
    asistenciaRegistrar($id, $m['j1'], 'presente');
    asistenciaRegistrar($id, $m['j2'], 'presente');
    asistenciaRegistrar($id, $m['j3'], 'falta');
    asistenciaRegistrar($id, $m['j5'], 'falta_justificada');
    asistenciaRegistrar(asistenciaEntrenamiento($m, 'b', dia: 11), $m['j3'], 'presente');

    $datos = asistenciaPeticion($this, 'GET', "/equipos/{$m['a']}/asistencia-entrenamientos")->assertOk()->json('datos');

    expect($datos)->toBe([
        'equipo_id' => $m['a'], 'porcentaje' => '50.00', 'presentes' => 2, 'faltas' => 1, 'faltas_justificadas' => 1,
        'por_jugador' => [
            ['jugador_id' => $m['j2'], 'nombres' => 'J2', 'apellidos' => 'Dos', 'porcentaje' => '100.00', 'presentes' => 1, 'faltas' => 0, 'faltas_justificadas' => 0],
            ['jugador_id' => $m['j3'], 'nombres' => 'J3', 'apellidos' => 'Tres', 'porcentaje' => '0.00', 'presentes' => 0, 'faltas' => 1, 'faltas_justificadas' => 0],
            ['jugador_id' => $m['j1'], 'nombres' => 'J1', 'apellidos' => 'Uno', 'porcentaje' => '100.00', 'presentes' => 1, 'faltas' => 0, 'faltas_justificadas' => 0],
        ],
    ]);
});

test('HU-11: un equipo sin registros tiene porcentaje null y sus jugadores también', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();

    $datos = asistenciaPeticion($this, 'GET', "/equipos/{$m['b']}/asistencia-entrenamientos", correo: 'e2@x.com')->assertOk()->json('datos');

    expect($datos['porcentaje'])->toBeNull()
        ->and($datos['por_jugador'])->toHaveCount(2)
        ->and(array_column($datos['por_jugador'], 'porcentaje'))->toBe([null, null]);
});

test('HU-11 (§7.3 fila 11): E1 consulta el porcentaje de J4 (equipo B) y recibe el 404 de un jugador inexistente', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    asistenciaRegistrar(asistenciaEntrenamiento($m, 'b'), $m['j4'], 'presente');

    $ajeno = asistenciaPeticion($this, 'GET', "/jugadores/{$m['j4']}/asistencia-entrenamientos")->assertNotFound();
    $inexistente = asistenciaPeticion($this, 'GET', '/jugadores/'.Identificador::generar()->valor().'/asistencia-entrenamientos')->assertNotFound();
    $v4 = asistenciaPeticion($this, 'GET', '/jugadores/123e4567-e89b-42d3-a456-426614174000/asistencia-entrenamientos')->assertNotFound();

    expect($ajeno->getContent())->toBe($inexistente->getContent())
        ->and($v4->getContent())->toBe($inexistente->getContent())
        ->and($ajeno->json('codigo'))->toBe('recurso_no_encontrado');
});

test('HU-11 (DA-20): un jugador en baja sigue visible para su entrenador; un docente recibe 404 aunque le enseñe', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    asistenciaRegistrar(asistenciaEntrenamiento($m), $m['j5'], 'presente');
    $materia = mundoInsertar('materias', [
        'asignatura_id' => mundoInsertar('asignaturas', ['clave' => 'MAT-1', 'nombre' => 'Matemáticas', 'grado' => 1]),
        'periodo_id' => mundoInsertar('periodos_escolares', ['nombre' => '2026-2', 'fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-12-18']),
        'docente_id' => $m['d'],
    ]);
    mundoInscribir($materia, $m['j1']);

    asistenciaPeticion($this, 'GET', "/jugadores/{$m['j5']}/asistencia-entrenamientos")->assertOk()->assertJsonPath('datos.porcentaje', '100.00');
    asistenciaPeticion($this, 'GET', "/jugadores/{$m['j1']}/asistencia-entrenamientos", correo: 'd@x.com')->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');
});

test('HU-11 (DA-17): un jugador repetido, una lista vacía o un estado inválido dan 422 sin guardar nada', function (array $registros, string $campo) {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = asistenciaEntrenamiento($m);
    $registros = array_map(fn (array $r) => ['jugador_id' => $m[$r[0]] ?? $r[0], 'estado' => $r[1]], $registros);

    asistenciaPeticion($this, 'POST', "/entrenamientos/$id/asistencias", ['registros' => $registros])
        ->assertUnprocessable()->assertJsonPath('codigo', 'validacion_fallida')->assertJsonPath('errores.0.campo', $campo);
    expect(asistenciaFilas())->toBe(['asistencias' => 0, 'historial' => 0]);
})->with([
    'repetido' => [[['j1', 'presente'], ['j1', 'falta']], 'registros.0.jugador_id'],
    'vacía' => [[], 'registros'],
    'estado inválido' => [[['j1', 'retardo']], 'registros.0.estado'],
    'jugador no uuid' => [[['x', 'presente']], 'registros.0.jugador_id'],
]);

test('HU-11 (DA-18): E3 corrige la asistencia de un jugador que ya está en baja', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $asistencia = asistenciaRegistrar(asistenciaEntrenamiento($m), $m['j5'], 'falta');

    asistenciaPeticion($this, 'PATCH', "/asistencias-entrenamiento/$asistencia", ['estado' => 'falta_justificada'], 'e3@x.com')
        ->assertOk()->assertJsonPath('datos.estado', 'falta_justificada');
});

test('HU-11: una corrección vacía, con un estado inválido o con un campo no admitido da 422', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $ruta = '/asistencias-entrenamiento/'.asistenciaRegistrar(asistenciaEntrenamiento($m), $m['j1'], 'falta');

    asistenciaPeticion($this, 'PATCH', $ruta)->assertUnprocessable()->assertJsonPath('errores.0.campo', 'estado');
    asistenciaPeticion($this, 'PATCH', $ruta, ['estado' => 'tarde'])->assertUnprocessable()->assertJsonPath('errores.0.campo', 'estado');
    asistenciaPeticion($this, 'PATCH', $ruta, ['estado' => 'presente', 'jugador_id' => $m['j2']])->assertUnprocessable()
        ->assertJsonPath('errores.0.campo', 'jugador_id')->assertJsonPath('errores.0.codigo', 'prohibited');
    expect(asistenciaFilas())->toBe(['asistencias' => 1, 'historial' => 0]);
});

test('HU-11: el listado de asistencias de un entrenamiento trae sus registros, también los de un jugador en baja', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = asistenciaEntrenamiento($m);
    $primera = asistenciaRegistrar($id, $m['j1'], 'presente');
    $segunda = asistenciaRegistrar($id, $m['j5'], 'falta');
    asistenciaRegistrar(asistenciaEntrenamiento($m, dia: 11), $m['j2'], 'falta');

    $respuesta = asistenciaPeticion($this, 'GET', "/entrenamientos/$id/asistencias", correo: 'e3@x.com')->assertOk();

    expect($respuesta->json('datos.*.id'))->toEqualCanonicalizing([$primera, $segunda])
        ->and($respuesta->json('meta.total'))->toBe(2)
        ->and(array_keys($respuesta->json('datos.0')))->toBe(['id', 'entrenamiento_id', 'jugador_id', 'estado', 'actualizado_en']);
    asistenciaPeticion($this, 'GET', "/entrenamientos/$id/asistencias?por_pagina=101")->assertUnprocessable();
});

test('RNF-01 (§7.6 fila 1): E2, sin relación con A, consulta un entrenamiento de A y recibe 404', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = asistenciaEntrenamiento($m);

    $ajeno = asistenciaPeticion($this, 'GET', "/entrenamientos/$id", correo: 'e2@x.com')->assertNotFound();
    $inexistente = asistenciaPeticion($this, 'GET', '/entrenamientos/'.Identificador::generar()->valor(), correo: 'e2@x.com')->assertNotFound();

    expect($ajeno->getContent())->toBe($inexistente->getContent());
});

test('RNF-01 (§7.6 fila 2): E2 intenta registrar asistencia en un entrenamiento de A y recibe el mismo 404 que con un id inexistente', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = asistenciaEntrenamiento($m);
    $cuerpo = ['registros' => [['jugador_id' => $m['j3'], 'estado' => 'presente']]];

    $ajeno = asistenciaPeticion($this, 'POST', "/entrenamientos/$id/asistencias", $cuerpo, 'e2@x.com')->assertNotFound();
    $inexistente = asistenciaPeticion($this, 'POST', '/entrenamientos/'.Identificador::generar()->valor().'/asistencias', $cuerpo, 'e2@x.com')->assertNotFound();

    expect($ajeno->getContent())->toBe($inexistente->getContent())
        ->and(asistenciaFilas())->toBe(['asistencias' => 0, 'historial' => 0]);
});

test('RNF-01 (§7.6 fila 8): la asistencia de un equipo ajeno responde 404 byte a byte con cuerpo válido e inválido, por cualquier método, sin filas nuevas', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = asistenciaEntrenamiento($m);
    $asistencia = asistenciaRegistrar($id, $m['j3'], 'falta');
    $antes = asistenciaFilas();
    $otroId = Identificador::generar()->valor();
    $validoAlta = ['registros' => [['jugador_id' => $m['j3'], 'estado' => 'presente']]];
    $invalidoAlta = ['registros' => 'x', 'otro' => 1];
    $pares = [
        ['POST', "/entrenamientos/$id/asistencias", "/entrenamientos/$otroId/asistencias", $validoAlta],
        ['POST', "/entrenamientos/$id/asistencias", "/entrenamientos/$otroId/asistencias", $invalidoAlta],
        ['GET', "/entrenamientos/$id/asistencias", "/entrenamientos/$otroId/asistencias", []],
        ['GET', "/entrenamientos/$id/asistencias?por_pagina=0", "/entrenamientos/$otroId/asistencias?por_pagina=0", []],
        ['PATCH', "/asistencias-entrenamiento/$asistencia", "/asistencias-entrenamiento/$otroId", ['estado' => 'presente']],
        ['PATCH', "/asistencias-entrenamiento/$asistencia", "/asistencias-entrenamiento/$otroId", ['estado' => 'x', 'jugador_id' => 1]],
        ['PATCH', "/asistencias-entrenamiento/$asistencia", '/asistencias-entrenamiento/123e4567-e89b-42d3-a456-426614174000', []],
        ['GET', "/equipos/{$m['a']}/asistencia-entrenamientos", "/equipos/$otroId/asistencia-entrenamientos", []],
    ];

    foreach ($pares as [$metodo, $ajena, $inexistente, $cuerpo]) {
        $respuestaAjena = asistenciaPeticion($this, $metodo, $ajena, $cuerpo, 'e2@x.com')->assertNotFound();
        $respuestaInexistente = asistenciaPeticion($this, $metodo, $inexistente, $cuerpo, 'e2@x.com')->assertNotFound();
        expect($respuestaAjena->getContent())->toBe($respuestaInexistente->getContent(), "$metodo $ajena");
    }
    expect(asistenciaFilas())->toBe($antes)
        ->and(DB::table('asistencias_entrenamiento')->where('id', $asistencia)->value('estado'))->toBe('falta');
});

test('HU-11: un docente recibe 403 en las rutas de entrenador de asistencia, y sin sesión 401 en todas', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = asistenciaEntrenamiento($m);
    $asistencia = asistenciaRegistrar($id, $m['j1'], 'falta');
    $deEntrenador = [
        ['GET', "/entrenamientos/$id/asistencias"], ['POST', "/entrenamientos/$id/asistencias"],
        ['PATCH', "/asistencias-entrenamiento/$asistencia"], ['GET', "/equipos/{$m['a']}/asistencia-entrenamientos"],
    ];

    foreach ($deEntrenador as [$metodo, $ruta]) {
        asistenciaPeticion($this, $metodo, $ruta, ['estado' => 'presente'], 'd@x.com')->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    }
    foreach ([...$deEntrenador, ['GET', "/jugadores/{$m['j1']}/asistencia-entrenamientos"]] as [$metodo, $ruta]) {
        sesionesPeticion($this, $metodo, '/api/v1'.$ruta, ['estado' => 'presente'])->assertUnauthorized();
    }
});
