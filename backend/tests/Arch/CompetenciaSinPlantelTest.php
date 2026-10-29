<?php

/*
 * Competencia no lee tablas de Plantel por SQL ni importa su código: usa los puertos EquiposDelEntrenador y
 * JugadoresDelEquipo de Compartido, con sus adaptadores en Plantel (docs/hitos/f1-h3.md §5.3).
 */

const TABLAS_DE_PLANTEL = ['categorias', 'posiciones', 'jugadores', 'equipos', 'jugador_equipo', 'entrenador_equipo', 'bajas_jugador'];

/**
 * Archivos que importan `Src\Plantel` o nombran una tabla de Plantel en una cadena (también `tabla.columna` o
 * `tabla as alias`). Los comentarios no cuentan.
 *
 * @param  array<string, string>  $archivos  ruta relativa => código
 * @return list<string>
 */
function archivosQueTocanPlantel(array $archivos): array
{
    $patronTabla = '/^[\'"]('.implode('|', TABLAS_DE_PLANTEL).')([.\s][^\'"]*)?[\'"]$/';
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

test('F1-H3-T03: el detector encuentra importaciones de Plantel y tablas de Plantel, y no confunde otros nombres', function () {
    $archivos = [
        'src/Competencia/Aplicacion/Importa.php' => "<?php\nuse Src\\Plantel\\Dominio\\Equipo;",
        'src/Competencia/Infraestructura/Tabla.php' => "<?php\n\$x = \$c->table('jugador_equipo');",
        'src/Competencia/Infraestructura/Alias.php' => "<?php\n\$x = \$c->table('equipos as e');",
        'src/Competencia/Infraestructura/Columna.php' => "<?php\n\$x = \$c->where('jugadores.id', 1);",
        'src/Competencia/Infraestructura/Propia.php' => "<?php\n\$x = \$c->table('entrenamientos')->where('equipo_id', 1);",
        'src/Competencia/Infraestructura/Comentario.php' => "<?php\n// 'jugadores' en un comentario no cuenta",
    ];

    expect(archivosQueTocanPlantel($archivos))->toBe([
        'src/Competencia/Aplicacion/Importa.php',
        'src/Competencia/Infraestructura/Tabla.php',
        'src/Competencia/Infraestructura/Alias.php',
        'src/Competencia/Infraestructura/Columna.php',
    ]);
});

test('F1-H3-T03: Competencia no importa Plantel ni lee sus tablas', function () {
    $competencia = array_filter(
        archivosPhpDeLaAplicacion(),
        fn (string $ruta) => str_starts_with($ruta, 'src/Competencia/'),
        ARRAY_FILTER_USE_KEY,
    );

    expect($competencia)->not->toBeEmpty()
        ->and(archivosQueTocanPlantel($competencia))->toBe([]);
});
