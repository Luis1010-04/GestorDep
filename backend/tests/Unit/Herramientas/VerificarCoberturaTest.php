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

test('PT-02: si el clover no existe, falla con «El reporte Clover no existe»', function () {
    $reporte = sys_get_temp_dir().'/no-existe-clover-'.uniqid().'.xml';

    [$codigo, $salida] = ejecutarVerificacion($reporte);

    expect($codigo)->toBe(1)->and($salida)->toBe("El reporte Clover no existe: {$reporte}\n");
});

test('PT-02: el guion borra el clover tras leerlo, apruebe o no los umbrales', function (int $cubiertas, int $esperado) {
    $reporte = escribirClover([
        'src/Plantel/Dominio/Equipo.php' => [100, $cubiertas],
        'src/Plantel/Aplicacion/Caso.php' => [100, 100],
        'src/Plantel/Infraestructura/Repo.php' => [100, 100],
    ]);

    [$codigo] = ejecutarVerificacion($reporte);

    expect($codigo)->toBe($esperado)->and(file_exists($reporte))->toBeFalse();
})->with([
    'aprueba' => [100, 0],
    'bajo el umbral' => [90, 1],
]);

test('PT-02: un clover que no se puede leer también se borra', function () {
    $reporte = tempnam(sys_get_temp_dir(), 'clover');
    file_put_contents($reporte, 'esto no es XML');

    [$codigo, $salida] = ejecutarVerificacion($reporte);

    expect($codigo)->toBe(1)->and($salida)->toContain('no se puede leer')->and(file_exists($reporte))->toBeFalse();
});

test('PT-02: una segunda verificación sin regenerar el clover falla, aunque la primera aprobara', function () {
    $reporte = escribirClover([
        'src/Plantel/Dominio/Equipo.php' => [100, 100],
        'src/Plantel/Aplicacion/Caso.php' => [100, 100],
        'src/Plantel/Infraestructura/Repo.php' => [100, 100],
    ]);

    [$primera] = ejecutarVerificacion($reporte);
    [$segunda, $salida] = ejecutarVerificacion($reporte);

    expect($primera)->toBe(0)
        ->and($segunda)->toBe(1)
        ->and($salida)->toBe("El reporte Clover no existe: {$reporte}\n");
});
