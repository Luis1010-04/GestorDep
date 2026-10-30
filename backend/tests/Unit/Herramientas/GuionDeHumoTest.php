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

/*
 * F1-H3-T09: humo-h3.sh se ejecuta completo con un `curl` falso que responde siempre lo mismo y con `php -S` anulado: sin
 * servidor ni base. Lo que se prueba es que el guion real se detiene en el primer paso roto, por código o por cuerpo.
 */

/** @return array{int, string} código de salida y salida de humo-h3.sh cuando toda petición responde `$codigo` con `$cuerpo` */
function ejecutarHumoH3ConCurlFalso(int $codigo, string $cuerpo): array
{
    $falsos = sys_get_temp_dir().'/humo-h3-falsos-'.bin2hex(random_bytes(4));
    mkdir($falsos);
    file_put_contents($falsos.'/curl', "#!/usr/bin/env bash\nfor argumento in \"\$@\"; do [ \"\$argumento\" = -o ] && exit 0; done\nprintf '%s\n%s' \"\$HUMO_FALSO_CUERPO\" \"\$HUMO_FALSO_CODIGO\"\n");
    file_put_contents($falsos.'/php', "#!/usr/bin/env bash\n[ \"\${1:-}\" = -S ] && exit 0\nexec ".escapeshellarg(PHP_BINARY)." \"\$@\"\n");
    chmod($falsos.'/curl', 0755);
    chmod($falsos.'/php', 0755);

    $comando = 'PATH='.escapeshellarg($falsos).':"$PATH" HUMO_FALSO_CODIGO='.$codigo.' HUMO_FALSO_CUERPO='.escapeshellarg($cuerpo)
        .' bash '.escapeshellarg(carpetaDeHumo().'/humo-h3.sh');
    /** @var resource $proceso */
    $proceso = proc_open($comando, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tuberias, dirname(__DIR__, 3));
    $salida = stream_get_contents($tuberias[1]).stream_get_contents($tuberias[2]);
    fclose($tuberias[1]);
    fclose($tuberias[2]);
    $codigoDeSalida = proc_close($proceso);
    unlink($falsos.'/curl');
    unlink($falsos.'/php');
    rmdir($falsos);

    return [$codigoDeSalida, (string) $salida];
}

/** La función `humo_igual` de humo-h3.sh (comparación byte a byte del paso 12), para cargarla junto a lib.sh. */
function funcionHumoIgual(): string
{
    preg_match('/^humo_igual\(\) \{.*?^\}/ms', (string) file_get_contents(carpetaDeHumo().'/humo-h3.sh'), $funcion);

    return $funcion[0] ?? '';
}

test('F1-H3-T09: humo-h3.sh se detiene en el paso 1 con FALLA y código 1 si la salud no responde 200', function () {
    [$codigo, $salida] = ejecutarHumoH3ConCurlFalso(500, '{}');

    expect($codigo)->toBe(1)
        ->and($salida)->toContain('FALLA  paso 1: GET /api/v1/salud -> HTTP 500 (se esperaba 200)')
        ->and($salida)->not->toContain('paso 2')
        ->and($salida)->not->toContain('HUMO H3 OK');
});

test('F1-H3-T09: humo-h3.sh se detiene en el paso 2 con FALLA y código 1 si el cuerpo del inicio de sesión no cumple', function () {
    [$codigo, $salida] = ejecutarHumoH3ConCurlFalso(200, '{"datos":{"id":"x","roles":["docente"]}}');

    expect($codigo)->toBe(1)
        ->and($salida)->toContain('OK     paso 1: GET /api/v1/salud -> HTTP 200')
        ->and($salida)->toContain('FALLA  paso 2: E1 inicia sesión -> HTTP 200 (el cuerpo no cumple')
        ->and($salida)->not->toContain('paso 3')
        ->and($salida)->not->toContain('HUMO H3 OK');
});

test('F1-H3-T09: el paso 12 se detiene con FALLA y código 1 si el 404 no es igual byte a byte al de referencia', function () {
    [$codigo, $salida] = ejecutarConLibreriaDeHumo(funcionHumoIgual()."\n".'HUMO_CODIGO=404; HUMO_CUERPO=\'{"codigo":"recurso_no_encontrado","mensaje":"otro"}\'; humo_igual 12 "ajeno" 404 \'{"codigo":"recurso_no_encontrado","mensaje":"uno"}\'; echo SIGUIO');

    expect($codigo)->toBe(1)
        ->and($salida)->toContain('FALLA  paso 12: ajeno -> HTTP 404 (el cuerpo no es igual byte a byte al de referencia)')
        ->and($salida)->not->toContain('SIGUIO');
});

test('F1-H3-T09: el paso 12 se detiene con FALLA y código 1 si el código no es 404', function () {
    [$codigo, $salida] = ejecutarConLibreriaDeHumo(funcionHumoIgual()."\n".'HUMO_CODIGO=200; HUMO_CUERPO=\'{"datos":{}}\'; humo_igual 12 "ajeno" 404 \'{"codigo":"recurso_no_encontrado"}\'; echo SIGUIO');

    expect($codigo)->toBe(1)
        ->and($salida)->toContain('FALLA  paso 12: ajeno -> HTTP 200 (se esperaba 404)')
        ->and($salida)->not->toContain('SIGUIO');
});

test('F1-H3-T09: el paso 12 imprime OK y el guion sigue si el 404 es igual byte a byte', function () {
    [$codigo, $salida] = ejecutarConLibreriaDeHumo(funcionHumoIgual()."\n".'HUMO_CODIGO=404; HUMO_CUERPO=\'{"codigo":"recurso_no_encontrado"}\'; humo_igual 12 "ajeno" 404 \'{"codigo":"recurso_no_encontrado"}\'; echo SIGUIO');

    expect($codigo)->toBe(0)->and($salida)->toContain('OK     paso 12: ajeno -> HTTP 404')->and($salida)->toContain('SIGUIO');
});

test('F1-H3-T09: humo-h3.sh se detiene ante cualquier error y recorre los 16 pasos de §9 en orden', function () {
    $guion = (string) file_get_contents(carpetaDeHumo().'/humo-h3.sh');
    preg_match_all('/^\s*humo_(?:paso|igual) (\d+) /m', $guion, $pasos);

    expect($guion)->toContain('set -euo pipefail')
        ->and($guion)->toContain('source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"')
        ->and($guion)->toContain('humo_igual 12 ')
        ->and(array_values(array_unique(array_map('intval', $pasos[1]))))->toBe(range(1, 16));
});
