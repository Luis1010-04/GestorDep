<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Src\Plantel\Aplicacion\ActualizarExpedienteDeJugador;
use Src\Plantel\Aplicacion\JugadorNoEncontrado;
use Src\Plantel\Dominio\RepositorioDeJugadores;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function expedienteActualizar(TestCase $prueba, string $correo, string $jugadorId, array $cuerpo): TestResponse
{
    return sesionesPeticion($prueba, 'PATCH', "/api/v1/jugadores/{$jugadorId}", $cuerpo, sesionesIniciar($prueba, $correo));
}

/** @return array<string, mixed> */
function expedienteFila(string $jugadorId): array
{
    return (array) DB::table('jugadores')->where('id', $jugadorId)->first();
}

test('HU-05: A actualiza nombres de J1 y el historial guarda la instantánea completa con un único instante', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-03-10 15:20:45.789', new DateTimeZone('UTC'))));
    $usuarioId = DB::table('usuarios')->where('correo', 'a@x.com')->value('id');

    $respuesta = expedienteActualizar($this, 'a@x.com', $m['j1'], ['nombres' => '  Uno Nuevo  '])->assertOk();

    $datos = $respuesta->json('datos');
    expect(array_keys($datos))->toBe(['id', 'nombres', 'apellidos', 'fecha_nacimiento', 'matricula', 'estatus_academico', 'estatus_deportivo', 'equipos', 'actualizado_en'])
        ->and($datos['nombres'])->toBe('Uno Nuevo')
        ->and($datos['actualizado_en'])->toBe('2026-03-10T15:20:45+00:00');

    $fila = expedienteFila($m['j1']);
    expect($fila['nombres'])->toBe('Uno Nuevo')
        ->and($fila['actualizado_por'])->toBe($usuarioId);

    $historial = DB::table('jugadores_historial')->where('jugador_id', $m['j1'])->get();
    expect($historial)->toHaveCount(1)
        ->and($historial[0]->operacion)->toBe('actualizar')
        ->and($historial[0]->version)->toBe(1)
        ->and($historial[0]->usuario_id)->toBe($usuarioId)
        ->and(json_decode($historial[0]->datos_anteriores, true))->toEqual([
            'nombres' => 'Uno', 'apellidos' => 'Alvarez', 'fecha_nacimiento' => '2009-05-10', 'matricula' => 'M-1',
            'estatus_academico' => 'activo', 'estatus_deportivo' => 'activo',
        ])
        ->and(json_decode($historial[0]->datos_nuevos, true))->toEqual([
            'nombres' => 'Uno Nuevo', 'apellidos' => 'Alvarez', 'fecha_nacimiento' => '2009-05-10', 'matricula' => 'M-1',
            'estatus_academico' => 'activo', 'estatus_deportivo' => 'activo',
        ])
        ->and((new DateTimeImmutable($historial[0]->ocurrido_en))->getTimestamp())->toBe((new DateTimeImmutable('2026-03-10 15:20:45', new DateTimeZone('UTC')))->getTimestamp())
        ->and((new DateTimeImmutable($fila['actualizado_en']))->getTimestamp())->toBe((new DateTimeImmutable($historial[0]->ocurrido_en))->getTimestamp());
});

test('HU-05: dos cambios seguidos dejan versiones consecutivas', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    expedienteActualizar($this, 'a@x.com', $m['j1'], ['nombres' => 'Uno B'])->assertOk();
    expedienteActualizar($this, 'a@x.com', $m['j1'], ['apellidos' => 'Alvarez B'])->assertOk();

    $historial = DB::table('jugadores_historial')->where('jugador_id', $m['j1'])->orderBy('version')->get();
    expect($historial->pluck('version')->all())->toBe([1, 2])
        ->and(json_decode($historial[1]->datos_anteriores, true))->toMatchArray(['nombres' => 'Uno B', 'apellidos' => 'Alvarez'])
        ->and(json_decode($historial[1]->datos_nuevos, true))->toMatchArray(['nombres' => 'Uno B', 'apellidos' => 'Alvarez B']);
});

test('HU-05: una actualización parcial cambia solo el campo enviado', function (string $campo, string $valor) {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $antes = expedienteFila($m['j1']);

    expedienteActualizar($this, 'a@x.com', $m['j1'], [$campo => $valor])->assertOk()->assertJsonPath("datos.{$campo}", $valor);

    $despues = expedienteFila($m['j1']);
    foreach (['nombres', 'apellidos', 'fecha_nacimiento', 'matricula', 'estatus_academico', 'estatus_deportivo'] as $columna) {
        expect($despues[$columna])->toBe($columna === $campo ? $valor : $antes[$columna]);
    }
    expect(DB::table('jugadores_historial')->where('jugador_id', $m['j1'])->count())->toBe(1);
})->with([
    'nombres' => ['nombres', 'Otro'],
    'apellidos' => ['apellidos', 'Otros'],
    'fecha_nacimiento' => ['fecha_nacimiento', '2010-02-03'],
]);

test('HU-05: se actualizan los tres campos a la vez y el recurso solo muestra los equipos del usuario (RN-14)', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    $respuesta = expedienteActualizar($this, 'a@x.com', $m['j3'], ['nombres' => 'N', 'apellidos' => 'A', 'fecha_nacimiento' => '2011-12-31'])->assertOk();

    expect($respuesta->json('datos.equipos'))->toBe([
        ['equipo_id' => $m['ea'], 'nombre' => 'EA', 'categoria' => ['id' => $m['categoria'], 'nombre' => 'Sub-17'], 'posicion' => 'portero'],
    ]);
    expect(expedienteActualizar($this, 'b@x.com', $m['j3'], ['nombres' => 'N2'])->assertOk()->json('datos.equipos.0.equipo_id'))->toBe($m['eb']);
});

test('HU-05: un jugador en baja puede actualizar su expediente', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    expedienteActualizar($this, 'a@x.com', $m['j5'], ['nombres' => 'Cinco B'])->assertOk()->assertJsonPath('datos.estatus_deportivo', 'baja');
});

test('HU-05: un campo no permitido da 422 validacion_fallida señalando el campo y no cambia nada', function (string $campo, mixed $valor) {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $antes = expedienteFila($m['j1']);

    expedienteActualizar($this, 'a@x.com', $m['j1'], ['nombres' => 'Cambiado', $campo => $valor])
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', $campo)
        ->assertJsonPath('errores.0.codigo', 'prohibited')
        ->assertJsonPath('errores.0.mensaje', 'El campo '.str_replace('_', ' ', $campo).' no está permitido.');

    expect(expedienteFila($m['j1']))->toBe($antes)
        ->and(DB::table('jugadores_historial')->count())->toBe(0);
})->with([
    'matricula' => ['matricula', 'M-99'],
    'estatus_academico' => ['estatus_academico', 'baja'],
    'estatus_deportivo' => ['estatus_deportivo', 'baja'],
    'id' => ['id', '0198f6a0-0000-7000-8000-000000000001'],
    'otro' => ['equipo_id', 'x'],
]);

test('HU-05: un cuerpo vacío da 422 en el campo cuerpo y uno sin campos permitidos señala el campo', function (array $cuerpo, array $campos, string $mensaje) {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    $respuesta = expedienteActualizar($this, 'a@x.com', $m['j1'], $cuerpo)->assertUnprocessable()->assertJsonPath('codigo', 'validacion_fallida');

    expect(array_column($respuesta->json('errores'), 'campo'))->toBe($campos)
        ->and($respuesta->json('errores.0.mensaje'))->toBe($mensaje)
        ->and(DB::table('jugadores_historial')->count())->toBe(0);
})->with([
    'vacío' => [[], ['cuerpo'], 'Debe enviar al menos uno de: nombres, apellidos, fecha_nacimiento'],
    'solo no permitidos' => [['matricula' => 'M-9'], ['matricula'], 'El campo matricula no está permitido.'],
]);

test('HU-05: nombres y apellidos vacíos, de solo espacios, largos o de tipo incorrecto, y fecha mal formada, dan 422 con el campo', function (string $campo, mixed $valor) {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $antes = expedienteFila($m['j1']);

    expedienteActualizar($this, 'a@x.com', $m['j1'], [$campo => $valor])
        ->assertUnprocessable()->assertJsonPath('codigo', 'validacion_fallida')->assertJsonPath('errores.0.campo', $campo);

    expect(expedienteFila($m['j1']))->toBe($antes)
        ->and(DB::table('jugadores_historial')->count())->toBe(0);
})->with([
    'nombres vacío' => ['nombres', ''],
    'nombres espacios' => ['nombres', '   '],
    'nombres nulo' => ['nombres', null],
    'nombres largo' => ['nombres', str_repeat('a', 121)],
    'nombres arreglo' => ['nombres', ['x']],
    'apellidos espacios' => ['apellidos', '   '],
    'apellidos largo' => ['apellidos', str_repeat('a', 121)],
    'fecha formato' => ['fecha_nacimiento', '10/05/2009'],
    'fecha inexistente' => ['fecha_nacimiento', '2009-02-31'],
    'fecha número' => ['fecha_nacimiento', 20090510],
]);

test('HU-05: 120 caracteres y una fecha extrema se aceptan (sin mínimo ni máximo)', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    expedienteActualizar($this, 'a@x.com', $m['j1'], ['nombres' => str_repeat('a', 120), 'fecha_nacimiento' => '1900-01-01'])->assertOk();
    expedienteActualizar($this, 'a@x.com', $m['j1'], ['fecha_nacimiento' => '2999-12-31'])->assertOk();
});

test('HU-05: una actualización sin cambios reales responde 200 sin escribir historial ni tocar la fila', function (array $cuerpo) {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $antes = expedienteFila($m['j1']);

    expedienteActualizar($this, 'a@x.com', $m['j1'], $cuerpo)->assertOk()->assertJsonPath('datos.nombres', 'Uno');

    expect(expedienteFila($m['j1']))->toBe($antes)
        ->and(DB::table('jugadores_historial')->count())->toBe(0);
})->with([
    'igual' => [['nombres' => 'Uno', 'apellidos' => 'Alvarez', 'fecha_nacimiento' => '2009-05-10']],
    'igual tras recortar' => [['nombres' => '  Uno ', 'apellidos' => ' Alvarez  ']],
]);

test('HU-05: un jugador ajeno o inexistente da 404 con cualquier cuerpo y sin tocar nada', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $antes = [expedienteFila($m['j2']), expedienteFila($m['j4'])];

    foreach (['j2', 'j4'] as $ajeno) {
        foreach ([['nombres' => 'Hack'], [], ['matricula' => 'X'], ['nombres' => '   ']] as $cuerpo) {
            expedienteActualizar($this, 'a@x.com', $m[$ajeno], $cuerpo)->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');
        }
    }
    expedienteActualizar($this, 'a@x.com', (string) Str::uuid7(), ['nombres' => 'Hack'])->assertNotFound();

    expect([expedienteFila($m['j2']), expedienteFila($m['j4'])])->toBe($antes)
        ->and(DB::table('jugadores_historial')->count())->toBe(0);
});

test('HU-05: un UUID mal formado o que no es v7 da 404, no 500', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();

    foreach (['3f8c2a52-6f3c-4a43-9d8f-0b1d2f4e5a6b', 'no-es-uuid'] as $id) {
        expedienteActualizar($this, 'a@x.com', $id, ['nombres' => 'X'])->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');
        expedienteActualizar($this, 'a@x.com', $id, [])->assertNotFound();
    }
});

test('HU-05: el caso de uso exige usuario identificado', function () {
    /** @var TestCase $this */
    expect(fn () => app(ActualizarExpedienteDeJugador::class)->ejecutar(Identificador::generar(), 'N', null, null))->toThrow(LogicException::class, 'usuario_no_identificado');
});

test('HU-05: si el jugador desaparece antes del bloqueo, el repositorio lanza JugadorNoEncontrado', function () {
    /** @var TestCase $this */
    mundoDeJugadores();

    expect(fn () => app(RepositorioDeJugadores::class)->actualizarExpediente(Identificador::generar(), 'X', null, null))->toThrow(JugadorNoEncontrado::class);
    expect(DB::table('jugadores_historial')->count())->toBe(0);
});

test('RNF-02: si falla el historial, la actualización del expediente no se guarda', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $sesion = sesionesIniciar($this, 'a@x.com');
    $antes = expedienteFila($m['j1']);
    $propietario = DB::connection('propietario');
    $propietario->unprepared("create or replace function falla_historial_de_prueba() returns trigger language plpgsql as \$\$ begin raise exception 'historial_de_prueba_falla'; end \$\$");
    $propietario->unprepared('create trigger tg_falla_de_prueba before insert on jugadores_historial for each row execute function falla_historial_de_prueba()');

    try {
        sesionesPeticion($this, 'PATCH', "/api/v1/jugadores/{$m['j1']}", ['nombres' => 'Cambiado'], $sesion)
            ->assertStatus(500)->assertJsonPath('codigo', 'error_interno');

        expect(expedienteFila($m['j1']))->toBe($antes)
            ->and(DB::table('jugadores_historial')->count())->toBe(0);
    } finally {
        $propietario->unprepared('drop trigger if exists tg_falla_de_prueba on jugadores_historial');
        $propietario->unprepared('drop function if exists falla_historial_de_prueba()');
    }
});
