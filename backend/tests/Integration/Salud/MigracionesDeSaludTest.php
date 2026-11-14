<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Src\Compartido\Dominio\Identificador;

const TABLAS_DE_SALUD = ['lesiones', 'avisos_lesion', 'estados_disponibilidad'];

const HISTORIALES_DE_SALUD = [
    'lesiones_historial' => 'lesion_id',
    'avisos_lesion_historial' => 'aviso_id',
    'estados_disponibilidad_historial' => 'estado_id',
];

beforeEach(function () {
    esquemaReiniciar();
});

/** @param  array<string, mixed>  $atributos */
function saludInsertar(string $tabla, array $atributos): string
{
    $id = Identificador::generar()->valor();
    DB::connection('propietario')->table($tabla)->insert(['id' => $id, ...$atributos]);

    return $id;
}

function saludRechazo(callable $operacion): ?string
{
    try {
        $operacion();
    } catch (QueryException $e) {
        return (string) $e->getCode();
    }

    return null;
}

function saludUnico(): string
{
    return substr(Identificador::generar()->valor(), -12);
}

function saludUsuario(): string
{
    return saludInsertar('usuarios', [
        'nombre' => 'Entrenador de prueba', 'correo' => saludUnico().'@example.test', 'contrasena_hash' => 'hash-de-prueba',
    ]);
}

function saludJugador(): string
{
    return saludInsertar('jugadores', [
        'nombres' => 'Jugador', 'apellidos' => 'De Prueba', 'fecha_nacimiento' => '2009-05-10', 'matricula' => 'M-'.saludUnico(),
    ]);
}

/**
 * @param  array<string, mixed>  $atributos
 * @return array<string, mixed>
 */
function saludDatosDeLesion(array $atributos = []): array
{
    return [
        'jugador_id' => saludJugador(), 'registrada_por' => saludUsuario(), 'tipo' => 'Esguince', 'zona' => 'Tobillo', 'causa' => 'partido',
        'fecha_inicio' => '2026-11-10', 'fecha_estimada_regreso' => '2026-11-30', 'fecha_alta' => null, 'nota' => null, ...$atributos,
    ];
}

function saludLesion(): string
{
    return saludInsertar('lesiones', saludDatosDeLesion());
}

/**
 * @param  array<string, mixed>  $atributos
 * @return array<string, mixed>
 */
function saludDatosDeAviso(array $atributos = []): array
{
    return [
        'jugador_id' => saludJugador(), 'creado_por' => saludUsuario(), 'nota' => 'Molestia en el tobillo', 'estado' => 'pendiente',
        'fecha_aviso' => '2026-11-10', ...$atributos,
    ];
}

/**
 * @param  array<string, mixed>  $atributos
 * @return array<string, mixed>
 */
function saludDatosDeEstado(array $atributos = []): array
{
    return [
        'jugador_id' => saludJugador(), 'estado' => 'apto', 'lesion_id' => null, 'fecha_estimada_regreso' => null,
        'confirmado_por' => saludUsuario(), 'vigente_desde' => '2026-11-10 10:30:00+00', 'vigente_hasta' => null, ...$atributos,
    ];
}

test('RNF-03: la causa de una lesión solo admite entrenamiento, partido u otra', function () {
    foreach (['entrenamiento', 'partido', 'otra'] as $causa) {
        expect(saludRechazo(fn () => saludInsertar('lesiones', saludDatosDeLesion(['causa' => $causa]))))->toBeNull($causa);
    }

    expect(saludRechazo(fn () => saludInsertar('lesiones', saludDatosDeLesion(['causa' => 'desconocida']))))->toBe('23514');
});

test('RNF-03 (DA-17): la fecha estimada de regreso de la lesión no puede ser anterior a su inicio', function () {
    expect(saludRechazo(fn () => saludInsertar('lesiones', saludDatosDeLesion(['fecha_estimada_regreso' => '2026-11-09']))))->toBe('23514')
        ->and(saludRechazo(fn () => saludInsertar('lesiones', saludDatosDeLesion(['fecha_estimada_regreso' => '2026-11-10']))))->toBeNull()
        ->and(saludRechazo(fn () => saludInsertar('lesiones', saludDatosDeLesion(['fecha_estimada_regreso' => null]))))->toBe('23502');
});

test('RNF-03 (DA-17): la fecha de alta es opcional y no puede ser anterior al inicio', function () {
    expect(saludRechazo(fn () => saludInsertar('lesiones', saludDatosDeLesion(['fecha_alta' => '2026-11-09']))))->toBe('23514')
        ->and(saludRechazo(fn () => saludInsertar('lesiones', saludDatosDeLesion(['fecha_alta' => '2026-11-10']))))->toBeNull()
        ->and(saludRechazo(fn () => saludInsertar('lesiones', saludDatosDeLesion(['fecha_alta' => null]))))->toBeNull();
});

test('RNF-03: tipo y zona admiten hasta 100 caracteres y la nota de la lesión hasta 500', function () {
    expect(saludRechazo(fn () => saludInsertar('lesiones', saludDatosDeLesion(['tipo' => str_repeat('a', 100), 'zona' => str_repeat('a', 100), 'nota' => str_repeat('a', 500)]))))->toBeNull()
        ->and(saludRechazo(fn () => saludInsertar('lesiones', saludDatosDeLesion(['tipo' => str_repeat('a', 101)]))))->toBe('22001')
        ->and(saludRechazo(fn () => saludInsertar('lesiones', saludDatosDeLesion(['zona' => str_repeat('a', 101)]))))->toBe('22001')
        ->and(saludRechazo(fn () => saludInsertar('lesiones', saludDatosDeLesion(['nota' => str_repeat('a', 501)]))))->toBe('22001');
});

test('RNF-03: el estado de un aviso solo admite pendiente, confirmado o descartado, y reportado_por_jugador es falso por defecto', function () {
    $id = saludInsertar('avisos_lesion', saludDatosDeAviso());

    expect(DB::connection('propietario')->table('avisos_lesion')->where('id', $id)->value('reportado_por_jugador'))->toBeFalse()
        ->and(saludRechazo(fn () => saludInsertar('avisos_lesion', saludDatosDeAviso(['estado' => 'cerrado']))))->toBe('23514');
});

test('RNF-03 (DD-58): creado_por es obligatorio en avisos_lesion y las demás tablas conservan el macro sin cambios', function () {
    $nulabilidad = collect(DB::connection('propietario')->select(
        "select table_name, is_nullable from information_schema.columns where column_name = 'creado_por' and table_name in ('avisos_lesion', 'lesiones', 'estados_disponibilidad')",
    ))->pluck('is_nullable', 'table_name');

    expect(saludRechazo(fn () => saludInsertar('avisos_lesion', saludDatosDeAviso(['creado_por' => null]))))->toBe('23502')
        ->and($nulabilidad->all())->toEqualCanonicalizing(['avisos_lesion' => 'NO', 'lesiones' => 'YES', 'estados_disponibilidad' => 'YES']);
});

test('RNF-03: la nota del aviso es obligatoria y admite hasta 500 caracteres, igual que la nota del descarte', function () {
    $descarte = fn (string $nota) => saludDatosDeAviso(['estado' => 'descartado', 'descartado_por' => saludUsuario(), 'nota_descarte' => $nota]);

    expect(saludRechazo(fn () => saludInsertar('avisos_lesion', saludDatosDeAviso(['nota' => null]))))->toBe('23502')
        ->and(saludRechazo(fn () => saludInsertar('avisos_lesion', saludDatosDeAviso(['nota' => str_repeat('a', 500)]))))->toBeNull()
        ->and(saludRechazo(fn () => saludInsertar('avisos_lesion', saludDatosDeAviso(['nota' => str_repeat('a', 501)]))))->toBe('22001')
        ->and(saludRechazo(fn () => saludInsertar('avisos_lesion', $descarte(str_repeat('a', 500)))))->toBeNull()
        ->and(saludRechazo(fn () => saludInsertar('avisos_lesion', $descarte(str_repeat('a', 501)))))->toBe('22001');
});

test('RNF-03 (DA-19): confirmado_por no es nulo si y solo si el aviso está confirmado', function () {
    expect(saludRechazo(fn () => saludInsertar('avisos_lesion', saludDatosDeAviso(['estado' => 'confirmado']))))->toBe('23514')
        ->and(saludRechazo(fn () => saludInsertar('avisos_lesion', saludDatosDeAviso(['confirmado_por' => saludUsuario()]))))->toBe('23514')
        ->and(saludRechazo(fn () => saludInsertar('avisos_lesion', saludDatosDeAviso(['estado' => 'descartado', 'descartado_por' => saludUsuario(), 'nota_descarte' => 'No aplica', 'confirmado_por' => saludUsuario()]))))->toBe('23514')
        ->and(saludRechazo(fn () => saludInsertar('avisos_lesion', saludDatosDeAviso(['estado' => 'confirmado', 'confirmado_por' => saludUsuario()]))))->toBeNull();
});

test('RNF-03 (DA-11): descartado_por y nota_descarte no son nulos si y solo si el aviso está descartado', function (array $atributos, ?string $codigo) {
    expect(saludRechazo(fn () => saludInsertar('avisos_lesion', saludDatosDeAviso($atributos))))->toBe($codigo);
})->with([
    'descartado sin los dos' => [['estado' => 'descartado'], '23514'],
    'descartado sin nota' => [fn () => ['estado' => 'descartado', 'descartado_por' => saludUsuario()], '23514'],
    'descartado sin autor' => [['estado' => 'descartado', 'nota_descarte' => 'No aplica'], '23514'],
    'pendiente con autor del descarte' => [fn () => ['descartado_por' => saludUsuario()], '23514'],
    'pendiente con nota del descarte' => [['nota_descarte' => 'No aplica'], '23514'],
    'confirmado con nota del descarte' => [fn () => ['estado' => 'confirmado', 'confirmado_por' => saludUsuario(), 'nota_descarte' => 'No aplica'], '23514'],
    'descartado con los dos' => [fn () => ['estado' => 'descartado', 'descartado_por' => saludUsuario(), 'nota_descarte' => 'No aplica'], null],
]);

test('RNF-03: lesion_id es nulo salvo en un aviso confirmado, que también puede no tener lesión (apto)', function () {
    $confirmado = fn () => ['estado' => 'confirmado', 'confirmado_por' => saludUsuario()];

    expect(saludRechazo(fn () => saludInsertar('avisos_lesion', saludDatosDeAviso(['lesion_id' => saludLesion()]))))->toBe('23514')
        ->and(saludRechazo(fn () => saludInsertar('avisos_lesion', saludDatosDeAviso(['estado' => 'descartado', 'descartado_por' => saludUsuario(), 'nota_descarte' => 'No aplica', 'lesion_id' => saludLesion()]))))->toBe('23514')
        ->and(saludRechazo(fn () => saludInsertar('avisos_lesion', saludDatosDeAviso([...$confirmado(), 'lesion_id' => saludLesion()]))))->toBeNull()
        ->and(saludRechazo(fn () => saludInsertar('avisos_lesion', saludDatosDeAviso($confirmado()))))->toBeNull();
});

test('RNF-03: el estado oficial solo admite apto, con_molestia o lesionado', function () {
    expect(saludRechazo(fn () => saludInsertar('estados_disponibilidad', saludDatosDeEstado(['estado' => 'baja']))))->toBe('23514');
});

test('RNF-03 (DD-57): lesion_id y fecha_estimada_regreso son nulos si y solo si el estado es apto', function (array $atributos, ?string $codigo) {
    expect(saludRechazo(fn () => saludInsertar('estados_disponibilidad', saludDatosDeEstado($atributos))))->toBe($codigo);
})->with([
    'apto sin los dos' => [['estado' => 'apto'], null],
    'apto con lesión' => [fn () => ['estado' => 'apto', 'lesion_id' => saludLesion()], '23514'],
    'apto con fecha' => [['estado' => 'apto', 'fecha_estimada_regreso' => '2026-11-30'], '23514'],
    'lesionado sin los dos' => [['estado' => 'lesionado'], '23514'],
    'lesionado sin lesión' => [['estado' => 'lesionado', 'fecha_estimada_regreso' => '2026-11-30'], '23514'],
    'lesionado sin fecha' => [fn () => ['estado' => 'lesionado', 'lesion_id' => saludLesion()], '23514'],
    'con molestia sin lesión' => [['estado' => 'con_molestia', 'fecha_estimada_regreso' => '2026-11-30'], '23514'],
    'con molestia sin fecha' => [fn () => ['estado' => 'con_molestia', 'lesion_id' => saludLesion()], '23514'],
    'lesionado con los dos' => [fn () => ['estado' => 'lesionado', 'lesion_id' => saludLesion(), 'fecha_estimada_regreso' => '2026-11-30'], null],
    'con molestia con los dos' => [fn () => ['estado' => 'con_molestia', 'lesion_id' => saludLesion(), 'fecha_estimada_regreso' => '2026-11-30'], null],
]);

test('RNF-03: vigente_hasta debe ser posterior a vigente_desde, y confirmado_por es obligatorio', function () {
    expect(saludRechazo(fn () => saludInsertar('estados_disponibilidad', saludDatosDeEstado(['vigente_hasta' => '2026-11-10 10:30:00+00']))))->toBe('23514')
        ->and(saludRechazo(fn () => saludInsertar('estados_disponibilidad', saludDatosDeEstado(['vigente_hasta' => '2026-11-09 10:30:00+00']))))->toBe('23514')
        ->and(saludRechazo(fn () => saludInsertar('estados_disponibilidad', saludDatosDeEstado(['vigente_hasta' => '2026-11-10 10:30:01+00']))))->toBeNull()
        ->and(saludRechazo(fn () => saludInsertar('estados_disponibilidad', saludDatosDeEstado(['confirmado_por' => null]))))->toBe('23502');
});

test('RNF-03: un jugador tiene un solo estado vigente; cerrarlo permite abrir otro, y otro jugador no choca', function () {
    $datos = saludDatosDeEstado();
    $vigente = saludInsertar('estados_disponibilidad', $datos);

    expect(saludRechazo(fn () => saludInsertar('estados_disponibilidad', $datos)))->toBe('23505')
        ->and(saludRechazo(fn () => saludInsertar('estados_disponibilidad', saludDatosDeEstado())))->toBeNull();

    DB::connection('propietario')->table('estados_disponibilidad')->where('id', $vigente)->update(['vigente_hasta' => '2026-11-11 10:30:00+00']);

    expect(saludRechazo(fn () => saludInsertar('estados_disponibilidad', [...$datos, 'vigente_desde' => '2026-11-11 10:30:00+00'])))->toBeNull();
});

test('RNF-03: los índices de §5.1 existen con sus columnas', function () {
    $indices = collect(DB::connection('propietario')->select(
        "select indexname, indexdef from pg_indexes where schemaname = 'public' and tablename in ('lesiones', 'avisos_lesion', 'estados_disponibilidad')",
    ))->pluck('indexdef', 'indexname');

    expect($indices['ix_lesiones_jugador_registrada_por'])->toContain('(jugador_id, registrada_por)')
        ->and($indices['ix_avisos_lesion_jugador_estado'])->toContain('(jugador_id, estado)')
        ->and($indices['uq_estados_disponibilidad_vigente'])->toContain('(jugador_id) WHERE (vigente_hasta IS NULL)');
});

test('RNF-03: las claves foráneas de Salud no borran en cascada', function () {
    $lesion = saludDatosDeLesion();
    $idLesion = saludInsertar('lesiones', $lesion);
    $aviso = saludDatosDeAviso(['estado' => 'confirmado', 'confirmado_por' => saludUsuario(), 'lesion_id' => saludLesion()]);
    saludInsertar('avisos_lesion', $aviso);
    $descartado = saludDatosDeAviso(['estado' => 'descartado', 'descartado_por' => saludUsuario(), 'nota_descarte' => 'No aplica']);
    saludInsertar('avisos_lesion', $descartado);
    $estado = saludDatosDeEstado(['estado' => 'lesionado', 'lesion_id' => $idLesion, 'fecha_estimada_regreso' => '2026-11-30']);
    saludInsertar('estados_disponibilidad', $estado);
    $borrar = fn (string $tabla, mixed $id) => saludRechazo(fn () => DB::connection('propietario')->table($tabla)->where('id', $id)->delete());

    expect($borrar('jugadores', $lesion['jugador_id']))->toBe('23503')
        ->and($borrar('usuarios', $lesion['registrada_por']))->toBe('23503')
        ->and($borrar('lesiones', $idLesion))->toBe('23503')
        ->and($borrar('lesiones', $aviso['lesion_id']))->toBe('23503')
        ->and($borrar('jugadores', $aviso['jugador_id']))->toBe('23503')
        ->and($borrar('usuarios', $aviso['creado_por']))->toBe('23503')
        ->and($borrar('usuarios', $aviso['confirmado_por']))->toBe('23503')
        ->and($borrar('usuarios', $descartado['descartado_por']))->toBe('23503')
        ->and($borrar('jugadores', $estado['jugador_id']))->toBe('23503')
        ->and($borrar('usuarios', $estado['confirmado_por']))->toBe('23503');
});

test('RNF-02: cada tabla de historial de Salud es de solo inserción, para la aplicación y para el propietario', function () {
    $entidades = [
        'lesiones_historial' => saludLesion(),
        'avisos_lesion_historial' => saludInsertar('avisos_lesion', saludDatosDeAviso()),
        'estados_disponibilidad_historial' => saludInsertar('estados_disponibilidad', saludDatosDeEstado()),
    ];

    foreach (HISTORIALES_DE_SALUD as $tabla => $columna) {
        DB::connection('propietario')->table($tabla)->insert([
            'id' => Identificador::generar()->valor(), $columna => $entidades[$tabla], 'version' => 1, 'operacion' => 'crear',
            'datos_nuevos' => json_encode(['dato' => 'x']), 'usuario_id' => null, 'ocurrido_en' => '2026-11-15 10:30:00+00',
        ]);

        expect(saludRechazo(fn () => DB::table($tabla)->update(['motivo' => 'x'])))->not->toBeNull("UPDATE de la aplicación sobre {$tabla}")
            ->and(saludRechazo(fn () => DB::table($tabla)->delete()))->not->toBeNull("DELETE de la aplicación sobre {$tabla}")
            ->and(saludRechazo(fn () => DB::connection('propietario')->table($tabla)->update(['motivo' => 'x'])))->not->toBeNull("UPDATE del propietario sobre {$tabla}")
            ->and(saludRechazo(fn () => DB::connection('propietario')->table($tabla)->delete()))->not->toBeNull("DELETE del propietario sobre {$tabla}")
            ->and(DB::connection('propietario')->table($tabla)->count())->toBe(1);
    }
});

test('RNF-02: cada tabla de Salud tiene su historial con la columna de entidad y la auditoría básica', function () {
    foreach (HISTORIALES_DE_SALUD as $tabla => $columna) {
        expect(Schema::connection('propietario')->hasColumn($tabla, $columna))->toBeTrue($tabla);
    }
    foreach (TABLAS_DE_SALUD as $tabla) {
        expect(Schema::connection('propietario')->hasColumns($tabla, ['creado_en', 'creado_por', 'actualizado_en', 'actualizado_por', 'eliminado_en', 'eliminado_por']))->toBeTrue($tabla);
    }
});

test('RNF-03: migrate:rollback de Salud no falla y se vuelve a aplicar', function () {
    esquemaMarcarSucio();
    $tablas = [...TABLAS_DE_SALUD, ...array_keys(HISTORIALES_DE_SALUD)];

    consolaArtisan('migrate:rollback', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    foreach ($tablas as $tabla) {
        expect(Schema::connection('propietario')->hasTable($tabla))->toBeFalse($tabla);
    }

    consolaArtisan('migrate', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    foreach ($tablas as $tabla) {
        expect(Schema::connection('propietario')->hasTable($tabla))->toBeTrue($tabla);
    }
});
