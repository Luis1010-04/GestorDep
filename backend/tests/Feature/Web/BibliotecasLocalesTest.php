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

/**
 * SHA-256 de DataTables y sus extensiones (FRONT-R3, DAF-03: sin jQuery), JSZip y pdfmake, copiados sin cambios de npm.
 * datatables.net@3.1.6, datatables.net-bs5@3.1.6, datatables.net-buttons@4.1.4, datatables.net-buttons-bs5@4.1.4: los
 * cuatro traen el mismo License.txt, que se copia una vez. jszip@3.10.2 y pdfmake@0.3.15.
 */
const BIBLIOTECAS_DE_TABLAS = [
    'assets/vendor/datatables/js/dataTables.min.js' => '7d0e64e81aa9ca42210f35afdaa348ca0016b08bb88292dab9da8014ec7a1887',
    'assets/vendor/datatables/js/dataTables.bootstrap5.min.js' => 'f21ebd37ba224864e9224c99bd8d29895dfc36f4be4af516d4c48404017f6503',
    'assets/vendor/datatables/css/dataTables.bootstrap5.min.css' => '5ff84d5553f3a60b4927ae5732c2e3799d10c348f10d5b5ebf49f0035352aa05',
    'assets/vendor/datatables/js/dataTables.buttons.min.js' => '274d1189b99b693fe5a0aafcd9cf47761aadb2f9efef98bc1da561c51414ebe1',
    'assets/vendor/datatables/js/buttons.bootstrap5.min.js' => '0fea4aa0f206a52e83ac11b29fd2a2460ad6111e9b812f08dfb2b13e006851b4',
    'assets/vendor/datatables/css/buttons.bootstrap5.min.css' => 'd226de2277037f269f2616b6b8e9754ee52908ef64b17b808dc6568d7bcf3b3a',
    'assets/vendor/datatables/License.txt' => '62b5fdd54371b4f6bd07a60dbc78b38814f49a8028213f540dc2733cad605382',
    'assets/vendor/jszip/jszip.min.js' => '7f839b2d4688b845c105ebf5d2f9803075f91ea0fe72bdaac176c3a04dd3d2c1',
    'assets/vendor/jszip/LICENSE.markdown' => '566c953c6090b1218ca6217dd7359d45dde46581968586dc607d59a78af6a9c4',
    'assets/vendor/pdfmake/pdfmake.min.js' => 'a3453acc05b244d2a9264d1231abf7e53f869c56a834553046be56a514a7e0f6',
    'assets/vendor/pdfmake/vfs_fonts.js' => 'f3db5ac36ce8c9a6a3f55d5d00c2a0e21a3d7698db149dff22f4e670ffcfd5c4',
    'assets/vendor/pdfmake/LICENSE' => 'ab919430421c2e244dbb8694d70916eae16235b75c0ea07e9a6c67cd4265c954',
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

test('FRONT-R3 12: los archivos de DataTables, Buttons, JSZip y pdfmake existen y su SHA-256 coincide con el de su paquete npm (DAF-03, DAF-06, DAF-07)', function () {
    foreach (BIBLIOTECAS_DE_TABLAS as $archivo => $hash) {
        expect(File::exists(public_path($archivo)))->toBeTrue($archivo)
            ->and(hash_file('sha256', public_path($archivo)))->toBe($hash, $archivo);
    }
});

test('FRONT-R3 13: las versiones son DataTables 3.1.6, Buttons 4.1.4, JSZip 3.10.2 y pdfmake 0.3.15, y sus licencias son MIT (JSZip, MIT o GPLv3 a elección)', function () {
    $leer = fn (string $archivo) => File::get(public_path('assets/vendor/'.$archivo));

    expect($leer('datatables/js/dataTables.min.js'))->toStartWith("/*! DataTables 3.1.6\n")
        ->and($leer('datatables/js/dataTables.buttons.min.js'))->toStartWith("/*! Buttons 4.1.4 for DataTables\n")
        ->and($leer('datatables/js/buttons.bootstrap5.min.js'))->toStartWith("/*! Buttons Bootstrap 5 styling 4.1.4 for DataTables\n")
        ->and($leer('jszip/jszip.min.js'))->toContain('JSZip v3.10.2')
        ->and($leer('pdfmake/pdfmake.min.js'))->toStartWith('/*! pdfmake v0.3.15, @license MIT')
        ->and($leer('datatables/License.txt'))->toContain('MIT')
        ->and($leer('jszip/LICENSE.markdown'))->toStartWith('JSZip is dual licensed. At your choice you may use it under the MIT license')
        ->and($leer('pdfmake/LICENSE'))->toStartWith('The MIT License (MIT)');
});

test('FRONT-R3 14: no hay jQuery: ningún archivo de public/assets ni de public/NiceAdmin se llama jquery y ninguna vista lo carga (DAF-03)', function () {
    $jquery = collect([...File::allFiles(public_path('assets')), ...File::allFiles(public_path('NiceAdmin'))])
        ->filter(fn ($archivo) => str_contains(strtolower($archivo->getFilename()), 'jquery'))
        ->map(fn ($archivo) => $archivo->getRelativePathname())
        ->values()
        ->all();

    expect($jquery)->toBe([]);

    foreach (File::allFiles(resource_path('views')) as $vista) {
        expect(strtolower(File::get($vista->getPathname())))->not->toContain('jquery', $vista->getFilename());
    }
});
