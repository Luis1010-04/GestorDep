<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * La columna de la entidad es `usuario_afectado_id` y no `usuario_id`: en el historial `usuario_id`
 * es el autor del cambio (docs/arquitectura.md §9.1) y la macro no admite dos columnas con ese nombre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::historial('usuarios', 'usuario_afectado_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios_historial');
    }
};
