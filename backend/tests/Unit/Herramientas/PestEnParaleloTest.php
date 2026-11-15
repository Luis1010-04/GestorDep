<?php

/**
 * El paso de Pest del script `verificar` de `composer.json` (DA-101).
 */
function pasoDePestDeVerificar(): string
{
    /** @var array{scripts: array{verificar: list<string>}} $composer */
    $composer = json_decode((string) file_get_contents(dirname(__DIR__, 3).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    $pasos = array_values(array_filter($composer['scripts']['verificar'], fn (string $paso): bool => str_contains($paso, 'vendor/bin/pest')));

    expect($pasos)->toHaveCount(1);

    return $pasos[0];
}

test('HERRAMIENTAS DA-101: make verificar corre Pest en paralelo con un número fijo de procesos, entre 2 y 8', function () {
    $paso = pasoDePestDeVerificar();

    expect($paso)->toContain('--parallel')
        ->and(preg_match('/--processes=(\d+)(\s|$)/', $paso, $coincidencia))->toBe(1)
        // Las bases por proceso son `gestordep_pruebas_1` a `_8`: más procesos no tendrían base.
        ->and((int) ($coincidencia[1] ?? 0))->toBeGreaterThanOrEqual(2)->toBeLessThanOrEqual(8);
});

test('HERRAMIENTAS DA-101: el paso de Pest conserva la cobertura y escribe el Clover que lee la verificación, justo después', function () {
    /** @var array{scripts: array{verificar: list<string>}} $composer */
    $composer = json_decode((string) file_get_contents(dirname(__DIR__, 3).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    $pasos = $composer['scripts']['verificar'];
    $pest = array_key_first(array_filter($pasos, fn (string $paso): bool => str_contains($paso, 'vendor/bin/pest')));

    expect(pasoDePestDeVerificar())->toContain('--coverage ')
        ->toContain('--coverage-html=build/coverage')
        ->toContain('--coverage-clover=build/coverage/clover.xml')
        ->and($pasos[$pest + 1] ?? null)->toBe('@php bin/verificar-cobertura.php build/coverage/clover.xml');
});

test('HERRAMIENTAS DA-101: pcov mide todo el proyecto en el proceso principal y en los procesos hijos', function () {
    $paso = pasoDePestDeVerificar();

    // Sin `pcov.directory=.`, pcov mide solo `src/` y deja `app/` fuera del reporte. Los procesos de paratest no
    // heredan las opciones `-d` del principal: las reciben por `--passthru-php`.
    expect($paso)->toStartWith('@php -d memory_limit=1G -d pcov.directory=. vendor/bin/pest ')
        ->and(preg_match('/--passthru-php="([^"]*)"/', $paso, $coincidencia))->toBe(1)
        ->and($coincidencia[1] ?? '')->toContain("'-d' 'pcov.directory=.'")->toContain("'-d' 'memory_limit=1G'");
});
