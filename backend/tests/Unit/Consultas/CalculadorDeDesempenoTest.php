<?php

use Src\Compartido\Dominio\NumeroDecimal;
use Src\Consultas\Aplicacion\CalculadorDeDesempeno;

/**
 * Orden por desempeño (RN-18, DD-12, DA-95, DA-118): de mayor a menor promedio; sin promedio (datos insuficientes) al final;
 * los empates conservan el orden de entrada.
 *
 * @param  list<array{string, string|null}>  $jugadores  nombre y promedio de la ventana
 * @return list<string> los nombres, en orden
 */
function desempenoOrdenar(array $jugadores): array
{
    $orden = (new CalculadorDeDesempeno)->ordenar(
        $jugadores,
        fn (array $jugador): ?NumeroDecimal => $jugador[1] === null ? null : NumeroDecimal::desdeTexto($jugador[1]),
    );

    return array_map(fn (array $jugador): string => $jugador[0], $orden);
}

test('RN-18 (§7.5 fila 1): A 8.0, B 6.5, C 7.2 se ordenan A, C, B', function () {
    expect(desempenoOrdenar([['A', '8.0'], ['B', '6.5'], ['C', '7.2']]))->toBe(['A', 'C', 'B']);
});

test('RN-18 (§7.5 fila 2): A 7.0, B datos insuficientes, C 5.0 se ordenan A, C, B', function () {
    expect(desempenoOrdenar([['A', '7.0'], ['B', null], ['C', '5.0']]))->toBe(['A', 'C', 'B']);
});

test('RN-18 (§7.5 fila 3): A 7.0 y B 7.0 conservan el orden de entrada', function () {
    expect(desempenoOrdenar([['A', '7.0'], ['B', '7.0']]))->toBe(['A', 'B'])
        ->and(desempenoOrdenar([['B', '7.0'], ['A', '7.0']]))->toBe(['B', 'A']);
});

test('RN-18 (DA-118): los jugadores sin promedio van al final y conservan entre sí el orden de entrada', function () {
    expect(desempenoOrdenar([['A', null], ['B', '5.0'], ['C', null], ['D', '9.0']]))->toBe(['D', 'B', 'A', 'C']);
});

test('RN-18: el promedio se compara como decimal, no como texto', function () {
    expect(desempenoOrdenar([['A', '10.00'], ['B', '9.50'], ['C', '9.5']]))->toBe(['A', 'B', 'C']);
});

test('RN-18: una lista vacía queda vacía', function () {
    expect(desempenoOrdenar([]))->toBe([]);
});
