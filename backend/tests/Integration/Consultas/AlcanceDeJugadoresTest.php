<?php

use Illuminate\Database\Eloquent\Builder as ConsultaEloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Aplicacion\AlcanceDeJugadores;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;

beforeEach(function () {
    esquemaReiniciar();
});

/** @param  array<string, mixed>  $atributos */
function alcanceInsertar(string $tabla, array $atributos): string
{
    $id = Identificador::generar()->valor();
    DB::connection('propietario')->table($tabla)->insert(['id' => $id, ...$atributos]);

    return $id;
}

function alcanceUsuario(string $correo): string
{
    return alcanceInsertar('usuarios', ['nombre' => $correo, 'correo' => $correo, 'contrasena_hash' => 'hash-de-prueba']);
}

function alcanceJugador(string $matricula, bool $enBaja = false): string
{
    return alcanceInsertar('jugadores', [
        'nombres' => 'Jugador',
        'apellidos' => $matricula,
        'fecha_nacimiento' => '2009-05-10',
        'matricula' => $matricula,
        ...($enBaja ? ['estatus_academico' => 'baja', 'estatus_deportivo' => 'baja'] : []),
    ]);
}

function alcanceEquipo(string $nombre): string
{
    $categoria = alcanceInsertar('categorias', ['nombre' => 'Categoria '.$nombre]);

    return alcanceInsertar('equipos', ['nombre' => $nombre, 'categoria_id' => $categoria]);
}

/** @param  array<string, mixed>  $atributos */
function alcanceMembresia(string $jugador, string $equipo, array $atributos = []): string
{
    $posicion = DB::connection('propietario')->table('posiciones')->value('id')
        ?? alcanceInsertar('posiciones', ['codigo' => 'portero', 'nombre' => 'Portero']);

    return alcanceInsertar('jugador_equipo', ['jugador_id' => $jugador, 'equipo_id' => $equipo, 'posicion_id' => $posicion, 'desde' => '2026-01-01', ...$atributos]);
}

/** @param  array<string, mixed>  $atributos */
function alcanceAsignacion(string $usuario, string $equipo, array $atributos = []): string
{
    return alcanceInsertar('entrenador_equipo', ['usuario_id' => $usuario, 'equipo_id' => $equipo, 'desde' => '2026-01-01', ...$atributos]);
}

/** @param  list<string>  $roles */
function alcanceActual(?string $id, array $roles): UsuarioActual
{
    return new class($id, $roles) implements UsuarioActual
    {
        /** @param  list<string>  $roles */
        public function __construct(private readonly ?string $id, private readonly array $roles) {}

        public function identificador(): ?Identificador
        {
            return $this->id === null ? null : Identificador::desdeTexto($this->id);
        }

        public function roles(): array
        {
            return $this->roles;
        }
    };
}

/**
 * Datos de partida de docs/hitos/f1-h1.md, 7.4: entrenadores A y B, docente D, entrenador-docente AD;
 * equipos EA y EB; jugadores J1 (EA), J2 (EB), J3 (EA y EB), J4 (sin equipo) y J5 (EA, con baja).
 *
 * @return array<string, string>
 */
function alcanceEscenario(): array
{
    $e = [
        'A' => alcanceUsuario('a@x.com'), 'B' => alcanceUsuario('b@x.com'),
        'D' => alcanceUsuario('d@x.com'), 'AD' => alcanceUsuario('ad@x.com'),
        'EA' => alcanceEquipo('EA'), 'EB' => alcanceEquipo('EB'),
        'J1' => alcanceJugador('J1'), 'J2' => alcanceJugador('J2'), 'J3' => alcanceJugador('J3'),
        'J4' => alcanceJugador('J4'), 'J5' => alcanceJugador('J5', enBaja: true),
    ];
    alcanceAsignacion($e['A'], $e['EA']);
    alcanceAsignacion($e['B'], $e['EB']);
    alcanceMembresia($e['J1'], $e['EA']);
    alcanceMembresia($e['J2'], $e['EB']);
    alcanceMembresia($e['J3'], $e['EA']);
    alcanceMembresia($e['J3'], $e['EB']);
    alcanceMembresia($e['J5'], $e['EA']);

    return $e;
}

/**
 * @param  list<string>  $roles
 * @return list<string> matriculas visibles, ordenadas
 */
function alcanceMatriculas(?string $id, array $roles, bool $incluirBajas = false): array
{
    $consulta = app(AlcanceDeJugadores::class)->restringir(alcanceActual($id, $roles), DB::table('jugadores'), 'jugadores.id', $incluirBajas);

    return $consulta->orderBy('matricula')->pluck('matricula')->all();
}

test('HU-02: A ve a J1 y J3, no a J2, J4 ni J5 (fila 1)', function () {
    $e = alcanceEscenario();

    expect(alcanceMatriculas($e['A'], ['entrenador']))->toBe(['J1', 'J3']);
});

test('HU-02: J3 está en el alcance de A y sus membresías se ven con la misma restricción (fila 2)', function () {
    $e = alcanceEscenario();
    $consulta = app(AlcanceDeJugadores::class)->restringir(alcanceActual($e['A'], ['entrenador']), DB::table('jugador_equipo'), 'jugador_equipo.jugador_id');

    // El alcance restringe jugadores, no filas: el arreglo `equipos` con solo los equipos de A es de T13.
    expect($consulta->where('jugador_id', $e['J3'])->pluck('equipo_id')->all())->toContain($e['EA']);
});

test('RNF-01: A no ve a J2, que solo está en el equipo de B (fila 3)', function () {
    $e = alcanceEscenario();

    expect(alcanceMatriculas($e['A'], ['entrenador'], incluirBajas: true))->not->toContain('J2');
});

test('RNF-01: A no ve a J4, que no tiene equipo (fila 4)', function () {
    $e = alcanceEscenario();

    expect(alcanceMatriculas($e['A'], ['entrenador'], incluirBajas: true))->not->toContain('J4');
});

test('RNF-01: B no ve a J1 (fila 5)', function () {
    $e = alcanceEscenario();

    expect(alcanceMatriculas($e['B'], ['entrenador']))->toBe(['J2', 'J3']);
});

test('RN-23: con incluirBajas A ve a J5 y sin él no (fila 6)', function () {
    $e = alcanceEscenario();

    expect(alcanceMatriculas($e['A'], ['entrenador'], incluirBajas: true))->toBe(['J1', 'J3', 'J5'])
        ->and(alcanceMatriculas($e['A'], ['entrenador'], incluirBajas: false))->toBe(['J1', 'J3']);
});

test('RNF-01: filtrar por el equipo de B no amplía el alcance de A (fila 7)', function () {
    $e = alcanceEscenario();
    $consulta = app(AlcanceDeJugadores::class)->restringir(alcanceActual($e['A'], ['entrenador']), DB::table('jugadores'), 'jugadores.id');
    $consulta->join('jugador_equipo as m', 'm.jugador_id', '=', 'jugadores.id')->where('m.equipo_id', $e['EB']);

    // J2 solo está en EB y no aparece. Que J3 tampoco aparezca al filtrar es de T13 (el filtro se cruza con los equipos del usuario).
    expect($consulta->pluck('matricula')->all())->not->toContain('J2');
});

test('RNF-01: un docente no ve ningún jugador (fila 8)', function () {
    $e = alcanceEscenario();

    expect(alcanceMatriculas($e['D'], ['docente'], incluirBajas: true))->toBe([]);
});

test('RNF-01: un docente no ve a J1 (fila 9)', function () {
    $e = alcanceEscenario();

    expect(alcanceMatriculas($e['D'], ['docente']))->not->toContain('J1');
});

test('RNF-01: un usuario con los dos roles y sin equipos no ve jugadores', function () {
    $e = alcanceEscenario();

    expect(alcanceMatriculas($e['AD'], ['docente', 'entrenador']))->toBe([]);
});

test('RNF-01: sin usuario no se ve ningún jugador', function () {
    alcanceEscenario();

    expect(alcanceMatriculas(null, []))->toBe([]);
});

test('RNF-01: sin el rol de entrenador una asignación vigente no da alcance', function () {
    $e = alcanceEscenario();

    expect(alcanceMatriculas($e['A'], ['docente']))->toBe([]);
});

test('RN-17: una asignación de entrenador cerrada no da alcance', function () {
    $e = alcanceEscenario();
    DB::connection('propietario')->table('entrenador_equipo')->where('usuario_id', $e['A'])->update(['hasta' => '2026-02-01']);

    expect(alcanceMatriculas($e['A'], ['entrenador']))->toBe([]);
});

test('RN-17: una membresía cerrada no da alcance', function () {
    $e = alcanceEscenario();
    DB::connection('propietario')->table('jugador_equipo')->where('jugador_id', $e['J1'])->update(['hasta' => '2026-02-01']);

    expect(alcanceMatriculas($e['A'], ['entrenador']))->toBe(['J3']);
});

test('RNF-02: una asignación o una membresía con borrado lógico no da alcance', function () {
    $e = alcanceEscenario();
    DB::connection('propietario')->table('jugador_equipo')->where('jugador_id', $e['J1'])->update(['eliminado_en' => '2026-03-01 10:00:00+00']);
    DB::connection('propietario')->table('entrenador_equipo')->where('usuario_id', $e['B'])->update(['eliminado_en' => '2026-03-01 10:00:00+00']);

    expect(alcanceMatriculas($e['A'], ['entrenador']))->toBe(['J3'])
        ->and(alcanceMatriculas($e['B'], ['entrenador']))->toBe([]);
});

test('RNF-01: la restricción también se aplica a una consulta de Eloquent y devuelve la misma consulta', function () {
    $e = alcanceEscenario();
    $modelo = new class extends Model
    {
        protected $table = 'jugadores';

        protected $keyType = 'string';

        public $incrementing = false;
    };
    $consulta = $modelo::query();

    $devuelta = app(AlcanceDeJugadores::class)->restringir(alcanceActual($e['A'], ['entrenador']), $consulta, 'jugadores.id');

    expect($devuelta)->toBe($consulta)->toBeInstanceOf(ConsultaEloquent::class)
        ->and($devuelta->orderBy('matricula')->pluck('matricula')->all())->toBe(['J1', 'J3']);
});

test('RNF-01: la restricción no pierde los filtros que la consulta ya tenía', function () {
    $e = alcanceEscenario();
    $consulta = app(AlcanceDeJugadores::class)
        ->restringir(alcanceActual($e['A'], ['entrenador']), DB::table('jugadores')->where('matricula', 'J2')->orWhere('matricula', 'J3'), 'jugadores.id');

    expect($consulta->pluck('matricula')->all())->toBe(['J3']);
});
