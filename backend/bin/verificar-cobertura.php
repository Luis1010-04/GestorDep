<?php

declare(strict_types=1);

/**
 * Verifica la cobertura de lineas por capa a partir de un reporte Clover.
 * Umbrales: docs/estrategia-pruebas.md §4. Uso: php bin/verificar-cobertura.php <clover.xml>. El reporte se borra tras leerlo (PT-02).
 */
$capas = [
    'Dominio' => ['umbral' => 95, 'patron' => '#^src/[^/]+/Dominio/#'],
    'Aplicación' => ['umbral' => 85, 'patron' => '#^src/[^/]+/Aplicacion/#'],
    'Infraestructura y HTTP' => ['umbral' => 70, 'patron' => '#^(src/[^/]+/Infraestructura/|app/)#'],
];

$reporte = $argv[1] ?? '';

if ($reporte === '' || ! is_file($reporte)) {
    fwrite(STDERR, "El reporte Clover no existe: {$reporte}\n");
    exit(1);
}

// PT-02: el reporte se consume. Se lee y se borra antes de validarlo, también si luego falla: una corrida que no
// regenere el clover no puede aprobar con uno viejo, porque la siguiente verificación lo encontrará ausente.
$contenido = (string) file_get_contents($reporte);
unlink($reporte);

$xml = @simplexml_load_string($contenido);

if ($xml === false) {
    fwrite(STDERR, "El reporte Clover no se puede leer: {$reporte}\n");
    exit(1);
}

$raiz = str_replace('\\', '/', dirname(__DIR__)).'/';
$totales = array_fill_keys(array_keys($capas), ['ejecutables' => 0, 'cubiertas' => 0]);

foreach ($xml->xpath('//file') ?: [] as $archivo) {
    $ruta = str_replace('\\', '/', (string) $archivo['name']);
    $relativa = str_starts_with($ruta, $raiz) ? substr($ruta, strlen($raiz)) : $ruta;

    foreach ($capas as $nombre => $capa) {
        if (preg_match($capa['patron'], $relativa) === 1) {
            $totales[$nombre]['ejecutables'] += (int) $archivo->metrics['statements'];
            $totales[$nombre]['cubiertas'] += (int) $archivo->metrics['coveredstatements'];
            break;
        }
    }
}

$fallo = false;
echo sprintf("%-24s %10s %8s  %s\n", 'Capa', 'Cobertura', 'Umbral', 'Estado');

foreach ($capas as $nombre => $capa) {
    ['ejecutables' => $ejecutables, 'cubiertas' => $cubiertas] = $totales[$nombre];

    if ($ejecutables === 0) {
        echo sprintf("%-24s %10s %7d%%  %s\n", $nombre, '-', $capa['umbral'], 'sin código');

        continue;
    }

    $aprueba = $cubiertas * 100 >= $capa['umbral'] * $ejecutables;
    $fallo = $fallo || ! $aprueba;
    // Truncado a un decimal para que 94.99 % no se muestre como 95.0 %.
    $porcentaje = intdiv($cubiertas * 1000, $ejecutables) / 10;

    echo sprintf(
        "%-24s %9.1f%% %7d%%  %s\n",
        $nombre,
        $porcentaje,
        $capa['umbral'],
        $aprueba ? 'ok' : 'BAJO EL UMBRAL',
    );
}

exit($fallo ? 1 : 0);
