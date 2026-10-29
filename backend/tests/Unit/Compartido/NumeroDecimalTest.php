<?php

use App\Http\Resources\DecimalComoCadena;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Dominio\NumeroDecimalInvalido;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;

function decimal(string $texto): NumeroDecimal
{
    return NumeroDecimal::desdeTexto($texto);
}

test('RN-04 (§7.12 fila 3): 7.495 redondeado a dos decimales es 7.50', function () {
    expect(decimal('7.495')->redondear(2)->comoCadena())->toBe('7.50')
        ->and(decimal('7.495')->comoCadena())->toBe('7.50');
});

test('RN-04: el redondeo es al medio hacia arriba, también con negativos', function (string $entrada, string $esperado) {
    expect(decimal($entrada)->comoCadena())->toBe($esperado);
})->with([
    'justo debajo del medio' => ['7.494', '7.49'],
    'el medio exacto sube' => ['7.495', '7.50'],
    'justo encima del medio' => ['7.496', '7.50'],
    'cinco milésimas' => ['0.005', '0.01'],
    'cuatro milésimas' => ['0.004', '0.00'],
    'negativo en el medio se aleja del cero' => ['-7.495', '-7.50'],
    'negativo bajo el medio' => ['-7.494', '-7.49'],
    'ya con dos decimales' => ['8.50', '8.50'],
    'sin decimales' => ['8', '8.00'],
    'un decimal' => ['8.5', '8.50'],
    'acarreo al redondear' => ['9.999', '10.00'],
]);

test('RN-02 (§7.12 fila 4): 1 entre 3 es 0.33 y 2 entre 3 es 0.67', function () {
    expect(decimal('1')->dividir(decimal('3'), 2)->comoCadena())->toBe('0.33')
        ->and(decimal('2')->dividir(decimal('3'), 2)->comoCadena())->toBe('0.67');
});

test('RN-02: la división redondea al medio hacia arriba con el dígito siguiente', function (string $a, string $b, int $escala, string $esperado) {
    expect(decimal($a)->dividir(decimal($b), $escala)->comoCadena($escala))->toBe($esperado);
})->with([
    'termina exactamente en el medio' => ['1', '8', 2, '0.13'],
    'cociente exacto' => ['10', '4', 2, '2.50'],
    'negativo' => ['-1', '3', 2, '-0.33'],
    'divisor negativo' => ['2', '-3', 2, '-0.67'],
    'escala cero' => ['5', '2', 0, '3'],
    'escala tres' => ['2', '3', 3, '0.667'],
]);

test('RN-02: dividir entre cero lanza una regla de negocio violada', function () {
    try {
        decimal('1')->dividir(decimal('0.00'), 2);
        $codigo = null;
    } catch (ReglaDeNegocioViolada $e) {
        $codigo = $e->codigo();
    }

    expect($codigo)->toBe('division_entre_cero');
});

test('RNF-04: una escala negativa es inválida al dividir, al redondear y al escribir', function () {
    expect(fn () => decimal('1')->dividir(decimal('3'), -1))->toThrow(InvalidArgumentException::class, 'escala_decimal_invalida')
        ->and(fn () => decimal('1')->redondear(-1))->toThrow(InvalidArgumentException::class, 'escala_decimal_invalida')
        ->and(fn () => decimal('1')->comoCadena(-2))->toThrow(InvalidArgumentException::class, 'escala_decimal_invalida');
});

test('RNF-04 (§7.12 fila 5): la suma, la resta y la multiplicación son exactas', function () {
    expect(decimal('0.1')->sumar(decimal('0.2'))->comoCadena(1))->toBe('0.3')
        ->and(decimal('0.1')->sumar(decimal('0.2'))->igualA(decimal('0.3')))->toBeTrue()
        ->and(decimal('8.50')->sumar(decimal('1.255'))->comoCadena(3))->toBe('9.755')
        ->and(decimal('9.00')->restar(decimal('0.1'))->comoCadena(2))->toBe('8.90')
        ->and(decimal('0.3')->restar(decimal('0.1'))->igualA(decimal('0.2')))->toBeTrue()
        ->and(decimal('1')->restar(decimal('3'))->comoCadena())->toBe('-2.00')
        ->and(decimal('1.5')->multiplicar(decimal('1.25'))->comoCadena(3))->toBe('1.875')
        ->and(decimal('0.1')->multiplicar(decimal('3'))->igualA(decimal('0.3')))->toBeTrue()
        ->and(decimal('-2')->multiplicar(decimal('2.5'))->comoCadena())->toBe('-5.00');
});

test('RNF-04 (§7.12 fila 5): la media es exacta y se redondea al medio hacia arriba', function () {
    expect(NumeroDecimal::media([decimal('8.00'), decimal('9.00')])->comoCadena())->toBe('8.50')
        ->and(NumeroDecimal::media([decimal('7.00'), decimal('8.00'), decimal('7.00')])->comoCadena())->toBe('7.33')
        ->and(NumeroDecimal::media([decimal('7.00'), decimal('7.01')])->comoCadena())->toBe('7.01')
        ->and(NumeroDecimal::media([decimal('7.49'), decimal('7.50')])->comoCadena())->toBe('7.50')
        ->and(NumeroDecimal::media([decimal('6.00')])->comoCadena())->toBe('6.00')
        ->and(NumeroDecimal::media([decimal('1'), decimal('2')], 0)->comoCadena(0))->toBe('2')
        ->and(NumeroDecimal::media([decimal('1'), decimal('2')], 3)->comoCadena(3))->toBe('1.500');
});

test('RNF-04: la media de una lista vacía lanza una regla de negocio violada', function () {
    try {
        NumeroDecimal::media([]);
        $codigo = null;
    } catch (ReglaDeNegocioViolada $e) {
        $codigo = $e->codigo();
    }

    expect($codigo)->toBe('media_sin_valores');
});

test('RNF-04 (§7.12 fila 5): la comparación es exacta y no depende de los decimales escritos', function () {
    $siete = decimal('7.00');

    expect($siete->comparar(decimal('7')))->toBe(0)
        ->and($siete->comparar(decimal('7.001')))->toBe(-1)
        ->and($siete->comparar(decimal('6.999')))->toBe(1)
        ->and($siete->igualA(decimal('7.0')))->toBeTrue()
        ->and($siete->igualA(decimal('7.01')))->toBeFalse()
        ->and($siete->menorQue(decimal('7.01')))->toBeTrue()
        ->and($siete->menorQue(decimal('7.00')))->toBeFalse()
        ->and($siete->menorQue(decimal('6.99')))->toBeFalse()
        ->and($siete->menorOIgualQue(decimal('7.00')))->toBeTrue()
        ->and($siete->menorOIgualQue(decimal('7.01')))->toBeTrue()
        ->and($siete->menorOIgualQue(decimal('6.99')))->toBeFalse()
        ->and($siete->mayorQue(decimal('6.99')))->toBeTrue()
        ->and($siete->mayorQue(decimal('7.00')))->toBeFalse()
        ->and($siete->mayorQue(decimal('7.01')))->toBeFalse()
        ->and($siete->mayorOIgualQue(decimal('7.00')))->toBeTrue()
        ->and($siete->mayorOIgualQue(decimal('6.99')))->toBeTrue()
        ->and($siete->mayorOIgualQue(decimal('7.01')))->toBeFalse();
});

test('RNF-04: una diferencia más pequeña que dos decimales se distingue al comparar', function () {
    expect(decimal('7.495')->menorQue(decimal('7.50')))->toBeTrue()
        ->and(decimal('7.4950001')->mayorQue(decimal('7.495')))->toBeTrue();
});

test('RNF-04 (§7.12 fila 6): un texto que no es decimal lanza la excepción de dominio', function (string $texto) {
    expect(fn () => NumeroDecimal::desdeTexto($texto))->toThrow(NumeroDecimalInvalido::class);
})->with(['abc', '1,5', '', ' ', ' 1', '1 ', '1.', '.5', '+1', '1e3', '--1', '1.2.3', "1\n", '8.5.0']);

test('RNF-04: la excepción de texto inválido es una regla de negocio con código estable', function () {
    try {
        NumeroDecimal::desdeTexto('abc');
        $codigo = null;
    } catch (ReglaDeNegocioViolada $e) {
        $codigo = $e->codigo();
    }

    expect($codigo)->toBe('numero_decimal_invalido');
});

test('RNF-04: el texto válido se normaliza sin perder decimales', function (string $entrada, int $decimales, string $esperado) {
    expect(decimal($entrada)->comoCadena($decimales))->toBe($esperado)
        ->and(decimal($entrada)->decimales())->toBe($decimales);
})->with([
    'ceros a la izquierda' => ['007.50', 2, '7.50'],
    'cero con signo' => ['-0.00', 2, '0.00'],
    'cero entero' => ['0', 0, '0'],
    'negativo' => ['-3.25', 2, '-3.25'],
    'muchos decimales' => ['1.123456789', 9, '1.123456789'],
]);

test('RNF-04 (§7.12 fila 7): el dominio acepta más de dos decimales y los informa para que la API los rechace', function () {
    expect(decimal('7.495')->decimales())->toBe(3)
        ->and(decimal('8.50')->decimales())->toBe(2)
        ->and(decimal('8.5')->decimales())->toBe(1)
        ->and(decimal('8')->decimales())->toBe(0)
        ->and(decimal('8.555')->comoCadena())->toBe('8.56');
});

test('RNF-04: la cadena lleva exactamente los decimales pedidos', function () {
    expect(decimal('7.5')->comoCadena(3))->toBe('7.500')
        ->and(decimal('7.5')->comoCadena(0))->toBe('8')
        ->and(decimal('7.4')->comoCadena(0))->toBe('7')
        ->and(decimal('7.5')->comoCadena(1))->toBe('7.5');
});

test('RNF-04: el ayudante de la API serializa el decimal como cadena con dos decimales o nulo', function () {
    expect(DecimalComoCadena::de(decimal('7.495')))->toBe('7.50')
        ->and(DecimalComoCadena::de(decimal('8')))->toBe('8.00')
        ->and(DecimalComoCadena::de(null))->toBeNull();
});
