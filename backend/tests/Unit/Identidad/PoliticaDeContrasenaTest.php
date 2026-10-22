<?php

use Src\Identidad\Dominio\PoliticaDeContrasena;

test('RNF-10: contraseña de 9 caracteres no cumple', function () {
    expect((new PoliticaDeContrasena(10))->cumple('123456789'))->toBeFalse();
});

test('RNF-10: contraseña de 10 caracteres cumple', function () {
    expect((new PoliticaDeContrasena(10))->cumple('1234567890'))->toBeTrue();
});

test('RNF-10: la longitud se cuenta en caracteres y no en bytes', function () {
    expect((new PoliticaDeContrasena(10))->cumple('ñññññññññ'))->toBeFalse()
        ->and((new PoliticaDeContrasena(10))->cumple('ñññññññññé'))->toBeTrue();
});

test('RNF-10: una longitud mínima menor que 1 es inválida', function () {
    expect(fn () => new PoliticaDeContrasena(0))->toThrow(InvalidArgumentException::class);
});
