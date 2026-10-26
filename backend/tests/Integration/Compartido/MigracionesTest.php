<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\artisan;

function citextHabilitada(): bool
{
    return DB::connection('propietario')->scalar("select count(*) from pg_extension where extname = 'citext'") === 1;
}

test('F1-H0-T07: migrate:fresh con el rol propietario habilita citext', function () {
    artisan('migrate:fresh', ['--database' => 'propietario', '--force' => true])->assertSuccessful();

    expect(citextHabilitada())->toBeTrue();
});

test('F1-H0-T07: la migracion de citext se revierte y se vuelve a aplicar', function () {
    esquemaMarcarSucio();
    artisan('migrate:fresh', ['--database' => 'propietario', '--force' => true])->assertSuccessful();

    artisan('migrate:rollback', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    expect(citextHabilitada())->toBeFalse();

    artisan('migrate', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    expect(citextHabilitada())->toBeTrue();
});

test('F1-H0-EXTRA: migrate:fresh crea las tablas de la cola database que usa la configuracion', function () {
    artisan('migrate:fresh', ['--database' => 'propietario', '--force' => true])->assertSuccessful();

    $tablaDeTrabajos = config('queue.connections.database.table');
    $tablaDeFallidos = config('queue.failed.table');

    expect($tablaDeTrabajos)->toBe('trabajos')
        ->and($tablaDeFallidos)->toBe('trabajos_fallidos')
        ->and(Schema::connection('propietario')->hasTable($tablaDeTrabajos))->toBeTrue()
        ->and(Schema::connection('propietario')->hasTable($tablaDeFallidos))->toBeTrue();
});

test('F1-H0-T07: las migraciones de las subcarpetas por contexto estan registradas', function () {
    $rutas = app('migrator')->paths();

    expect($rutas)->toContain(database_path('migrations/compartido'));
});

test('F1-H0-T07: las migraciones siguen el formato AAAA_MM_DD_GGGSSS_descripcion', function () {
    $archivos = glob(database_path('migrations/*/*.php')) ?: [];

    expect($archivos)->not->toBeEmpty();
    foreach ($archivos as $archivo) {
        expect(basename($archivo))->toMatch('/^\d{4}_\d{2}_\d{2}_\d{3}\d{3}_[a-z0-9_]+\.php$/');
    }
});

test('F1-H0-T07: el rol de aplicacion no puede ejecutar migraciones', function () {
    expect(fn () => DB::connection('pgsql')->statement('create table prueba_sin_permiso (id int)'))
        ->toThrow(QueryException::class);
});
