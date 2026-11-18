<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Src\Compartido\Dominio\Identificador;
use Src\Identidad\Infraestructura\Modelos\UsuarioModelo;
use Tests\TestCase;

function usuarioDelPanel(TestCase $prueba): void
{
    $usuario = new UsuarioModelo;
    $usuario->id = Identificador::generar()->valor();
    $prueba->actingAs($usuario, 'web');
}

/** @return list<string> */
function direccionesDeActivos(string $html): array
{
    preg_match_all('/\b(?:href|src)="([^"#]+)"/i', $html, $direcciones);

    return array_values(array_filter($direcciones[1], fn (string $direccion) => $direccion !== '/' && ! str_starts_with($direccion, '/panel') && ! str_starts_with($direccion, '/ingreso')));
}

test('PT-12 4: GET /panel sin sesión redirige a /ingreso y no envía contenido privado', function () {
    /** @var TestCase $this */
    $respuesta = $this->get('/panel')->assertRedirect('/ingreso');

    expect((string) $respuesta->getContent())->not->toContain('id="menu"');
    expect((string) $respuesta->getContent())->not->toContain('id="cabecera"');
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
        ->assertSee('id="cabecera"', false)
        ->assertSee('id="menu"', false)
        ->assertSee('id="pie"', false);

    $panel = File::get(resource_path('views/panel.blade.php'));
    $layout = File::get(resource_path('views/layouts/app.blade.php'));

    expect($panel)->toContain("@extends('layouts.app')")
        ->and($layout)->toContain("@include('parciales.cabecera')")
        ->and($layout)->toContain("@include('parciales.menu')")
        ->and($layout)->toContain("@include('parciales.pie')");
});

test('PT-12 7: las páginas cargan activos solo desde /assets/ y todos existen en public', function () {
    /** @var TestCase $this */
    usuarioDelPanel($this);
    $paginas = [(string) $this->get('/panel')->getContent()];
    Auth::forgetGuards();
    $paginas[] = (string) $this->get('/ingreso')->getContent();

    foreach ($paginas as $html) {
        $direcciones = direccionesDeActivos($html);
        expect($direcciones)->not->toBeEmpty();

        foreach ($direcciones as $direccion) {
            expect($direccion)->toStartWith('/assets/')
                ->and(File::exists(public_path($direccion)))->toBeTrue($direccion);
        }
    }
});

test('PT-12 8: el menú del panel solo contiene «Panel» y ningún enlace a pantallas no construidas', function () {
    /** @var TestCase $this */
    usuarioDelPanel($this);
    $html = (string) $this->get('/panel')->getContent();

    preg_match('/<aside id="menu".*?<\/aside>/s', $html, $menu);
    preg_match_all('/<a\b[^>]*href="([^"]*)"[^>]*>(.*?)<\/a>/s', $menu[0] ?? '', $enlaces);

    expect($enlaces[1])->toBe(['/panel'])
        ->and(trim(strip_tags($enlaces[2][0])))->toBe(__('interfaz.menu.panel'));
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
