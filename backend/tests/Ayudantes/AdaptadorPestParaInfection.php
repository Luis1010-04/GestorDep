#!/usr/bin/env php
<?php

use PHPUnit\Runner\Version;

/**
 * Ejecutable que Infection usa en lugar de `vendor/bin/phpunit` (infection.json5, `phpUnit.customPath`).
 *
 * Infection solo conoce la salida de PHPUnit y las pruebas del proyecto son de Pest. Este guion cierra tres brechas
 * y no cambia qué se ejecuta ni cómo se decide si una prueba pasa (el código de salida de Pest):
 *  1. `--version`: informa la versión de PHPUnit, que es la que Infection usa para generar su configuración.
 *  2. Salida: agrega la línea `OK (...)` o `FAILURES!` que Infection busca para saber si las pruebas pasaron.
 *  3. JUnit: Pest nombra las clases `Tests\...` en el JUnit y `P\Tests\...` en la cobertura; se alinea el JUnit.
 */
require_once dirname(__DIR__, 2).'/vendor/autoload.php';

$argumentos = array_slice($argv, 1);

if (in_array('--version', $argumentos, true)) {
    echo 'PHPUnit '.Version::id()." by Sebastian Bergmann and contributors.\n";
    exit(0);
}

$comando = array_merge([PHP_BINARY, dirname(__DIR__, 2).'/vendor/bin/pest'], $argumentos);
$proceso = proc_open($comando, [0 => STDIN, 1 => STDOUT, 2 => STDERR], $tuberias);

if (! is_resource($proceso)) {
    fwrite(STDERR, "No se pudo ejecutar Pest.\n");
    exit(1);
}

$codigo = proc_close($proceso);

foreach ($argumentos as $argumento) {
    if (str_starts_with($argumento, '--log-junit=') && is_file($ruta = substr($argumento, strlen('--log-junit=')))) {
        $contenido = (string) file_get_contents($ruta);
        file_put_contents($ruta, str_replace(['<testsuite name="Tests\\', ' class="Tests\\'], ['<testsuite name="P\\Tests\\', ' class="P\\Tests\\'], $contenido));
    }
}

echo $codigo === 0 ? "\nOK (1 test, 1 assertion)\n" : "\nFAILURES!\n";
exit($codigo);
