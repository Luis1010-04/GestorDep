<?php

use Illuminate\Support\Facades\File;

const RAIZ_DE_NICEADMIN = 'NiceAdmin/';

/** SHA-256 de los archivos de bootstrap@5.3.3 (integridad npm sha512-8HLCdWgyoMguSO9o+aH+iuZ+aht+mzW0u3HIMzVu7Srrpv7EBBxTnrFlSCskwdY1+EOFQSm7uMJhNQHkdPcmjg==); los de la plantilla son idénticos. */
const ARCHIVOS_DE_BOOTSTRAP = [
    'assets/vendor/bootstrap/css/bootstrap.min.css' => '3c8f27e6009ccfd710a905e6dcf12d0ee3c6f2ac7da05b0572d3e0d12e736fc8',
    'assets/vendor/bootstrap/js/bootstrap.bundle.min.js' => '0833b2e9c3a26c258476c46266e6877fc75218625162e0460be9a3a098a61c6c',
    'assets/vendor/bootstrap/LICENSE' => '8c14611ae41ac6fd543c13349f22188eb12c69b3e59105c5eca3925a8e4eca3e',
];

/** SHA-256 de los archivos de la plantilla NiceAdmin que se incluyen sin modificar. */
const ARCHIVOS_DE_LA_PLANTILLA = [
    'assets/js/main.js' => 'ef1f6ab133060acf821cbd8bd3ce60df47cfbaf3f8e2c4e1a0b6c88e1e9b7112',
    'assets/css/style.css' => '56d13ed182dc0999ec80cdc9d1370f4bbee0c66ed326df6631667c36e2a21619',
];

test('PT-12 12: existen los archivos de NiceAdmin que usa la rebanada 1 y la licencia', function () {
    $archivos = [
        ...array_keys(ARCHIVOS_DE_BOOTSTRAP),
        ...array_keys(ARCHIVOS_DE_LA_PLANTILLA),
        'assets/vendor/bootstrap-icons/bootstrap-icons.css',
        'assets/vendor/bootstrap-icons/fonts/bootstrap-icons.woff2',
        'assets/vendor/bootstrap-icons/fonts/bootstrap-icons.woff',
        'assets/img/logo.png',
        'assets/img/favicon.png',
        'assets/img/apple-touch-icon.png',
        'LICENCIA.md',
    ];

    foreach ($archivos as $archivo) {
        expect(File::exists(public_path(RAIZ_DE_NICEADMIN.$archivo)))->toBeTrue($archivo);
    }
});

test('PT-12 13: el SHA-256 de cada archivo de Bootstrap coincide con el registrado', function () {
    foreach (ARCHIVOS_DE_BOOTSTRAP as $archivo => $hash) {
        expect(hash_file('sha256', public_path(RAIZ_DE_NICEADMIN.$archivo)))->toBe($hash, $archivo);
    }
});

test('PT-12 14: Bootstrap declara su licencia MIT en el encabezado de sus archivos y en su LICENSE', function () {
    expect(File::get(public_path(RAIZ_DE_NICEADMIN.'assets/vendor/bootstrap/LICENSE')))
        ->toContain('The MIT License (MIT)')
        ->toContain('The Bootstrap Authors');

    foreach (['assets/vendor/bootstrap/css/bootstrap.min.css', 'assets/vendor/bootstrap/js/bootstrap.bundle.min.js'] as $archivo) {
        $inicio = substr(File::get(public_path(RAIZ_DE_NICEADMIN.$archivo)), 0, 400);

        expect($inicio)->toMatch('/Bootstrap\s+v5\.3\.3/')->toContain('Licensed under MIT');
    }
});

test('PT-12 20: main.js y style.css conservan su encabezado de licencia y su SHA-256 sin modificar', function () {
    foreach (ARCHIVOS_DE_LA_PLANTILLA as $archivo => $hash) {
        $ruta = public_path(RAIZ_DE_NICEADMIN.$archivo);

        expect(hash_file('sha256', $ruta))->toBe($hash, $archivo)
            ->and(substr(File::get($ruta), 0, 300))
            ->toContain('Template Name: NiceAdmin')
            ->toContain('Author: BootstrapMade.com')
            ->toContain('License: https://bootstrapmade.com/license/');
    }
});

test('PT-12 21: LICENCIA.md remite a la licencia oficial de BootstrapMade y a la obligación del pie', function () {
    $licencia = File::get(public_path(RAIZ_DE_NICEADMIN.'LICENCIA.md'));

    expect($licencia)
        ->toContain('https://bootstrapmade.com/license/')
        ->toContain('Designed by BootstrapMade')
        ->toContain('académico');
});
