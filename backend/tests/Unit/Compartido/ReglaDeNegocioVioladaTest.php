<?php

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

test('F1-H0-T06: la excepcion de regla de negocio expone su codigo', function () {
    $excepcion = new ReglaDeNegocioViolada('codigo_de_ejemplo');

    expect($excepcion->codigo())->toBe('codigo_de_ejemplo')
        ->and($excepcion)->toBeInstanceOf(DomainException::class);
});

test('F1-H0-T06: la excepcion de regla de negocio se puede lanzar y capturar con su codigo', function () {
    try {
        throw new ReglaDeNegocioViolada('otro_codigo');
    } catch (ReglaDeNegocioViolada $e) {
        expect($e->codigo())->toBe('otro_codigo');

        return;
    }
});

test('F1-H0-T06: rechaza un codigo que no esta en snake_case', function (string $codigo) {
    new ReglaDeNegocioViolada($codigo);
})->throws(InvalidArgumentException::class)->with([
    'vacio' => '',
    'con espacios' => 'codigo invalido',
    'con mayusculas' => 'CodigoInvalido',
    'con tilde' => 'calificación',
]);
