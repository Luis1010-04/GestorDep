<?php

use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/**
 * Recorrido de seguridad de H5 (docs/hitos/f1-h5.md §7.8). Solo la fila 1 no tenía una prueba equivalente que recorriera
 * todas las rutas; las filas 2 a 6 están en las pruebas de Salud y Condición física (ver f1-h5-t08.md §2).
 */
beforeEach(function () {
    sesionesPrepararBase();
});

test('§7.8 fila 1 (RNF-01): sin sesión, todas las rutas de H5 responden 401 con el mismo cuerpo', function () {
    /** @var TestCase $this */
    $jugador = Identificador::generar()->valor();
    $aviso = Identificador::generar()->valor();
    $lesion = Identificador::generar()->valor();
    $rutas = [
        ['GET', "/api/v1/jugadores/$jugador/avisos-lesion"],
        ['POST', "/api/v1/jugadores/$jugador/avisos-lesion"],
        ['POST', "/api/v1/avisos-lesion/$aviso/descarte"],
        ['POST', "/api/v1/avisos-lesion/$aviso/confirmacion"],
        ['GET', "/api/v1/jugadores/$jugador/estado-disponibilidad"],
        ['PATCH', "/api/v1/jugadores/$jugador/estado-disponibilidad"],
        ['POST', "/api/v1/jugadores/$jugador/estado-disponibilidad/alta"],
        ['GET', "/api/v1/jugadores/$jugador/lesiones"],
        ['GET', "/api/v1/lesiones/$lesion"],
        ['PATCH', "/api/v1/lesiones/$lesion"],
        ['GET', "/api/v1/jugadores/$jugador/mediciones"],
        ['POST', "/api/v1/jugadores/$jugador/mediciones"],
        ['GET', '/api/v1/tipos-prueba'],
        ['GET', "/api/v1/jugadores/$jugador/pruebas-fisicas"],
        ['POST', "/api/v1/jugadores/$jugador/pruebas-fisicas"],
    ];

    $cuerpos = array_map(
        fn (array $ruta) => sesionesPeticion($this, $ruta[0], $ruta[1])->assertUnauthorized()->assertJsonPath('codigo', 'no_autenticado')->getContent(),
        $rutas,
    );

    expect(array_unique($cuerpos))->toHaveCount(1);
});
