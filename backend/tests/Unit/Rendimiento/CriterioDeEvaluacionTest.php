<?php

use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Rendimiento\Dominio\CriterioDeEvaluacion;
use Src\Rendimiento\Dominio\PlantillaDeEvaluacion;
use Src\Rendimiento\Dominio\ReglaDeCampoViolada;
use Src\Rendimiento\Dominio\UmbralDeRendimiento;

function criterioDePrueba(string $calificacion = '6.00', int $ventana = 5): CriterioDeEvaluacion
{
    return new CriterioDeEvaluacion(NumeroDecimal::desdeTexto($calificacion), $ventana);
}

test('HU-15 (RNF-09): el criterio acepta los extremos de la calificación mínima y de la ventana', function (string $calificacion, int $ventana) {
    $criterio = criterioDePrueba($calificacion, $ventana);

    expect($criterio->calificacionMinima()->comoCadena())->toBe(NumeroDecimal::desdeTexto($calificacion)->comoCadena())
        ->and($criterio->ventanaPartidos())->toBe($ventana);
})->with([
    ['1.00', 1],
    ['10.00', 32767],
    ['6.5', 5],
]);

test('HU-15 (§7.2 fila 7): una calificación mínima o una ventana fuera de rango da valor_fuera_de_rango en su campo', function (string $calificacion, int $ventana, string $esperado) {
    try {
        criterioDePrueba($calificacion, $ventana);
        $rechazo = null;
    } catch (ReglaDeCampoViolada $e) {
        $rechazo = $e->codigo().' '.$e->campo();
    }

    expect($rechazo)->toBe($esperado);
})->with([
    'calificación 0' => ['0', 5, 'valor_fuera_de_rango calificacion_minima'],
    'calificación 0.99' => ['0.99', 5, 'valor_fuera_de_rango calificacion_minima'],
    'calificación 10.01' => ['10.01', 5, 'valor_fuera_de_rango calificacion_minima'],
    'ventana 0' => ['6.00', 0, 'valor_fuera_de_rango ventana_partidos'],
    'ventana negativa' => ['6.00', -1, 'valor_fuera_de_rango ventana_partidos'],
    'ventana sobre el límite técnico' => ['6.00', 32768, 'valor_fuera_de_rango ventana_partidos'],
    'la calificación se valida antes que la ventana' => ['0', 0, 'valor_fuera_de_rango calificacion_minima'],
]);

test('HU-15: una plantilla propia guarda a su autor y no es de fábrica', function () {
    $autor = Identificador::generar();
    $posicion = Identificador::generar();
    $id = Identificador::generar();
    $plantilla = PlantillaDeEvaluacion::propia($id, 'Mia', $posicion, $autor, criterioDePrueba('7.00', 3));

    expect($plantilla->id())->toBe($id)
        ->and($plantilla->nombre())->toBe('Mia')
        ->and($plantilla->posicionId())->toBe($posicion)
        ->and($plantilla->creadaPor())->toBe($autor)
        ->and($plantilla->esDeFabrica())->toBeFalse()
        ->and($plantilla->criterio()->ventanaPartidos())->toBe(3);
});

test('HU-15: una plantilla sin autor es de fábrica', function () {
    $plantilla = new PlantillaDeEvaluacion(Identificador::generar(), 'Desarrollo', Identificador::generar(), null, criterioDePrueba());

    expect($plantilla->esDeFabrica())->toBeTrue()
        ->and($plantilla->creadaPor())->toBeNull();
});

test('HU-15: un umbral conserva el criterio, la plantilla de procedencia y su vigencia', function () {
    $plantilla = Identificador::generar();
    $desde = new DateTimeImmutable('2026-09-20 15:00:00+00:00');
    [$id, $jugador, $equipo, $entrenador] = [Identificador::generar(), Identificador::generar(), Identificador::generar(), Identificador::generar()];
    $umbral = new UmbralDeRendimiento($id, $jugador, $equipo, $entrenador, $plantilla, criterioDePrueba('7.00', 3), $desde);

    expect($umbral->id())->toBe($id)
        ->and($umbral->jugadorId())->toBe($jugador)
        ->and($umbral->equipoId())->toBe($equipo)
        ->and($umbral->entrenadorId())->toBe($entrenador)
        ->and($umbral->plantillaId())->toBe($plantilla)
        ->and($umbral->criterio()->calificacionMinima()->comoCadena())->toBe('7.00')
        ->and($umbral->vigenteDesde())->toBe($desde);
});
