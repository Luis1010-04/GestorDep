<?php

/*
 * Mutaciones de T08 (docs/reportes/f1-h7-t08.md): las dos pruebas de capacidad pasaron a la primera porque el sistema ya
 * aguantaba el volumen. Cada mutación rompe algo que la prueba de volumen debe detectar: que el seeder pase por el candado
 * de T06 y que los dos comandos terminen con código 0. Mismo esquema que f1-h7-t07-mutaciones.php: corre sobre la copia de
 * `/tmp/mut`, restaura el archivo original tras cada mutación y comprueba que quedó idéntico; termina con 3 si una mutación
 * no se aplica, con 4 si sobrevive alguna y con 5 si el control inicial no pasa.
 *
 * Uso: php f1-h7-t08-mutaciones.php 1 [solo-reemplazos]
 */
const RAIZ = '/tmp/mut';
const ORIGINAL = '/var/www/html';
const CAPACIDAD = 'tests/Feature/Volumen/CapacidadTest.php';

$lote = (int) ($argv[1] ?? 0);
$soloReemplazos = ($argv[2] ?? '') === 'solo-reemplazos';

/*
 * Cada mutación: [id, qué rompe, archivo, texto original, texto mutado, archivo de pruebas, filtro].
 */
$todas = [
    1 => [
        'nombre' => 'capacidad (RNF-06)',
        'control' => [CAPACIDAD],
        'mutaciones' => [
            ['9.01', 'el candado de T06 rechaza a todos los jugadores (prueba que el seeder pasa por él)', 'src/Plantel/Infraestructura/CandadoDeJugadoresPlantel.php',
                'if ($inactivos !== []) {', 'if (true) {', CAPACIDAD, 'terminan con código 0'],
            ['9.02', 'alertas:recalcular termina con código 1', 'src/Alertas/Infraestructura/Consola/RecalcularAlertasComando.php',
                'return $resultado->huboFallas() ? self::FAILURE : self::SUCCESS;', 'return self::FAILURE;', CAPACIDAD, 'terminan con código 0'],
            ['9.03', 'resumenes:conciliar termina con código 1', 'src/Academico/Infraestructura/Consola/ConciliarResumenesComando.php',
                'return self::SUCCESS;', 'return self::FAILURE;', CAPACIDAD, 'terminan con código 0'],
        ],
    ],
];

if (! isset($todas[$lote])) {
    fwrite(STDERR, "Lote inválido: $lote\n");
    exit(2);
}

$datos = $todas[$lote];

function limpio(string $texto): string
{
    return (string) preg_replace('/\e\[[0-9;]*m/', '', $texto);
}

/**
 * @param  list<string>  $archivos
 * @return array{resumen: string, fallan: list<string>}
 */
function correrPest(array $archivos, ?string $filtro = null): array
{
    $argumentos = implode(' ', array_map('escapeshellarg', $archivos)).($filtro === null ? '' : ' --filter='.escapeshellarg($filtro));
    exec('cd '.RAIZ.' && php -d memory_limit=1G vendor/bin/pest '.$argumentos.' 2>&1', $salida);
    $texto = limpio(implode("\n", $salida));
    preg_match('/Tests:\s+(.*)/', $texto, $resumen);
    preg_match_all('/^\s*⨯ (.+?)\s+[\d.]+s?\s*$/mu', $texto, $fallan);

    return ['resumen' => trim($resumen[1] ?? 'sin resumen de pruebas'), 'fallan' => $fallan[1]];
}

function restaurar(string $archivo): void
{
    if (! copy(ORIGINAL.'/'.$archivo, RAIZ.'/'.$archivo)) {
        fwrite(STDERR, "No se pudo restaurar $archivo\n");
        exit(2);
    }
}

echo "Lote $lote: {$datos['nombre']} ".($soloReemplazos ? '(solo se comprueban los reemplazos)' : '')."\n";

if (! $soloReemplazos) {
    $control = correrPest($datos['control']);
    echo "=== control antes: {$control['resumen']}\n";
    if ($control['fallan'] !== [] || ! str_contains($control['resumen'], 'passed') || str_contains($control['resumen'], 'failed')) {
        echo "El control inicial no pasa; no tiene sentido mutar.\n";
        exit(5);
    }
}

$matadas = [];
$sobreviven = [];
foreach ($datos['mutaciones'] as [$id, $que, $archivo, $viejo, $nuevo, $pruebas, $filtro]) {
    restaurar($archivo);
    $ruta = RAIZ.'/'.$archivo;
    $original = (string) file_get_contents($ruta);
    $antes = substr_count($original, $viejo);
    echo "=== $id $que ($archivo)\n";

    if ($antes !== 1) {
        echo "NO APLICADA: el texto a cambiar aparece $antes veces y debe aparecer exactamente una.\n";
        exit(3);
    }

    $mutado = str_replace($viejo, $nuevo, $original);
    file_put_contents($ruta, $mutado);
    $sintaxis = [];
    exec('php -l '.escapeshellarg($ruta).' 2>&1', $sintaxis, $codigoSintaxis);
    if ($mutado === $original || $codigoSintaxis !== 0) {
        echo 'NO APLICADA: '.($mutado === $original ? 'el archivo no cambió' : 'sintaxis inválida: '.implode(' ', $sintaxis))."\n";
        restaurar($archivo);
        exit(3);
    }

    if ($soloReemplazos) {
        echo "reemplazo correcto\n";
        restaurar($archivo);

        continue;
    }

    $resultado = correrPest([$pruebas], $filtro);
    $murio = $resultado['fallan'] !== [] || str_contains($resultado['resumen'], 'failed');
    echo "pruebas ($pruebas --filter=\"$filtro\"): {$resultado['resumen']}\n";
    foreach ($resultado['fallan'] as $prueba) {
        echo "  falla: $prueba\n";
    }
    echo $murio ? "MUERE\n" : "SOBREVIVE\n";
    $murio ? $matadas[] = $id : $sobreviven[] = $id;

    restaurar($archivo);
    if ((string) file_get_contents($ruta) !== (string) file_get_contents(ORIGINAL.'/'.$archivo)) {
        echo "La restauración de $archivo no coincide con el original.\n";
        exit(2);
    }
    echo "restaurado: $archivo idéntico al original\n";
}

if (! $soloReemplazos) {
    $control = correrPest($datos['control']);
    echo "=== control después: {$control['resumen']}\n";
}

echo '=== resultado: mueren '.count($matadas).' ('.implode(', ', $matadas).'); sobreviven '.count($sobreviven).' ('.implode(', ', $sobreviven).")\n";
exit($sobreviven === [] ? 0 : 4);
