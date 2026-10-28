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
