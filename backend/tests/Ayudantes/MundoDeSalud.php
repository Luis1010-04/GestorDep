<?php

/*
 * Datos de partida de docs/hitos/f1-h5.md §7.0: E1 entrena A, E2 entrena B, E3 entrena A y B; J1 y F1 en A, F1 también
 * en B; JB en A, dado de baja. D1 da una materia con F1 y JB inscritos; D2 no tiene relación con ellos; DE es docente y
 * entrena A. Hoy de negocio: 2026-11-15. Se inserta directo con la conexión del propietario.
 */

use Database\Seeders\PosicionesSeeder;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;

/** @return array<string, string> */
function avisosMundo(): array
{
    sesionesPrepararBase();
    (new PosicionesSeeder)->run();
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-11-15 18:00:00', new DateTimeZone('UTC'))));

    $e1 = sesionesCrearUsuario('e1@x.com', ['entrenador']);
    $e2 = sesionesCrearUsuario('e2@x.com', ['entrenador']);
    $e3 = sesionesCrearUsuario('e3@x.com', ['entrenador']);
    $d1 = sesionesCrearUsuario('d1@x.com', ['docente']);
    $d2 = sesionesCrearUsuario('d2@x.com', ['docente']);
    $de = sesionesCrearUsuario('de@x.com', ['docente', 'entrenador']);

    $categoria = mundoInsertar('categorias', ['nombre' => 'Sub-17']);
    $a = mundoEquipo('A', $categoria, $e1);
    $b = mundoEquipo('B', $categoria, $e2);
    foreach ([[$e3, $a], [$e3, $b], [$de, $a]] as [$entrenador, $equipo]) {
        mundoInsertar('entrenador_equipo', ['usuario_id' => $entrenador, 'equipo_id' => $equipo, 'desde' => '2026-01-01']);
    }

    $j1 = mundoJugador('M-J1', 'Uno', [$a => 'medio'], nombres: 'J1');
    $f1 = mundoJugador('M-F1', 'Delantero', [$a => 'delantero', $b => 'delantero'], nombres: 'F1');
    $jb = mundoJugador('M-JB', 'Baja', [$a => 'medio'], enBaja: true, nombres: 'JB');

    $periodo = mundoInsertar('periodos_escolares', ['nombre' => '2026-2', 'fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-12-18']);
    $asignatura = mundoInsertar('asignaturas', ['clave' => 'MAT-101', 'nombre' => 'Matemáticas', 'grado' => 1]);
    $materia = mundoInsertar('materias', ['asignatura_id' => $asignatura, 'periodo_id' => $periodo, 'docente_id' => $d1]);

    return [
        'e1' => $e1, 'e2' => $e2, 'e3' => $e3, 'd1' => $d1, 'd2' => $d2, 'de' => $de, 'a' => $a, 'b' => $b,
        'j1' => $j1, 'f1' => $f1, 'jb' => $jb, 'periodo' => $periodo, 'asignatura' => $asignatura, 'materia' => $materia,
        'inscripcion_f1' => mundoInscribir($materia, $f1), 'inscripcion_jb' => mundoInscribir($materia, $jb),
    ];
}

/**
 * Inserta un aviso directo en la base, para los estados que la API de T02 no produce.
 *
 * @param  array<string, mixed>  $atributos
 */
function avisoInsertar(string $jugador, string $creadoPor, array $atributos = []): string
{
    return mundoInsertar('avisos_lesion', [
        'jugador_id' => $jugador, 'creado_por' => $creadoPor, 'nota' => 'Molestia en el tobillo', 'estado' => 'pendiente',
        'fecha_aviso' => '2026-11-14', ...$atributos,
    ]);
}

function avisosRuta(string $jugador): string
{
    return "/api/v1/jugadores/$jugador/avisos-lesion";
}

function avisoDescarteRuta(string $aviso): string
{
    return "/api/v1/avisos-lesion/$aviso/descarte";
}
