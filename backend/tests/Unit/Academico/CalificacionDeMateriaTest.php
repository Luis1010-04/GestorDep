<?php

use Src\Academico\Dominio\CalificacionDeMateria;
use Src\Compartido\Dominio\NumeroDecimal;

function dCal(string $texto): NumeroDecimal
{
    return NumeroDecimal::desdeTexto($texto);
}

/**
 * @param  list<string>  $parciales
 */
function calificacionDeMateria(?string $final, array $parciales): ?string
{
    return CalificacionDeMateria::calcular(
        $final === null ? null : dCal($final),
        array_map(dCal(...), $parciales),
    )?->comoCadena();
}

test('RN-02 (§7.9 calificación fila 1): solo final 8.00 da 8.00', function () {
    expect(calificacionDeMateria('8.00', []))->toBe('8.00');
});

test('RN-02 (§7.9 calificación fila 2): la final prevalece sobre los parciales', function () {
    expect(calificacionDeMateria('6.00', ['9.00', '9.00']))->toBe('6.00');
});

test('RN-02 (§7.9 calificación fila 3): parciales 8.00 y 7.00 dan 7.50', function () {
    expect(calificacionDeMateria(null, ['8.00', '7.00']))->toBe('7.50');
});

test('RN-02 (§7.9 calificación fila 4): 22 entre 3 da 7.33', function () {
    expect(calificacionDeMateria(null, ['7.00', '8.00', '7.00']))->toBe('7.33');
});

test('DD-43 (§7.9 calificación fila 5): 7.005 se redondea al medio hacia arriba a 7.01', function () {
    expect(calificacionDeMateria(null, ['7.00', '7.01']))->toBe('7.01');
});

test('RN-02 (§7.9 calificación fila 6): un solo parcial es la calificación', function () {
    expect(calificacionDeMateria(null, ['6.50']))->toBe('6.50');
});

test('RN-02 (§7.9 calificación fila 7): sin final ni parciales no hay calificación', function () {
    expect(CalificacionDeMateria::calcular(null, []))->toBeNull();
});

test('RN-02 (§7.9 calificación fila 8): el cero es una calificación, no una ausencia', function () {
    $calificacion = CalificacionDeMateria::calcular(dCal('0.00'), []);

    expect($calificacion)->not->toBeNull()
        ->and($calificacion->comoCadena())->toBe('0.00');
});

test('RN-02 (§7.9 calificación fila 9): 10.00 es 10.00', function () {
    expect(calificacionDeMateria('10.00', []))->toBe('10.00');
});

test('DD-43: una final con más precisión también se redondea al medio hacia arriba', function () {
    expect(calificacionDeMateria('7.995', []))->toBe('8.00')
        ->and(CalificacionDeMateria::calcular(dCal('7.995'), [])->decimales())->toBe(2);
});

test('DD-43: la media de parciales queda con dos decimales aunque salga exacta', function () {
    expect(CalificacionDeMateria::calcular(null, [dCal('8'), dCal('9')])->decimales())->toBe(2);
});
