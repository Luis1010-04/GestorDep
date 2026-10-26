<?php

use Database\Seeders\PosicionesSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Src\Plantel\Aplicacion\MatriculaDuplicada;
use Src\Plantel\Aplicacion\RegistrarJugador;
use Src\Plantel\Dominio\Jugador;
use Src\Plantel\Dominio\RepositorioDeJugadores;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

beforeEach(function () {
    sesionesPrepararBase();
    (new PosicionesSeeder)->run();
    sesionesCrearUsuario('a@x.com', ['entrenador']);
    sesionesCrearUsuario('b@x.com', ['entrenador']);
    sesionesCrearUsuario('d@x.com', ['docente']);
    sesionesCrearUsuario('ad@x.com', ['docente', 'entrenador']);
});

/**
 * Crea una categoría y un equipo por la API con el entrenador indicado; devuelve el id y la categoría del equipo.
 *
 * @return array{id: string, categoria: array{id: string, nombre: string}}
 */
function jugadoresCrearEquipo(TestCase $prueba, string $correo, string $nombre = 'EA', string $categoria = 'Sub-17'): array
{
    $sesion = sesionesIniciar($prueba, $correo);
    $categoriaId = DB::table('categorias')->where('nombre', $categoria)->value('id')
        ?? sesionesPeticion($prueba, 'POST', '/api/v1/categorias', ['nombre' => $categoria], $sesion)->json('datos.id');
    $equipoId = sesionesPeticion($prueba, 'POST', '/api/v1/equipos', ['nombre' => $nombre, 'categoria_id' => $categoriaId], $sesion)->assertCreated()->json('datos.id');

    return ['id' => $equipoId, 'categoria' => ['id' => $categoriaId, 'nombre' => $categoria]];
}

/**
 * @param  array<string, mixed>  $cuerpo
 * @return array<string, mixed>
 */
function jugadoresCuerpo(string $equipoId, array $cuerpo = []): array
{
    return [
        'nombres' => 'Luis Ángel',
        'apellidos' => 'Pérez Gómez',
        'fecha_nacimiento' => '2009-05-10',
        'matricula' => 'M-001',
        'equipo_id' => $equipoId,
        'posicion' => 'medio',
        ...$cuerpo,
    ];
}

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function jugadoresRegistrar(TestCase $prueba, string $correo, array $cuerpo): TestResponse
{
    return sesionesPeticion($prueba, 'POST', '/api/v1/jugadores', $cuerpo, sesionesIniciar($prueba, $correo));
}

/** @return array<string, int> */
function jugadoresConteos(): array
{
    $conteos = [];
    foreach (['jugadores', 'jugadores_historial', 'jugador_equipo', 'jugador_equipo_historial'] as $tabla) {
        $conteos[$tabla] = DB::table($tabla)->count();
    }

    return $conteos;
}

test('HU-03: un entrenador registra un jugador y se crean su expediente, su membresía y los dos historiales', function () {
    /** @var TestCase $this */
    $equipo = jugadoresCrearEquipo($this, 'a@x.com');

    $respuesta = jugadoresRegistrar($this, 'a@x.com', jugadoresCuerpo($equipo['id']))->assertCreated();

    $datos = $respuesta->json('datos');
    expect(array_keys($datos))->toBe(['id', 'nombres', 'apellidos', 'fecha_nacimiento', 'matricula', 'estatus_academico', 'estatus_deportivo', 'equipos', 'actualizado_en'])
        ->and($datos)->toMatchArray([
            'nombres' => 'Luis Ángel',
            'apellidos' => 'Pérez Gómez',
            'fecha_nacimiento' => '2009-05-10',
            'matricula' => 'M-001',
            'estatus_academico' => 'activo',
            'estatus_deportivo' => 'activo',
        ])
        ->and($datos['equipos'])->toBe([
            ['equipo_id' => $equipo['id'], 'nombre' => 'EA', 'categoria' => $equipo['categoria'], 'posicion' => 'medio'],
        ]);

    $usuarioId = DB::table('usuarios')->where('correo', 'a@x.com')->value('id');
    $jugador = DB::table('jugadores')->where('id', $datos['id'])->first();
    expect($jugador->matricula)->toBe('M-001')
        ->and($jugador->creado_por)->toBe($usuarioId);

    $membresias = DB::table('jugador_equipo')->where('jugador_id', $datos['id'])->get();
    expect($membresias)->toHaveCount(1)
        ->and($membresias[0]->equipo_id)->toBe($equipo['id'])
        ->and($membresias[0]->posicion_id)->toBe(DB::table('posiciones')->where('codigo', 'medio')->value('id'))
        ->and($membresias[0]->hasta)->toBeNull()
        ->and($membresias[0]->creado_por)->toBe($usuarioId);

    $historialJugador = DB::table('jugadores_historial')->where('jugador_id', $datos['id'])->get();
    expect($historialJugador)->toHaveCount(1)
        ->and($historialJugador[0]->operacion)->toBe('crear')
        ->and($historialJugador[0]->usuario_id)->toBe($usuarioId)
        ->and($historialJugador[0]->datos_anteriores)->toBeNull()
        ->and(json_decode($historialJugador[0]->datos_nuevos, true))->toMatchArray(['matricula' => 'M-001', 'nombres' => 'Luis Ángel']);

    $historialMembresia = DB::table('jugador_equipo_historial')->where('jugador_equipo_id', $membresias[0]->id)->get();
    expect($historialMembresia)->toHaveCount(1)
        ->and($historialMembresia[0]->operacion)->toBe('crear')
        ->and($historialMembresia[0]->usuario_id)->toBe($usuarioId)
        ->and(json_decode($historialMembresia[0]->datos_nuevos, true))->toMatchArray(['jugador_id' => $datos['id'], 'equipo_id' => $equipo['id'], 'hasta' => null]);
});

test('HU-03: la membresía empieza el día de hoy en America/Mexico_City', function () {
    /** @var TestCase $this */
    // 2026-03-10 03:30 UTC sigue siendo el 9 en Ciudad de México.
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-03-10 03:30:00', new DateTimeZone('UTC'))));
    $equipo = jugadoresCrearEquipo($this, 'a@x.com');

    $id = jugadoresRegistrar($this, 'a@x.com', jugadoresCuerpo($equipo['id']))->assertCreated()->json('datos.id');

    expect(DB::table('jugador_equipo')->where('jugador_id', $id)->value('desde'))->toBe('2026-03-09');
});

test('HU-03: la categoría que se muestra es la del equipo', function () {
    /** @var TestCase $this */
    $equipo = jugadoresCrearEquipo($this, 'a@x.com', 'EA', 'Sub-15');

    jugadoresRegistrar($this, 'a@x.com', jugadoresCuerpo($equipo['id']))
        ->assertCreated()
        ->assertJsonPath('datos.equipos.0.categoria', ['id' => $equipo['categoria']['id'], 'nombre' => 'Sub-15']);
});

test('HU-03: los textos se recortan y la matrícula se guarda como la escribió el usuario', function () {
    /** @var TestCase $this */
    $equipo = jugadoresCrearEquipo($this, 'a@x.com');

    $respuesta = jugadoresRegistrar($this, 'a@x.com', jugadoresCuerpo($equipo['id'], ['nombres' => '  Luis ', 'matricula' => '  m-001x  ']))
        ->assertCreated();

    expect($respuesta->json('datos.matricula'))->toBe('m-001x')
        ->and($respuesta->json('datos.nombres'))->toBe('Luis')
        ->and(DB::table('jugadores')->value('matricula'))->toBe('m-001x');
});

test('HU-03: un usuario con los dos roles registra un jugador', function () {
    /** @var TestCase $this */
    $equipo = jugadoresCrearEquipo($this, 'ad@x.com');

    jugadoresRegistrar($this, 'ad@x.com', jugadoresCuerpo($equipo['id']))->assertCreated();
});

test('RNF-03: una matrícula ya existente da 409 matricula_duplicada sin datos del expediente existente', function () {
    /** @var TestCase $this */
    $equipo = jugadoresCrearEquipo($this, 'a@x.com');
    $existente = jugadoresRegistrar($this, 'a@x.com', jugadoresCuerpo($equipo['id']))->assertCreated()->json('datos');
    $antes = jugadoresConteos();

    $respuesta = jugadoresRegistrar($this, 'a@x.com', jugadoresCuerpo($equipo['id'], ['nombres' => 'Otro', 'apellidos' => 'Distinto']))
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'matricula_duplicada', 'mensaje' => 'Ya existe un jugador con esa matrícula.', 'errores' => []]);

    expect(jugadoresConteos())->toBe($antes);
    foreach (['Luis Ángel', 'Pérez Gómez', 'Luis', 'Pérez', $existente['id'], 'Otro', 'Distinto'] as $dato) {
        expect($respuesta->getContent())->not->toContain($dato);
    }
});

test('RNF-03: una matrícula en otras mayúsculas o con espacios alrededor da 409', function (string $matricula) {
    /** @var TestCase $this */
    $equipo = jugadoresCrearEquipo($this, 'a@x.com');
    jugadoresRegistrar($this, 'a@x.com', jugadoresCuerpo($equipo['id'], ['matricula' => 'Ab-12']))->assertCreated();
    $antes = jugadoresConteos();

    jugadoresRegistrar($this, 'a@x.com', jugadoresCuerpo($equipo['id'], ['matricula' => $matricula]))
        ->assertStatus(409)->assertJsonPath('codigo', 'matricula_duplicada');

    expect(jugadoresConteos())->toBe($antes);
})->with(['ab-12', 'AB-12', '  Ab-12', 'Ab-12   ', ' aB-12 ']);

test('RNF-03: un entrenador no puede registrar la matrícula que ya tiene un jugador de otro entrenador', function () {
    /** @var TestCase $this */
    $ea = jugadoresCrearEquipo($this, 'a@x.com');
    $eb = jugadoresCrearEquipo($this, 'b@x.com', 'EB');
    jugadoresRegistrar($this, 'a@x.com', jugadoresCuerpo($ea['id']))->assertCreated();

    jugadoresRegistrar($this, 'b@x.com', jugadoresCuerpo($eb['id']))->assertStatus(409)->assertJsonPath('codigo', 'matricula_duplicada');
});

test('RNF-03: la matrícula de un jugador con borrado lógico se puede registrar de nuevo', function () {
    /** @var TestCase $this */
    $equipo = jugadoresCrearEquipo($this, 'a@x.com');
    $primero = jugadoresRegistrar($this, 'a@x.com', jugadoresCuerpo($equipo['id']))->assertCreated()->json('datos.id');
    DB::connection('propietario')->table('jugadores')->where('id', $primero)->update(['eliminado_en' => now()]);

    jugadoresRegistrar($this, 'a@x.com', jugadoresCuerpo($equipo['id'], ['matricula' => 'm-001']))->assertCreated();
});

test('RNF-03: si otra petición ganó la carrera por la matrícula, el índice único también responde matricula_duplicada', function () {
    /** @var TestCase $this */
    $equipo = jugadoresCrearEquipo($this, 'a@x.com');
    $equipoId = Identificador::desdeTexto($equipo['id']);
    $posicionId = Identificador::desdeTexto(DB::table('posiciones')->where('codigo', 'medio')->value('id'));
    $repositorio = app(RepositorioDeJugadores::class);
    $ahora = app(Reloj::class)->ahora();
    $nacimiento = new DateTimeImmutable('2009-05-10');
    $repositorio->guardarNuevo(new Jugador(Identificador::generar(), 'Uno', 'Dos', $nacimiento, 'M-001', $ahora), $equipoId, $posicionId);
    $antes = jugadoresConteos();

    expect(fn () => $repositorio->guardarNuevo(new Jugador(Identificador::generar(), 'Tres', 'Cuatro', $nacimiento, 'm-001', $ahora), $equipoId, $posicionId))
        ->toThrow(MatriculaDuplicada::class);
    expect(jugadoresConteos())->toBe($antes);
});

test('HU-03: el equipo de otro entrenador, uno inexistente o con asignación cerrada responden 404', function () {
    /** @var TestCase $this */
    $ea = jugadoresCrearEquipo($this, 'a@x.com');
    $cerrado = jugadoresCrearEquipo($this, 'b@x.com', 'Cerrado');
    DB::connection('propietario')->table('entrenador_equipo')->where('equipo_id', $cerrado['id'])->update(['hasta' => '2026-01-01']);
    $antes = jugadoresConteos();

    jugadoresRegistrar($this, 'b@x.com', jugadoresCuerpo($ea['id']))
        ->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');
    jugadoresRegistrar($this, 'b@x.com', jugadoresCuerpo(Identificador::generar()->valor()))->assertNotFound();
    jugadoresRegistrar($this, 'b@x.com', jugadoresCuerpo($cerrado['id']))->assertNotFound();
    // Un equipo ajeno gana a una posición inválida: no se revela nada del equipo.
    jugadoresRegistrar($this, 'b@x.com', jugadoresCuerpo($ea['id'], ['posicion' => 'arquero']))->assertNotFound();

    expect(jugadoresConteos())->toBe($antes);
});

test('HU-03: una posición desconocida da 422 posicion_invalida', function () {
    /** @var TestCase $this */
    $equipo = jugadoresCrearEquipo($this, 'a@x.com');
    $antes = jugadoresConteos();

    jugadoresRegistrar($this, 'a@x.com', jugadoresCuerpo($equipo['id'], ['posicion' => 'arquero']))
        ->assertUnprocessable()
        ->assertExactJson(['codigo' => 'posicion_invalida', 'mensaje' => 'La posición indicada no existe.', 'errores' => []]);

    expect(jugadoresConteos())->toBe($antes);
});

test('HU-03: falta un campo obligatorio, 422 validacion_fallida con el campo señalado', function (string $campo) {
    /** @var TestCase $this */
    $equipo = jugadoresCrearEquipo($this, 'a@x.com');
    $cuerpo = jugadoresCuerpo($equipo['id']);
    unset($cuerpo[$campo]);

    jugadoresRegistrar($this, 'a@x.com', $cuerpo)
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', $campo);
    expect(DB::table('jugadores')->count())->toBe(0);
})->with(['nombres', 'apellidos', 'fecha_nacimiento', 'matricula', 'equipo_id', 'posicion']);

test('HU-03: los campos vacíos, demasiado largos o mal formados dan 422 y nunca 500', function (string $campo, mixed $valor) {
    /** @var TestCase $this */
    $equipo = jugadoresCrearEquipo($this, 'a@x.com');

    jugadoresRegistrar($this, 'a@x.com', jugadoresCuerpo($equipo['id'], [$campo => $valor]))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', $campo);
    expect(DB::table('jugadores')->count())->toBe(0);
})->with([
    'nombres en blanco' => ['nombres', '   '],
    'nombres de 121' => ['nombres', str_repeat('a', 121)],
    'apellidos de 121' => ['apellidos', str_repeat('a', 121)],
    'matricula en blanco' => ['matricula', '   '],
    'matricula de 31' => ['matricula', str_repeat('a', 31)],
    'fecha con otro formato' => ['fecha_nacimiento', '10/05/2009'],
    'fecha imposible' => ['fecha_nacimiento', '2009-02-30'],
    'equipo sin formato de uuid' => ['equipo_id', 'no-es-uuid'],
    'equipo no v7' => ['equipo_id', '3f8c2a52-6f3c-4a43-9d8f-0b1d2f4e5a6b'],
    'posicion arreglo' => ['posicion', ['medio']],
    'nombres arreglo' => ['nombres', ['x']],
]);

test('HU-03: los límites exactos de 120 y 30 caracteres se aceptan', function () {
    /** @var TestCase $this */
    $equipo = jugadoresCrearEquipo($this, 'a@x.com');

    jugadoresRegistrar($this, 'a@x.com', jugadoresCuerpo($equipo['id'], [
        'nombres' => str_repeat('a', 120),
        'apellidos' => str_repeat('b', 120),
        'matricula' => str_repeat('c', 30),
    ]))->assertCreated();
});

test('HU-01: un docente recibe 403 sin_permiso al registrar un jugador', function () {
    /** @var TestCase $this */
    $equipo = jugadoresCrearEquipo($this, 'a@x.com');

    jugadoresRegistrar($this, 'd@x.com', jugadoresCuerpo($equipo['id']))
        ->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    expect(DB::table('jugadores')->count())->toBe(0);
});

test('HU-01: sin sesión, POST /jugadores responde 401', function () {
    /** @var TestCase $this */
    sesionesPeticion($this, 'POST', '/api/v1/jugadores', jugadoresCuerpo(Identificador::generar()->valor()))->assertUnauthorized();
});

test('HU-03: registrar un jugador sin usuario identificado falla', function () {
    /** @var TestCase $this */
    expect(fn () => app(RegistrarJugador::class)->ejecutar('Luis', 'Pérez', new DateTimeImmutable('2009-05-10'), 'M-001', Identificador::generar(), 'medio'))
        ->toThrow(LogicException::class, 'usuario_no_identificado');
});

test('RNF-02: si falla el historial, ni el jugador, ni la membresía ni los historiales se guardan', function (string $tablaQueFalla) {
    /** @var TestCase $this */
    $equipo = jugadoresCrearEquipo($this, 'a@x.com');
    $sesion = sesionesIniciar($this, 'a@x.com');
    $propietario = DB::connection('propietario');
    esquemaMarcarSucio();
    $propietario->statement("create or replace function falla_historial_de_prueba() returns trigger language plpgsql as \$\$ begin raise exception 'historial_de_prueba_falla'; end \$\$");
    $propietario->statement("create trigger tg_falla_de_prueba before insert on {$tablaQueFalla} for each row execute function falla_historial_de_prueba()");

    try {
        sesionesPeticion($this, 'POST', '/api/v1/jugadores', jugadoresCuerpo($equipo['id']), $sesion)
            ->assertStatus(500)->assertJsonPath('codigo', 'error_interno');

        foreach (['jugadores', 'jugadores_historial', 'jugador_equipo', 'jugador_equipo_historial'] as $tabla) {
            expect(DB::table($tabla)->count())->toBe(0, $tabla);
        }
    } finally {
        $propietario->statement("drop trigger if exists tg_falla_de_prueba on {$tablaQueFalla}");
        $propietario->statement('drop function if exists falla_historial_de_prueba()');
    }
})->with(['jugadores_historial', 'jugador_equipo_historial']);
