<?php

/*
 * Mutaciones de T06b (docs/reportes/f1-h7-t06b.md): las pruebas que pasaron a la primera porque cubren comportamiento
 * que ya existía (paginación y desempate de T02, HU-26 c2 en H5 y RN-13). Cada mutación rompe ese comportamiento y debe
 * hacer fallar su prueba. Mismo esquema que f1-h7-t06-mutaciones.php: corre sobre la copia de `/tmp/mut`, restaura el
 * archivo original tras cada mutación y comprueba que quedó idéntico; termina con 3 si una mutación no se aplica, con 4
 * si sobrevive alguna y con 5 si el control inicial no pasa.
 *
 * Uso: php f1-h7-t06b-mutaciones.php 1 [solo-reemplazos]
 */
const RAIZ = '/tmp/mut';
const ORIGINAL = '/var/www/html';
const ACADEMICO = 'tests/Feature/Academico/HistorialAcademicoTest.php';
const SALUD = 'tests/Feature/Salud/HistorialDeSaludTest.php';
const FISICO = 'tests/Feature/CondicionFisica/HistorialDeCondicionFisicaTest.php';
const PERFIL = 'tests/Feature/Seguridad/HistorialPorPerfilTest.php';

$lote = (int) ($argv[1] ?? 0);
$soloReemplazos = ($argv[2] ?? '') === 'solo-reemplazos';

/** Agrega el filtro de borrado lógico a la unión con la entidad del historial. */
function conFiltroDeBorrado(string $union): string
{
    return $union."->whereNull('e.eliminado_en')";
}

/*
 * Cada mutación: [id, qué rompe, archivo, texto original, texto mutado, archivo de pruebas, filtro].
 */
$todas = [
    1 => [
        'nombre' => 'paginación de T02, c2 en H5 y RN-13',
        'control' => [ACADEMICO, SALUD, FISICO, PERFIL],
        'mutaciones' => [
            ['7.01', 'el historial académico admite por_pagina mayor que 100', 'app/Http/Controllers/HistorialAcademicoController.php',
                "'por_pagina' => ['integer', 'min:1', 'max:100'],", "'por_pagina' => ['integer', 'min:1', 'max:1000'],", ACADEMICO, 'por_pagina=101'],
            ['7.02', 'el historial académico pagina de 20 en 20 por omisión', 'app/Http/Controllers/HistorialAcademicoController.php',
                'private const POR_PAGINA_PREDETERMINADO = 15;', 'private const POR_PAGINA_PREDETERMINADO = 20;', ACADEMICO, 'sin por_pagina'],
            ['7.03', 'el desempate del historial académico va por id ascendente', 'src/Consultas/Infraestructura/ConsultaDeHistorialAcademicoSql.php',
                "->orderByDesc('h.id')", "->orderBy('h.id')", ACADEMICO, 'con el mismo ocurrido_en'],
            ['7.04', 'el historial de avisos oculta los avisos con borrado lógico', 'src/Salud/Infraestructura/ConsultaDeHistorialDeSaludSql.php',
                "->join('avisos_lesion as e', 'e.id', '=', 'h.aviso_id')", conFiltroDeBorrado("->join('avisos_lesion as e', 'e.id', '=', 'h.aviso_id')"), SALUD, 'HU-26 c2'],
            ['7.05', 'el historial de lesiones oculta las lesiones con borrado lógico', 'src/Salud/Infraestructura/ConsultaDeHistorialDeSaludSql.php',
                "->join('lesiones as e', 'e.id', '=', 'h.lesion_id')", conFiltroDeBorrado("->join('lesiones as e', 'e.id', '=', 'h.lesion_id')"), SALUD, 'HU-26 c2'],
            ['7.06', 'el historial de mediciones oculta las mediciones con borrado lógico', 'src/CondicionFisica/Infraestructura/ConsultaDeHistorialDeCondicionFisicaSql.php',
                "->join('mediciones_corporales as e', 'e.id', '=', 'h.medicion_id')", conFiltroDeBorrado("->join('mediciones_corporales as e', 'e.id', '=', 'h.medicion_id')"), FISICO, 'HU-26 c2'],
            ['7.07', 'el historial de pruebas físicas oculta las pruebas con borrado lógico', 'src/CondicionFisica/Infraestructura/ConsultaDeHistorialDeCondicionFisicaSql.php',
                "->join('pruebas_fisicas as e', 'e.id', '=', 'h.prueba_id')", conFiltroDeBorrado("->join('pruebas_fisicas as e', 'e.id', '=', 'h.prueba_id')"), FISICO, 'HU-26 c2'],
            ['7.08', 'el docente cuenta como entrenador en Condición física (la mutación 3.05 de T05)', 'src/CondicionFisica/Infraestructura/VisibilidadDeJugadorSql.php',
                'incluirBajas: $incluirBajas, soloEntrenador: true)', 'incluirBajas: $incluirBajas, soloEntrenador: false)', PERFIL, 'RN-13'],
            ['7.09', 'el docente cuenta como entrenador en las lesiones de Salud (la mutación 2.07 de T05)', 'src/Salud/Infraestructura/VisibilidadDeJugadorSql.php',
                'incluirBajas: true, soloEntrenador: true)->exists();', 'incluirBajas: true, soloEntrenador: false)->exists();', PERFIL, 'RN-13'],
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
