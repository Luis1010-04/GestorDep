<?php

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;

test('F1-H0-T03: las pruebas usan la base de datos de pruebas', function () {
    expect(DB::connection()->getDatabaseName())->toBe('gestordep_pruebas');
});

test('F1-H0-T03: toda ruta registrada esta bajo /api/v1 o es la cookie csrf de Sanctum (DD-28)', function () {
    $rutas = collect(app('router')->getRoutes()->getRoutes())
        ->map(fn (Route $ruta) => $ruta->uri())
        ->reject(fn (string $uri) => str_starts_with($uri, 'api/v1/'))
        ->values()
        ->all();

    expect($rutas)->toBe(['sanctum/csrf-cookie']);
});
