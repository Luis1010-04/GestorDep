<?php

/*
 * F1-H2-T15: el guion de humo de H2 se detiene con un código distinto de 0 ante el primer paso roto. Se ejecuta `bash` sobre
 * `scripts/humo/lib.sh` sin servidor: lo que se prueba es el mecanismo de parada y que `humo-h2.sh` recorre los 16 pasos de
 * docs/hitos/f1-h2.md §9.
 */

/** Dentro del contenedor los guiones se montan en /scripts; fuera (integración continua), están junto a backend/. */
function carpetaDeHumo(): string
{
    return is_dir('/scripts/humo') ? '/scripts/humo' : dirname(__DIR__, 4).'/scripts/humo';
}

/** @return array{int, string} código de salida y salida de `bash -c` con lib.sh cargado */
function ejecutarConLibreriaDeHumo(string $guion): array
{
    $comando = 'bash -c '.escapeshellarg('source '.escapeshellarg(carpetaDeHumo().'/lib.sh').'; '.$guion);
    /** @var resource $proceso */
    $proceso = proc_open($comando, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tuberias);
    $salida = stream_get_contents($tuberias[1]).stream_get_contents($tuberias[2]);
    fclose($tuberias[1]);
    fclose($tuberias[2]);

    return [proc_close($proceso), (string) $salida];
}

test('F1-H2-T15: un paso con un código HTTP distinto del esperado detiene el guion con FALLA y código 1', function () {
    [$codigo, $salida] = ejecutarConLibreriaDeHumo('HUMO_CODIGO=500; HUMO_CUERPO="{}"; humo_paso 3 "crea un periodo" 201; echo SIGUIO');

    expect($codigo)->toBe(1)
        ->and($salida)->toContain('FALLA  paso 3: crea un periodo -> HTTP 500 (se esperaba 201)')
        ->and($salida)->not->toContain('SIGUIO');
});

test('F1-H2-T15: un paso cuyo cuerpo no cumple la condición detiene el guion con FALLA y código 1', function () {
    [$codigo, $salida] = ejecutarConLibreriaDeHumo('HUMO_CODIGO=200; HUMO_CUERPO=\'{"datos":{"nivel_riesgo":"en_riesgo"}}\'; humo_paso 10 "situación" 200 \'$d["datos"]["nivel_riesgo"] === "sin_riesgo"\'; echo SIGUIO');

    expect($codigo)->toBe(1)
        ->and($salida)->toContain('FALLA  paso 10: situación -> HTTP 200 (el cuerpo no cumple')
        ->and($salida)->not->toContain('SIGUIO');
});

test('F1-H2-T15: un paso correcto imprime OK y el guion sigue', function () {
    [$codigo, $salida] = ejecutarConLibreriaDeHumo('HUMO_CODIGO=200; HUMO_CUERPO=\'{"datos":{"nivel_riesgo":"sin_riesgo"}}\'; humo_paso 10 "situación" 200 \'$d["datos"]["nivel_riesgo"] === "sin_riesgo"\'; echo SIGUIO');

    expect($codigo)->toBe(0)->and($salida)->toContain('OK     paso 10: situación -> HTTP 200')->and($salida)->toContain('SIGUIO');
});

test('F1-H2-T15: humo-h2.sh se detiene ante cualquier error, declara la cola sync y recorre los 16 pasos de §9', function () {
    $guion = (string) file_get_contents(carpetaDeHumo().'/humo-h2.sh');
    preg_match_all('/^\s*humo_paso (\d+) /m', $guion, $pasos);

    expect($guion)->toContain('set -euo pipefail')
        ->and($guion)->toContain('export QUEUE_CONNECTION=sync')
        ->and($guion)->toContain('source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"')
        ->and(array_values(array_unique(array_map('intval', $pasos[1]))))->toBe(range(1, 16));
});
