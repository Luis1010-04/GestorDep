<?php

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Osteel\OpenApi\Testing\ValidatorBuilder;
use Tests\TestCase;

function rutaDelContrato(): string
{
    return base_path('../docs/api/openapi.yaml');
}

/** Ruta estandar de Sanctum, fuera de /api/v1 (DD-28); el contrato la declara con su propio servidor. */
const RUTA_DE_COOKIE_CSRF = 'sanctum/csrf-cookie';

/** @return list<string> operaciones del contrato como "METODO /api/v1/ruta" (o "METODO /ruta" si la ruta declara el servidor raiz) */
function operacionesDocumentadas(): array
{
    /** @var OpenApi $contrato */
    $contrato = Reader::readFromYamlFile(rutaDelContrato());

    $operaciones = [];
    foreach ($contrato->paths as $ruta => $elemento) {
        $prefijo = ($elemento->servers[0]->url ?? null) === '/' ? '' : '/api/v1';
        foreach (array_keys($elemento->getOperations()) as $metodo) {
            $operaciones[] = strtoupper($metodo).' '.$prefijo.$ruta;
        }
    }
    sort($operaciones);

    return $operaciones;
}

/** @return list<string> rutas registradas bajo /api/v1, y la cookie CSRF de Sanctum, como "METODO /ruta" */
function operacionesRegistradas(): array
{
    $operaciones = [];
    foreach (app('router')->getRoutes()->getRoutes() as $ruta) {
        /** @var Route $ruta */
        if (! str_starts_with($ruta->uri(), 'api/v1/') && $ruta->uri() !== RUTA_DE_COOKIE_CSRF) {
            continue;
        }
        foreach (array_diff($ruta->methods(), ['HEAD']) as $metodo) {
            $operaciones[] = $metodo.' /'.$ruta->uri();
        }
    }
    sort($operaciones);

    return $operaciones;
}

test('F1-H0-T09: cada ruta de /api/v1 esta documentada y cada ruta documentada existe', function () {
    expect(operacionesRegistradas())->not->toBeEmpty()
        ->and(operacionesDocumentadas())->toBe(operacionesRegistradas());
});

test('F1-H0-T09: la respuesta 200 de salud cumple el contrato', function () {
    /** @var TestCase $this */
    $respuesta = $this->getJson('/api/v1/salud')->assertOk();

    $validador = ValidatorBuilder::fromYamlFile(rutaDelContrato())->getValidator();

    expect($validador->validate($respuesta->baseResponse, '/salud', 'get'))->toBeTrue();
});

test('F1-H0-T09: si la base no responde, salud devuelve 503 con el formato de error y cumple el contrato', function () {
    /** @var TestCase $this */
    config(['database.connections.pgsql.port' => 1]);
    DB::purge('pgsql');

    $respuesta = $this->getJson('/api/v1/salud')
        ->assertServiceUnavailable()
        ->assertExactJson([
            'codigo' => 'servicio_no_disponible',
            'mensaje' => 'El servicio no está disponible en este momento.',
            'errores' => [],
        ]);

    $validador = ValidatorBuilder::fromYamlFile(rutaDelContrato())->getValidator();

    expect($validador->validate($respuesta->baseResponse, '/salud', 'get'))->toBeTrue();
});
