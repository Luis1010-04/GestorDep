<?php

/*
 * Competencia no lee tablas de Salud por SQL ni importa su código: usa el puerto EstadoDeDisponibilidadOficial de
 * Compartido, con su adaptador en Salud (docs/hitos/f1-h5.md §8, T04; equivalente a CompetenciaSinPlantelTest).
 */

const TABLAS_DE_SALUD_QUE_COMPETENCIA_NO_LEE = [
    'lesiones', 'avisos_lesion', 'estados_disponibilidad', 'lesiones_historial', 'avisos_lesion_historial', 'estados_disponibilidad_historial',
];

/**
 * Archivos que importan `Src\Salud` o nombran una tabla de Salud en una cadena (también `tabla.columna` o `tabla as
 * alias`). Los comentarios no cuentan.
 *
 * @param  array<string, string>  $archivos  ruta relativa => código
 * @return list<string>
 */
function archivosQueTocanSalud(array $archivos): array
{
    $patronTabla = '/^[\'"]('.implode('|', TABLAS_DE_SALUD_QUE_COMPETENCIA_NO_LEE).')([.\s][^\'"]*)?[\'"]$/';
    $rutas = [];
    foreach ($archivos as $ruta => $codigo) {
        foreach (token_get_all($codigo) as $token) {
            if (! is_array($token)) {
                continue;
            }
            $importa = in_array($token[0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)
                && preg_match('/^\\\\?Src\\\\Salud\\\\/', $token[1]) === 1;
            $nombraTabla = $token[0] === T_CONSTANT_ENCAPSED_STRING && preg_match($patronTabla, $token[1]) === 1;
            if ($importa || $nombraTabla) {
                $rutas[] = $ruta;
                break;
            }
        }
    }

    return $rutas;
}

test('F1-H5-T04: el detector encuentra importaciones de Salud y tablas de Salud, y no confunde otros nombres', function () {
    $archivos = [
        'src/Competencia/Aplicacion/Importa.php' => "<?php\nuse Src\\Salud\\Dominio\\Lesion;",
        'src/Competencia/Infraestructura/Tabla.php' => "<?php\n\$x = \$c->table('estados_disponibilidad');",
        'src/Competencia/Infraestructura/Alias.php' => "<?php\n\$x = \$c->table('lesiones as l');",
        'src/Competencia/Infraestructura/Columna.php' => "<?php\n\$x = \$c->where('avisos_lesion.id', 1);",
        'src/Competencia/Infraestructura/Historial.php' => "<?php\n\$x = \$c->table('lesiones_historial');",
        'src/Competencia/Infraestructura/Propia.php' => "<?php\n\$x = \$c->table('convocatorias')->where('jugador_id', 1);",
        'src/Competencia/Infraestructura/Puerto.php' => "<?php\nuse Src\\Compartido\\Aplicacion\\EstadoDeDisponibilidadOficial;",
        'src/Competencia/Infraestructura/Comentario.php' => "<?php\n// 'lesiones' en un comentario no cuenta",
    ];

    expect(archivosQueTocanSalud($archivos))->toBe([
        'src/Competencia/Aplicacion/Importa.php',
        'src/Competencia/Infraestructura/Tabla.php',
        'src/Competencia/Infraestructura/Alias.php',
        'src/Competencia/Infraestructura/Columna.php',
        'src/Competencia/Infraestructura/Historial.php',
    ]);
});

test('F1-H5-T04: Competencia no importa Salud ni lee sus tablas', function () {
    $competencia = array_filter(
        archivosPhpDeLaAplicacion(),
        fn (string $ruta) => str_starts_with($ruta, 'src/Competencia/'),
        ARRAY_FILTER_USE_KEY,
    );

    expect($competencia)->not->toBeEmpty()
        ->and(archivosQueTocanSalud($competencia))->toBe([]);
});
