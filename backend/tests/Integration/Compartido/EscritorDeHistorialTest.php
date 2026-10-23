<?php

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\Compartido\Infraestructura\RelojFijo;

/*
 * Las tablas de estas pruebas se crean y confirman con el rol propietario, para que la conexion
 * del rol de aplicacion ('pgsql') las vea; se eliminan al terminar cada prueba.
 */
const USUARIO_DE_PRUEBA = '01920000-0000-7000-8000-000000000001';
const MOMENTO_DE_PRUEBA = '2026-03-15 10:30:00';

/** Recuerda si la prueba creo la tabla usuarios, para eliminarla solo en ese caso. */
function usuariosCreadosPorLaPrueba(?bool $creados = null): bool
{
    static $valor = false;
    if ($creados !== null) {
        $valor = $creados;
    }

    return $valor;
}

function usuarioDePrueba(): Identificador
{
    return Identificador::desdeTexto(USUARIO_DE_PRUEBA);
}

function momentoDePrueba(): DateTimeImmutable
{
    return new DateTimeImmutable(MOMENTO_DE_PRUEBA, new DateTimeZone('UTC'));
}

function escritorDeHistorial(): EscritorDeHistorial
{
    $usuario = new class implements UsuarioActual
    {
        public function identificador(): Identificador
        {
            return usuarioDePrueba();
        }

        public function roles(): array
        {
            return [];
        }
    };

    return new EscritorDeHistorial(DB::connection(), new RelojFijo(momentoDePrueba()), $usuario);
}

beforeEach(function () {
    $esquema = Schema::connection('propietario');
    $esquema->dropIfExists('prueba_registros_historial');
    $esquema->dropIfExists('prueba_registros');

    usuariosCreadosPorLaPrueba(! $esquema->hasTable('usuarios'));
    if (usuariosCreadosPorLaPrueba()) {
        $esquema->create('usuarios', function (Blueprint $t) {
            $t->uuid('id')->primary();
        });
    }

    $esquema->create('prueba_registros', function (Blueprint $t) {
        $t->uuid('id')->primary();
        $t->string('nombre', 50);
    });
    $esquema->historial('prueba_registros', 'prueba_registro_id');

    DB::connection('propietario')->table('usuarios')->insert([
        'id' => USUARIO_DE_PRUEBA,
        ...(usuariosCreadosPorLaPrueba() ? [] : [
            'nombre' => 'Usuario de prueba',
            'correo' => 'historial@example.test',
            'contrasena_hash' => 'hash-de-prueba',
        ]),
    ]);
});

afterEach(function () {
    $esquema = Schema::connection('propietario');
    $esquema->dropIfExists('prueba_registros_historial');
    $esquema->dropIfExists('prueba_registros');

    if (usuariosCreadosPorLaPrueba()) {
        $esquema->dropIfExists('usuarios');
    } else {
        DB::connection('propietario')->table('usuarios')->where('id', USUARIO_DE_PRUEBA)->delete();
    }
});

/** Ejecuta la operacion y devuelve el SQLSTATE con el que la base de datos la rechazo. */
function sqlstateDelRechazo(callable $operacion): ?string
{
    try {
        $operacion();
    } catch (QueryException $e) {
        return (string) $e->getCode();
    }

    return null;
}

function insertarHistorialComoPropietario(Identificador $entidadId, Identificador $usuarioId): void
{
    DB::connection('propietario')->table('prueba_registros')->insert(['id' => $entidadId->valor(), 'nombre' => 'inicial']);
    DB::connection('propietario')->table('prueba_registros_historial')->insert([
        'id' => Identificador::generar()->valor(),
        'prueba_registro_id' => $entidadId->valor(),
        'version' => 1,
        'operacion' => 'crear',
        'datos_nuevos' => json_encode(['nombre' => 'inicial']),
        'usuario_id' => $usuarioId->valor(),
        'ocurrido_en' => '2026-03-15 10:30:00+00',
    ]);
}

test('F1-H0-T08: un cambio genera su registro de historial con version consecutiva', function () {
    $id = Identificador::generar();

    escritorDeHistorial()->registrar('prueba_registros', 'prueba_registro_id', $id, 'crear', null, ['nombre' => 'inicial'],
        fn () => DB::table('prueba_registros')->insert(['id' => $id->valor(), 'nombre' => 'inicial']));
    escritorDeHistorial()->registrar('prueba_registros', 'prueba_registro_id', $id, 'actualizar', ['nombre' => 'inicial'], ['nombre' => 'cambiado'],
        fn () => DB::table('prueba_registros')->where('id', $id->valor())->update(['nombre' => 'cambiado']),
        'correccion');

    $historial = DB::table('prueba_registros_historial')
        ->where('prueba_registro_id', $id->valor())->orderBy('version')->get();

    expect(DB::table('prueba_registros')->where('id', $id->valor())->value('nombre'))->toBe('cambiado')
        ->and($historial)->toHaveCount(2)
        ->and($historial[0]->version)->toBe(1)
        ->and($historial[0]->operacion)->toBe('crear')
        ->and($historial[0]->datos_anteriores)->toBeNull()
        ->and(json_decode($historial[0]->datos_nuevos, true))->toBe(['nombre' => 'inicial'])
        ->and($historial[0]->motivo)->toBeNull()
        ->and($historial[1]->version)->toBe(2)
        ->and($historial[1]->operacion)->toBe('actualizar')
        ->and(json_decode($historial[1]->datos_anteriores, true))->toBe(['nombre' => 'inicial'])
        ->and(json_decode($historial[1]->datos_nuevos, true))->toBe(['nombre' => 'cambiado'])
        ->and($historial[1]->motivo)->toBe('correccion');

    foreach ($historial as $registro) {
        expect(Identificador::desdeTexto($registro->id)->valor())->toBe($registro->id)
            ->and($registro->usuario_id)->toBe(USUARIO_DE_PRUEBA)
            ->and(new DateTimeImmutable($registro->ocurrido_en))->toEqual(momentoDePrueba());
    }
});

test('F1-H0-T08: la version es consecutiva por entidad', function () {
    $primera = Identificador::generar();
    $segunda = Identificador::generar();

    foreach ([$primera, $segunda] as $id) {
        escritorDeHistorial()->registrar('prueba_registros', 'prueba_registro_id', $id, 'crear', null, ['nombre' => 'inicial'],
            fn () => DB::table('prueba_registros')->insert(['id' => $id->valor(), 'nombre' => 'inicial']));
    }

    expect(DB::table('prueba_registros_historial')->where('prueba_registro_id', $segunda->valor())->value('version'))->toBe(1);
});

test('F1-H0-T08: si el cambio falla no queda ni el dato ni el historial', function () {
    $id = Identificador::generar();

    expect(fn () => escritorDeHistorial()->registrar('prueba_registros', 'prueba_registro_id', $id, 'crear', null, ['nombre' => 'inicial'],
        function () use ($id) {
            DB::table('prueba_registros')->insert(['id' => $id->valor(), 'nombre' => 'inicial']);
            throw new RuntimeException('fallo_del_cambio');
        }))->toThrow(RuntimeException::class, 'fallo_del_cambio');

    expect(DB::table('prueba_registros')->count())->toBe(0)
        ->and(DB::table('prueba_registros_historial')->count())->toBe(0);
});

test('F1-H0-T08: si el historial falla no queda ni el dato ni el historial', function () {
    $id = Identificador::generar();

    // 'renombrar' no es una operacion permitida: el CHECK del historial rechaza la insercion.
    expect(fn () => escritorDeHistorial()->registrar('prueba_registros', 'prueba_registro_id', $id, 'renombrar', null, ['nombre' => 'inicial'],
        fn () => DB::table('prueba_registros')->insert(['id' => $id->valor(), 'nombre' => 'inicial'])))
        ->toThrow(QueryException::class);

    expect(DB::table('prueba_registros')->count())->toBe(0)
        ->and(DB::table('prueba_registros_historial')->count())->toBe(0);
});

test('F1-H0-T08: el disparador rechaza UPDATE en el historial', function () {
    insertarHistorialComoPropietario(Identificador::generar(), usuarioDePrueba());

    // El propietario si tiene permiso de UPDATE: el rechazo viene del disparador (raise_exception).
    expect(sqlstateDelRechazo(fn () => DB::connection('propietario')->table('prueba_registros_historial')->update(['motivo' => 'alterado'])))
        ->toBe('P0001');
});

test('F1-H0-T08: el disparador rechaza DELETE en el historial', function () {
    insertarHistorialComoPropietario(Identificador::generar(), usuarioDePrueba());

    expect(sqlstateDelRechazo(fn () => DB::connection('propietario')->table('prueba_registros_historial')->delete()))
        ->toBe('P0001')
        ->and(DB::connection('propietario')->table('prueba_registros_historial')->count())->toBe(1);
});

test('F1-H0-T08: el rol de aplicacion no tiene permiso de UPDATE en el historial', function () {
    insertarHistorialComoPropietario(Identificador::generar(), usuarioDePrueba());

    // insufficient_privilege: la base de datos revisa el permiso antes de ejecutar el disparador.
    expect(sqlstateDelRechazo(fn () => DB::table('prueba_registros_historial')->update(['motivo' => 'alterado'])))
        ->toBe('42501');
});

test('F1-H0-T08: el rol de aplicacion no tiene permiso de DELETE en el historial', function () {
    insertarHistorialComoPropietario(Identificador::generar(), usuarioDePrueba());

    expect(sqlstateDelRechazo(fn () => DB::table('prueba_registros_historial')->delete()))
        ->toBe('42501');
});

test('F1-H1-T04: sin usuario actual el historial se escribe con usuario_id nulo', function () {
    $sinUsuario = new class implements UsuarioActual
    {
        public function identificador(): ?Identificador
        {
            return null;
        }

        public function roles(): array
        {
            return [];
        }
    };
    $escritor = new EscritorDeHistorial(DB::connection(), new RelojFijo(momentoDePrueba()), $sinUsuario);
    $id = Identificador::generar();

    $escritor->registrar('prueba_registros', 'prueba_registro_id', $id, 'crear', null, ['nombre' => 'inicial'],
        fn () => DB::table('prueba_registros')->insert(['id' => $id->valor(), 'nombre' => 'inicial']));

    $registro = DB::table('prueba_registros_historial')->where('prueba_registro_id', $id->valor())->first();

    expect($registro->usuario_id)->toBeNull()
        ->and($registro->operacion)->toBe('crear');
});
