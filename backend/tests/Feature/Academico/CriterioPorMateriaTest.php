<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Eventos\CriterioDeMateriaModificado;
use Src\Compartido\Dominio\Identificador;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/** Criterio de aprobación por materia (HU-08, RN-03, DD-47). Datos de partida de docs/hitos/f1-h2.md §7.0. */

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function criterioDefinir(TestCase $prueba, string $materiaId, array $cuerpo, string $correo = 'd1@x.com'): TestResponse
{
    return sesionesPeticion($prueba, 'PUT', "/api/v1/materias/$materiaId/criterio", $cuerpo, sesionesIniciar($prueba, $correo));
}

function criterioVigentesDe(string $materiaId): int
{
    return DB::table('criterios_aprobacion')->where('materia_id', $materiaId)->whereNull('vigente_hasta')->count();
}

test('HU-08 (§7.6 fila 1): sin criterio propio, la materia muestra el general 7.00', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    sesionesPeticion($this, 'GET', '/api/v1/materias/'.$m['m1'], idDeSesion: sesionesIniciar($this, 'd1@x.com'))
        ->assertOk()->assertJsonPath('datos.criterio_aplicado', ['ambito' => 'general', 'calificacion_minima' => '7.00']);
});

test('HU-08 (§7.6 fila 2): D1 define 6.00, queda ámbito materia con historial crear, y GET lo muestra', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    $respuesta = criterioDefinir($this, $m['m1'], ['calificacion_minima' => '6.00'])->assertOk();

    expect($respuesta->json('datos'))->toBe(['ambito' => 'materia', 'calificacion_minima' => '6.00', 'vigente_desde' => '2026-03-10']);
    $criterio = DB::table('criterios_aprobacion')->where('materia_id', $m['m1'])->first();
    $historial = DB::table('criterios_aprobacion_historial')->where('criterio_aprobacion_id', $criterio->id)->get();
    expect($historial)->toHaveCount(1)
        ->and($historial[0]->operacion)->toBe('crear')
        ->and($historial[0]->usuario_id)->toBe($m['d1'])
        ->and($historial[0]->ocurrido_en)->toStartWith('2026-03-10 12:00:00')
        ->and($criterio->creado_por)->toBe($m['d1']);
    sesionesPeticion($this, 'GET', '/api/v1/materias/'.$m['m1'], idDeSesion: sesionesIniciar($this, 'd1@x.com'))
        ->assertJsonPath('datos.criterio_aplicado', ['ambito' => 'materia', 'calificacion_minima' => '6.00']);
});

test('HU-08 (§7.6 fila 3): un segundo PUT cierra el anterior y crea el nuevo, con actualizar y crear en una transacción', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    criterioDefinir($this, $m['m1'], ['calificacion_minima' => '6.00'])->assertOk();
    $primero = DB::table('criterios_aprobacion')->where('materia_id', $m['m1'])->value('id');

    criterioDefinir($this, $m['m1'], ['calificacion_minima' => '8.00'])->assertOk()->assertJsonPath('datos.calificacion_minima', '8.00');

    $cerrado = DB::table('criterios_aprobacion')->where('id', $primero)->first();
    $nuevo = DB::table('criterios_aprobacion')->where('materia_id', $m['m1'])->whereNull('vigente_hasta')->first();
    expect($cerrado->vigente_hasta)->toBe('2026-03-10')
        ->and($nuevo->calificacion_minima)->toBe('8.00')->and($nuevo->vigente_desde)->toBe('2026-03-10')
        ->and(criterioVigentesDe($m['m1']))->toBe(1)
        ->and(DB::table('criterios_aprobacion')->where('materia_id', $m['m1'])->count())->toBe(2);
    $deCerrado = DB::table('criterios_aprobacion_historial')->where('criterio_aprobacion_id', $primero)->orderBy('version')->pluck('operacion')->all();
    $deNuevo = DB::table('criterios_aprobacion_historial')->where('criterio_aprobacion_id', $nuevo->id)->pluck('operacion')->all();
    expect($deCerrado)->toBe(['crear', 'actualizar'])->and($deNuevo)->toBe(['crear']);
    $cierre = DB::table('criterios_aprobacion_historial')->where('criterio_aprobacion_id', $primero)->where('operacion', 'actualizar')->first();
    $alta = DB::table('criterios_aprobacion_historial')->where('criterio_aprobacion_id', $nuevo->id)->first();
    expect($cierre->ocurrido_en)->toBe($alta->ocurrido_en)
        ->and(json_decode($cierre->datos_anteriores, true)['vigente_hasta'])->toBeNull()
        ->and(json_decode($cierre->datos_nuevos, true)['vigente_hasta'])->toBe('2026-03-10');
});

test('HU-08 (§7.6 fila 4): un PUT con el mismo valor vigente responde 200 sin escribir ni publicar nada', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    criterioDefinir($this, $m['m1'], ['calificacion_minima' => '6.00'])->assertOk();
    $filas = DB::table('criterios_aprobacion')->count();
    $historial = DB::table('criterios_aprobacion_historial')->count();
    Event::fake([CriterioDeMateriaModificado::class]);

    criterioDefinir($this, $m['m1'], ['calificacion_minima' => '6.00'])->assertOk()->assertJsonPath('datos.calificacion_minima', '6.00');
    criterioDefinir($this, $m['m1'], ['calificacion_minima' => '6'])->assertOk();

    expect(DB::table('criterios_aprobacion')->count())->toBe($filas)
        ->and(DB::table('criterios_aprobacion_historial')->count())->toBe($historial);
    Event::assertNotDispatched(CriterioDeMateriaModificado::class);
});

test('HU-08 (§7.6 fila 5): 10.01 y -0.01 dan 422 calificacion_fuera_de_rango; 0.00 y 10.00 se aceptan', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    foreach (['10.01', '-0.01'] as $fuera) {
        criterioDefinir($this, $m['m1'], ['calificacion_minima' => $fuera])
            ->assertUnprocessable()
            ->assertJsonPath('codigo', 'calificacion_fuera_de_rango')
            ->assertJsonPath('errores.0.campo', 'calificacion_minima')
            ->assertJsonPath('errores.0.codigo', 'calificacion_fuera_de_rango');
    }
    expect(DB::table('criterios_aprobacion')->where('materia_id', $m['m1'])->count())->toBe(0);

    criterioDefinir($this, $m['m1'], ['calificacion_minima' => '0.00'])->assertOk();
    criterioDefinir($this, $m['m1'], ['calificacion_minima' => '10.00'])->assertOk()->assertJsonPath('datos.calificacion_minima', '10.00');
});

test('HU-08 (§7.6 fila 6): un número JSON, vacío, "abc" o "6.555" da 422 validacion_fallida con el campo señalado', function (mixed $valor) {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    criterioDefinir($this, $m['m1'], ['calificacion_minima' => $valor])
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', 'calificacion_minima');
    expect(DB::table('criterios_aprobacion')->where('materia_id', $m['m1'])->count())->toBe(0);
})->with([
    'número JSON' => [6],
    'número JSON con decimales' => [6.5],
    'vacío' => [''],
    'solo espacios' => ['   '],
    'texto' => ['abc'],
    'tres decimales' => ['6.555'],
    'coma decimal' => ['6,5'],
    'nulo' => [null],
]);

test('RNF-01 (§7.6 fila 7): D1 define el criterio de M2 (de D2) y recibe un 404 idéntico al de una materia inexistente, con cuerpo válido o inválido', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    $inexistente = sesionesPeticion($this, 'PUT', '/api/v1/materias/'.Identificador::generar()->valor().'/criterio', ['calificacion_minima' => '6.00'], $sesion)->assertNotFound();

    foreach ([['calificacion_minima' => '6.00'], ['calificacion_minima' => 'abc'], ['calificacion_minima' => '99'], []] as $cuerpo) {
        $respuesta = sesionesPeticion($this, 'PUT', '/api/v1/materias/'.$m['m2'].'/criterio', $cuerpo, $sesion)->assertNotFound();
        expect($respuesta->getContent())->toBe($inexistente->getContent());
    }
    sesionesPeticion($this, 'PUT', '/api/v1/materias/123e4567-e89b-42d3-a456-426614174000/criterio', ['calificacion_minima' => '6.00'], $sesion)->assertNotFound();
    expect(DB::table('criterios_aprobacion')->where('ambito', 'materia')->count())->toBe(0);
});

test('HU-01 (§7.6 fila 8): un entrenador sin el rol docente recibe 403 al definir un criterio', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    criterioDefinir($this, $m['m1'], ['calificacion_minima' => '6.00'], 'a@x.com')->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    sesionesPeticion($this, 'PUT', '/api/v1/materias/'.$m['m1'].'/criterio', ['calificacion_minima' => '6.00'])->assertUnauthorized();
    expect(DB::table('criterios_aprobacion')->where('ambito', 'materia')->count())->toBe(0);
});

test('DD-46 (§7.6 fila 9): al confirmarse el PUT se publica CriterioDeMateriaModificado con el id de la materia, ya confirmado', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    // En el momento del despacho: la materia del evento, el nivel de transacción de la aplicación y el criterio vigente
    // visto desde OTRA conexión, que solo ve lo confirmado (auditoría de C2, M-6). Ver también la fila 10 y la prueba del
    // publicador en RecalculoDelResumenTest.
    $observado = [];
    Event::listen(CriterioDeMateriaModificado::class, function (CriterioDeMateriaModificado $e) use (&$observado): void {
        $observado[] = [
            $e->materiaId,
            DB::transactionLevel(),
            DB::connection('propietario')->table('criterios_aprobacion')->where('materia_id', $e->materiaId)->whereNull('vigente_hasta')->value('calificacion_minima'),
        ];
    });

    criterioDefinir($this, $m['m1'], ['calificacion_minima' => '6.00'])->assertOk();

    expect($observado)->toBe([[$m['m1'], 0, '6.00']]);
});

test('RNF-02 (§7.6 fila 10): si falla el historial no queda criterio nuevo ni cierre del anterior, ni se publica el evento', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    criterioDefinir($this, $m['m1'], ['calificacion_minima' => '6.00'])->assertOk();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    esquemaMarcarSucio();
    DB::connection('propietario')->statement("alter table criterios_aprobacion_historial add constraint ck_prueba_fallo check (operacion <> 'actualizar')");
    Event::fake([CriterioDeMateriaModificado::class]);

    sesionesPeticion($this, 'PUT', '/api/v1/materias/'.$m['m1'].'/criterio', ['calificacion_minima' => '8.00'], $sesion)
        ->assertStatus(500)->assertJsonPath('codigo', 'error_interno');

    $vigente = DB::table('criterios_aprobacion')->where('materia_id', $m['m1'])->whereNull('vigente_hasta')->first();
    expect(DB::table('criterios_aprobacion')->where('materia_id', $m['m1'])->count())->toBe(1)
        ->and($vigente->calificacion_minima)->toBe('6.00')
        ->and(DB::table('criterios_aprobacion_historial')->count())->toBe(1);
    Event::assertNotDispatched(CriterioDeMateriaModificado::class);
});

test('HU-08: GET /materias muestra el criterio propio de cada materia y el general en las demás', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $otra = mundoInsertar('materias', ['asignatura_id' => $m['s2'], 'periodo_id' => $m['p0'], 'docente_id' => $m['d1']]);
    criterioDefinir($this, $m['m1'], ['calificacion_minima' => '6.50'])->assertOk();

    $lista = sesionesPeticion($this, 'GET', '/api/v1/materias', idDeSesion: sesionesIniciar($this, 'd1@x.com'))->assertOk();

    $porId = array_column($lista->json('datos'), 'criterio_aplicado', 'id');
    expect($porId[$m['m1']])->toBe(['ambito' => 'materia', 'calificacion_minima' => '6.50'])
        ->and($porId[$otra])->toBe(['ambito' => 'general', 'calificacion_minima' => '7.00']);
});

test('RN-03 (§7.10 fila 5): un criterio propio de 8.00 con final 7.50 deja 1 reprobada; con final 8.00, 0', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $calificacion = mundoCalificar($m['i1'], 'final', '7.50');
    consolaArtisan('alertas:recalcular')->assertSuccessful();
    expect(DB::table('resumenes_academicos')->where('jugador_id', $m['j1'])->value('materias_reprobadas'))->toBe(0);

    criterioDefinir($this, $m['m1'], ['calificacion_minima' => '8.00'])->assertOk();
    expect(DB::table('resumenes_academicos')->where('jugador_id', $m['j1'])->value('materias_reprobadas'))->toBe(1);

    DB::connection('propietario')->table('calificaciones')->where('id', $calificacion)->update(['valor' => '8.00']);
    criterioDefinir($this, $m['m1'], ['calificacion_minima' => '8.00'])->assertOk();
    consolaArtisan('alertas:recalcular')->assertSuccessful();
    expect(DB::table('resumenes_academicos')->where('jugador_id', $m['j1'])->value('materias_reprobadas'))->toBe(0);
});

test('RN-03 (§7.10 fila 6): un criterio propio de 6.00 con final 6.50 deja 0 reprobadas (antes 1) y actualiza el resumen', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    mundoCalificar($m['i1'], 'final', '6.50');
    consolaArtisan('alertas:recalcular')->assertSuccessful();
    expect(DB::table('resumenes_academicos')->where('jugador_id', $m['j1'])->first())->toMatchObject(['materias_reprobadas' => 1, 'nivel_riesgo' => 'en_riesgo']);

    criterioDefinir($this, $m['m1'], ['calificacion_minima' => '6.00'])->assertOk();

    // El nivel sigue en_riesgo: el promedio 6.50 está bajo 7.00 (RN-04), con independencia de las reprobadas.
    expect(DB::table('resumenes_academicos')->where('jugador_id', $m['j1'])->first())->toMatchObject(['materias_reprobadas' => 0, 'promedio' => '6.50', 'nivel_riesgo' => 'en_riesgo']);
});
