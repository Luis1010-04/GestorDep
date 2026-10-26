<?php

use Illuminate\Support\Facades\Schema;

/**
 * Tablas marcadas (H) en docs/arquitectura.md: filas de tabla con la forma "| `tabla` (H) |".
 *
 * @return list<string>
 */
function tablasConHistorialSegunArquitectura(): array
{
    $texto = (string) file_get_contents(base_path('../docs/arquitectura.md'));
    preg_match_all('/^\|\s*`([a-z_]+)`\s*\(H\)\s*\|/m', $texto, $coincidencias);

    return $coincidencias[1];
}

/**
 * Tablas (H) que ya existen y no tienen su `{tabla}_historial`.
 *
 * @param  list<string>  $tablasConHistorial
 * @param  list<string>  $tablasExistentes
 * @return list<string>
 */
function tablasSinHistorial(array $tablasConHistorial, array $tablasExistentes): array
{
    $faltantes = [];
    foreach ($tablasConHistorial as $tabla) {
        if (in_array($tabla, $tablasExistentes, true) && ! in_array($tabla.'_historial', $tablasExistentes, true)) {
            $faltantes[] = $tabla;
        }
    }

    return $faltantes;
}

test('F1-H1-T08: la comparacion exige historial solo a las tablas (H) que ya existen', function () {
    $marcadas = ['jugadores', 'inscripciones'];

    expect(tablasSinHistorial($marcadas, ['jugadores', 'jugadores_historial']))->toBe([])
        ->and(tablasSinHistorial($marcadas, ['jugadores']))->toBe(['jugadores'])
        ->and(tablasSinHistorial($marcadas, ['jugadores', 'jugadores_historial', 'inscripciones']))->toBe(['inscripciones'])
        ->and(tablasSinHistorial($marcadas, []))->toBe([]);
});

test('F1-H1-T08: la arquitectura se lee y marca las tablas de H1 con historial', function () {
    expect(tablasConHistorialSegunArquitectura())->toContain(
        'usuarios', 'jugadores', 'equipos', 'jugador_equipo', 'entrenador_equipo', 'bajas_jugador',
    );
});

test('F1-H1-T08: cada tabla (H) que existe en la base tiene su tabla de historial', function () {
    esquemaReiniciar();

    $existentes = Schema::connection('propietario')->getTableListing(schema: 'public', schemaQualified: false);

    expect(tablasSinHistorial(tablasConHistorialSegunArquitectura(), $existentes))->toBe([]);
});
