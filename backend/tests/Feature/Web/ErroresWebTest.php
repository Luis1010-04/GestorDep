<?php

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

const DETALLE_INTERNO = 'detalle-interno-que-no-debe-salir';

/**
 * Cadenas que delatan una traza, una ruta de archivo o el mensaje de una excepción en una respuesta.
 *
 * @return list<string>
 */
function pistasDeDetalleInterno(): array
{
    return [DETALLE_INTERNO, 'RuntimeException', 'Stack trace', 'vendor/', base_path(), 'ErroresWebTest'];
}

test('PT-12 H-01: un error interno fuera de /api con APP_DEBUG=true no expone trazas ni detalles', function () {
    /** @var TestCase $this */
    config(['app.debug' => true]);
    Route::middleware('web')->get('/prueba-error-interno', fn () => throw new RuntimeException(DETALLE_INTERNO));

    $respuesta = $this->get('/prueba-error-interno')->assertStatus(500);

    foreach (pistasDeDetalleInterno() as $pista) {
        expect((string) $respuesta->getContent())->not->toContain($pista);
    }
});

test('PT-12 H-01: un 404 web con APP_DEBUG=true no expone trazas ni detalles', function () {
    /** @var TestCase $this */
    config(['app.debug' => true]);

    $respuesta = $this->get('/ruta-que-no-existe')->assertNotFound();

    foreach (pistasDeDetalleInterno() as $pista) {
        expect((string) $respuesta->getContent())->not->toContain($pista);
    }
});
