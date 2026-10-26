<?php

/**
 * @param  array<string, array{int, int}>  $archivos  ruta relativa => [lineas ejecutables, lineas cubiertas]
 */
function escribirClover(array $archivos): string
{
    $raiz = dirname(__DIR__, 3);
    $xml = '<?xml version="1.0" encoding="UTF-8"?><coverage><project>';

    foreach ($archivos as $ruta => [$total, $cubiertas]) {
        $xml .= sprintf(
            '<file name="%s/%s"><metrics statements="%d" coveredstatements="%d"/></file>',
            $raiz,
            $ruta,
            $total,
            $cubiertas,
        );
    }

    $archivo = tempnam(sys_get_temp_dir(), 'clover');
    file_put_contents($archivo, $xml.'</project></coverage>');

    return $archivo;
}

/**
 * @return array{int, string}
 */
function ejecutarVerificacion(string $reporte): array
{
    $comando = escapeshellarg(PHP_BINARY).' '.escapeshellarg(dirname(__DIR__, 3).'/bin/verificar-cobertura.php').' '.escapeshellarg($reporte);
    /** @var resource $proceso */
    $proceso = proc_open($comando, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tuberias);
    $salida = stream_get_contents($tuberias[1]).stream_get_contents($tuberias[2]);
    fclose($tuberias[1]);
    fclose($tuberias[2]);

    return [proc_close($proceso), $salida];
}

test('HERRAMIENTAS cobertura: falla si Dominio queda en 94.9 %', function () {
    [$codigo, $salida] = ejecutarVerificacion(escribirClover([
        'src/Plantel/Dominio/Equipo.php' => [1000, 949],
        'src/Plantel/Aplicacion/Caso.php' => [100, 100],
        'src/Plantel/Infraestructura/Repo.php' => [100, 100],
    ]));

    expect($codigo)->not->toBe(0)
        ->and($salida)->toContain('Dominio')->toContain('94.9');
});

test('HERRAMIENTAS cobertura: pasa en 95.0 %', function () {
    [$codigo, $salida] = ejecutarVerificacion(escribirClover([
        'src/Plantel/Dominio/Equipo.php' => [100, 95],
        'src/Plantel/Aplicacion/Caso.php' => [100, 85],
        'src/Plantel/Infraestructura/Repo.php' => [100, 70],
    ]));

    expect($codigo)->toBe(0)->and($salida)->toContain('95.0');
});

test('HERRAMIENTAS cobertura: falla si Aplicación queda bajo 85 %', function () {
    [$codigo, $salida] = ejecutarVerificacion(escribirClover([
        'src/Plantel/Dominio/Equipo.php' => [100, 100],
        'src/Plantel/Aplicacion/Caso.php' => [100, 84],
        'src/Plantel/Infraestructura/Repo.php' => [100, 100],
    ]));

    expect($codigo)->not->toBe(0)->and($salida)->toContain('Aplicación');
});

test('HERRAMIENTAS cobertura: falla si Infraestructura y HTTP queda bajo 70 %', function () {
    [$codigo, $salida] = ejecutarVerificacion(escribirClover([
        'src/Plantel/Dominio/Equipo.php' => [100, 100],
        'src/Plantel/Aplicacion/Caso.php' => [100, 100],
        'src/Plantel/Infraestructura/Repo.php' => [50, 40],
        'app/Http/Controllers/SaludController.php' => [50, 20],
    ]));

    expect($codigo)->not->toBe(0)->and($salida)->toContain('Infraestructura y HTTP')->toContain('60.0');
});

test('HERRAMIENTAS cobertura: una capa sin archivos no falla', function () {
    [$codigo, $salida] = ejecutarVerificacion(escribirClover([
        'src/Plantel/Infraestructura/Repo.php' => [100, 100],
    ]));

    expect($codigo)->toBe(0)->and($salida)->toContain('sin código');
});

test('HERRAMIENTAS cobertura: falla si el reporte no existe', function () {
    [$codigo, $salida] = ejecutarVerificacion(sys_get_temp_dir().'/no-existe-clover.xml');

    expect($codigo)->not->toBe(0)->and($salida)->toContain('no existe');
});
