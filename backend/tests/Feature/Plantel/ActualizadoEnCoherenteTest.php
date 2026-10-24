<?php

use Database\Seeders\PosicionesSeeder;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Tests\TestCase;

beforeEach(function () {
    sesionesPrepararBase();
    (new PosicionesSeeder)->run();
    sesionesCrearUsuario('a@x.com', ['entrenador']);
});

/** Fija el reloj en un instante con fracción de segundo (12:00:00.900). */
function coherenciaFijarReloj(): void
{
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-03-15 12:00:00.900', new DateTimeZone('UTC'))));
}

/** Normaliza un valor guardado en la base al formato ATOM en UTC del contrato. */
function coherenciaComoAtom(string $guardado): string
{
    return (new DateTimeImmutable($guardado))->setTimezone(new DateTimeZone('UTC'))->format(DateTimeInterface::ATOM);
}

/** @return array{string, string} identificador del equipo y sesión del entrenador */
function coherenciaCrearEquipo(TestCase $prueba): array
{
    $sesion = sesionesIniciar($prueba, 'a@x.com');
    $categoriaId = sesionesPeticion($prueba, 'POST', '/api/v1/categorias', ['nombre' => 'Sub-17'], $sesion)->assertCreated()->json('datos.id');
    $equipoId = sesionesPeticion($prueba, 'POST', '/api/v1/equipos', ['nombre' => 'Tigres', 'categoria_id' => $categoriaId], $sesion)->assertCreated()->json('datos.id');

    return [$equipoId, $sesion];
}

test('RNF-02: POST y GET de categorías devuelven el mismo actualizado_en, que es el guardado', function () {
    /** @var TestCase $this */
    coherenciaFijarReloj();
    $sesion = sesionesIniciar($this, 'a@x.com');

    $creada = sesionesPeticion($this, 'POST', '/api/v1/categorias', ['nombre' => 'Sub-17'], $sesion)->assertCreated();
    $listada = sesionesPeticion($this, 'GET', '/api/v1/categorias', idDeSesion: $sesion)->assertOk();

    expect($creada->json('datos.actualizado_en'))->toBe('2026-03-15T12:00:00+00:00')
        ->and($listada->json('datos.0.actualizado_en'))->toBe($creada->json('datos.actualizado_en'))
        ->and(coherenciaComoAtom(DB::table('categorias')->value('actualizado_en')))->toBe($creada->json('datos.actualizado_en'))
        ->and(coherenciaComoAtom(DB::table('categorias')->value('creado_en')))->toBe($creada->json('datos.actualizado_en'));
});

test('RNF-02: POST y GET de equipos devuelven el mismo actualizado_en, que es el guardado', function () {
    /** @var TestCase $this */
    coherenciaFijarReloj();
    $sesion = sesionesIniciar($this, 'a@x.com');
    $categoriaId = sesionesPeticion($this, 'POST', '/api/v1/categorias', ['nombre' => 'Sub-17'], $sesion)->json('datos.id');

    $creado = sesionesPeticion($this, 'POST', '/api/v1/equipos', ['nombre' => 'Tigres', 'categoria_id' => $categoriaId], $sesion)->assertCreated();
    $listado = sesionesPeticion($this, 'GET', '/api/v1/equipos', idDeSesion: $sesion)->assertOk();

    expect($creado->json('datos.actualizado_en'))->toBe('2026-03-15T12:00:00+00:00')
        ->and($listado->json('datos.0.actualizado_en'))->toBe($creado->json('datos.actualizado_en'))
        ->and(coherenciaComoAtom(DB::table('equipos')->value('actualizado_en')))->toBe($creado->json('datos.actualizado_en'));
});

test('RNF-02: POST y GET de jugadores devuelven el mismo actualizado_en, que es el guardado', function () {
    /** @var TestCase $this */
    coherenciaFijarReloj();
    [$equipoId, $sesion] = coherenciaCrearEquipo($this);

    $creado = sesionesPeticion($this, 'POST', '/api/v1/jugadores', [
        'nombres' => 'Uno', 'apellidos' => 'Prueba', 'fecha_nacimiento' => '2010-01-01',
        'matricula' => 'M-1', 'equipo_id' => $equipoId, 'posicion' => 'medio',
    ], $sesion)->assertCreated();
    $detalle = sesionesPeticion($this, 'GET', '/api/v1/jugadores/'.$creado->json('datos.id'), idDeSesion: $sesion)->assertOk();
    $listado = sesionesPeticion($this, 'GET', '/api/v1/jugadores', idDeSesion: $sesion)->assertOk();

    expect($creado->json('datos.actualizado_en'))->toBe('2026-03-15T12:00:00+00:00')
        ->and($detalle->json('datos.actualizado_en'))->toBe($creado->json('datos.actualizado_en'))
        ->and($listado->json('datos.0.actualizado_en'))->toBe($creado->json('datos.actualizado_en'))
        ->and(coherenciaComoAtom(DB::table('jugadores')->value('actualizado_en')))->toBe($creado->json('datos.actualizado_en'));
});

test('RNF-02: el historial de equipos y jugadores comparte el instante de la operación', function () {
    /** @var TestCase $this */
    coherenciaFijarReloj();
    [$equipoId, $sesion] = coherenciaCrearEquipo($this);
    sesionesPeticion($this, 'POST', '/api/v1/jugadores', [
        'nombres' => 'Uno', 'apellidos' => 'Prueba', 'fecha_nacimiento' => '2010-01-01',
        'matricula' => 'M-1', 'equipo_id' => $equipoId, 'posicion' => 'medio',
    ], $sesion)->assertCreated();

    foreach (['equipos', 'entrenador_equipo', 'jugadores', 'jugador_equipo'] as $tabla) {
        $historial = DB::table("{$tabla}_historial")->get();
        expect($historial)->toHaveCount(1)
            ->and(coherenciaComoAtom($historial[0]->ocurrido_en))->toBe('2026-03-15T12:00:00+00:00', $tabla)
            ->and(coherenciaComoAtom(DB::table($tabla)->value('actualizado_en')))->toBe('2026-03-15T12:00:00+00:00', $tabla);
    }
});

test('RNF-02: con el reloj real, ocurrido_en y actualizado_en guardados coinciden al crear un equipo', function () {
    /** @var TestCase $this */
    coherenciaCrearEquipo($this);

    $equipo = DB::table('equipos')->first();
    $asignacion = DB::table('entrenador_equipo')->first();
    $actualizado = coherenciaComoAtom($equipo->actualizado_en);

    expect(coherenciaComoAtom(DB::table('equipos_historial')->value('ocurrido_en')))->toBe($actualizado)
        ->and(coherenciaComoAtom(DB::table('entrenador_equipo_historial')->value('ocurrido_en')))->toBe($actualizado)
        ->and(coherenciaComoAtom($equipo->creado_en))->toBe($actualizado)
        ->and(coherenciaComoAtom($asignacion->actualizado_en))->toBe($actualizado);
});

test('RNF-02: el borrado lógico de una categoría guarda el mismo instante truncado en eliminado_en y actualizado_en', function () {
    /** @var TestCase $this */
    $sesion = sesionesIniciar($this, 'a@x.com');
    $id = sesionesPeticion($this, 'POST', '/api/v1/categorias', ['nombre' => 'Sub-17'], $sesion)->json('datos.id');
    coherenciaFijarReloj();

    sesionesPeticion($this, 'DELETE', "/api/v1/categorias/$id", idDeSesion: $sesion)->assertNoContent();

    $fila = DB::table('categorias')->where('id', $id)->first();
    expect(coherenciaComoAtom($fila->eliminado_en))->toBe('2026-03-15T12:00:00+00:00')
        ->and(coherenciaComoAtom($fila->actualizado_en))->toBe('2026-03-15T12:00:00+00:00');
});

test('RNF-02: el historial sin instante explícito sale del reloj truncado a segundos', function () {
    /** @var TestCase $this */
    coherenciaFijarReloj();

    $this->artisan('usuarios:crear', ['correo' => 'nuevo@x.com', 'nombre' => 'Nuevo', '--rol' => ['entrenador'], '--contrasena' => 'una-clave-larga-1'])
        ->assertSuccessful();
    $id = DB::table('usuarios')->where('correo', 'nuevo@x.com')->value('id');

    expect(coherenciaComoAtom(DB::table('usuarios_historial')->where('usuario_afectado_id', $id)->value('ocurrido_en')))->toBe('2026-03-15T12:00:00+00:00');
});
