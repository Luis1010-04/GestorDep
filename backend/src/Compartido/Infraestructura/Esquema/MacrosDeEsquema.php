<?php

namespace Src\Compartido\Infraestructura\Esquema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/**
 * Macros del constructor de esquema (docs/arquitectura.md, secciones 6, 8.1 y 9.1)
 * para que la auditoria basica y las tablas de historial sean identicas en todas las tablas.
 */
final class MacrosDeEsquema
{
    public static function registrar(): void
    {
        Blueprint::macro('auditoriaBasica', function (): void {
            /** @var Blueprint $this */
            $tabla = $this->getTable();

            $this->timestampTz('creado_en')->useCurrent();
            $this->timestampTz('actualizado_en')->useCurrent();
            $this->uuid('creado_por')->nullable();
            $this->uuid('actualizado_por')->nullable();
            $this->timestampTz('eliminado_en')->nullable();
            $this->uuid('eliminado_por')->nullable();

            foreach (['creado_por', 'actualizado_por', 'eliminado_por'] as $columna) {
                $this->foreign($columna, "fk_{$tabla}_{$columna}")
                    ->references('id')->on('usuarios')->restrictOnDelete();
            }
        });

        Builder::macro('historial', function (string $tablaEntidad, string $columnaEntidad): void {
            /** @var Builder $this */
            $tabla = "{$tablaEntidad}_historial";

            $this->create($tabla, function (Blueprint $t) use ($tabla, $tablaEntidad, $columnaEntidad) {
                $t->uuid('id');
                $t->uuid($columnaEntidad);
                $t->integer('version');
                $t->string('operacion', 20);
                $t->jsonb('datos_anteriores')->nullable();
                $t->jsonb('datos_nuevos')->nullable();
                $t->uuid('usuario_id')->nullable();
                $t->timestampTz('ocurrido_en');
                $t->string('motivo')->nullable();

                $t->foreign($columnaEntidad, "fk_{$tabla}_{$columnaEntidad}")
                    ->references('id')->on($tablaEntidad)->restrictOnDelete();
                $t->foreign('usuario_id', "fk_{$tabla}_usuario_id")
                    ->references('id')->on('usuarios')->restrictOnDelete();
                $t->unique([$columnaEntidad, 'version'], "uq_{$tabla}_{$columnaEntidad}_version");
                $t->rawIndex("{$columnaEntidad}, ocurrido_en desc", "ix_{$tabla}_{$columnaEntidad}_ocurrido_en");
            });

            // El gramatico de PostgreSQL de Laravel ignora el nombre de la clave primaria.
            $this->getConnection()->statement(
                "alter table {$tabla} add constraint pk_{$tabla} primary key (id)"
            );
            $this->getConnection()->statement(
                "alter table {$tabla} add constraint ck_{$tabla}_operacion
                    check (operacion in ('crear', 'actualizar', 'eliminar', 'restaurar'))"
            );
        });
    }
}
