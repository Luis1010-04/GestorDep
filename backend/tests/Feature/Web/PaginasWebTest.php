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

test('PT-12 17: ninguna vista ni script propio carga recursos externos, salvo el enlace del crédito de BootstrapMade', function () {
    $archivos = [
        ...File::allFiles(resource_path('views')),
        ...File::allFiles(public_path('assets/js')),
    ];

    expect($archivos)->not->toBeEmpty();

    foreach ($archivos as $archivo) {
        $contenido = str_replace('https://bootstrapmade.com/', '', File::get($archivo->getPathname()));

        expect(preg_match('~https?://|(?<![:\w])//[a-z]~i', $contenido))->toBe(0, $archivo->getFilename());
    }
});

test('PT-12 18: la plantilla se versiona sin forms/ y sin ningún archivo PHP en public/NiceAdmin', function () {
    expect(File::isDirectory(public_path('NiceAdmin/forms')))->toBeFalse();

    $php = collect(File::allFiles(public_path('NiceAdmin')))
        ->filter(fn ($archivo) => $archivo->getExtension() === 'php')
        ->map(fn ($archivo) => $archivo->getPathname())
        ->values()
        ->all();

    expect($php)->toBe([]);
});

test('PT-12 22: no existe ningún archivo .html dentro de public/NiceAdmin', function () {
    $paginas = collect(File::allFiles(public_path('NiceAdmin')))
        ->filter(fn ($archivo) => in_array(strtolower($archivo->getExtension()), ['html', 'htm'], true))
        ->map(fn ($archivo) => $archivo->getRelativePathname())
        ->sort()
        ->values()
        ->all();

    expect($paginas)->toBe([]);
});

test('PT-12 19: el layout define el tinymce vacío antes de main.js y carga Bootstrap antes que main.js', function () {
    $layout = File::get(resource_path('views/layouts/app.blade.php'));
    $posiciones = array_map(
        fn (string $script) => strpos($layout, $script),
        [
            '/NiceAdmin/assets/vendor/bootstrap/js/bootstrap.bundle.min.js',
            '/assets/js/tinymce-vacio.js',
            '/NiceAdmin/assets/js/main.js',
            '/assets/js/api.js',
            '/assets/js/sesion.js',
        ],
    );

    expect($posiciones)->not->toContain(false)
        ->and($posiciones)->toBe(collect($posiciones)->sort()->values()->all())
        ->and(File::get(public_path('assets/js/tinymce-vacio.js')))->toContain('window.tinymce');
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
