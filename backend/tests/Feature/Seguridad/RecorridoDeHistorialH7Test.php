<?php

use Illuminate\Support\Str;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/*
 * Recorrido de seguridad de las siete rutas de historial de H7 (docs/hitos/f1-h7.md §5.3, §6.1, §7.1 y §8 T05; RNF-01).
 * Mundo de docs/hitos/f1-h5.md §7.0 (pruebasMundo): F de §7.1 es F1 (equipo A de E1 y equipo B de E2), inscrito en la
 * materia de D1; D2 no tiene relación con F1; E3 entrena A y B; DE es docente sin materia con F1 y entrena A; J1 está solo
 * en A. Se agrega E4, entrenador sin equipos, como el entrenador sin F de la fila 11 de §7.1.
 *
 * La paridad compara el código HTTP de cada ruta de historial con el de la ruta de lectura de su recurso y, cuando no es
 * 200, también el cuerpo. El contenido de los 200 ya lo prueban T02 a T04. Las lecturas de calificaciones y asistencia a
 * clase van por inscripción: para F1 se usa su inscripción en la materia de D1; para un jugador sin inscripción, una
 * inscripción inexistente. RN-13 y RN-23 quedan pendientes de decisión (encargo 47) y no se prueban aquí.
 */
const UUID_V4_DEL_RECORRIDO_H7 = '9b2e6c1a-3f4d-4e8b-a1c2-7d5e9f0b3a64';

/** @return array<string, string> */
function recorridoH7Mundo(): array
{
    $mundo = pruebasMundo();
    sesionesCrearUsuario('e4@x.com', ['entrenador']);

    return $mundo;
}

/**
 * Cada ruta de historial de §6.1 con la ruta de lectura de su recurso.
 *
 * @return array<string, array{0: string, 1: string}>
 */
function recorridoH7Pares(string $jugador, string $inscripcion): array
{
    $historial = "/api/v1/jugadores/$jugador/historial";
    $delJugador = "/api/v1/jugadores/$jugador";

    return [
        'calificaciones' => ["$historial/calificaciones", "/api/v1/inscripciones/$inscripcion/calificaciones"],
        'asistencias-clase' => ["$historial/asistencias-clase", "/api/v1/inscripciones/$inscripcion/asistencias"],
        'avisos-lesion' => ["$historial/avisos-lesion", "$delJugador/avisos-lesion"],
        'lesiones' => ["$historial/lesiones", "$delJugador/lesiones"],
        'estados-disponibilidad' => ["$historial/estados-disponibilidad", "$delJugador/estado-disponibilidad"],
        'mediciones' => ["$historial/mediciones", "$delJugador/mediciones"],
        'pruebas-fisicas' => ["$historial/pruebas-fisicas", "$delJugador/pruebas-fisicas"],
    ];
}

/**
 * El cuerpo de cualquier 404 (esquema `Error` de docs/api/openapi.yaml; mensaje de backend/lang/es/errores.php).
 *
 * @return array<string, mixed>
 */
function recorridoH7Cuerpo404(): array
{
    return ['codigo' => 'recurso_no_encontrado', 'mensaje' => __('errores.recurso_no_encontrado'), 'errores' => []];
}

dataset('usuarios del recorrido de historial', [
    'D1 (docente de F1)' => ['d1@x.com'],
    'D2 (docente sin F1)' => ['d2@x.com'],
    'E1 (entrena A)' => ['e1@x.com'],
    'E2 (entrena B)' => ['e2@x.com'],
    'E3 (entrena A y B)' => ['e3@x.com'],
    'DE (docente sin F1 y entrenador de A)' => ['de@x.com'],
    'E4 (entrenador sin F)' => ['e4@x.com'],
    'sin sesión' => [null],
]);

test('RNF-01: la ruta de historial de cada recurso responde igual que su ruta de lectura para cada usuario', function (?string $correo) {
    /** @var TestCase $this */
    $m = recorridoH7Mundo();
    $sesion = $correo === null ? null : sesionesIniciar($this, $correo);
    $inexistente = Identificador::generar()->valor();
    $casos = [
        'F1' => recorridoH7Pares($m['f1'], $m['inscripcion_f1']),
        'J1 (solo en A, sin inscripción)' => recorridoH7Pares($m['j1'], $inexistente),
        'jugador inexistente' => recorridoH7Pares($inexistente, $inexistente),
        'UUID v4' => recorridoH7Pares(UUID_V4_DEL_RECORRIDO_H7, UUID_V4_DEL_RECORRIDO_H7),
    ];

    foreach ($casos as $jugador => $pares) {
        foreach ($pares as $recurso => [$historial, $lectura]) {
            $respuestaDeLectura = sesionesPeticion($this, 'GET', $lectura, [], $sesion);
            $respuestaDeHistorial = sesionesPeticion($this, 'GET', $historial, [], $sesion);
            $caso = "$jugador, $recurso: lectura {$respuestaDeLectura->getStatusCode()}, historial {$respuestaDeHistorial->getStatusCode()}";

            expect($respuestaDeHistorial->getStatusCode())->toBe($respuestaDeLectura->getStatusCode(), $caso);
            if ($respuestaDeLectura->getStatusCode() !== 200) {
                expect($respuestaDeHistorial->json())->toBe($respuestaDeLectura->json(), $caso);
            }
        }
    }
})->with('usuarios del recorrido de historial');

test('RNF-01: un jugador ajeno responde 404 con el mismo cuerpo que uno inexistente en las siete rutas', function () {
    /** @var TestCase $this */
    // El literal es un UUID v4 válido que no puede ser un identificador del sistema (v7).
    expect(Str::isUuid(UUID_V4_DEL_RECORRIDO_H7))->toBeTrue()
        ->and(UUID_V4_DEL_RECORRIDO_H7[14])->toBe('4')
        ->and(fn () => Identificador::desdeTexto(UUID_V4_DEL_RECORRIDO_H7))->toThrow(InvalidArgumentException::class);

    $m = recorridoH7Mundo();
    // Jugadores ajenos: J1 está solo en A y sin inscripción, así que es ajeno a D1, a D2 y a E2; F1 está inscrito en la materia
    // de D1, así que es ajeno a D2 aunque tenga inscripción. Cada ruta se recorre con los roles que la pueden consultar:
    // docentes en las académicas; docente y entrenador en avisos y estados; entrenador en las demás.
    $sesiones = ['d1' => sesionesIniciar($this, 'd1@x.com'), 'd2' => sesionesIniciar($this, 'd2@x.com'), 'e2' => sesionesIniciar($this, 'e2@x.com')];
    $ajenos = ['d1' => ['J1' => $m['j1']], 'd2' => ['J1' => $m['j1'], 'F1' => $m['f1']], 'e2' => ['J1' => $m['j1']]];
    $usuariosPorRecurso = [
        'calificaciones' => ['d1', 'd2'], 'asistencias-clase' => ['d1', 'd2'], 'avisos-lesion' => ['d2', 'e2'], 'lesiones' => ['e2'],
        'estados-disponibilidad' => ['d2', 'e2'], 'mediciones' => ['e2'], 'pruebas-fisicas' => ['e2'],
    ];

    foreach ($usuariosPorRecurso as $recurso => $usuarios) {
        foreach ($usuarios as $usuario) {
            $jugadores = [...$ajenos[$usuario], 'inexistente' => Identificador::generar()->valor(), 'UUID v4' => UUID_V4_DEL_RECORRIDO_H7];
            foreach ($jugadores as $jugador => $id) {
                expect(sesionesPeticion($this, 'GET', "/api/v1/jugadores/$id/historial/$recurso", [], $sesiones[$usuario])->json())
                    ->toBe(recorridoH7Cuerpo404(), "$recurso, $usuario, $jugador");
            }
        }
    }
});

test('RNF-01: sin sesión, las siete rutas de historial responden 401 con el mismo cuerpo', function () {
    /** @var TestCase $this */
    $m = recorridoH7Mundo();

    $cuerpos = array_map(
        fn (array $par) => sesionesPeticion($this, 'GET', $par[0])->assertUnauthorized()->assertJsonPath('codigo', 'no_autenticado')->getContent(),
        array_values(recorridoH7Pares($m['f1'], $m['inscripcion_f1'])),
    );

    expect($cuerpos)->toHaveCount(7)->and(array_unique($cuerpos))->toHaveCount(1);
});
