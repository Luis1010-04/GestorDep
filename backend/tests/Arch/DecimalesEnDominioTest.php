<?php

/*
 * Los valores decimales de negocio (promedios, umbrales, porcentajes) se calculan con NumeroDecimal (DD-54).
 * `float` se prohíbe leyendo los tokens de cada archivo de src/{Contexto}/Dominio/: tipos `float`, conversiones `(float)`,
 * literales con punto o exponente y las funciones de redondeo y de conversión a `float`. Los comentarios no cuentan.
 */

/** @return list<string> rutas de los archivos PHP de src/{Contexto}/Dominio */
function archivosDeDominio(): array
{
    $archivos = [];
    foreach (glob(base_path('src/*/Dominio'), GLOB_ONLYDIR) ?: [] as $carpeta) {
        $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($carpeta, FilesystemIterator::SKIP_DOTS));
        foreach ($iterador as $archivo) {
            /** @var SplFileInfo $archivo */
            if ($archivo->getExtension() === 'php') {
                $archivos[] = $archivo->getPathname();
            }
        }
    }

    return $archivos;
}

/** @return list<string> usos de `float` en un código PHP */
function usosDeFloatEn(string $codigo): array
{
    $prohibidas = ['float', 'double', 'floatval', 'doubleval', 'is_float', 'round', 'floor', 'ceil', 'number_format'];
    $hallazgos = [];

    foreach (token_get_all($codigo) as $token) {
        if (! is_array($token)) {
            continue;
        }

        if ($token[0] === T_DNUMBER || $token[0] === T_DOUBLE_CAST) {
            $hallazgos[] = $token[1];
        } elseif ($token[0] === T_STRING && in_array(strtolower($token[1]), $prohibidas, true)) {
            $hallazgos[] = $token[1];
        }
    }

    return $hallazgos;
}

test('RNF-04: el detector reconoce float y las funciones de redondeo, y deja pasar texto y comentarios', function () {
    $codigo = <<<'PHP'
        <?php
        // float round(1.5)
        /** @param float $a */
        function f(float $a): float { return round($a * 1.5) + (float) '2' + floor(1) + ceil(1) + number_format(1) + 1e3 + intval('1'); }
        $a = 'float round 1.5';
        PHP;

    expect(usosDeFloatEn($codigo))->toBe(['float', 'float', 'round', '1.5', '(float)', 'floor', 'ceil', 'number_format', '1e3']);
});

test('RNF-04: el dominio no usa float ni round, floor, ceil ni number_format', function () {
    $hallazgos = [];
    foreach (archivosDeDominio() as $archivo) {
        foreach (usosDeFloatEn((string) file_get_contents($archivo)) as $uso) {
            $hallazgos[] = substr($archivo, strlen(base_path()) + 1).': '.$uso;
        }
    }

    expect(archivosDeDominio())->not->toBeEmpty()
        ->and($hallazgos)->toBe([]);
});

arch('RNF-04: el dominio no usa round, floor, ceil ni number_format (arch)', function () {
    expect('Src\*\Dominio')->not->toUse(['round', 'floor', 'ceil', 'number_format']);
});
