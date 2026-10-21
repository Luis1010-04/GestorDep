<?php

use Tests\TestCase;

test('F1-H0-T03: salud responde 200 con aplicacion y base de datos en ok', function () {
    /** @var TestCase $this */
    $this->getJson('/api/v1/salud')
        ->assertOk()
        ->assertExactJson([
            'datos' => [
                'aplicacion' => 'ok',
                'base_de_datos' => 'ok',
            ],
        ]);
});

test('F1-H0-T03: la aplicacion usa utc, idioma es y postgresql', function () {
    expect(config('app.timezone'))->toBe('UTC')
        ->and(config('app.locale'))->toBe('es')
        ->and(config('app.zona_presentacion'))->toBe('America/Mexico_City')
        ->and(config('database.default'))->toBe('pgsql');
});
