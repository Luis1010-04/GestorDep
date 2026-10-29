<?php

use Src\Academico\Dominio\ConteoDeAsistencia;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;

test('RN-05 (§7.9 asistencia fila 1): 10 presentes dan 100.00', function () {
    expect((new ConteoDeAsistencia(10, 0, 0))->porcentaje()->comoCadena())->toBe('100.00');
});

test('RN-05 (§7.9 asistencia fila 2): 8 presentes y 2 faltas dan 80.00', function () {
    expect((new ConteoDeAsistencia(8, 0, 2))->porcentaje()->comoCadena())->toBe('80.00');
});

test('DD-44 (§7.9 asistencia fila 3): la falta justificada cuenta como asistencia', function () {
    expect((new ConteoDeAsistencia(7, 1, 2))->porcentaje()->comoCadena())->toBe('80.00');
});

test('RN-05 (§7.9 asistencia fila 4): 1 presente y 2 faltas dan 33.33', function () {
    expect((new ConteoDeAsistencia(1, 0, 2))->porcentaje()->comoCadena())->toBe('33.33');
});

test('RN-05 (§7.9 asistencia fila 5): 2 presentes y 1 falta dan 66.67', function () {
    expect((new ConteoDeAsistencia(2, 0, 1))->porcentaje()->comoCadena())->toBe('66.67');
});

test('RN-05 (§7.9 asistencia fila 6): sin registros el porcentaje es nulo', function () {
    $conteo = new ConteoDeAsistencia(0, 0, 0);

    expect($conteo->porcentaje())->toBeNull()
        ->and($conteo->registros())->toBe(0);
});

test('RN-05: solo faltas dan 0.00 y el total de registros suma los tres conteos', function () {
    $conteo = new ConteoDeAsistencia(0, 0, 3);

    expect($conteo->porcentaje()->comoCadena())->toBe('0.00')
        ->and((new ConteoDeAsistencia(2, 3, 4))->registros())->toBe(9);
});

test('RN-05: un conteo negativo no es válido', function (int $presentes, int $justificadas, int $faltas) {
    expect(fn () => new ConteoDeAsistencia($presentes, $justificadas, $faltas))
        ->toThrow(ReglaDeNegocioViolada::class, 'conteo_de_asistencia_invalido');
})->with([
    'presentes' => [-1, 0, 0],
    'justificadas' => [0, -1, 0],
    'faltas' => [0, 0, -1],
]);

test('RN-05: el cero es un conteo válido', function () {
    expect((new ConteoDeAsistencia(0, 0, 0))->registros())->toBe(0);
});
