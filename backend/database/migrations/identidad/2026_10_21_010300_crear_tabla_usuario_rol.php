<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario_rol', function (Blueprint $table) {
            $table->uuid('usuario_id');
            $table->uuid('rol_id');

            $table->primary(['usuario_id', 'rol_id'], 'pk_usuario_rol');
            $table->foreign('usuario_id', 'fk_usuario_rol_usuario_id')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('rol_id', 'fk_usuario_rol_rol_id')->references('id')->on('roles')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_rol');
    }
};
