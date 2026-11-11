<?php

use Database\Seeders\RangosMedicionesCorporalesSeeder;
use Database\Seeders\TiposPruebaSeeder;
use Illuminate\Support\Facades\DB;

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

/**
 * El mundo de §7.0 con los rangos de §5.2 y los tres tipos de prueba sembrados.
 *
 * @return array<string, string> además del mundo, `velocidad`, `resistencia` y `tiempo_prueba` con el id de cada tipo
 */
function pruebasMundo(): array
{
    $mundo = medicionesMundo();
    (new TiposPruebaSeeder)->run();

    foreach (['velocidad', 'resistencia', 'tiempo_prueba'] as $codigo) {
        $mundo[$codigo] = (string) DB::table('tipos_prueba')->where('codigo', $codigo)->value('id');
    }

    return $mundo;
}

function pruebasRuta(string $jugador): string
{
    return "/api/v1/jugadores/{$jugador}/pruebas-fisicas";
}

/**
 * @param  array<string, mixed>  $cambios
 * @return array<string, mixed>
 */
function pruebaCuerpo(string $equipo, string $tipo, array $cambios = []): array
{
    return ['equipo_id' => $equipo, 'tipo_prueba_id' => $tipo, 'resultado' => '4.35', 'fecha' => '2026-11-03', ...$cambios];
}

/**
 * Inserta una prueba directo en la base. Un id explícito fija el desempate por id (lección de RN-15).
 *
 * @param  array<string, mixed>  $atributos
 */
function pruebaInsertar(string $jugador, string $equipo, string $registradoPor, string $tipo, string $fecha = '2026-11-03', array $atributos = [], ?string $id = null): string
{
    $fila = [
        'jugador_id' => $jugador, 'equipo_id' => $equipo, 'registrado_por' => $registradoPor, 'tipo_prueba_id' => $tipo, 'fecha' => $fecha,
        'periodo' => substr($fecha, 0, 7), 'resultado' => '4.35', 'creado_en' => '2026-11-10 10:00:00+00', 'actualizado_en' => '2026-11-10 10:00:00+00', ...$atributos,
    ];
    if ($id === null) {
        return mundoInsertar('pruebas_fisicas', $fila);
    }

    DB::connection('propietario')->table('pruebas_fisicas')->insert(['id' => $id, ...$fila]);

    return $id;
}
