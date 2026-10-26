<?php

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Las tablas de estas pruebas se crean con el rol propietario dentro de una transaccion
 * que se revierte al final: PostgreSQL revierte tambien el DDL.
 */
beforeEach(function () {
    esquemaMarcarSucio();

    /** @var Connection $conexion */
    $conexion = DB::connection('propietario');
    $conexion->beginTransaction();

    if (! Schema::connection('propietario')->hasTable('usuarios')) {
        Schema::connection('propietario')->create('usuarios', function (Blueprint $t) {
            $t->uuid('id')->primary();
        });
    }

    Schema::connection('propietario')->create('prueba_entidades', function (Blueprint $t) {
        $t->uuid('id')->primary();
        $t->auditoriaBasica();
    });
});

afterEach(function () {
    DB::connection('propietario')->rollBack();
});

/** @return array<string, array{tipo: string, nulo: string, predeterminado: string|null}> */
function columnasDe(string $tabla): array
{
    $filas = DB::connection('propietario')->select(
        'select column_name, data_type, is_nullable, column_default
           from information_schema.columns
          where table_schema = current_schema() and table_name = ?
          order by ordinal_position',
        [$tabla],
    );

    $columnas = [];
    foreach ($filas as $fila) {
        $columnas[$fila->column_name] = [
            'tipo' => $fila->data_type,
            'nulo' => $fila->is_nullable,
            'predeterminado' => $fila->column_default,
        ];
    }

    return $columnas;
}

/** @return array<string, array{columna: string, destino: string, al_borrar: string}> */
function clavesForaneasDe(string $tabla): array
{
    $filas = DB::connection('propietario')->select(
        "select c.conname as nombre,
                a.attname as columna,
                c.confrelid::regclass::text as destino,
                c.confdeltype as al_borrar
           from pg_constraint c
           join pg_attribute a on a.attrelid = c.conrelid and a.attnum = c.conkey[1]
          where c.contype = 'f' and c.conrelid = ?::regclass",
        [$tabla],
    );

    $claves = [];
    foreach ($filas as $fila) {
        $claves[$fila->nombre] = ['columna' => $fila->columna, 'destino' => $fila->destino, 'al_borrar' => $fila->al_borrar];
    }
    ksort($claves);

    return $claves;
}

/** @return array<string, string> */
function indicesDe(string $tabla): array
{
    $filas = DB::connection('propietario')->select(
        'select indexname, indexdef from pg_indexes where schemaname = current_schema() and tablename = ?',
        [$tabla],
    );

    $indices = [];
    foreach ($filas as $fila) {
        $indices[$fila->indexname] = $fila->indexdef;
    }
    ksort($indices);

    return $indices;
}

test('F1-H0-T07: auditoriaBasica agrega las seis columnas con sus tipos', function () {
    $columnas = columnasDe('prueba_entidades');

    expect(array_keys($columnas))->toBe([
        'id', 'creado_en', 'actualizado_en', 'creado_por', 'actualizado_por', 'eliminado_en', 'eliminado_por',
    ]);

    foreach (['creado_en', 'actualizado_en'] as $columna) {
        expect($columnas[$columna]['tipo'])->toBe('timestamp with time zone')
            ->and($columnas[$columna]['nulo'])->toBe('NO')
            ->and($columnas[$columna]['predeterminado'])->toBe('CURRENT_TIMESTAMP');
    }

    expect($columnas['eliminado_en']['tipo'])->toBe('timestamp with time zone')
        ->and($columnas['eliminado_en']['nulo'])->toBe('YES');

    foreach (['creado_por', 'actualizado_por', 'eliminado_por'] as $columna) {
        expect($columnas[$columna]['tipo'])->toBe('uuid')
            ->and($columnas[$columna]['nulo'])->toBe('YES');
    }
});

test('F1-H0-T07: auditoriaBasica crea las claves foraneas a usuarios con nombre explicito y sin cascada', function () {
    expect(clavesForaneasDe('prueba_entidades'))->toBe([
        'fk_prueba_entidades_actualizado_por' => ['columna' => 'actualizado_por', 'destino' => 'usuarios', 'al_borrar' => 'r'],
        'fk_prueba_entidades_creado_por' => ['columna' => 'creado_por', 'destino' => 'usuarios', 'al_borrar' => 'r'],
        'fk_prueba_entidades_eliminado_por' => ['columna' => 'eliminado_por', 'destino' => 'usuarios', 'al_borrar' => 'r'],
    ]);
});

test('F1-H0-T07: historial crea la tabla con las columnas y tipos de la seccion 9', function () {
    Schema::connection('propietario')->historial('prueba_entidades', 'prueba_entidad_id');

    $columnas = columnasDe('prueba_entidades_historial');

    expect(array_map(fn (array $c) => [$c['tipo'], $c['nulo']], $columnas))->toBe([
        'id' => ['uuid', 'NO'],
        'prueba_entidad_id' => ['uuid', 'NO'],
        'version' => ['integer', 'NO'],
        'operacion' => ['character varying', 'NO'],
        'datos_anteriores' => ['jsonb', 'YES'],
        'datos_nuevos' => ['jsonb', 'YES'],
        'usuario_id' => ['uuid', 'YES'],
        'ocurrido_en' => ['timestamp with time zone', 'NO'],
        'motivo' => ['character varying', 'YES'],
    ]);
});

test('F1-H0-T07: historial crea las claves foraneas a la entidad y a usuarios sin cascada', function () {
    Schema::connection('propietario')->historial('prueba_entidades', 'prueba_entidad_id');

    expect(clavesForaneasDe('prueba_entidades_historial'))->toBe([
        'fk_prueba_entidades_historial_prueba_entidad_id' => ['columna' => 'prueba_entidad_id', 'destino' => 'prueba_entidades', 'al_borrar' => 'r'],
        'fk_prueba_entidades_historial_usuario_id' => ['columna' => 'usuario_id', 'destino' => 'usuarios', 'al_borrar' => 'r'],
    ]);
});

test('F1-H0-T07: historial crea la clave primaria, la unica por version y el indice por fecha descendente', function () {
    Schema::connection('propietario')->historial('prueba_entidades', 'prueba_entidad_id');

    expect(indicesDe('prueba_entidades_historial'))->toBe([
        'ix_prueba_entidades_historial_prueba_entidad_id_ocurrido_en' => 'CREATE INDEX ix_prueba_entidades_historial_prueba_entidad_id_ocurrido_en ON public.prueba_entidades_historial USING btree (prueba_entidad_id, ocurrido_en DESC)',
        'pk_prueba_entidades_historial' => 'CREATE UNIQUE INDEX pk_prueba_entidades_historial ON public.prueba_entidades_historial USING btree (id)',
        'uq_prueba_entidades_historial_prueba_entidad_id_version' => 'CREATE UNIQUE INDEX uq_prueba_entidades_historial_prueba_entidad_id_version ON public.prueba_entidades_historial USING btree (prueba_entidad_id, version)',
    ]);
});

test('F1-H0-T07: historial restringe la operacion a crear, actualizar, eliminar o restaurar', function () {
    Schema::connection('propietario')->historial('prueba_entidades', 'prueba_entidad_id');

    $definicion = DB::connection('propietario')->scalar(
        "select pg_get_constraintdef(oid) from pg_constraint
          where conrelid = 'prueba_entidades_historial'::regclass and conname = 'ck_prueba_entidades_historial_operacion'",
    );

    expect($definicion)->toBe("CHECK (((operacion)::text = ANY ((ARRAY['crear'::character varying, 'actualizar'::character varying, 'eliminar'::character varying, 'restaurar'::character varying])::text[])))");
});
