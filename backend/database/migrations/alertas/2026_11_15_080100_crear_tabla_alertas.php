<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alertas del jugador (HU-22, ADR-07; docs/hitos/f1-h6.md §5.1, DA-87). Persistente, sin auditoría y sin tabla de
 * historial: el propio registro guarda cuándo se activó y cuándo se cerró. Hay a lo sumo una alerta abierta por tipo,
 * jugador y equipo; el equipo es nulo en la académica. La única modificación permitida es cerrarla una vez
 * (`cerrada_en` de nulo a una fecha); el disparador rechaza cualquier otro UPDATE y todo DELETE, también al propietario.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_alertas');
            $table->string('tipo', 40);
            $table->uuid('jugador_id');
            $table->uuid('equipo_id')->nullable();
            $table->jsonb('causa');
            $table->timestampTz('activada_en');
            $table->timestampTz('cerrada_en')->nullable();

            $table->foreign('jugador_id', 'fk_alertas_jugador_id')->references('id')->on('jugadores')->restrictOnDelete();
            $table->foreign('equipo_id', 'fk_alertas_equipo_id')->references('id')->on('equipos')->restrictOnDelete();
        });

        DB::statement("alter table alertas add constraint ck_alertas_tipo check (tipo in ('riesgo_baja_academica', 'riesgo_baja_deportiva', 'acumulacion_sanciones_deportivas'))");
        DB::statement("create unique index uq_alertas_abierta on alertas (tipo, jugador_id, coalesce(equipo_id, '00000000-0000-0000-0000-000000000000'::uuid)) where cerrada_en is null");
        DB::statement('create index ix_alertas_jugador_cerrada_en on alertas (jugador_id, cerrada_en)');

        DB::statement(<<<'SQL'
            create or replace function rechazar_modificacion_alerta() returns trigger
            language plpgsql as $$
            begin
                if tg_op = 'DELETE' then
                    raise exception 'alerta_no_se_borra';
                end if;

                if old.cerrada_en is not null or new.cerrada_en is null
                    or new.id is distinct from old.id
                    or new.tipo is distinct from old.tipo
                    or new.jugador_id is distinct from old.jugador_id
                    or new.equipo_id is distinct from old.equipo_id
                    or new.causa is distinct from old.causa
                    or new.activada_en is distinct from old.activada_en then
                    raise exception 'alerta_solo_admite_cierre';
                end if;

                return new;
            end;
            $$
            SQL);
        DB::statement('create trigger tg_alertas_solo_cierre before update or delete on alertas for each row execute function rechazar_modificacion_alerta()');
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
        DB::statement('drop function if exists rechazar_modificacion_alerta()');
    }
};
