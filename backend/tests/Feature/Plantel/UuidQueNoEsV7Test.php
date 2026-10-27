<?php

use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/** Un UUID válido que no es v7 (aquí un v4) en un parámetro de ruta es un recurso inexistente: 404, nunca 500. */
beforeEach(function () {
    sesionesPrepararBase();
    sesionesCrearUsuario('ad@x.com', ['entrenador', 'docente']);
});

const UUID_V4 = '123e4567-e89b-42d3-a456-426614174000';

/**
 * Cada ruta de H1 con un parámetro de ruta: método, plantilla con `{v}` en el identificador, cuerpo válido.
 *
 * @return array<string, array{string, string, array<string, mixed>}>
 */
function rutasConIdentificador(): array
{
    return [
        'DELETE /categorias/{id}' => ['DELETE', '/api/v1/categorias/{v}', []],
        'POST /equipos/{equipo}/jugadores' => ['POST', '/api/v1/equipos/{v}/jugadores', ['matricula' => 'M-1', 'posicion' => 'medio']],
        'PATCH /equipos/{equipo}/jugadores/{jugador} (equipo)' => ['PATCH', '/api/v1/equipos/{v}/jugadores/'.Identificador::generar()->valor(), ['posicion' => 'medio']],
        'PATCH /equipos/{equipo}/jugadores/{jugador} (jugador)' => ['PATCH', '/api/v1/equipos/'.Identificador::generar()->valor().'/jugadores/{v}', ['posicion' => 'medio']],
        'GET /jugadores/{jugador}' => ['GET', '/api/v1/jugadores/{v}', []],
        'PATCH /jugadores/{jugador}' => ['PATCH', '/api/v1/jugadores/{v}', ['nombres' => 'Ana']],
        'POST /jugadores/{jugador}/baja' => ['POST', '/api/v1/jugadores/{v}/baja', ['motivo' => 'x', 'fecha' => '2026-03-10']],
    ];
}

test('F1-H2-FIX: un UUID que no es v7 en la ruta responde el mismo 404 que un UUID v7 inexistente', function (string $metodo, string $plantilla, array $cuerpo) {
    /** @var TestCase $this */
    $sesion = sesionesIniciar($this, 'ad@x.com');

    $v7 = sesionesPeticion($this, $metodo, str_replace('{v}', Identificador::generar()->valor(), $plantilla), $cuerpo, $sesion);
    $v4 = sesionesPeticion($this, $metodo, str_replace('{v}', UUID_V4, $plantilla), $cuerpo, $sesion);

    expect($v7->getStatusCode())->toBe(404)
        ->and($v4->getStatusCode())->toBe(404)
        ->and($v4->getContent())->toBe($v7->getContent())
        ->and($v4->json('codigo'))->toBe('recurso_no_encontrado');
})->with(fn () => rutasConIdentificador());
