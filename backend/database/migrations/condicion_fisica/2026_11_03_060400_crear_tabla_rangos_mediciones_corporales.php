<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rangos de peso y estatura (RNF-03, RNF-09, DA-16). Se siembran (`RangosMedicionesCorporalesSeeder`) y no se editan
 * desde la API. Lleva auditoría básica y no tiene historial, como `metricas`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rangos_mediciones_corporales', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id', 'pk_rangos_mediciones_corporales');
            $table->string('codigo', 50);
            $table->string('nombre', 100);
            $table->string('unidad', 20);
            $table->decimal('minimo', 6, 2);
            $table->decimal('maximo', 6, 2);
            $table->auditoriaBasica();
        });

        DB::statement('alter table rangos_mediciones_corporales add constraint uq_rangos_mediciones_corporales_codigo unique (codigo)');
        DB::statement("alter table rangos_mediciones_corporales add constraint ck_rangos_mediciones_corporales_codigo check (codigo in ('peso_kg', 'estatura_cm'))");
        DB::statement('alter table rangos_mediciones_corporales add constraint ck_rangos_mediciones_corporales_rango check (minimo > 0 and maximo > minimo)');
    }

    public function down(): void
    {
        Schema::dropIfExists('rangos_mediciones_corporales');
    }
};
