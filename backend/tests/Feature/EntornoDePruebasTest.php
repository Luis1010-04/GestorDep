<?php

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;

test('F1-H0-T03 (DA-101): las pruebas usan la base de pruebas, o la de su proceso en paralelo, y nunca la de desarrollo', function () {
    // En serie no hay `TEST_TOKEN` y la base es `gestordep_pruebas`. En paralelo, paratest numera cada proceso desde 1
    // y a cada uno le toca `gestordep_pruebas_N`.
    $proceso = getenv('TEST_TOKEN');
    $esperada = $proceso === false || $proceso === '' ? 'gestordep_pruebas' : 'gestordep_pruebas_'.$proceso;

    expect($esperada)->toMatch('/^gestordep_pruebas(_[1-8])?$/')
        ->and(DB::connection()->getDatabaseName())->toBe($esperada)
        ->and(DB::connection('propietario')->getDatabaseName())->toBe($esperada)
        ->and((string) DB::connection()->scalar('select current_database()'))->toBe($esperada)
        ->and((string) DB::connection('propietario')->scalar('select current_database()'))->toBe($esperada);
});

test('F1-H0-T03: toda ruta registrada esta bajo /api/v1 o es la cookie csrf de Sanctum (DD-28, excepcion de DA-122 para las rutas web del frontend)', function () {
    $rutas = collect(app('router')->getRoutes()->getRoutes())
        ->map(fn (Route $ruta) => $ruta->uri())
        ->reject(fn (string $uri) => str_starts_with($uri, 'api/v1/'))
        ->sort()
        ->values()
        ->all();

    // Fuera de /api/v1 solo se permiten la cookie csrf (DD-28) y las tres rutas web de PT-12 (DA-122); cualquier otra falla.
    expect($rutas)->toBe(['/', 'ingreso', 'panel', 'sanctum/csrf-cookie']);
});
