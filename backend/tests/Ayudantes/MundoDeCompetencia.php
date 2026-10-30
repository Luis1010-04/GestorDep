<?php

/*
 * Datos de partida de docs/hitos/f1-h3.md, 7.0: E1 entrena A, E2 entrena B, E3 entrena A y B; D es docente. J1 y J2 en
 * A; J3 en A y en B; J4 en B; J5 en A con baja. Lugar L1 "Cancha norte", rival R1, temporada T1 (2026-08-01 a
 * 2026-12-15) y torneo TO1 en T1. Se inserta directo con la conexion del propietario: lo que se prueba es lo que sigue.
 */

use Database\Seeders\PosicionesSeeder;

/**
 * Requiere `sesionesPrepararBase()` antes: los usuarios se crean con `sesionesCrearUsuario` para poder iniciar sesion.
 *
 * @return array{e1: string, e2: string, e3: string, d: string, a: string, b: string, j1: string, j2: string, j3: string, j4: string, j5: string, l1: string, r1: string, t1: string, to1: string}
 */
function mundoDeCompetencia(): array
{
    (new PosicionesSeeder)->run();
    $e1 = sesionesCrearUsuario('e1@x.com', ['entrenador']);
    $e2 = sesionesCrearUsuario('e2@x.com', ['entrenador']);
    $e3 = sesionesCrearUsuario('e3@x.com', ['entrenador']);
    $d = sesionesCrearUsuario('d@x.com', ['docente']);
    $categoria = mundoInsertar('categorias', ['nombre' => 'Sub-17']);
    $a = mundoEquipo('A', $categoria, $e1);
    $b = mundoEquipo('B', $categoria, $e2);
    mundoInsertar('entrenador_equipo', ['usuario_id' => $e3, 'equipo_id' => $a, 'desde' => '2026-01-01']);
    mundoInsertar('entrenador_equipo', ['usuario_id' => $e3, 'equipo_id' => $b, 'desde' => '2026-01-01']);
    $t1 = mundoInsertar('temporadas', ['nombre' => 'T1', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2026-12-15']);

    return [
        'e1' => $e1, 'e2' => $e2, 'e3' => $e3, 'd' => $d, 'a' => $a, 'b' => $b,
        'j1' => mundoJugador('M-1', 'Uno', [$a => 'defensa'], nombres: 'J1'),
        'j2' => mundoJugador('M-2', 'Dos', [$a => 'medio'], nombres: 'J2'),
        'j3' => mundoJugador('M-3', 'Tres', [$a => 'medio', $b => 'delantero'], nombres: 'J3'),
        'j4' => mundoJugador('M-4', 'Cuatro', [$b => 'portero'], nombres: 'J4'),
        'j5' => mundoJugador('M-5', 'Cinco', [$a => 'medio'], enBaja: true, nombres: 'J5'),
        'l1' => mundoInsertar('lugares', ['nombre' => 'Cancha norte']),
        'r1' => mundoInsertar('rivales', ['nombre' => 'R1']),
        't1' => $t1,
        'to1' => mundoInsertar('torneos', ['nombre' => 'TO1', 'temporada_id' => $t1]),
    ];
}

/** @param  array<string, mixed>  $atributos */
function mundoEntrenamiento(string $equipoId, string $lugarId, string $responsableId, string $inicia, string $termina, array $atributos = []): string
{
    return mundoInsertar('entrenamientos', [
        'equipo_id' => $equipoId, 'lugar_id' => $lugarId, 'responsable_id' => $responsableId,
        'inicia_en' => $inicia, 'termina_en' => $termina, 'estado' => 'programado', ...$atributos,
    ]);
}
