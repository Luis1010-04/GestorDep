<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Src\Compartido\Dominio\Identificador;
use Src\Identidad\Infraestructura\Modelos\UsuarioModelo;
use Tests\TestCase;

function usuarioDePaginas(TestCase $prueba): void
{
    $usuario = new UsuarioModelo;
    $usuario->id = Identificador::generar()->valor();
    $prueba->actingAs($usuario, 'web');
}

test('PT-12 10: ninguna vista ni script propio usa localStorage ni sessionStorage', function () {
    $archivos = [
        ...File::allFiles(resource_path('views')),
        ...File::allFiles(public_path('assets/js')),
    ];

    expect($archivos)->not->toBeEmpty();

    foreach ($archivos as $archivo) {
        $contenido = File::get($archivo->getPathname());

        expect($contenido)->not->toContain('localStorage');
        expect($contenido)->not->toContain('sessionStorage');
    }
});

test('PT-12 11: los textos de ingreso y panel salen de lang/es/interfaz.php', function () {
    /** @var TestCase $this */
    $this->get('/ingreso')
        ->assertOk()
        ->assertSee(__('interfaz.ingreso.titulo'))
        ->assertSee(__('interfaz.ingreso.correo'))
        ->assertSee(__('interfaz.ingreso.contrasena'))
        ->assertSee(__('interfaz.ingreso.entrar'));

    usuarioDePaginas($this);

    $this->get('/panel')
        ->assertOk()
        ->assertSee(__('interfaz.panel.titulo'))
        ->assertSee(__('interfaz.panel.sin_secciones'))
        ->assertSee(__('interfaz.menu.panel'))
        ->assertSee(__('interfaz.menu.cerrar_sesion'));

    expect(__('interfaz.ingreso.titulo'))->not->toBe('interfaz.ingreso.titulo');
});

test('PT-12 15: las rutas web registradas son exactamente /, /ingreso y /panel con su middleware', function () {
    $rutas = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($ruta) => in_array('web', $ruta->gatherMiddleware(), true) && ! str_starts_with($ruta->uri(), 'sanctum/'))
        ->mapWithKeys(fn ($ruta) => [$ruta->uri() => array_values(array_diff($ruta->gatherMiddleware(), ['web']))])
        ->sortKeys()
        ->all();

    expect($rutas)->toBe([
        '/' => [],
        'ingreso' => ['guest:web'],
        'panel' => ['auth:web'],
    ]);
});
