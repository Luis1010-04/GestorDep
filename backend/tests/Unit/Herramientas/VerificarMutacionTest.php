<?php

/**
 * @param  array<string, int>  $contadores  etiqueta del resumen de Infection => cantidad
 */
function escribirResumenDeMutacion(array $contadores): string
{
    $contenido = '';
    foreach ($contadores as $etiqueta => $cantidad) {
        $contenido .= "{$etiqueta}: {$cantidad}\n";
    }

    $archivo = tempnam(sys_get_temp_dir(), 'resumen');
    file_put_contents($archivo, $contenido);

    return $archivo;
}

/** @return array<string, int> */
function resumenDeMutacionLimpio(): array
{
    return [
        'Total' => 335, 'Killed by Test Framework' => 335, 'Killed by Static Analysis' => 0, 'Errored' => 0,
        'Syntax Errors' => 0, 'Escaped' => 0, 'Timed Out' => 0, 'Skipped' => 0, 'Ignored' => 0, 'Not Covered' => 0,
    ];
}

/**
 * @return array{int, string}
 */
function ejecutarVerificacionDeMutacion(string $resumen): array
{
    $comando = escapeshellarg(PHP_BINARY).' '.escapeshellarg(dirname(__DIR__, 3).'/bin/verificar-mutacion.php').' '.escapeshellarg($resumen);
    /** @var resource $proceso */
    $proceso = proc_open($comando, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tuberias);
    $salida = stream_get_contents($tuberias[1]).stream_get_contents($tuberias[2]);
    fclose($tuberias[1]);
    fclose($tuberias[2]);

    return [proc_close($proceso), $salida];
}

test('HERRAMIENTAS mutación: pasa si Infection no omitió ni descartó ningún mutante por tiempo', function () {
    [$codigo, $salida] = ejecutarVerificacionDeMutacion(escribirResumenDeMutacion(resumenDeMutacionLimpio()));

    expect($codigo)->toBe(0)->and($salida)->toContain('335');
});

test('HERRAMIENTAS mutación: falla si un solo mutante agotó el tiempo', function () {
    [$codigo, $salida] = ejecutarVerificacionDeMutacion(escribirResumenDeMutacion([...resumenDeMutacionLimpio(), 'Killed by Test Framework' => 334, 'Timed Out' => 1]));

    expect($codigo)->not->toBe(0)->and($salida)->toContain('Timed Out')->toContain('1');
});

test('HERRAMIENTAS mutación: falla si Infection omitió mutantes (Skipped)', function () {
    [$codigo, $salida] = ejecutarVerificacionDeMutacion(escribirResumenDeMutacion([...resumenDeMutacionLimpio(), 'Killed by Test Framework' => 285, 'Skipped' => 50]));

    expect($codigo)->not->toBe(0)->and($salida)->toContain('Skipped')->toContain('50');
});

test('HERRAMIENTAS mutación: informa los dos problemas cuando hay mutantes omitidos y con tiempo agotado', function () {
    [$codigo, $salida] = ejecutarVerificacionDeMutacion(escribirResumenDeMutacion([...resumenDeMutacionLimpio(), 'Timed Out' => 2, 'Skipped' => 3]));

    expect($codigo)->not->toBe(0)->and($salida)->toContain('Timed Out')->toContain('Skipped');
});

test('HERRAMIENTAS mutación: falla si el resumen no existe', function () {
    [$codigo, $salida] = ejecutarVerificacionDeMutacion(sys_get_temp_dir().'/no-existe-'.uniqid());

    expect($codigo)->not->toBe(0)->and($salida)->toContain('resumen');
});

test('HERRAMIENTAS mutación: falla si el resumen no trae los contadores', function () {
    [$codigo] = ejecutarVerificacionDeMutacion(escribirResumenDeMutacion(['Total' => 335]));

    expect($codigo)->not->toBe(0);
});

test('HERRAMIENTAS mutación: falla si Infection no generó ningún mutante', function () {
    [$codigo, $salida] = ejecutarVerificacionDeMutacion(escribirResumenDeMutacion([...resumenDeMutacionLimpio(), 'Total' => 0, 'Killed by Test Framework' => 0]));

    expect($codigo)->not->toBe(0)->and($salida)->toContain('ningún mutante');
});

/**
 * `infection.json5` sin sus comentarios de línea, decodificado (el archivo no usa otras extensiones de JSON5).
 *
 * @return array<string, mixed>
 */
function configuracionDeInfection(): array
{
    $lineas = preg_grep('/^\s*\/\//', file(dirname(__DIR__, 3).'/infection.json5', FILE_IGNORE_NEW_LINES) ?: [], PREG_GREP_INVERT);

    /** @var array<string, mixed> */
    return json_decode(implode("\n", $lineas ?: []), true, flags: JSON_THROW_ON_ERROR);
}

test('HERRAMIENTAS mutación (M-10 a): Infection escribe el resumen en la ruta que lee la verificación, justo después en el script mutacion', function () {
    /** @var array{scripts: array{mutacion: list<string>}} $composer */
    $composer = json_decode((string) file_get_contents(dirname(__DIR__, 3).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    $pasos = $composer['scripts']['mutacion'];
    $infection = array_key_first(array_filter($pasos, fn (string $paso): bool => str_contains($paso, 'vendor/bin/infection')));

    // Si el resumen dejara de escribirse aquí, la verificación podría leer uno viejo de otra corrida.
    expect(configuracionDeInfection()['logs'] ?? null)->toMatchArray(['summary' => 'build/infection/resumen.log'])
        ->and($infection)->not->toBeNull()
        ->and($pasos[$infection + 1] ?? null)->toBe('@php bin/verificar-mutacion.php build/infection/resumen.log');
});

test('HERRAMIENTAS mutación (F1-H3-FIX, enmienda a DD-53): make verificar ya no corre Infection; make mutacion sí', function () {
    /** @var array{scripts: array{verificar: list<string>, mutacion?: list<string>}} $composer */
    $composer = json_decode((string) file_get_contents(dirname(__DIR__, 3).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    $verificar = implode("\n", $composer['scripts']['verificar']);

    expect($verificar)->not->toContain('vendor/bin/infection')
        ->and($verificar)->not->toContain('verificar-mutacion')
        ->and($composer['scripts'])->toHaveKey('mutacion');
});

test('HERRAMIENTAS mutación (M-10 b): los mutantes con error se cuentan como eliminados, como hace Infection, y no hacen fallar', function () {
    [$codigo] = ejecutarVerificacionDeMutacion(escribirResumenDeMutacion([...resumenDeMutacionLimpio(), 'Killed by Test Framework' => 332, 'Errored' => 3]));

    expect($codigo)->toBe(0);
});

test('HERRAMIENTAS mutación (M-10 b): los ignorados no hacen fallar, y la configuración no ignora ningún mutador', function () {
    [$codigo] = ejecutarVerificacionDeMutacion(escribirResumenDeMutacion([...resumenDeMutacionLimpio(), 'Ignored' => 4]));
    $configuracion = (string) json_encode(configuracionDeInfection());

    // `Ignored` solo puede salir de un `ignore` o `ignoreSourceCodeByRegex` en `mutators`: aquí no hay ninguno.
    expect($codigo)->toBe(0)
        ->and(configuracionDeInfection())->not->toHaveKey('mutators')
        ->and($configuracion)->not->toContain('"ignore');
});
