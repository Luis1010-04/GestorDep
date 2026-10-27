<?php

/*
 * Datos de partida de docs/hitos/f1-h2.md, 7.0: docentes D1 y D2, entrenador A, docente-entrenador DE; periodos P1, P0 y
 * P2; asignaturas S1 y S2; materias M1 (S1+P1 de D1) y M2 (S2+P1 de D2); jugadores J1 inscrito en M1 y en el equipo EA
 * de A, J2 inscrito en M2, J3 inscrito en M1 y M2, J4 sin inscripcion ni equipo, J5 inscrito en M1 con baja.
 * Se inserta directo con la conexion del propietario: lo que se prueba es la lectura, no el alta.
 */

use Database\Seeders\CriterioGeneralSeeder;
use Database\Seeders\PosicionesSeeder;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;

function mundoInscribir(string $materiaId, string $jugadorId): string
{
    return mundoInsertar('inscripciones', ['materia_id' => $materiaId, 'jugador_id' => $jugadorId]);
}

/**
 * @return array{d1: string, d2: string, a: string, de: string, categoria: string, ea: string, p1: string, p0: string, p2: string, s1: string, s2: string, m1: string, m2: string, j1: string, j2: string, j3: string, j4: string, j5: string, i1: string, i2: string, i3a: string, i3b: string, i5: string}
 */
function mundoDeDocentes(): array
{
    sesionesPrepararBase();
    (new PosicionesSeeder)->run();
    (new CriterioGeneralSeeder)->run();
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-03-10 12:00:00', new DateTimeZone('UTC'))));

    $d1 = sesionesCrearUsuario('d1@x.com', ['docente']);
    $d2 = sesionesCrearUsuario('d2@x.com', ['docente']);
    $a = sesionesCrearUsuario('a@x.com', ['entrenador']);
    $de = sesionesCrearUsuario('de@x.com', ['docente', 'entrenador']);

    $categoria = mundoInsertar('categorias', ['nombre' => 'Sub-17']);
    $ea = mundoEquipo('EA', $categoria, $a);
    $p1 = mundoInsertar('periodos_escolares', ['nombre' => '2026-1', 'fecha_inicio' => '2026-01-12', 'fecha_fin' => '2026-06-30']);
    $p0 = mundoInsertar('periodos_escolares', ['nombre' => '2025-2', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-12-20']);
    $p2 = mundoInsertar('periodos_escolares', ['nombre' => '2026-2', 'fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-12-18']);
    $s1 = mundoInsertar('asignaturas', ['clave' => 'MAT-101', 'nombre' => 'Matemáticas', 'grado' => 1]);
    $s2 = mundoInsertar('asignaturas', ['clave' => 'FIS-101', 'nombre' => 'Física', 'grado' => 1]);
    $m1 = mundoInsertar('materias', ['asignatura_id' => $s1, 'periodo_id' => $p1, 'docente_id' => $d1]);
    $m2 = mundoInsertar('materias', ['asignatura_id' => $s2, 'periodo_id' => $p1, 'docente_id' => $d2]);

    $j1 = mundoJugador('M-1', 'Alvarez', [$ea => 'medio'], nombres: 'Uno');
    $j2 = mundoJugador('M-2', 'Benitez', nombres: 'Dos');
    $j3 = mundoJugador('M-3', 'Cruz', nombres: 'Tres');
    $j4 = mundoJugador('M-4', 'Dominguez', nombres: 'Cuatro');
    $j5 = mundoJugador('M-5', 'Estrada', enBaja: true, nombres: 'Cinco');

    return [
        'd1' => $d1, 'd2' => $d2, 'a' => $a, 'de' => $de, 'categoria' => $categoria, 'ea' => $ea,
        'p1' => $p1, 'p0' => $p0, 'p2' => $p2, 's1' => $s1, 's2' => $s2, 'm1' => $m1, 'm2' => $m2,
        'j1' => $j1, 'j2' => $j2, 'j3' => $j3, 'j4' => $j4, 'j5' => $j5,
        'i1' => mundoInscribir($m1, $j1), 'i2' => mundoInscribir($m2, $j2), 'i3a' => mundoInscribir($m1, $j3), 'i3b' => mundoInscribir($m2, $j3), 'i5' => mundoInscribir($m1, $j5),
    ];
}

/**
 * Agrega, solo para las filas que lo piden, el equipo ED de DE con J4 y la materia M3 (S1+P1 de DE) con J1 inscrito.
 *
 * @param  array<string, string>  $m  el mundo de `mundoDeDocentes()`
 * @return array<string, string> el mismo mundo con `ed`, `m3` e `i1m3`
 */
function mundoAgregarEquipoYMateriaDeDocenteEntrenador(array $m): array
{
    $ed = mundoEquipo('ED', $m['categoria'], $m['de']);
    mundoMembresia($m['j4'], $ed, 'medio');
    $m3 = mundoInsertar('materias', ['asignatura_id' => $m['s1'], 'periodo_id' => $m['p1'], 'docente_id' => $m['de']]);

    return [...$m, 'ed' => $ed, 'm3' => $m3, 'i1m3' => mundoInscribir($m3, $m['j1'])];
}
