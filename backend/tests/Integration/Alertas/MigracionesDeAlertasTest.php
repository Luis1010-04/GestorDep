<?php

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ParametrosInstitucionalesSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Src\Compartido\Dominio\Identificador;

beforeEach(function () {
    esquemaReiniciar();
});

/** @param  array<string, mixed>  $atributos */
function alertasInsertar(string $tabla, array $atributos): string
{
    $id = Identificador::generar()->valor();
    DB::connection('propietario')->table($tabla)->insert(['id' => $id, ...$atributos]);

    return $id;
}

function alertasRechazo(callable $operacion): ?string
{
    try {
        $operacion();
    } catch (QueryException $e) {
        return (string) $e->getCode();
    }

    return null;
}

function alertasUnico(): string
{
    return substr(Identificador::generar()->valor(), -12);
}

function alertasJugador(): string
{
    return alertasInsertar('jugadores', [
        'nombres' => 'Jugador', 'apellidos' => 'De Prueba', 'fecha_nacimiento' => '2009-05-10', 'matricula' => 'M-'.alertasUnico(),
    ]);
}

function alertasEquipo(): string
{
    $categoria = alertasInsertar('categorias', ['nombre' => 'Categoría '.alertasUnico()]);

    return alertasInsertar('equipos', ['nombre' => 'Equipo '.alertasUnico(), 'categoria_id' => $categoria]);
}

/**
 * @param  array<string, mixed>  $atributos
 * @return array<string, mixed>
 */
function alertasDatos(array $atributos = []): array
{
    return [
        'tipo' => 'riesgo_baja_deportiva', 'jugador_id' => alertasJugador(), 'equipo_id' => alertasEquipo(),
        'causa' => json_encode(['resultado' => 'no_pasa']), 'activada_en' => '2026-11-15 08:00:00+00', 'cerrada_en' => null, ...$atributos,
    ];
}

/**
 * Valor vigente de un parámetro, con las claves en orden alfabético: `jsonb` no conserva el orden en que se escribieron.
 *
 * @return array<string, mixed>
 */
function alertasParametro(string $clave): array
{
    /** @var array<string, mixed> $valor */
    $valor = json_decode((string) DB::connection('propietario')->table('parametros_institucionales')
        ->where('clave', $clave)->whereNull('vigente_hasta')->value('valor'), true, flags: JSON_THROW_ON_ERROR);
    ksort($valor);

    return $valor;
}

test('HU-22 (DA-87): el tipo de una alerta solo admite las tres alertas de la Fase 1', function () {
    foreach (['riesgo_baja_academica', 'riesgo_baja_deportiva', 'acumulacion_sanciones_deportivas'] as $tipo) {
        expect(alertasRechazo(fn () => alertasInsertar('alertas', alertasDatos(['tipo' => $tipo]))))->toBeNull($tipo);
    }

    expect(alertasRechazo(fn () => alertasInsertar('alertas', alertasDatos(['tipo' => 'bajo_promedio']))))->toBe('23514');
});

test('HU-22 (DA-87): la base rechaza una segunda alerta abierta del mismo tipo, jugador y equipo', function () {
    $datos = alertasDatos();
    alertasInsertar('alertas', $datos);

    expect(alertasRechazo(fn () => alertasInsertar('alertas', $datos)))->toBe('23505')
        ->and(alertasRechazo(fn () => alertasInsertar('alertas', [...$datos, 'tipo' => 'acumulacion_sanciones_deportivas'])))->toBeNull()
        ->and(alertasRechazo(fn () => alertasInsertar('alertas', [...$datos, 'equipo_id' => alertasEquipo()])))->toBeNull()
        ->and(alertasRechazo(fn () => alertasInsertar('alertas', [...$datos, 'jugador_id' => alertasJugador()])))->toBeNull();
});

test('HU-22 (DA-87): la alerta académica no tiene equipo y tampoco admite una segunda abierta del mismo jugador', function () {
    $datos = alertasDatos(['tipo' => 'riesgo_baja_academica', 'equipo_id' => null]);
    alertasInsertar('alertas', $datos);

    expect(alertasRechazo(fn () => alertasInsertar('alertas', $datos)))->toBe('23505')
        ->and(alertasRechazo(fn () => alertasInsertar('alertas', [...$datos, 'jugador_id' => alertasJugador()])))->toBeNull();
});

test('HU-22 (DA-87): una alerta cerrada no ocupa el lugar de la abierta: si la condición vuelve, se crea otra', function () {
    $datos = alertasDatos();
    $primera = alertasInsertar('alertas', $datos);
    DB::connection('propietario')->table('alertas')->where('id', $primera)->update(['cerrada_en' => '2026-11-16 08:00:00+00']);

    expect(alertasRechazo(fn () => alertasInsertar('alertas', [...$datos, 'activada_en' => '2026-11-17 08:00:00+00'])))->toBeNull()
        ->and(DB::connection('propietario')->table('alertas')->where('jugador_id', $datos['jugador_id'])->count())->toBe(2);
});

test('HU-22 (DA-87): la única modificación permitida es cerrar la alerta una vez, también para la aplicación', function () {
    $porPropietario = alertasInsertar('alertas', alertasDatos());
    $porAplicacion = alertasInsertar('alertas', alertasDatos());

    expect(alertasRechazo(fn () => DB::connection('propietario')->table('alertas')->where('id', $porPropietario)->update(['cerrada_en' => '2026-11-16 08:00:00+00'])))->toBeNull()
        ->and(alertasRechazo(fn () => DB::table('alertas')->where('id', $porAplicacion)->update(['cerrada_en' => '2026-11-16 08:00:00+00'])))->toBeNull()
        ->and(DB::connection('propietario')->table('alertas')->whereNotNull('cerrada_en')->count())->toBe(2);
});

test('HU-22 (DA-87): el disparador rechaza cualquier otro UPDATE de una alerta', function (array $cambio) {
    $abierta = alertasInsertar('alertas', alertasDatos());
    $cambio = array_map(fn (mixed $valor): mixed => $valor instanceof Closure ? $valor() : $valor, $cambio);

    expect(alertasRechazo(fn () => DB::connection('propietario')->table('alertas')->where('id', $abierta)->update($cambio)))->toBe('P0001')
        ->and(alertasRechazo(fn () => DB::table('alertas')->where('id', $abierta)->update($cambio)))->toBe('P0001')
        ->and(DB::connection('propietario')->table('alertas')->where('id', $abierta)->whereNull('cerrada_en')->where('tipo', 'riesgo_baja_deportiva')->count())->toBe(1);
})->with([
    'la causa' => [['causa' => '{"resultado": "pasa"}']],
    'el tipo' => [['tipo' => 'acumulacion_sanciones_deportivas']],
    'la fecha de activación' => [['activada_en' => '2026-11-01 08:00:00+00']],
    'el jugador' => [['jugador_id' => fn () => alertasJugador()]],
    'el equipo' => [['equipo_id' => fn () => alertasEquipo()]],
    'cerrar y cambiar la causa a la vez' => [['cerrada_en' => '2026-11-16 08:00:00+00', 'causa' => '{"resultado": "pasa"}']],
]);

test('HU-22 (DA-87): una alerta cerrada no se reabre ni cambia su fecha de cierre', function () {
    $cerrada = alertasInsertar('alertas', alertasDatos());
    DB::connection('propietario')->table('alertas')->where('id', $cerrada)->update(['cerrada_en' => '2026-11-16 08:00:00+00']);
    $actualizar = fn (?string $valor) => alertasRechazo(fn () => DB::connection('propietario')->table('alertas')->where('id', $cerrada)->update(['cerrada_en' => $valor]));

    expect($actualizar(null))->toBe('P0001')
        ->and($actualizar('2026-11-17 08:00:00+00'))->toBe('P0001')
        ->and(DB::connection('propietario')->table('alertas')->where('id', $cerrada)->where('cerrada_en', '2026-11-16 08:00:00+00')->count())->toBe(1);
});

test('HU-22 (DA-87): el disparador rechaza todo DELETE de una alerta, abierta o cerrada, para la aplicación y para el propietario', function () {
    $abierta = alertasInsertar('alertas', alertasDatos());
    $cerrada = alertasInsertar('alertas', alertasDatos());
    DB::connection('propietario')->table('alertas')->where('id', $cerrada)->update(['cerrada_en' => '2026-11-16 08:00:00+00']);

    foreach ([$abierta, $cerrada] as $id) {
        expect(alertasRechazo(fn () => DB::table('alertas')->where('id', $id)->delete()))->toBe('P0001')
            ->and(alertasRechazo(fn () => DB::connection('propietario')->table('alertas')->where('id', $id)->delete()))->toBe('P0001');
    }

    expect(DB::connection('propietario')->table('alertas')->count())->toBe(2);
});

test('HU-22 (DA-87): la alerta exige jugador, causa y fecha de activación, y sus claves foráneas no borran en cascada', function () {
    $datos = alertasDatos();
    alertasInsertar('alertas', $datos);
    $borrar = fn (string $tabla, mixed $id) => alertasRechazo(fn () => DB::connection('propietario')->table($tabla)->where('id', $id)->delete());

    expect(alertasRechazo(fn () => alertasInsertar('alertas', alertasDatos(['jugador_id' => null]))))->toBe('23502')
        ->and(alertasRechazo(fn () => alertasInsertar('alertas', alertasDatos(['causa' => null]))))->toBe('23502')
        ->and(alertasRechazo(fn () => alertasInsertar('alertas', alertasDatos(['activada_en' => null]))))->toBe('23502')
        ->and(alertasRechazo(fn () => alertasInsertar('alertas', alertasDatos(['jugador_id' => Identificador::generar()->valor()]))))->toBe('23503')
        ->and(alertasRechazo(fn () => alertasInsertar('alertas', alertasDatos(['equipo_id' => Identificador::generar()->valor()]))))->toBe('23503')
        ->and($borrar('jugadores', $datos['jugador_id']))->toBe('23503')
        ->and($borrar('equipos', $datos['equipo_id']))->toBe('23503');
});

test('HU-22 (DA-87): los índices de §5.1 existen, y la tabla no tiene historial ni columnas de auditoría', function () {
    $indices = collect(DB::connection('propietario')->select("select indexname, indexdef from pg_indexes where schemaname = 'public' and tablename = 'alertas'"))
        ->pluck('indexdef', 'indexname');

    expect($indices['uq_alertas_abierta'])->toContain('UNIQUE')->toContain('COALESCE(equipo_id')->toContain('WHERE (cerrada_en IS NULL)')
        ->and($indices['ix_alertas_jugador_cerrada_en'])->toContain('(jugador_id, cerrada_en)')
        ->and(Schema::connection('propietario')->hasTable('alertas_historial'))->toBeFalse()
        ->and(Schema::connection('propietario')->getColumnListing('alertas'))->toEqualCanonicalizing(['id', 'tipo', 'jugador_id', 'equipo_id', 'causa', 'activada_en', 'cerrada_en']);
});

test('HU-22 (DA-87): migrate:rollback de Alertas no falla, quita la tabla y su función, y se vuelve a aplicar', function () {
    esquemaMarcarSucio();
    $funcion = fn (): bool => DB::connection('propietario')->table('pg_proc')->where('proname', 'rechazar_modificacion_alerta')->exists();

    consolaArtisan('migrate:rollback', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    expect(Schema::connection('propietario')->hasTable('alertas'))->toBeFalse()
        ->and($funcion())->toBeFalse();

    consolaArtisan('migrate', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    expect(Schema::connection('propietario')->hasTable('alertas'))->toBeTrue()
        ->and($funcion())->toBeTrue();
});

test('RNF-09 (DA-100): ParametrosInstitucionalesSeeder siembra los tres parámetros de H6 con sus valores de fábrica', function () {
    (new ParametrosInstitucionalesSeeder)->run();

    // [Propuesta] por validar con la institución (AB-13): sanciones y días de desactualización.
    expect(alertasParametro('sanciones_deportivas'))->toBe(['faltas_umbral' => 3, 'faltas_ventana_dias' => 30, 'tarjetas_amarillas' => 3, 'tarjetas_rojas' => 1])
        ->and(alertasParametro('alerta_academica'))->toBe(['nivel' => 'en_riesgo'])
        ->and(alertasParametro('desactualizacion'))->toBe(['dias' => 30])
        ->and(DB::connection('propietario')->table('parametros_institucionales')->whereNull('vigente_hasta')->orderBy('clave')->pluck('clave')->all())
        ->toBe(['alerta_academica', 'desactualizacion', 'riesgo_academico', 'sanciones_deportivas']);
});

test('RNF-09 (DA-100): el seeder es idempotente y no escribe historial', function () {
    (new ParametrosInstitucionalesSeeder)->run();
    $ids = DB::connection('propietario')->table('parametros_institucionales')->orderBy('clave')->pluck('id', 'clave')->all();

    (new ParametrosInstitucionalesSeeder)->run();

    expect(DB::connection('propietario')->table('parametros_institucionales')->orderBy('clave')->pluck('id', 'clave')->all())->toBe($ids)
        ->and($ids)->toHaveCount(4)
        ->and(DB::connection('propietario')->table('parametros_institucionales_historial')->count())->toBe(0);
});

test('RNF-09 (DA-100): el seeder no sobrescribe una clave vigente ya ajustada y siembra las que falten', function (string $clave) {
    alertasInsertar('parametros_institucionales', [
        'clave' => $clave, 'valor' => json_encode(['ajustado' => true]), 'descripcion' => 'Ajustado', 'vigente_desde' => '2026-02-01',
    ]);

    (new ParametrosInstitucionalesSeeder)->run();

    expect(alertasParametro($clave))->toBe(['ajustado' => true])
        ->and(DB::connection('propietario')->table('parametros_institucionales')->where('clave', $clave)->count())->toBe(1)
        ->and(DB::connection('propietario')->table('parametros_institucionales')->whereNull('vigente_hasta')->count())->toBe(4);
})->with(['riesgo_academico', 'sanciones_deportivas', 'alerta_academica', 'desactualizacion']);

test('RNF-09 (DA-100): DatabaseSeeder siembra los parámetros de H6 y repetirlo no duplica nada', function () {
    (new DatabaseSeeder)->run();
    (new DatabaseSeeder)->run();

    expect(DB::connection('propietario')->table('parametros_institucionales')->count())->toBe(4)
        ->and(alertasParametro('desactualizacion'))->toBe(['dias' => 30]);
});
