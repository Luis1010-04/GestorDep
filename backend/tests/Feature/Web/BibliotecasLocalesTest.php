<?php

use Illuminate\Support\Facades\File;

/**
 * SHA-256 de las bibliotecas fuera de la plantilla, copiadas sin cambios de su paquete npm (DAF-07). La integridad de
 * cada paquete se registra en frontend/docs/ACTIVOS_NICEADMIN.md.
 * sweetalert2@11.26.26: sha512-ihhDJ/0wA+jpuakxnD5CTBzhos96NVSnt7iHoIS6IaqHFwkQNQ2Q4BJwXeHARfsCvhVH4o1GMm80LZRWN9ST6g==
 */
const BIBLIOTECAS_LOCALES = [
    'assets/vendor/sweetalert2/sweetalert2.min.js' => 'f4bfbd113bc52f347ce8b0d474f07733d726975f435a465c58a58d48b4ceccd3',
    'assets/vendor/sweetalert2/sweetalert2.min.css' => 'a1173e56fd474be04ddbefa81bd75cc465627ef2f5db261792e7d25fb7871743',
    'assets/vendor/sweetalert2/LICENSE' => '879aa33feba35b3428c6485fc17cddd8c820d7a8780281aa945e60582a28c4b4',
];

test('FRONT-R2 7: los archivos de SweetAlert2 existen y su SHA-256 coincide con el del paquete npm (DAF-05, DAF-07)', function () {
    foreach (BIBLIOTECAS_LOCALES as $archivo => $hash) {
        expect(File::exists(public_path($archivo)))->toBeTrue($archivo)
            ->and(hash_file('sha256', public_path($archivo)))->toBe($hash, $archivo);
    }
});

test('FRONT-R2 8: SweetAlert2 es la versión 11.26.26 y su licencia es MIT', function () {
    expect(File::get(public_path('assets/vendor/sweetalert2/sweetalert2.min.js')))->toStartWith("/*!\n* sweetalert2 v11.26.26\n* Released under the MIT License.")
        ->and(File::get(public_path('assets/vendor/sweetalert2/LICENSE')))->toStartWith('The MIT License (MIT)');
});

test('FRONT-R2 9: SweetAlert2 no contiene código que dependa del dominio de la página (location)', function () {
    expect(File::get(public_path('assets/vendor/sweetalert2/sweetalert2.min.js')))->not->toContain('location');
});
