<?php

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;

test('F1-H0-T03: las pruebas usan la base de datos de pruebas', function () {
    expect(DB::connection()->getDatabaseName())->toBe('gestordep_pruebas');
});

test('F1-H0-T03: la unica ruta registrada es la de salud', function () {
    $rutas = collect(app('router')->getRoutes()->getRoutes())
        ->map(fn (Route $ruta) => $ruta->uri())
        ->values()
        ->all();

    expect($rutas)->toBe(['api/v1/salud']);
});
