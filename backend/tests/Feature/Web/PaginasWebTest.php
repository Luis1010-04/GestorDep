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

test('PT-12 19: el layout define el tinymce vacío antes de main.js, carga Bootstrap antes que main.js y deja las bibliotecas y los scripts de cada página al final (enmienda FRONT-R2)', function () {
    $layout = File::get(resource_path('views/layouts/app.blade.php'));
    $posiciones = array_map(
        fn (string $script) => strpos($layout, $script),
        [
            '/NiceAdmin/assets/vendor/bootstrap/js/bootstrap.bundle.min.js',
            '/assets/js/tinymce-vacio.js',
            '/NiceAdmin/assets/js/main.js',
            '/assets/js/api.js',
            '/assets/js/sesion.js',
            "@stack('scripts')",
        ],
    );

    expect($posiciones)->not->toContain(false)
        ->and($posiciones)->toBe(collect($posiciones)->sort()->values()->all())
        ->and(strpos($layout, "@stack('estilos')"))->toBeGreaterThan((int) strpos($layout, '/NiceAdmin/assets/css/style.css'))
        ->and(File::get(public_path('assets/js/tinymce-vacio.js')))->toContain('window.tinymce');
});

test('FRONT-R2 10: ninguna vista usa las clases que main.js de la plantilla inicializa sin comprobar su biblioteca (DAF-12)', function () {
    $prohibidas = ['echart', 'quill-editor-default', 'quill-editor-bubble', 'quill-editor-full', 'tinymce-editor'];

    foreach (File::allFiles(resource_path('views')) as $archivo) {
        preg_match_all('/\bclass="([^"]*)"/', File::get($archivo->getPathname()), $clases);
        $usadas = collect($clases[1])->flatMap(fn (string $lista) => preg_split('/\s+/', trim($lista)) ?: [])->all();

        expect(array_intersect($prohibidas, $usadas))->toBe([], $archivo->getFilename());
    }
});

test('PT-12 15: las rutas web registradas son exactamente las aprobadas, con su middleware (enmiendas FRONT-R2 y FRONT-R3)', function () {
    $rutas = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($ruta) => in_array('web', $ruta->gatherMiddleware(), true) && ! str_starts_with($ruta->uri(), 'sanctum/'))
        ->mapWithKeys(fn ($ruta) => [$ruta->uri() => array_values(array_diff($ruta->gatherMiddleware(), ['web']))])
        ->sortKeys()
        ->all();

    expect($rutas)->toBe([
        '/' => [],
        'contrasena' => ['auth:web'],
        'ingreso' => ['guest:web'],
        'jugadores' => ['auth:web'],
        'jugadores/{jugador}' => ['auth:web'],
        'panel' => ['auth:web'],
    ]);
});
