<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/**
 * DA-10: las escrituras de plantel usan solo el alcance de entrenador. DE ve a J1 porque lo enseña (M3) pero J1 no es
 * de su equipo: no puede editar su expediente ni darlo de baja. J4 sí es de su equipo ED.
 */
test('DA-10: DE no edita el expediente de J1 (solo lo enseña) y sí el de J4 (de su equipo)', function () {
    /** @var TestCase $this */
    $m = mundoAgregarEquipoYMateriaDeDocenteEntrenador(mundoDeDocentes());
    $sesion = sesionesIniciar($this, 'de@x.com');

    sesionesPeticion($this, 'PATCH', '/api/v1/jugadores/'.$m['j1'], ['nombres' => 'Cambiado'], $sesion)
        ->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');
    sesionesPeticion($this, 'PATCH', '/api/v1/jugadores/'.$m['j4'], ['nombres' => 'Cambiado'], $sesion)
        ->assertOk()->assertJsonPath('datos.nombres', 'Cambiado');
    expect(DB::table('jugadores')->where('id', $m['j1'])->value('nombres'))->toBe('Uno');
});

test('DA-10: DE no da de baja a J1 (solo lo enseña) y sí a J4 (de su equipo)', function () {
    /** @var TestCase $this */
    $m = mundoAgregarEquipoYMateriaDeDocenteEntrenador(mundoDeDocentes());
    $sesion = sesionesIniciar($this, 'de@x.com');
    $cuerpo = ['motivo' => 'Cambio de escuela', 'fecha' => '2026-03-10'];

    $ajeno = sesionesPeticion($this, 'POST', '/api/v1/jugadores/'.$m['j1'].'/baja', $cuerpo, $sesion)->assertNotFound();
    sesionesPeticion($this, 'POST', '/api/v1/jugadores/'.$m['j4'].'/baja', $cuerpo, $sesion)->assertCreated();
    expect($ajeno->json('codigo'))->toBe('recurso_no_encontrado')
        ->and(DB::table('jugadores')->where('id', $m['j1'])->value('estatus_deportivo'))->toBe('activo');
});

test('DA-10: DE no cambia la posición de J1 en ED, donde no tiene membresía, y sí la de J4', function () {
    /** @var TestCase $this */
    $m = mundoAgregarEquipoYMateriaDeDocenteEntrenador(mundoDeDocentes());
    $sesion = sesionesIniciar($this, 'de@x.com');

    sesionesPeticion($this, 'PATCH', '/api/v1/equipos/'.$m['ed'].'/jugadores/'.$m['j1'], ['posicion' => 'portero'], $sesion)->assertNotFound();
    sesionesPeticion($this, 'PATCH', '/api/v1/equipos/'.$m['ed'].'/jugadores/'.$m['j4'], ['posicion' => 'portero'], $sesion)
        ->assertOk()->assertJsonPath('datos.equipos.0.posicion', 'portero');
});

test('DA-10 / RN-20: DE asigna a J1 a su equipo ED por matrícula y la respuesta sale con el alcance de entrenador', function () {
    /** @var TestCase $this */
    $m = mundoAgregarEquipoYMateriaDeDocenteEntrenador(mundoDeDocentes());

    sesionesPeticion($this, 'POST', '/api/v1/equipos/'.$m['ed'].'/jugadores', ['matricula' => 'M-1', 'posicion' => 'defensa'], sesionesIniciar($this, 'de@x.com'))
        ->assertCreated()
        ->assertJsonPath('datos.id', $m['j1'])
        ->assertJsonPath('datos.equipos.0.equipo_id', $m['ed']);
});

test('DA-10: escribir sobre un jugador que DE solo enseña da un 404 byte a byte idéntico al de un jugador inexistente', function () {
    /** @var TestCase $this */
    $m = mundoAgregarEquipoYMateriaDeDocenteEntrenador(mundoDeDocentes());
    $sesion = sesionesIniciar($this, 'de@x.com');
    $inexistente = Identificador::generar()->valor();

    $intentos = [
        ['PATCH', '/api/v1/jugadores/{j}', ['nombres' => 'Cambiado']],
        ['PATCH', '/api/v1/jugadores/{j}', ['nombres' => '']],
        ['PATCH', '/api/v1/jugadores/{j}', ['matricula' => 'X']],
        ['POST', '/api/v1/jugadores/{j}/baja', ['motivo' => 'x', 'fecha' => '2026-03-10']],
        ['POST', '/api/v1/jugadores/{j}/baja', []],
        ['PATCH', '/api/v1/equipos/'.$m['ed'].'/jugadores/{j}', ['posicion' => 'portero']],
    ];
    foreach ($intentos as [$metodo, $ruta, $cuerpo]) {
        $referencia = sesionesPeticion($this, $metodo, str_replace('{j}', $inexistente, $ruta), $cuerpo, $sesion)->assertNotFound();
        $ajeno = sesionesPeticion($this, $metodo, str_replace('{j}', $m['j1'], $ruta), $cuerpo, $sesion)->assertNotFound();
        expect($ajeno->getContent())->toBe($referencia->getContent());
    }
    expect(DB::table('jugadores')->where('id', $m['j1'])->first())->toMatchObject(['nombres' => 'Uno', 'estatus_deportivo' => 'activo']);
});

test('DA-10: un docente puro no escribe sobre ningún jugador de plantel (403 antes de consultar nada)', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'd1@x.com');

    sesionesPeticion($this, 'PATCH', '/api/v1/jugadores/'.$m['j1'], ['nombres' => 'X'], $sesion)->assertForbidden();
    sesionesPeticion($this, 'POST', '/api/v1/jugadores/'.$m['j1'].'/baja', ['motivo' => 'x', 'fecha' => '2026-03-10'], $sesion)->assertForbidden();
    sesionesPeticion($this, 'POST', '/api/v1/equipos/'.$m['ea'].'/jugadores', ['matricula' => 'M-4', 'posicion' => 'medio'], $sesion)->assertForbidden();
    expect(DB::table('jugadores')->where('id', $m['j1'])->value('nombres'))->toBe('Uno');
});

test('DA-10: el entrenador A sigue editando y dando de baja a J1, de su equipo, como en H1', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'a@x.com');

    sesionesPeticion($this, 'PATCH', '/api/v1/jugadores/'.$m['j1'], ['nombres' => 'Nuevo'], $sesion)->assertOk()->assertJsonPath('datos.nombres', 'Nuevo');
    sesionesPeticion($this, 'POST', '/api/v1/jugadores/'.$m['j1'].'/baja', ['motivo' => 'x', 'fecha' => '2026-03-10'], $sesion)->assertCreated();
});
