<?php

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;

test('F1-H0-T03: las pruebas usan la base de datos de pruebas', function () {
    expect(DB::connection()->getDatabaseName())->toBe('gestordep_pruebas');
});

test('F1-H0-T03: las unicas rutas registradas son la de salud y la cookie csrf de Sanctum (DD-28)', function () {
    $rutas = collect(app('router')->getRoutes()->getRoutes())
        ->map(fn (Route $ruta) => $ruta->uri())
        ->values()
        ->all();

    expect($rutas)->toBe(['sanctum/csrf-cookie', 'api/v1/salud']);
});
