<?php

use Database\Seeders\RangosMedicionesCorporalesSeeder;

/**
 * El mundo de §7.0 con los rangos de peso y estatura de §5.2.
 *
 * @return array<string, string>
 */
function medicionesMundo(): array
{
    $mundo = avisosMundo();
    (new RangosMedicionesCorporalesSeeder)->run();

    return $mundo;
}

function medicionesRuta(string $jugador): string
{
    return "/api/v1/jugadores/{$jugador}/mediciones";
}

/**
 * @param  array<string, mixed>  $cambios
 * @return array<string, mixed>
 */
function medicionCuerpo(string $equipo, array $cambios = []): array
{
    return ['equipo_id' => $equipo, 'peso_kg' => '70.00', 'estatura_cm' => '175.0', ...$cambios];
}

/**
 * Inserta una medición directo en la base, para preparar periodos anteriores sin mover el reloj.
 *
 * @param  array<string, mixed>  $atributos
 */
function medicionInsertar(string $jugador, string $equipo, string $registradoPor, string $fecha = '2026-11-10', array $atributos = []): string
{
    return mundoInsertar('mediciones_corporales', [
        'jugador_id' => $jugador, 'equipo_id' => $equipo, 'registrado_por' => $registradoPor, 'fecha' => $fecha, 'periodo' => substr($fecha, 0, 7),
        'peso_kg' => '70.00', 'estatura_cm' => '175.0', 'creado_en' => '2026-11-10 10:00:00+00', 'actualizado_en' => '2026-11-10 10:00:00+00', ...$atributos,
    ]);
}
