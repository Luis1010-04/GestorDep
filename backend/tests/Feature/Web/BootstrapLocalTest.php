<?php

use Illuminate\Support\Facades\File;

/** SHA-256 de los archivos de bootstrap@5.3.3 (integridad npm sha512-8HLCdWgyoMguSO9o+aH+iuZ+aht+mzW0u3HIMzVu7Srrpv7EBBxTnrFlSCskwdY1+EOFQSm7uMJhNQHkdPcmjg==). */
const ARCHIVOS_DE_BOOTSTRAP = [
    'css/bootstrap.min.css' => '3c8f27e6009ccfd710a905e6dcf12d0ee3c6f2ac7da05b0572d3e0d12e736fc8',
    'js/bootstrap.bundle.min.js' => '0833b2e9c3a26c258476c46266e6877fc75218625162e0460be9a3a098a61c6c',
    'LICENSE' => '8c14611ae41ac6fd543c13349f22188eb12c69b3e59105c5eca3925a8e4eca3e',
];

test('PT-12 12: existen bootstrap.min.css, bootstrap.bundle.min.js y LICENSE de Bootstrap', function () {
    foreach (array_keys(ARCHIVOS_DE_BOOTSTRAP) as $archivo) {
        expect(File::exists(public_path('assets/vendor/bootstrap/'.$archivo)))->toBeTrue($archivo);
    }
});

test('PT-12 13: el SHA-256 de cada archivo de Bootstrap coincide con el registrado', function () {
    foreach (ARCHIVOS_DE_BOOTSTRAP as $archivo => $hash) {
        expect(hash_file('sha256', public_path('assets/vendor/bootstrap/'.$archivo)))->toBe($hash, $archivo);
    }
});

test('PT-12 14: el LICENSE de Bootstrap es MIT', function () {
    expect(File::get(public_path('assets/vendor/bootstrap/LICENSE')))
        ->toContain('The MIT License (MIT)')
        ->toContain('The Bootstrap Authors');
});
