<?php

declare(strict_types=1);

/**
 * Verifica que Infection no haya omitido ni descartado mutantes por tiempo. Infection cuenta los mutantes con tiempo
 * agotado (`Timed Out`) y los omitidos (`Skipped`) sin hacer fallar la ejecución mientras el MSI supere el mínimo, de
 * modo que `make verificar` podía pasar con mutantes que nunca se evaluaron (docs/estrategia-pruebas.md §2).
 * Uso: php bin/verificar-mutacion.php <resumen.log>   (el resumen que escribe Infection: build/infection/resumen.log)
 */
$resumen = $argv[1] ?? '';

if ($resumen === '' || ! is_file($resumen)) {
    fwrite(STDERR, "El resumen de Infection no existe: {$resumen}\n");
    exit(1);
}

$contadores = [];
foreach (file($resumen, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $linea) {
    if (preg_match('/^([A-Za-z ]+): (\d+)$/', $linea, $partes) === 1) {
        $contadores[$partes[1]] = (int) $partes[2];
    }
}

foreach (['Total', 'Timed Out', 'Skipped'] as $etiqueta) {
    if (! array_key_exists($etiqueta, $contadores)) {
        fwrite(STDERR, "El resumen de Infection no trae el contador \"{$etiqueta}\": {$resumen}\n");
        exit(1);
    }
}

if ($contadores['Total'] === 0) {
    fwrite(STDERR, "Infection no generó ningún mutante: la mutación no se está ejecutando sobre el dominio.\n");
    exit(1);
}

$fallos = [];
foreach (['Timed Out' => 'agotaron el tiempo', 'Skipped' => 'fueron omitidos'] as $etiqueta => $descripcion) {
    if ($contadores[$etiqueta] > 0) {
        $fallos[] = "{$contadores[$etiqueta]} mutantes {$descripcion} ({$etiqueta})";
    }
}

if ($fallos !== []) {
    fwrite(STDERR, 'Infection no evaluó todos los mutantes: '.implode('; ', $fallos).
        ". Un mutante sin evaluar no demuestra nada; revisa las pruebas del dominio o el tiempo límite de infection.json5.\n");
    exit(1);
}

echo "Mutación completa: {$contadores['Total']} mutantes evaluados, ninguno omitido ni con tiempo agotado.\n";
