<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('create extension if not exists citext');
    }

    public function down(): void
    {
        DB::statement('drop extension if exists citext');
    }
};
