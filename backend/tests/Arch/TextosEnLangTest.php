<?php

/*
 * Ningun texto visible al usuario se escribe en el codigo (docs/convenciones-api.md, seccion 5).
 * Se considera texto visible una cadena que empieza con mayuscula seguida de mas palabras,
 * o que lleva tildes, ene o signos de apertura. Los codigos estables y el SQL van en minusculas.
 */

/** @return list<string> */
function textosVisiblesEn(string $codigo): array
{
    $textos = [];
    foreach (token_get_all($codigo) as $token) {
        if (! is_array($token) || ! in_array($token[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)) {
            continue;
        }

        $texto = trim($token[1], "'\"");
        if (preg_match('/^[\p{Lu}¿¡]\p{L}*\s+\S/u', $texto) === 1 || preg_match('/[áéíóúüñÁÉÍÓÚÜÑ¿¡]/u', $texto) === 1) {
            $textos[] = $texto;
        }
    }

    return $textos;
}

test('F1-H0-T09: el detector reconoce texto visible y deja pasar codigos y sql', function () {
    $codigo = <<<'PHP'
        <?php
        abort(404, 'El jugador no existe.');
        $a = "Atención";
        $b = 'recurso_no_encontrado';
        $c = 'select 1';
        $d = 'api/v1';
        PHP;

    expect(textosVisiblesEn($codigo))->toBe(['El jugador no existe.', 'Atención']);
});

test('F1-H0-T09: ningun texto visible esta escrito fuera de lang/es', function () {
    $hallazgos = [];
    foreach (['app', 'src', 'routes', 'bootstrap'] as $carpeta) {
        $archivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($carpeta), FilesystemIterator::SKIP_DOTS));
        foreach ($archivos as $archivo) {
            /** @var SplFileInfo $archivo */
            if ($archivo->getExtension() !== 'php' || str_contains($archivo->getPathname(), 'bootstrap'.DIRECTORY_SEPARATOR.'cache')) {
                continue;
            }
            foreach (textosVisiblesEn((string) file_get_contents($archivo->getPathname())) as $texto) {
                $hallazgos[] = $archivo->getPathname().': '.$texto;
            }
        }
    }

    expect($hallazgos)->toBe([]);
});
