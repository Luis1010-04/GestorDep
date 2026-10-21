<?php

use Src\Compartido\Dominio\Identificador;

const PATRON_UUID_V7 = '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

test('F1-H0-T06: un identificador generado es un UUID v7 valido', function () {
    expect(Identificador::generar()->valor())->toMatch(PATRON_UUID_V7);
});

test('F1-H0-T06: el identificador generado lleva la marca de tiempo actual en milisegundos', function () {
    $antes = (int) floor(microtime(true) * 1000);
    $valor = Identificador::generar()->valor();
    $despues = (int) floor(microtime(true) * 1000);

    $marca = (int) hexdec(substr(str_replace('-', '', $valor), 0, 12));

    expect($marca)->toBeGreaterThanOrEqual($antes)->toBeLessThanOrEqual($despues);
});

test('F1-H0-T06: dos identificadores generados son distintos', function () {
    expect(Identificador::generar()->igualA(Identificador::generar()))->toBeFalse();
});

test('F1-H0-T06: identificadores generados en milisegundos distintos se ordenan por tiempo', function () {
    $primero = Identificador::generar()->valor();
    usleep(2000);
    $segundo = Identificador::generar()->valor();

    expect(strcmp($primero, $segundo))->toBeLessThan(0);
});

test('F1-H0-T06: acepta un UUID v7 valido desde texto y lo normaliza a minusculas', function () {
    $identificador = Identificador::desdeTexto('0192F1A8-3B4C-7D5E-8F60-1A2B3C4D5E6F');

    expect($identificador->valor())->toBe('0192f1a8-3b4c-7d5e-8f60-1a2b3c4d5e6f');
});

test('F1-H0-T06: rechaza un identificador invalido', function (string $texto) {
    Identificador::desdeTexto($texto);
})->throws(InvalidArgumentException::class)->with([
    'texto cualquiera' => 'no-es-un-uuid',
    'vacio' => '',
    'uuid v4' => '9b2e4c1a-5f3d-4a8b-9c7e-1d2f3a4b5c6d',
    'variante incorrecta' => '0192f1a8-3b4c-7d5e-cf60-1a2b3c4d5e6f',
    'sin guiones' => '0192f1a83b4c7d5e8f601a2b3c4d5e6f',
]);

test('F1-H0-T06: dos identificadores con el mismo valor son iguales', function () {
    $valor = Identificador::generar()->valor();

    expect(Identificador::desdeTexto($valor)->igualA(Identificador::desdeTexto(strtoupper($valor))))->toBeTrue();
});
