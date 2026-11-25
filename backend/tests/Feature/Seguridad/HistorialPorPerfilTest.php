<?php

/*
 * RN-13 en el historial (docs/hitos/f1-h7.md v0.6 §8, T06b; DA-136): cada perfil ve el detalle de su área y, del otro
 * lado, solo lo que ya le deja ver la ruta de lectura (§5.3), sin reglas nuevas. Mundo de `avisosMundo()`: F1 está en A
 * (E1) y en B (E2) e inscrito en la materia de D1. DD es un usuario nuevo con los dos roles que da clase a F1 sin
 * entrenarlo: es el hueco del mundo de pruebas que dejó la mutación 3.05 de T05.
 */

use Database\Seeders\CriterioGeneralSeeder;
use Database\Seeders\ParametrosInstitucionalesSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

function perfilEstado(TestCase $prueba, string $ruta, string $correo): int
{
    return sesionesPeticion($prueba, 'GET', $ruta, [], sesionesIniciar($prueba, $correo))->getStatusCode();
}

/** @return list<string> las entidades del historial que recibe el usuario */
function perfilEntidades(TestCase $prueba, string $jugador, string $recurso, string $correo): array
{
    /** @var list<array{entidad_id: string}> $datos */
    $datos = sesionesPeticion($prueba, 'GET', "/api/v1/jugadores/$jugador/historial/$recurso", [], sesionesIniciar($prueba, $correo))->assertOk()->json('datos');

    return array_column($datos, 'entidad_id');
}

/** Crea un aviso por la API (escribe su historial) y devuelve su id. */
function perfilAviso(TestCase $prueba, string $jugador, string $correo): string
{
    return (string) sesionesPeticion($prueba, 'POST', avisosRuta($jugador), ['nota' => 'Molestia', 'fecha' => '2026-11-14'], sesionesIniciar($prueba, $correo))
        ->assertCreated()->json('datos.id');
}

/** Captura una calificación final por la API (escribe su historial) y devuelve su id. */
function perfilCalificar(TestCase $prueba, string $inscripcion, string $correo): string
{
    return (string) sesionesPeticion($prueba, 'POST', "/api/v1/inscripciones/$inscripcion/calificaciones", ['tipo' => 'final', 'valor' => '8.00'], sesionesIniciar($prueba, $correo))
        ->assertCreated()->json('datos.id');
}

/**
 * Las rutas del lado deportivo que solo son del entrenador: historial y lectura de cada recurso.
 *
 * @return array<string, array{0: string, 1: string}>
 */
function perfilRutasDeEntrenador(string $jugador): array
{
    return [
        'lesiones' => ["/api/v1/jugadores/$jugador/historial/lesiones", lesionesRuta($jugador)],
        'mediciones' => ["/api/v1/jugadores/$jugador/historial/mediciones", medicionesRuta($jugador)],
        'pruebas-fisicas' => ["/api/v1/jugadores/$jugador/historial/pruebas-fisicas", pruebasRuta($jugador)],
    ];
}

test('RN-13: el docente de F ve del lado deportivo el historial de estados de disponibilidad y el de los avisos que creó; las rutas de lesiones, mediciones y pruebas físicas le responden como su lectura', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e1']);
    $propio = perfilAviso($this, $m['f1'], 'd1@x.com');
    $ajeno = perfilAviso($this, $m['f1'], 'e1@x.com');

    expect(perfilEstado($this, "/api/v1/jugadores/{$m['f1']}/historial/estados-disponibilidad", 'd1@x.com'))->toBe(200)
        ->and(perfilEntidades($this, $m['f1'], 'avisos-lesion', 'd1@x.com'))->toContain($propio)->not->toContain($ajeno);
    foreach (perfilRutasDeEntrenador($m['f1']) as $recurso => [$historial, $lectura]) {
        expect(perfilEstado($this, $historial, 'd1@x.com'))->toBe(perfilEstado($this, $lectura, 'd1@x.com'), $recurso)->toBe(403, $recurso);
    }
});

test('RN-13: un entrenador sin rol docente recibe del historial académico lo mismo que de la lectura (403)', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lectura = perfilEstado($this, "/api/v1/inscripciones/{$m['inscripcion_f1']}/calificaciones", 'e1@x.com');

    foreach (['calificaciones', 'asistencias-clase'] as $recurso) {
        expect(perfilEstado($this, "/api/v1/jugadores/{$m['f1']}/historial/$recurso", 'e1@x.com'))->toBe($lectura, $recurso)->toBe(403, $recurso);
    }
});

test('RN-13: un usuario con los dos roles que da clase a F sin entrenarlo ve el historial académico de su materia y, del lado deportivo, solo lo que ve un docente', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    // Capturar una calificación recalcula el resumen académico, que lee el criterio general y los parámetros de riesgo.
    (new CriterioGeneralSeeder)->run();
    (new ParametrosInstitucionalesSeeder)->run();
    sesionesCrearUsuario('dd@x.com', ['docente', 'entrenador']);
    $dd = (string) DB::table('usuarios')->where('correo', 'dd@x.com')->value('id');
    $materia = mundoInsertar('materias', ['asignatura_id' => $m['asignatura'], 'periodo_id' => $m['periodo'], 'docente_id' => $dd]);
    $suya = perfilCalificar($this, mundoInscribir($materia, $m['f1']), 'dd@x.com');
    $deD1 = perfilCalificar($this, $m['inscripcion_f1'], 'd1@x.com');
    medicionInsertar($m['f1'], $m['a'], $m['e1']);

    expect(perfilEntidades($this, $m['f1'], 'calificaciones', 'dd@x.com'))->toBe([$suya])->not->toContain($deD1)
        ->and(perfilEstado($this, "/api/v1/jugadores/{$m['f1']}/historial/estados-disponibilidad", 'dd@x.com'))->toBe(200);
    foreach (perfilRutasDeEntrenador($m['f1']) as $recurso => [$historial, $lectura]) {
        expect(perfilEstado($this, $historial, 'dd@x.com'))->toBe(perfilEstado($this, $lectura, 'dd@x.com'), $recurso)->toBe(404, $recurso);
    }
});
