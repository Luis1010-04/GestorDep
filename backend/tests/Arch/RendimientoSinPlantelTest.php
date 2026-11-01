<?php

/*
 * Rendimiento no lee tablas de Plantel por SQL ni importa su código: usa los puertos de Compartido (EquiposDelEntrenador,
 * PosicionEnEquipo y ConvocatoriaDePartido) y AlcanceDeJugadores (docs/hitos/f1-h4.md §8, T02; equivalente a
 * CompetenciaSinPlantelTest).
 */

const TABLAS_QUE_RENDIMIENTO_NO_LEE = ['categorias', 'posiciones', 'jugadores', 'equipos', 'jugador_equipo', 'entrenador_equipo', 'bajas_jugador'];

/**
 * Archivos que importan `Src\Plantel` o nombran una tabla de Plantel en una cadena (también `tabla.columna` o
 * `tabla as alias`). Los comentarios no cuentan.
 *
 * @param  array<string, string>  $archivos  ruta relativa => código
 * @return list<string>
 */
function archivosDeRendimientoQueTocanPlantel(array $archivos): array
{
    $patronTabla = '/^[\'"]('.implode('|', TABLAS_QUE_RENDIMIENTO_NO_LEE).')([.\s][^\'"]*)?[\'"]$/';
    $rutas = [];
    foreach ($archivos as $ruta => $codigo) {
        foreach (token_get_all($codigo) as $token) {
            if (! is_array($token)) {
                continue;
            }
            $importa = in_array($token[0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)
                && preg_match('/^\\\\?Src\\\\Plantel\\\\/', $token[1]) === 1;
            $nombraTabla = $token[0] === T_CONSTANT_ENCAPSED_STRING && preg_match($patronTabla, $token[1]) === 1;
            if ($importa || $nombraTabla) {
                $rutas[] = $ruta;
                break;
            }
        }
    }

    return $rutas;
}

test('F1-H4-T02: el detector encuentra importaciones de Plantel y tablas de Plantel, y no confunde otros nombres', function () {
    $archivos = [
        'src/Rendimiento/Aplicacion/Importa.php' => "<?php\nuse Src\\Plantel\\Dominio\\Equipo;",
        'src/Rendimiento/Infraestructura/Tabla.php' => "<?php\n\$x = \$c->table('jugador_equipo');",
        'src/Rendimiento/Infraestructura/Alias.php' => "<?php\n\$x = \$c->table('posiciones as p');",
        'src/Rendimiento/Infraestructura/Columna.php' => "<?php\n\$x = \$c->where('equipos.id', 1);",
        'src/Rendimiento/Infraestructura/Propia.php' => "<?php\n\$x = \$c->table('rendimientos_partido')->where('posicion_id', 1);",
        'src/Rendimiento/Infraestructura/Comentario.php' => "<?php\n// 'jugadores' en un comentario no cuenta",
    ];

    expect(archivosDeRendimientoQueTocanPlantel($archivos))->toBe([
        'src/Rendimiento/Aplicacion/Importa.php',
        'src/Rendimiento/Infraestructura/Tabla.php',
        'src/Rendimiento/Infraestructura/Alias.php',
        'src/Rendimiento/Infraestructura/Columna.php',
    ]);
});

test('F1-H4-T02: Rendimiento no importa Plantel ni lee sus tablas', function () {
    $rendimiento = array_filter(
        archivosPhpDeLaAplicacion(),
        fn (string $ruta) => str_starts_with($ruta, 'src/Rendimiento/'),
        ARRAY_FILTER_USE_KEY,
    );

    expect($rendimiento)->not->toBeEmpty()
        ->and(archivosDeRendimientoQueTocanPlantel($rendimiento))->toBe([]);
});
