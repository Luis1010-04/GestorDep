<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Src\Compartido\Dominio\Identificador;
use Src\Identidad\Infraestructura\Modelos\UsuarioModelo;
use Tests\TestCase;

const ENLACE_DEL_CREDITO = 'https://bootstrapmade.com/';

function usuarioDelPanel(TestCase $prueba): void
{
    $usuario = new UsuarioModelo;
    $usuario->id = Identificador::generar()->valor();
    $prueba->actingAs($usuario, 'web');
}

/** Rutas web propias que las páginas enlazan; no son activos (enmiendas FRONT-R2, FRONT-R3, FRONT-R6 y FRONT-R4). */
const PAGINAS_PROPIAS = ['/panel', '/ingreso', '/contrasena', '/jugadores', '/estatus', '/plantel/categorias', '/plantel/equipos'];

/**
 * Direcciones de recursos y enlaces de una página, sin las rutas web propias ni el enlace del crédito de BootstrapMade.
 *
 * @return list<string>
 */
function direccionesDeActivos(string $html): array
{
    preg_match_all('/\b(?:href|src)="([^"#]+)"/i', $html, $direcciones);

    return array_values(array_filter(
        $direcciones[1],
        fn (string $direccion) => $direccion !== '/'
            && $direccion !== ENLACE_DEL_CREDITO
            && ! in_array(strtok($direccion, '?'), PAGINAS_PROPIAS, true),
    ));
}

test('PT-12 4: GET /panel sin sesión redirige a /ingreso y no envía contenido privado', function () {
    /** @var TestCase $this */
    $respuesta = $this->get('/panel')->assertRedirect('/ingreso');

    expect((string) $respuesta->getContent())->not->toContain('id="sidebar"');
    expect((string) $respuesta->getContent())->not->toContain('id="header"');
});

test('PT-12 5: GET /panel con sesión responde 200', function () {
    /** @var TestCase $this */
    usuarioDelPanel($this);

    $this->get('/panel')->assertOk();
});

test('PT-12 6: el panel usa el layout y los tres parciales', function () {
    /** @var TestCase $this */
    usuarioDelPanel($this);

    $this->get('/panel')
        ->assertOk()
        ->assertSee('id="header"', false)
        ->assertSee('id="sidebar"', false)
        ->assertSee('id="footer"', false);

    $panel = File::get(resource_path('views/panel.blade.php'));
    $layout = File::get(resource_path('views/layouts/app.blade.php'));

    expect($panel)->toContain("@extends('layouts.app')")
        ->and($layout)->toContain("@include('parciales.cabecera')")
        ->and($layout)->toContain("@include('parciales.menu')")
        ->and($layout)->toContain("@include('parciales.pie')");
});

test('PT-12 7: las páginas cargan activos solo desde /assets/ o /NiceAdmin/ y todos existen en public', function () {
    /** @var TestCase $this */
    usuarioDelPanel($this);
    $paginas = [(string) $this->get('/panel')->getContent()];
    Auth::forgetGuards();
    $paginas[] = (string) $this->get('/ingreso')->getContent();

    foreach ($paginas as $html) {
        $direcciones = direccionesDeActivos($html);
        expect($direcciones)->not->toBeEmpty();

        foreach ($direcciones as $direccion) {
            expect(str_starts_with($direccion, '/assets/') || str_starts_with($direccion, '/NiceAdmin/'))->toBeTrue($direccion)
                ->and(File::exists(public_path($direccion)))->toBeTrue($direccion);
        }
    }
});

test('PT-12 8: el menú lateral contiene exactamente los ítems aprobados y ningún enlace a pantallas no construidas (enmiendas FRONT-R3: «Jugadores»; FRONT-R6: «Categorías» y «Equipos»; FRONT-R4: «Estatus de jugadores»)', function () {
    /** @var TestCase $this */
    usuarioDelPanel($this);
    $html = (string) $this->get('/panel')->getContent();

    preg_match('/<aside id="sidebar".*?<\/aside>/s', $html, $menu);
    preg_match_all('/<a\b[^>]*href="([^"]*)"[^>]*>(.*?)<\/a>/s', $menu[0] ?? '', $enlaces);

    expect($enlaces[1])->toBe(['/panel', '/jugadores', '/estatus', '/plantel/categorias', '/plantel/equipos'])
        ->and(array_map(fn (string $texto) => trim(strip_tags($texto)), $enlaces[2]))->toBe([
            __('interfaz.menu.panel'), __('interfaz.menu.jugadores'), __('interfaz.menu.estatus'), __('interfaz.menu.categorias'), __('interfaz.menu.equipos'),
        ]);
});

test('PT-12 9: el panel no contiene tarjetas, tablas, lienzos de gráfica ni cifras', function () {
    /** @var TestCase $this */
    usuarioDelPanel($this);
    $html = (string) $this->get('/panel')->getContent();

    preg_match('/<main\b.*?<\/main>/s', $html, $principal);
    $contenido = $principal[0] ?? '';

    expect($contenido)->not->toBe('')
        ->and($contenido)->not->toContain('class="card')
        ->and($contenido)->not->toContain('<table')
        ->and($contenido)->not->toContain('<canvas')
        ->and(strip_tags($contenido))->not->toMatch('/\d/');
});

test('PT-12 16: el pie conserva el crédito «Designed by BootstrapMade» con su enlace', function () {
    /** @var TestCase $this */
    usuarioDelPanel($this);
    $html = (string) $this->get('/panel')->getContent();

    preg_match('/<footer id="footer".*?<\/footer>/s', $html, $pie);

    expect($pie[0] ?? '')->toContain('Designed by <a href="'.ENLACE_DEL_CREDITO.'">BootstrapMade</a>');

    Auth::forgetGuards();
    $ingreso = (string) $this->get('/ingreso')->getContent();

    expect($ingreso)->toContain('Designed by <a href="'.ENLACE_DEL_CREDITO.'">BootstrapMade</a>');
});
