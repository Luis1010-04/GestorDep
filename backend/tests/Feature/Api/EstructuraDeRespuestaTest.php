<?php

use App\Http\Resources\ColeccionPaginada;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

test('F1-H0-T09: una respuesta lleva sus datos en datos y omite advertencias si no hay', function () {
    /** @var TestCase $this */
    Route::prefix('api/v1')->get('/prueba-respuesta', fn () => Respuesta::conDatos(['nombre' => 'valor']));

    $this->getJson('/api/v1/prueba-respuesta')
        ->assertOk()
        ->assertExactJson(['datos' => ['nombre' => 'valor']]);
});

test('F1-H0-T09: una respuesta incluye el arreglo de advertencias cuando las hay', function () {
    /** @var TestCase $this */
    Route::prefix('api/v1')->post('/prueba-respuesta', fn () => Respuesta::conDatos(
        ['nombre' => 'valor'],
        [['codigo' => 'advertencia_de_prueba', 'mensaje' => 'mensaje_de_prueba']],
        201,
    ));

    $this->postJson('/api/v1/prueba-respuesta')
        ->assertCreated()
        ->assertExactJson([
            'datos' => ['nombre' => 'valor'],
            'advertencias' => [['codigo' => 'advertencia_de_prueba', 'mensaje' => 'mensaje_de_prueba']],
        ]);
});

test('F1-H0-T09: una coleccion paginada responde con datos y meta', function () {
    /** @var TestCase $this */
    Route::prefix('api/v1')->get('/prueba-paginacion', fn () => new ColeccionPaginada(
        new LengthAwarePaginator([new JsonResource(['id' => 'a']), new JsonResource(['id' => 'b'])], total: 3, perPage: 2, currentPage: 1),
    ));

    $this->getJson('/api/v1/prueba-paginacion')
        ->assertOk()
        ->assertExactJson([
            'datos' => [['id' => 'a'], ['id' => 'b']],
            'meta' => ['pagina' => 1, 'por_pagina' => 2, 'total' => 3, 'total_paginas' => 2],
        ]);
});
