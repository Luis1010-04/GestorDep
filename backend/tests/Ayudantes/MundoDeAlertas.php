<?php

/*
 * Ayudantes de las pruebas de alertas (HU-22, docs/hitos/f1-h6.md). Viven aquí, y no dentro de un archivo de prueba,
 * porque los usan varios archivos y en paralelo cada proceso carga solo los suyos (DA-104).
 */

const ALERTA_ACADEMICA = 'riesgo_baja_academica';
const ALERTA_DEPORTIVA = 'riesgo_baja_deportiva';
const ALERTA_SANCIONES = 'acumulacion_sanciones_deportivas';

use Database\Seeders\CriterioGeneralSeeder;

function alertasRuta(string $jugador): string
{
    return "/api/v1/jugadores/$jugador/alertas";
}

function estatusRuta(string $consulta = ''): string
{
    return '/api/v1/estatus-de-jugadores'.($consulta === '' ? '' : "?$consulta");
}

/**
 * `avisosMundo()` (docs/hitos/f1-h5.md §7.0) más el criterio general de aprobación, que el detalle de materias del docente
 * necesita (HU-09).
 *
 * @return array<string, string>
 */
function estatusMundo(): array
{
    $mundo = avisosMundo();
    (new CriterioGeneralSeeder)->run();

    return $mundo;
}

/**
 * Inserta una alerta directo en la base, con la conexión del propietario: las pruebas de lectura no dependen del
 * recálculo (T03).
 *
 * @param  array<string, mixed>  $causa
 */
function alertaInsertar(string $jugador, string $tipo, ?string $equipo = null, array $causa = ['dato' => 'x'], ?string $cerradaEn = null): string
{
    return mundoInsertar('alertas', [
        'tipo' => $tipo, 'jugador_id' => $jugador, 'equipo_id' => $equipo, 'causa' => json_encode($causa),
        'activada_en' => '2026-11-15 08:00:00+00', 'cerrada_en' => $cerradaEn,
    ]);
}
