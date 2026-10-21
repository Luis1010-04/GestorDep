<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

/** Registra una ruta de prueba bajo /api/v1 que ejecuta la accion dada. */
function rutaDePrueba(Closure $accion): string
{
    Route::prefix('api/v1')->get('/prueba-error', $accion);

    return '/api/v1/prueba-error';
}

test('F1-H0-T09: 404 en una ruta inexistente de la api con el formato de error', function () {
    /** @var TestCase $this */
    $this->getJson('/api/v1/no-existe')
        ->assertNotFound()
        ->assertExactJson([
            'codigo' => 'recurso_no_encontrado',
            'mensaje' => 'El recurso solicitado no existe.',
            'errores' => [],
        ]);
});

test('F1-H0-T09: 404 cuando un modelo no se encuentra', function () {
    /** @var TestCase $this */
    $this->getJson(rutaDePrueba(fn () => throw new ModelNotFoundException))
        ->assertNotFound()
        ->assertJsonPath('codigo', 'recurso_no_encontrado');
});

test('F1-H0-T09: la api responde json aunque la peticion no lo pida', function () {
    /** @var TestCase $this */
    $this->get('/api/v1/no-existe', ['Accept' => 'text/html'])
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonPath('codigo', 'recurso_no_encontrado');
});

test('F1-H0-T09: 401 sin sesion con el formato de error', function () {
    /** @var TestCase $this */
    $this->getJson(rutaDePrueba(fn () => throw new AuthenticationException))
        ->assertUnauthorized()
        ->assertExactJson([
            'codigo' => 'no_autenticado',
            'mensaje' => 'Se requiere iniciar sesión.',
            'errores' => [],
        ]);
});

test('F1-H0-T09: 403 sin permiso con el formato de error', function () {
    /** @var TestCase $this */
    $this->getJson(rutaDePrueba(fn () => throw new AuthorizationException))
        ->assertForbidden()
        ->assertExactJson([
            'codigo' => 'sin_permiso',
            'mensaje' => 'No tiene permiso para realizar esta acción.',
            'errores' => [],
        ]);
});

test('F1-H0-T09: 409 de conflicto con el formato de error', function () {
    /** @var TestCase $this */
    $this->getJson(rutaDePrueba(fn () => throw new ConflictHttpException))
        ->assertConflict()
        ->assertExactJson([
            'codigo' => 'conflicto',
            'mensaje' => 'La operación entra en conflicto con los datos existentes.',
            'errores' => [],
        ]);
});

test('F1-H0-T09: 422 de validacion con el detalle por campo en espanol', function () {
    /** @var TestCase $this */
    $ruta = rutaDePrueba(fn () => Validator::make(
        ['valor' => 11],
        ['nombre' => ['required'], 'valor' => ['integer', 'max:10']],
    )->validate());

    $this->getJson($ruta)
        ->assertUnprocessable()
        ->assertExactJson([
            'codigo' => 'validacion_fallida',
            'mensaje' => 'Los datos enviados no son válidos.',
            'errores' => [
                ['campo' => 'nombre', 'codigo' => 'required', 'mensaje' => 'El campo nombre es obligatorio.'],
                ['campo' => 'valor', 'codigo' => 'max', 'mensaje' => 'El campo valor no debe ser mayor que 10.'],
            ],
        ]);
});

test('F1-H0-T09: una regla de negocio violada responde 422 con su codigo estable', function () {
    /** @var TestCase $this */
    $this->getJson(rutaDePrueba(fn () => throw new ReglaDeNegocioViolada('regla_de_prueba')))
        ->assertUnprocessable()
        ->assertExactJson([
            'codigo' => 'regla_de_prueba',
            'mensaje' => 'La operación no cumple una regla de negocio.',
            'errores' => [],
        ]);
});

test('F1-H0-T09: 500 ante un error inesperado con el formato de error', function () {
    /** @var TestCase $this */
    $this->getJson(rutaDePrueba(fn () => throw new RuntimeException('detalle_interno')))
        ->assertInternalServerError()
        ->assertExactJson([
            'codigo' => 'error_interno',
            'mensaje' => 'Ocurrió un error inesperado.',
            'errores' => [],
        ]);
});

test('F1-H0-T09: en produccion un error no filtra trazas, rutas de archivo ni sql', function () {
    /** @var TestCase $this */
    config(['app.env' => 'production', 'app.debug' => false]);

    $respuesta = $this->getJson(rutaDePrueba(fn () => throw new RuntimeException('select secreto from tabla')))
        ->assertInternalServerError();

    expect(array_keys($respuesta->json()))->toBe(['codigo', 'mensaje', 'errores'])
        ->and($respuesta->getContent())->not->toContain('select secreto')
        ->and($respuesta->getContent())->not->toContain('.php');
});
