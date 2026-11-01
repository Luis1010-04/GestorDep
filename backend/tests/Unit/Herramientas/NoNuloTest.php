<?php

use PHPUnit\Framework\AssertionFailedError;
use Tests\Ayudantes\NoNulo;

test('F1-H5-T00: NoNulo devuelve el valor cuando no es nulo', function () {
    $fila = (object) ['id' => 'x'];

    expect(NoNulo::exigir($fila, 'la fila'))->toBe($fila)
        ->and(NoNulo::exigir(0, 'el cero'))->toBe(0)
        ->and(NoNulo::exigir('', 'la cadena vacía'))->toBe('')
        ->and(NoNulo::exigir(false, 'el falso'))->toBeFalse();
});

test('F1-H5-T00: NoNulo hace fallar la prueba con un mensaje que dice qué faltaba', function () {
    NoNulo::exigir(null, 'la asignatura creada');
})->throws(AssertionFailedError::class, 'Se esperaba un valor y se obtuvo nulo: la asignatura creada.');
