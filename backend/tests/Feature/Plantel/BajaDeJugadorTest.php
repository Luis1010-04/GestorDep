<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Src\Plantel\Aplicacion\DarDeBajaAJugador;
use Src\Plantel\Aplicacion\JugadorNoEncontrado;
use Src\Plantel\Aplicacion\JugadorYaEnBaja;
use Src\Plantel\Dominio\RepositorioDeJugadores;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function bajaRegistrar(TestCase $prueba, string $correo, string $jugadorId, array $cuerpo): TestResponse
{
    return sesionesPeticion($prueba, 'POST', "/api/v1/jugadores/{$jugadorId}/baja", $cuerpo, sesionesIniciar($prueba, $correo));
}

/** @return array<string, mixed> */
function bajaEstado(string $jugadorId): array
{
    return [
        'jugador' => (array) DB::table('jugadores')->where('id', $jugadorId)->first(),
        'bajas' => DB::table('bajas_jugador')->where('jugador_id', $jugadorId)->count(),
        'historial_jugador' => DB::table('jugadores_historial')->where('jugador_id', $jugadorId)->count(),
        'historial_baja' => DB::table('bajas_jugador_historial')->count(),
    ];
}

test('HU-29: A da de baja a J1 con motivo y fecha; se guardan la baja, los estatus y los dos historiales con el mismo instante', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-03-10 15:20:45.789', new DateTimeZone('UTC'))));

    $respuesta = bajaRegistrar($this, 'a@x.com', $m['j1'], ['motivo' => '  Cambió de escuela  ', 'fecha' => '2026-03-09'])->assertCreated();

    $datos = $respuesta->json('datos');
    expect(array_keys($datos))->toBe(['jugador_id', 'motivo', 'fecha', 'creado_en'])
        ->and($datos['jugador_id'])->toBe($m['j1'])
        ->and($datos['motivo'])->toBe('Cambió de escuela')
        ->and($datos['fecha'])->toBe('2026-03-09')
        ->and($datos['creado_en'])->toBe('2026-03-10T15:20:45+00:00');

    $baja = DB::table('bajas_jugador')->where('jugador_id', $m['j1'])->get();
    expect($baja)->toHaveCount(1)
        ->and($baja[0]->motivo)->toBe('Cambió de escuela')
        ->and($baja[0]->fecha)->toBe('2026-03-09')
        ->and($baja[0]->registrada_por)->toBe($m['a']);

    $jugador = (array) DB::table('jugadores')->where('id', $m['j1'])->first();
    expect($jugador['estatus_academico'])->toBe('baja')
        ->and($jugador['estatus_deportivo'])->toBe('baja')
        ->and($jugador['actualizado_por'])->toBe($m['a']);

    $historialJugador = DB::table('jugadores_historial')->where('jugador_id', $m['j1'])->get();
    $historialBaja = DB::table('bajas_jugador_historial')->where('baja_jugador_id', $baja[0]->id)->get();
    expect($historialJugador)->toHaveCount(1)
        ->and($historialJugador[0]->operacion)->toBe('actualizar')
        ->and($historialJugador[0]->usuario_id)->toBe($m['a'])
        ->and(json_decode($historialJugador[0]->datos_anteriores, true))->toEqual([
            'nombres' => 'Uno', 'apellidos' => 'Alvarez', 'fecha_nacimiento' => '2009-05-10', 'matricula' => 'M-1',
            'estatus_academico' => 'activo', 'estatus_deportivo' => 'activo',
        ])
        ->and(json_decode($historialJugador[0]->datos_nuevos, true))->toEqual([
            'nombres' => 'Uno', 'apellidos' => 'Alvarez', 'fecha_nacimiento' => '2009-05-10', 'matricula' => 'M-1',
            'estatus_academico' => 'baja', 'estatus_deportivo' => 'baja',
        ])
        ->and($historialBaja)->toHaveCount(1)
        ->and($historialBaja[0]->operacion)->toBe('crear')
        ->and($historialBaja[0]->datos_anteriores)->toBeNull()
        ->and($historialBaja[0]->usuario_id)->toBe($m['a'])
        ->and(json_decode($historialBaja[0]->datos_nuevos, true))->toEqual([
            'jugador_id' => $m['j1'], 'motivo' => 'Cambió de escuela', 'fecha' => '2026-03-09', 'registrada_por' => $m['a'],
        ])
        ->and($historialBaja[0]->ocurrido_en)->toBe($historialJugador[0]->ocurrido_en)
        ->and((new DateTimeImmutable($historialBaja[0]->ocurrido_en))->getTimestamp())->toBe((new DateTimeImmutable('2026-03-10 15:20:45', new DateTimeZone('UTC')))->getTimestamp())
        ->and((new DateTimeImmutable($jugador['actualizado_en']))->getTimestamp())->toBe((new DateTimeImmutable($historialJugador[0]->ocurrido_en))->getTimestamp())
        ->and((new DateTimeImmutable($baja[0]->creado_en))->getTimestamp())->toBe((new DateTimeImmutable($historialJugador[0]->ocurrido_en))->getTimestamp());
});

test('RN-17: la baja no cierra las membresías del jugador', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    bajaRegistrar($this, 'a@x.com', $m['j1'], ['motivo' => 'x', 'fecha' => '2026-03-09'])->assertCreated();

    expect(DB::table('jugador_equipo')->where('jugador_id', $m['j1'])->whereNull('hasta')->count())->toBe(1)
        ->and(DB::table('jugador_equipo_historial')->count())->toBe(0);
});

test('HU-29: la fecha futura y el motivo de 500 caracteres se aceptan', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    bajaRegistrar($this, 'a@x.com', $m['j1'], ['motivo' => str_repeat('a', 500), 'fecha' => '2999-12-31'])->assertCreated()->assertJsonPath('datos.fecha', '2999-12-31');
});

test('RN-23: un motivo ausente, vacío o de solo espacios da 422 motivo_obligatorio y no se guarda nada', function (array $cuerpo) {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $antes = bajaEstado($m['j1']);

    bajaRegistrar($this, 'a@x.com', $m['j1'], $cuerpo)
        ->assertUnprocessable()
        ->assertExactJson(['codigo' => 'motivo_obligatorio', 'mensaje' => 'La baja requiere un motivo.', 'errores' => []]);

    expect(bajaEstado($m['j1']))->toBe($antes)
        ->and($antes['bajas'])->toBe(0);
})->with([
    'ausente' => [['fecha' => '2026-03-09']],
    'vacío' => [['motivo' => '', 'fecha' => '2026-03-09']],
    'nulo' => [['motivo' => null, 'fecha' => '2026-03-09']],
    'espacios' => [['motivo' => '   ', 'fecha' => '2026-03-09']],
    'tabuladores y saltos' => [['motivo' => " \t\n ", 'fecha' => '2026-03-09']],
]);

test('HU-29: una fecha ausente o mal formada da 422 validacion_fallida señalando el campo', function (array $cuerpo) {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $antes = bajaEstado($m['j1']);

    bajaRegistrar($this, 'a@x.com', $m['j1'], $cuerpo)
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', 'fecha');

    expect(bajaEstado($m['j1']))->toBe($antes);
})->with([
    'ausente' => [['motivo' => 'x']],
    'nula' => [['motivo' => 'x', 'fecha' => null]],
    'formato' => [['motivo' => 'x', 'fecha' => '09/03/2026']],
    'inexistente' => [['motivo' => 'x', 'fecha' => '2026-02-31']],
    'con hora' => [['motivo' => 'x', 'fecha' => '2026-03-09 10:00:00']],
    'número' => [['motivo' => 'x', 'fecha' => 20260309]],
    'arreglo' => [['motivo' => 'x', 'fecha' => ['2026-03-09']]],
    'además sin motivo' => [['fecha' => 'mal']],
]);

test('HU-29: un motivo de más de 500 caracteres o de tipo incorrecto da 422 validacion_fallida en motivo', function (mixed $motivo) {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $antes = bajaEstado($m['j1']);

    bajaRegistrar($this, 'a@x.com', $m['j1'], ['motivo' => $motivo, 'fecha' => '2026-03-09'])
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', 'motivo');

    expect(bajaEstado($m['j1']))->toBe($antes);
})->with([
    'largo' => [str_repeat('a', 501)],
    'arreglo' => [['x']],
    'número' => [5],
]);

test('HU-29: un jugador ajeno o inexistente da 404 con cualquier cuerpo y sin tocar nada', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $antes = [bajaEstado($m['j2']), bajaEstado($m['j4'])];

    foreach (['j2', 'j4'] as $ajeno) {
        foreach ([['motivo' => 'x', 'fecha' => '2026-03-09'], [], ['motivo' => '   ', 'fecha' => 'mal']] as $cuerpo) {
            bajaRegistrar($this, 'a@x.com', $m[$ajeno], $cuerpo)->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');
        }
    }
    bajaRegistrar($this, 'a@x.com', (string) Str::uuid7(), ['motivo' => 'x', 'fecha' => '2026-03-09'])->assertNotFound();

    expect([bajaEstado($m['j2']), bajaEstado($m['j4'])])->toBe($antes);
});

test('HU-29: un UUID mal formado o que no es v7 da 404, no 500', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    foreach (['3f8c2a52-6f3c-4a43-9d8f-0b1d2f4e5a6b', 'no-es-uuid'] as $id) {
        bajaRegistrar($this, 'a@x.com', $id, ['motivo' => 'x', 'fecha' => '2026-03-09'])->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');
        bajaRegistrar($this, 'a@x.com', $id, [])->assertNotFound();
    }
});

test('HU-29: el docente recibe 403 y no se guarda nada', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $antes = bajaEstado($m['j1']);

    bajaRegistrar($this, 'd@x.com', $m['j1'], ['motivo' => 'x', 'fecha' => '2026-03-09'])->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');

    expect(bajaEstado($m['j1']))->toBe($antes);
});

test('RN-23: dar de baja a quien ya está en baja da 409 jugador_ya_en_baja y deja una sola fila', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    bajaRegistrar($this, 'a@x.com', $m['j1'], ['motivo' => 'Primera', 'fecha' => '2026-03-09'])->assertCreated();
    $antes = bajaEstado($m['j1']);

    bajaRegistrar($this, 'a@x.com', $m['j1'], ['motivo' => 'Segunda', 'fecha' => '2026-03-10'])
        ->assertConflict()
        ->assertExactJson(['codigo' => 'jugador_ya_en_baja', 'mensaje' => 'El jugador ya está en baja.', 'errores' => []]);

    expect(bajaEstado($m['j1']))->toBe($antes)
        ->and($antes['bajas'])->toBe(1)
        ->and(DB::table('bajas_jugador')->where('jugador_id', $m['j1'])->value('motivo'))->toBe('Primera');
});

test('RN-23: un jugador que ya figura en baja (J5) da 409 jugador_ya_en_baja', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    bajaRegistrar($this, 'a@x.com', $m['j5'], ['motivo' => 'x', 'fecha' => '2026-03-09'])->assertConflict()->assertJsonPath('codigo', 'jugador_ya_en_baja');

    expect(DB::table('bajas_jugador')->count())->toBe(0);
});

test('RN-23: el repositorio comprueba el estatus bajo el bloqueo y rechaza al jugador que pasó a baja tras el alcance', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $repositorio = app(RepositorioDeJugadores::class);

    expect(fn () => $repositorio->darDeBaja(Identificador::desdeTexto($m['j5']), 'x', new DateTimeImmutable('2026-03-09')))->toThrow(JugadorYaEnBaja::class)
        ->and(fn () => $repositorio->darDeBaja(Identificador::generar(), 'x', new DateTimeImmutable('2026-03-09')))->toThrow(JugadorNoEncontrado::class);

    expect(DB::table('bajas_jugador')->count())->toBe(0)
        ->and(DB::table('jugadores_historial')->count())->toBe(0)
        ->and(DB::table('bajas_jugador_historial')->count())->toBe(0);
});

test('HU-29: el caso de uso exige usuario identificado', function () {
    /** @var TestCase $this */
    expect(fn () => app(DarDeBajaAJugador::class)->ejecutar(Identificador::generar(), 'x', new DateTimeImmutable('2026-03-09')))
        ->toThrow(LogicException::class, 'usuario_no_identificado');
});

test('DD-30: la baja de J3 por A lo quita del listado de A y de B, sin cerrar membresías, y ambos ven el detalle en baja', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    bajaRegistrar($this, 'a@x.com', $m['j3'], ['motivo' => 'Se mudó', 'fecha' => '2026-03-09'])->assertCreated();

    foreach (['a@x.com', 'b@x.com'] as $correo) {
        $sesion = sesionesIniciar($this, $correo);
        $ids = array_column(sesionesPeticion($this, 'GET', '/api/v1/jugadores', [], $sesion)->assertOk()->json('datos'), 'id');
        expect($ids)->not->toContain($m['j3']);

        sesionesPeticion($this, 'GET', "/api/v1/jugadores/{$m['j3']}", [], $sesion)
            ->assertOk()
            ->assertJsonPath('datos.estatus_academico', 'baja')
            ->assertJsonPath('datos.estatus_deportivo', 'baja');
    }
    expect(DB::table('jugador_equipo')->where('jugador_id', $m['j3'])->whereNull('hasta')->count())->toBe(2);
});

test('DD-30: B puede dar de baja a J3, que comparte con A', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    bajaRegistrar($this, 'b@x.com', $m['j3'], ['motivo' => 'x', 'fecha' => '2026-03-09'])->assertCreated();

    expect(DB::table('bajas_jugador')->where('jugador_id', $m['j3'])->value('registrada_por'))->toBe($m['b']);
});

test('DD-40: un jugador dado de baja por la API sigue recibiendo PATCH de posición y de expediente', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    bajaRegistrar($this, 'a@x.com', $m['j1'], ['motivo' => 'x', 'fecha' => '2026-03-09'])->assertCreated();
    $sesion = sesionesIniciar($this, 'a@x.com');

    sesionesPeticion($this, 'PATCH', "/api/v1/equipos/{$m['ea']}/jugadores/{$m['j1']}", ['posicion' => 'defensa'], $sesion)->assertOk();
    sesionesPeticion($this, 'PATCH', "/api/v1/jugadores/{$m['j1']}", ['nombres' => 'Uno B'], $sesion)
        ->assertOk()->assertJsonPath('datos.estatus_deportivo', 'baja')->assertJsonPath('datos.nombres', 'Uno B');

    expect(DB::table('jugador_equipo')->where('jugador_id', $m['j1'])->value('posicion_id'))->toBe(DB::table('posiciones')->where('codigo', 'defensa')->value('id'));
});

test('RN-23: el CHECK de la base rechaza un motivo vacío o de solo espacios', function (string $motivo) {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    expect(fn () => DB::connection('propietario')->table('bajas_jugador')->insert([
        'id' => Identificador::generar()->valor(), 'jugador_id' => $m['j1'], 'motivo' => $motivo, 'fecha' => '2026-03-09', 'registrada_por' => $m['a'],
    ]))->toThrow(QueryException::class, 'ck_bajas_jugador_motivo');
})->with(['vacío' => [''], 'espacios' => ['   ']]);

test('RNF-02: si falla el historial, la baja no se guarda', function (string $tablaConDisparador) {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $sesion = sesionesIniciar($this, 'a@x.com');
    $antes = bajaEstado($m['j1']);
    $propietario = DB::connection('propietario');
    esquemaMarcarSucio();
    $propietario->unprepared("create or replace function falla_historial_de_prueba() returns trigger language plpgsql as \$\$ begin raise exception 'historial_de_prueba_falla'; end \$\$");
    $propietario->unprepared("create trigger tg_falla_de_prueba before insert on {$tablaConDisparador} for each row execute function falla_historial_de_prueba()");

    try {
        sesionesPeticion($this, 'POST', "/api/v1/jugadores/{$m['j1']}/baja", ['motivo' => 'x', 'fecha' => '2026-03-09'], $sesion)
            ->assertStatus(500)->assertJsonPath('codigo', 'error_interno');

        expect(bajaEstado($m['j1']))->toBe($antes)
            ->and($antes['jugador']['estatus_deportivo'])->toBe('activo')
            ->and(DB::table('bajas_jugador')->count())->toBe(0)
            ->and(DB::table('jugadores_historial')->count())->toBe(0)
            ->and(DB::table('bajas_jugador_historial')->count())->toBe(0);
    } finally {
        $propietario->unprepared("drop trigger if exists tg_falla_de_prueba on {$tablaConDisparador}");
        $propietario->unprepared('drop function if exists falla_historial_de_prueba()');
    }
})->with([
    'falla jugadores_historial' => ['jugadores_historial'],
    'falla bajas_jugador_historial' => ['bajas_jugador_historial'],
]);
