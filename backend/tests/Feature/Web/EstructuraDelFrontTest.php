<?php

use Illuminate\Support\Facades\File;

/** Vistas de la rebanada 1 que quedan donde estaban (orden del responsable del 24/11/2026, 14:15). */
const VISTAS_DE_LA_REBANADA_1 = ['ingreso.blade.php', 'panel.blade.php'];

/** Nombres de vista que admite cada módulo, adaptados de la referencia de organización (index, crear, editar, ver). */
const VISTAS_DE_MODULO = ['index', 'crear', 'editar', 'ver'];

/** @return list<string> rutas relativas de las vistas de módulo, como "contrasena/editar.blade.php" */
function vistasDeModulo(): array
{
    return array_values(collect(File::allFiles(resource_path('views/modulos')))
        ->map(fn ($archivo) => str_replace('\\', '/', $archivo->getRelativePathname()))
        ->sort()
        ->all());
}

test('FRONT-R2 11: las vistas nuevas viven en modulos/{modulo}/ con los nombres index, crear, editar o ver; layouts y parciales siguen donde estaban', function () {
    $fueraDeModulos = collect(File::allFiles(resource_path('views')))
        ->map(fn ($archivo) => str_replace('\\', '/', $archivo->getRelativePathname()))
        ->reject(fn (string $ruta) => str_starts_with($ruta, 'modulos/') || str_starts_with($ruta, 'layouts/') || str_starts_with($ruta, 'parciales/'))
        ->sort()
        ->values()
        ->all();

    expect($fueraDeModulos)->toBe(VISTAS_DE_LA_REBANADA_1)
        ->and(vistasDeModulo())->not->toBeEmpty();

    foreach (vistasDeModulo() as $vista) {
        expect($vista)->toMatch('#^[a-z_]+/('.implode('|', VISTAS_DE_MODULO).')\.blade\.php$#');
    }
});

test('FRONT-R2 12: cada vista de módulo extiende el layout, define titulo y contenido y carga sus scripts desde comun/, modulos/{modulo}/ o vendor/', function () {
    foreach (vistasDeModulo() as $vista) {
        $contenido = File::get(resource_path('views/modulos/'.$vista));
        $modulo = explode('/', $vista)[0];
        preg_match_all('/<script src="([^"]+)"><\/script>/', $contenido, $scripts);

        expect($contenido)->toContain("@extends('layouts.app')")
            ->and($contenido)->toContain("@section('titulo'")
            ->and($contenido)->toContain("@section('contenido')")
            ->and($contenido)->toContain("@push('scripts')")
            ->and($contenido)->not->toContain('<script>');

        foreach ($scripts[1] as $script) {
            expect(str_starts_with($script, '/assets/vendor/')
                || str_starts_with($script, '/assets/js/comun/')
                || str_starts_with($script, '/assets/js/modulos/'.$modulo.'/'))->toBeTrue($vista.': '.$script);
        }
    }
});

test('FRONT-R2 13: solo comun/avisos.js usa SweetAlert2; ninguna vista ni otro script llama a Swal', function () {
    $archivos = [
        ...File::allFiles(resource_path('views')),
        ...File::allFiles(public_path('assets/js')),
    ];

    foreach ($archivos as $archivo) {
        $ruta = str_replace('\\', '/', $archivo->getPathname());
        if (str_ends_with($ruta, 'assets/js/comun/avisos.js')) {
            continue;
        }

        expect(str_contains(File::get($archivo->getPathname()), 'Swal'))->toBeFalse($archivo->getFilename());
    }
});

test('FRONT-R2 14: comun/avisos.js ofrece exito, error, aviso y confirmar; el aviso va arriba a la derecha, sin botón y dura 1,5 s; los errores usan icon error', function () {
    $avisos = File::get(public_path('assets/js/comun/avisos.js'));
    preg_match('/\n    function error\(detalle\) \{\n.*?\n    \}\n/s', $avisos, $funcionError);
    preg_match_all("/icon: '([a-z]+)'/", $funcionError[0] ?? '', $iconosDeError);

    expect($avisos)->toContain('window.Avisos = { exito: exito, error: error, aviso: aviso, confirmar: confirmar };')
        ->and($avisos)->toContain("position: 'top-end'")
        ->and($avisos)->toContain('showConfirmButton: false')
        ->and($avisos)->toContain('timer: 1500')
        ->and($iconosDeError[1])->not->toBeEmpty()
        ->and(array_unique($iconosDeError[1]))->toBe(['error']);
});
