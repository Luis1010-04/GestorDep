<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Criterio de aprobación (RN-03): uno general y, opcionalmente, uno propio por materia. Cambiar un criterio cierra el
 * vigente (`vigente_hasta`) y crea otro; los índices garantizan un solo criterio vigente por materia y uno general.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criterios_aprobacion', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_criterios_aprobacion');
            $table->string('ambito', 20);
            $table->uuid('materia_id')->nullable();
            $table->decimal('calificacion_minima', 4, 2);
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->auditoriaBasica();

            $table->foreign('materia_id', 'fk_criterios_aprobacion_materia_id')->references('id')->on('materias')->restrictOnDelete();
        });

        DB::statement("alter table criterios_aprobacion add constraint ck_criterios_aprobacion_ambito check (ambito in ('general', 'materia'))");
        DB::statement('alter table criterios_aprobacion add constraint ck_criterios_aprobacion_calificacion_minima check (calificacion_minima between 0 and 10)');
        DB::statement("alter table criterios_aprobacion add constraint ck_criterios_aprobacion_materia check ((ambito = 'general') = (materia_id is null))");
        DB::statement('create unique index uq_criterios_aprobacion_materia_vigente on criterios_aprobacion (materia_id) where materia_id is not null and vigente_hasta is null and eliminado_en is null');
        DB::statement("create unique index uq_criterios_aprobacion_general_vigente on criterios_aprobacion (ambito) where ambito = 'general' and vigente_hasta is null and eliminado_en is null");
    }

    public function down(): void
    {
        Schema::dropIfExists('criterios_aprobacion');
    }
};
