<?php

/*
 * RNF-11, DD-53 (auditoría del Lote E, M-1): la configuración de PHPStan no se debilita. Nivel 7 o más, sin
 * `ignoreErrors`, sin línea base (ni el archivo ni su inclusión), sin exclusiones de rutas, sin `includes` fuera de
 * `vendor/` y analizando las cinco carpetas de siempre. Se lee `phpstan.neon` como texto: el proyecto no instala un
 * analizador de NEON y la configuración es corta y plana.
 */

function rutaDelProyecto(string $relativa = ''): string
{
    return dirname(__DIR__, 3).($relativa === '' ? '' : '/'.$relativa);
}

/** `phpstan.neon` sin comentarios ni líneas en blanco, para que un comentario no oculte ni simule una opción. */
function configuracionDePhpstan(): string
{
    $lineas = file(rutaDelProyecto('phpstan.neon'), FILE_IGNORE_NEW_LINES) ?: [];
    $utiles = array_filter(array_map(fn (string $linea): string => rtrim((string) preg_replace('/#.*$/', '', $linea)), $lineas), fn (string $linea): bool => trim($linea) !== '');

    return implode("\n", $utiles);
}

/**
 * Los elementos de la lista `$clave:` (líneas `- valor` con más sangría que la clave).
 *
 * @return list<string>
 */
function listaDePhpstan(string $configuracion, string $clave): array
{
    if (preg_match('/^(\s*)'.preg_quote($clave, '/').':\s*\n((?:\1\s+-\s*.+\n?)+)/m', $configuracion, $bloque) !== 1) {
        return [];
    }
    preg_match_all('/^\s+-\s*(.+?)\s*$/m', $bloque[2], $elementos);

    return $elementos[1];
}

test('RNF-11 (M-1): PHPStan analiza en nivel 7 o más', function () {
    $encontrado = preg_match('/^\s*level:\s*(\d+)\s*$/m', configuracionDePhpstan(), $nivel);

    expect($encontrado)->toBe(1)
        ->and((int) ($nivel[1] ?? 0))->toBeGreaterThanOrEqual(7);
});

test('RNF-11 (M-1): PHPStan no ignora errores, no excluye rutas ni usa línea base', function () {
    $configuracion = configuracionDePhpstan();

    expect($configuracion)->not->toMatch('/^\s*ignoreErrors\s*:/m')
        ->and($configuracion)->not->toMatch('/^\s*excludePaths\s*:/m')
        ->and($configuracion)->not->toMatch('/^\s*reportUnmatchedIgnoredErrors\s*:\s*false/m')
        ->and($configuracion)->not->toContain('baseline')
        ->and(glob(rutaDelProyecto('phpstan-baseline*')) ?: [])->toBe([]);
});

test('RNF-11 (M-1): PHPStan solo incluye configuración de vendor/ y analiza app, bootstrap, routes, src y tests', function () {
    $configuracion = configuracionDePhpstan();
    $inclusiones = listaDePhpstan($configuracion, 'includes');

    expect($inclusiones)->not->toBe([])
        ->and(array_filter($inclusiones, fn (string $inclusion): bool => ! str_starts_with($inclusion, 'vendor/')))->toBe([])
        ->and(listaDePhpstan($configuracion, 'paths'))->toBe(['app', 'bootstrap', 'routes', 'src', 'tests']);
});
