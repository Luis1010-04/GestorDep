<?php

/*
 * Datos de partida de docs/hitos/f1-h1.md, 7.4: entrenadores A y B, docente D, entrenador-docente AD; equipos EA (de A)
 * y EB (de B); jugadores J1 en EA, J2 en EB, J3 en EA y EB, J4 sin equipo y J5 en EA con baja.
 * Se inserta directo con la conexion del propietario: lo que se prueba es la lectura, no el alta.
 */

use Database\Seeders\PosicionesSeeder;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;

/** @param  array<string, mixed>  $atributos */
function mundoInsertar(string $tabla, array $atributos): string
{
    $id = Identificador::generar()->valor();
    DB::connection('propietario')->table($tabla)->insert(['id' => $id, ...$atributos]);

    return $id;
}

/**
 * Inserta un jugador con una membresia vigente en cada equipo indicado.
 *
 * @param  array<string, string>  $equipos  id del equipo => codigo de posicion
 */
function mundoJugador(string $matricula, string $apellidos, array $equipos = [], bool $enBaja = false, string $nombres = 'Jugador'): string
{
    $id = mundoInsertar('jugadores', [
        'nombres' => $nombres,
        'apellidos' => $apellidos,
        'fecha_nacimiento' => '2009-05-10',
        'matricula' => $matricula,
        ...($enBaja ? ['estatus_academico' => 'baja', 'estatus_deportivo' => 'baja'] : []),
    ]);
    foreach ($equipos as $equipoId => $posicion) {
        mundoMembresia($id, (string) $equipoId, $posicion);
    }

    return $id;
}

function mundoMembresia(string $jugadorId, string $equipoId, string $posicion = 'medio', ?string $hasta = null): string
{
    return mundoInsertar('jugador_equipo', [
        'jugador_id' => $jugadorId,
        'equipo_id' => $equipoId,
        'posicion_id' => DB::table('posiciones')->where('codigo', $posicion)->value('id'),
        'desde' => '2026-01-01',
        'hasta' => $hasta,
    ]);
}

function mundoEquipo(string $nombre, string $categoriaId, string $entrenadorId): string
{
    $id = mundoInsertar('equipos', ['nombre' => $nombre, 'categoria_id' => $categoriaId]);
    mundoInsertar('entrenador_equipo', ['usuario_id' => $entrenadorId, 'equipo_id' => $id, 'desde' => '2026-01-01']);

    return $id;
}

/**
 * @return array{categoria: string, a: string, b: string, d: string, ad: string, ea: string, eb: string, j1: string, j2: string, j3: string, j4: string, j5: string}
 */
function mundoDeJugadores(): array
{
    sesionesPrepararBase();
    (new PosicionesSeeder)->run();

    $a = sesionesCrearUsuario('a@x.com', ['entrenador']);
    $b = sesionesCrearUsuario('b@x.com', ['entrenador']);
    $d = sesionesCrearUsuario('d@x.com', ['docente']);
    $ad = sesionesCrearUsuario('ad@x.com', ['docente', 'entrenador']);

    $categoria = mundoInsertar('categorias', ['nombre' => 'Sub-17']);
    $ea = mundoEquipo('EA', $categoria, $a);
    $eb = mundoEquipo('EB', $categoria, $b);

    return [
        'categoria' => $categoria, 'a' => $a, 'b' => $b, 'd' => $d, 'ad' => $ad, 'ea' => $ea, 'eb' => $eb,
        'j1' => mundoJugador('M-1', 'Alvarez', [$ea => 'medio'], nombres: 'Uno'),
        'j2' => mundoJugador('M-2', 'Benitez', [$eb => 'defensa'], nombres: 'Dos'),
        'j3' => mundoJugador('M-3', 'Cruz', [$ea => 'portero', $eb => 'delantero'], nombres: 'Tres'),
        'j4' => mundoJugador('M-4', 'Dominguez', nombres: 'Cuatro'),
        'j5' => mundoJugador('M-5', 'Estrada', [$ea => 'medio'], enBaja: true, nombres: 'Cinco'),
    ];
}
