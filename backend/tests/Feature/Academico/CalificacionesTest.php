<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Src\Academico\Aplicacion\CalificacionDuplicada;
use Src\Academico\Dominio\Calificacion;
use Src\Academico\Dominio\RepositorioDeCalificaciones;
use Src\Academico\Dominio\TipoDeCalificacion;
use Src\Compartido\Dominio\Eventos\CalificacionRegistrada;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/** Calificaciones (HU-07, RN-01, RN-02, RN-03, DD-47, DD-50, DD-51, DD-52, DD-55). Datos de partida de docs/hitos/f1-h2.md §7.0. */

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function calificacionesCapturar(TestCase $prueba, string $inscripcionId, array $cuerpo, string $correo = 'd1@x.com'): TestResponse
{
    return sesionesPeticion($prueba, 'POST', "/api/v1/inscripciones/$inscripcionId/calificaciones", $cuerpo, sesionesIniciar($prueba, $correo));
}

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function calificacionesCorregir(TestCase $prueba, string $calificacionId, array $cuerpo, string $correo = 'd1@x.com'): TestResponse
{
    return sesionesPeticion($prueba, 'PATCH', "/api/v1/calificaciones/$calificacionId", $cuerpo, sesionesIniciar($prueba, $correo));
}

function calificacionesResumen(string $jugadorId): ?stdClass
{
    return DB::table('resumenes_academicos')->where('jugador_id', $jugadorId)->first();
}

test('HU-07 (§7.7 fila 1): D1 captura el parcial 1 de I1 con 8.50; fecha de hoy, criterio general, historial crear y evento', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    Event::fake([CalificacionRegistrada::class]);

    $respuesta = calificacionesCapturar($this, $m['i1'], ['tipo' => 'parcial', 'numero_parcial' => 1, 'valor' => '8.50'])->assertCreated();

    $id = $respuesta->json('datos.id');
    expect($respuesta->json('datos'))->toBe([
        'id' => $id,
        'inscripcion_id' => $m['i1'],
        'tipo' => 'parcial',
        'numero_parcial' => 1,
        'valor' => '8.50',
        'criterio' => ['ambito' => 'general', 'calificacion_minima' => '7.00'],
        'fecha' => '2026-03-10',
        'actualizado_en' => '2026-03-10T12:00:00+00:00',
    ]);
    $fila = DB::table('calificaciones')->where('id', $id)->first();
    expect($fila->criterio_id)->toBe(DB::table('criterios_aprobacion')->where('ambito', 'general')->value('id'))
        ->and($fila->creado_por)->toBe($m['d1']);
    $historial = DB::table('calificaciones_historial')->where('calificacion_id', $id)->get();
    expect($historial)->toHaveCount(1)
        ->and($historial[0]->operacion)->toBe('crear')
        ->and($historial[0]->version)->toBe(1)
        ->and($historial[0]->usuario_id)->toBe($m['d1'])
        ->and(json_decode($historial[0]->datos_nuevos, true))->toEqual([
            'inscripcion_id' => $m['i1'], 'tipo' => 'parcial', 'numero_parcial' => 1, 'valor' => '8.50',
            'criterio_id' => $fila->criterio_id, 'fecha' => '2026-03-10',
        ]);
    Event::assertDispatchedTimes(CalificacionRegistrada::class, 1);
    Event::assertDispatched(CalificacionRegistrada::class, fn (CalificacionRegistrada $e) => $e->inscripcionId === $m['i1'] && $e->materiaId === $m['m1'] && $e->jugadorId === $m['j1']);
});

test('HU-07 (§7.7 fila 2): final con número, parcial sin número o número 0 u 11 dan 422 con el campo señalado', function (array $cuerpo, string $campo) {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    calificacionesCapturar($this, $m['i1'], $cuerpo)
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', $campo);
    expect(DB::table('calificaciones')->count())->toBe(0);
})->with([
    'final con número' => [['tipo' => 'final', 'numero_parcial' => 1, 'valor' => '8.00'], 'numero_parcial'],
    'parcial sin número' => [['tipo' => 'parcial', 'valor' => '8.00'], 'numero_parcial'],
    'parcial 0' => [['tipo' => 'parcial', 'numero_parcial' => 0, 'valor' => '8.00'], 'numero_parcial'],
    'parcial 11' => [['tipo' => 'parcial', 'numero_parcial' => 11, 'valor' => '8.00'], 'numero_parcial'],
    'parcial decimal' => [['tipo' => 'parcial', 'numero_parcial' => 1.5, 'valor' => '8.00'], 'numero_parcial'],
    'tipo desconocido' => [['tipo' => 'extra', 'valor' => '8.00'], 'tipo'],
    'sin tipo' => [['valor' => '8.00'], 'tipo'],
    'fecha con formato inválido' => [['tipo' => 'final', 'valor' => '8.00', 'fecha' => '10/03/2026'], 'fecha'],
]);

test('HU-07 (§7.7 fila 3): 10.01 y -0.01 dan 422 calificacion_fuera_de_rango con mensaje claro y no se guardan', function (string $valor) {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    calificacionesCapturar($this, $m['i1'], ['tipo' => 'final', 'valor' => $valor])
        ->assertUnprocessable()
        ->assertExactJson([
            'codigo' => 'calificacion_fuera_de_rango',
            'mensaje' => 'La calificación debe estar entre 0 y 10.',
            'errores' => [['campo' => 'valor', 'codigo' => 'calificacion_fuera_de_rango', 'mensaje' => 'La calificación debe estar entre 0 y 10.']],
        ]);
    expect(DB::table('calificaciones')->count())->toBe(0)->and(DB::table('calificaciones_historial')->count())->toBe(0);
})->with(['10.01', '-0.01']);

test('RN-01 (§7.7 fila 4): los límites 0, 0.00, 10 y 10.00 se aceptan', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    $casos = [[1, '0', '0.00'], [2, '0.00', '0.00'], [3, '10', '10.00'], [4, '10.00', '10.00']];
    foreach ($casos as [$parcial, $valor, $esperado]) {
        calificacionesCapturar($this, $m['i1'], ['tipo' => 'parcial', 'numero_parcial' => $parcial, 'valor' => $valor])
            ->assertCreated()->assertJsonPath('datos.valor', $esperado);
    }
    expect(DB::table('calificaciones')->count())->toBe(4);
});

test('DD-51 (§7.7 fila 5): "abc", vacío, "8.555" o el número JSON 8.5 dan 422 validacion_fallida con valor señalado', function (mixed $valor) {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    calificacionesCapturar($this, $m['i1'], ['tipo' => 'final', 'valor' => $valor])
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', 'valor');
    expect(DB::table('calificaciones')->count())->toBe(0);
})->with([
    'texto' => ['abc'],
    'vacío' => [''],
    'solo espacios' => ['   '],
    'tres decimales' => ['8.555'],
    'número JSON decimal' => [8.5],
    'número JSON entero' => [8],
    'coma decimal' => ['8,5'],
    'nulo' => [null],
]);

test('HU-07 (§7.7 fila 6): repetir el tipo y número de parcial, o una segunda final, da 409 calificacion_duplicada', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    calificacionesCapturar($this, $m['i1'], ['tipo' => 'parcial', 'numero_parcial' => 1, 'valor' => '8.00'])->assertCreated();
    calificacionesCapturar($this, $m['i1'], ['tipo' => 'final', 'valor' => '8.00'])->assertCreated();

    calificacionesCapturar($this, $m['i1'], ['tipo' => 'parcial', 'numero_parcial' => 1, 'valor' => '9.00'])
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'calificacion_duplicada', 'mensaje' => 'Ya existe esa calificación para la inscripción.', 'errores' => []]);
    calificacionesCapturar($this, $m['i1'], ['tipo' => 'final', 'valor' => '9.00'])->assertStatus(409)->assertJsonPath('codigo', 'calificacion_duplicada');
    calificacionesCapturar($this, $m['i1'], ['tipo' => 'parcial', 'numero_parcial' => 2, 'valor' => '9.00'])->assertCreated();
    expect(DB::table('calificaciones')->count())->toBe(3)->and(DB::table('calificaciones_historial')->count())->toBe(3);
});

test('HU-07 (§7.7 fila 7): PATCH con 9.00 responde 200 y escribe actualizar con datos anteriores y nuevos, versión consecutiva', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $id = calificacionesCapturar($this, $m['i1'], ['tipo' => 'final', 'valor' => '8.00'])->json('datos.id');
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-03-11 09:30:00', new DateTimeZone('UTC'))));
    Event::fake([CalificacionRegistrada::class]);

    calificacionesCorregir($this, $id, ['valor' => '9.00'])
        ->assertOk()
        ->assertJsonPath('datos.valor', '9.00')
        ->assertJsonPath('datos.fecha', '2026-03-10')
        ->assertJsonPath('datos.actualizado_en', '2026-03-11T09:30:00+00:00');

    $historial = DB::table('calificaciones_historial')->where('calificacion_id', $id)->orderBy('version')->get();
    expect($historial)->toHaveCount(2)
        ->and($historial[1]->operacion)->toBe('actualizar')
        ->and($historial[1]->version)->toBe(2)
        ->and($historial[1]->usuario_id)->toBe($m['d1'])
        ->and(json_decode($historial[1]->datos_anteriores, true)['valor'])->toBe('8.00')
        ->and(json_decode($historial[1]->datos_nuevos, true)['valor'])->toBe('9.00')
        ->and(array_keys(json_decode($historial[1]->datos_nuevos, true)))->toEqualCanonicalizing(['inscripcion_id', 'tipo', 'numero_parcial', 'valor', 'criterio_id', 'fecha'])
        ->and(DB::table('calificaciones')->where('id', $id)->value('actualizado_por'))->toBe($m['d1']);
    Event::assertDispatchedTimes(CalificacionRegistrada::class, 1);
});

test('HU-07 (§7.7 fila 8): PATCH con el mismo valor responde 200 sin historial, sin tocar actualizado_en y sin evento', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $id = calificacionesCapturar($this, $m['i1'], ['tipo' => 'final', 'valor' => '8.00'])->json('datos.id');
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-03-11 09:30:00', new DateTimeZone('UTC'))));
    Event::fake([CalificacionRegistrada::class]);

    calificacionesCorregir($this, $id, ['valor' => '8.00'])->assertOk()->assertJsonPath('datos.actualizado_en', '2026-03-10T12:00:00+00:00');
    calificacionesCorregir($this, $id, ['valor' => '8'])->assertOk()->assertJsonPath('datos.valor', '8.00');

    expect(DB::table('calificaciones_historial')->where('calificacion_id', $id)->count())->toBe(1);
    Event::assertNotDispatched(CalificacionRegistrada::class);
});

test('HU-07 (§7.7 fila 9): PATCH con 10.01 da calificacion_fuera_de_rango y con otro campo da 422 prohibited', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $id = calificacionesCapturar($this, $m['i1'], ['tipo' => 'final', 'valor' => '8.00'])->json('datos.id');

    calificacionesCorregir($this, $id, ['valor' => '10.01'])
        ->assertUnprocessable()->assertJsonPath('codigo', 'calificacion_fuera_de_rango')->assertJsonPath('errores.0.campo', 'valor');
    foreach ([['valor' => '9.00', 'tipo' => 'parcial'], ['valor' => '9.00', 'inscripcion_id' => $m['i3a']], ['tipo' => 'final'], ['fecha' => '2026-03-01', 'valor' => '9.00']] as $cuerpo) {
        $campo = array_values(array_diff(array_keys($cuerpo), ['valor']))[0];
        $respuesta = calificacionesCorregir($this, $id, $cuerpo)->assertUnprocessable()->assertJsonPath('codigo', 'validacion_fallida');
        /** @var list<array{campo: string, codigo: string}> $errores */
        $errores = $respuesta->json('errores');
        expect(array_values(array_filter($errores, fn (array $error): bool => $error['campo'] === $campo))[0] ?? null)->toMatchArray(['campo' => $campo, 'codigo' => 'prohibited']);
    }
    calificacionesCorregir($this, $id, [])->assertUnprocessable()->assertJsonPath('errores.0.campo', 'valor');
    calificacionesCorregir($this, $id, ['valor' => 9.5])->assertUnprocessable()->assertJsonPath('errores.0.campo', 'valor');

    expect(DB::table('calificaciones')->where('id', $id)->value('valor'))->toBe('8.00')
        ->and(DB::table('calificaciones_historial')->where('calificacion_id', $id)->count())->toBe(1);
});

test('DD-50 (§7.7 fila 10): D2 sobre I1 o una calificación de I1 recibe un 404 idéntico al de un recurso inexistente, con cualquier cuerpo', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $id = calificacionesCapturar($this, $m['i1'], ['tipo' => 'final', 'valor' => '8.00'])->json('datos.id');
    $sesion = sesionesIniciar($this, 'd2@x.com');
    $inexistente = Identificador::generar()->valor();
    $noV7 = '123e4567-e89b-42d3-a456-426614174000';
    $referencia = sesionesPeticion($this, 'POST', "/api/v1/inscripciones/$inexistente/calificaciones", ['tipo' => 'final', 'valor' => '9.00'], $sesion)->assertNotFound();
    expect($referencia->json())->toBe(['codigo' => 'recurso_no_encontrado', 'mensaje' => 'El recurso solicitado no existe.', 'errores' => []]);

    $cuerpos = [['tipo' => 'final', 'valor' => '9.00'], ['tipo' => 'parcial', 'numero_parcial' => 1, 'valor' => '9.00'], ['valor' => '10.01'], ['valor' => 'abc'], ['tipo' => 'final'], []];
    foreach ($cuerpos as $cuerpo) {
        foreach (['POST' => ["/api/v1/inscripciones/{$m['i1']}/calificaciones", "/api/v1/inscripciones/$noV7/calificaciones"], 'PATCH' => ["/api/v1/calificaciones/$id", "/api/v1/calificaciones/$inexistente", "/api/v1/calificaciones/$noV7"]] as $metodo => $rutas) {
            foreach ($rutas as $ruta) {
                $respuesta = sesionesPeticion($this, $metodo, $ruta, $cuerpo, $sesion)->assertNotFound();
                expect($respuesta->getContent())->toBe($referencia->getContent());
            }
        }
    }
    foreach (["/api/v1/inscripciones/{$m['i1']}/calificaciones", "/api/v1/inscripciones/$inexistente/calificaciones", "/api/v1/inscripciones/{$m['i1']}/calificaciones?pagina=abc"] as $ruta) {
        $respuesta = sesionesPeticion($this, 'GET', $ruta, idDeSesion: $sesion)->assertNotFound();
        expect($respuesta->getContent())->toBe($referencia->getContent());
    }
    // D2 tampoco toca I3a, la inscripción de J3 en M1, aunque J3 también sea su alumno en M2.
    $ajena = sesionesPeticion($this, 'POST', "/api/v1/inscripciones/{$m['i3a']}/calificaciones", ['tipo' => 'final', 'valor' => '9.00'], $sesion)->assertNotFound();
    expect($ajena->getContent())->toBe($referencia->getContent())
        ->and(DB::table('calificaciones')->count())->toBe(1)
        ->and(DB::table('calificaciones')->where('id', $id)->value('valor'))->toBe('8.00');
});

test('HU-01 (§7.7 fila 11): un entrenador sin el rol docente recibe 403 en las tres rutas de calificaciones', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $id = calificacionesCapturar($this, $m['i1'], ['tipo' => 'final', 'valor' => '8.00'])->json('datos.id');
    $sesion = sesionesIniciar($this, 'a@x.com');

    sesionesPeticion($this, 'GET', "/api/v1/inscripciones/{$m['i1']}/calificaciones", idDeSesion: $sesion)->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    sesionesPeticion($this, 'POST', "/api/v1/inscripciones/{$m['i1']}/calificaciones", ['tipo' => 'final', 'valor' => '9.00'], $sesion)->assertForbidden();
    sesionesPeticion($this, 'PATCH', "/api/v1/calificaciones/$id", ['valor' => '9.00'], $sesion)->assertForbidden();
    expect(DB::table('calificaciones')->where('id', $id)->value('valor'))->toBe('8.00');
});

test('DD-52 (§7.7 fila 12): calificar a I5 (J5 en baja) da 409 jugador_en_baja, y tampoco se corrige una calificación previa', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $previa = mundoCalificar($m['i5'], 'parcial', '7.00', 1);

    calificacionesCapturar($this, $m['i5'], ['tipo' => 'final', 'valor' => '8.00'])
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'jugador_en_baja', 'mensaje' => 'El jugador está en baja: no se puede asignar, inscribir ni capturar calificaciones o asistencias.', 'errores' => []]);
    calificacionesCorregir($this, $previa, ['valor' => '9.00'])->assertStatus(409)->assertJsonPath('codigo', 'jugador_en_baja');

    expect(DB::table('calificaciones')->where('inscripcion_id', $m['i5'])->count())->toBe(1)
        ->and(DB::table('calificaciones')->where('id', $previa)->value('valor'))->toBe('7.00');
    // Sigue visible para quien ya lo tenía (DD-52).
    sesionesPeticion($this, 'GET', "/api/v1/inscripciones/{$m['i5']}/calificaciones", idDeSesion: sesionesIniciar($this, 'd1@x.com'))
        ->assertOk()->assertJsonPath('datos.0.id', $previa);
});

test('HU-07 (§7.7 fila 13): el listado de I1 trae los parciales por número y la final al último, solo de I1', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $final = mundoCalificar($m['i1'], 'final', '8.00');
    $p2 = mundoCalificar($m['i1'], 'parcial', '7.00', 2);
    $p1 = mundoCalificar($m['i1'], 'parcial', '9.00', 1);
    $p10 = mundoCalificar($m['i1'], 'parcial', '6.00', 10);
    mundoCalificar($m['i3a'], 'parcial', '5.00', 1);
    $eliminada = mundoCalificar($m['i1'], 'parcial', '4.00', 3);
    DB::connection('propietario')->table('calificaciones')->where('id', $eliminada)->update(['eliminado_en' => '2026-03-01 00:00:00+00', 'eliminado_por' => $m['d1']]);
    $sesion = sesionesIniciar($this, 'd1@x.com');

    $respuesta = sesionesPeticion($this, 'GET', "/api/v1/inscripciones/{$m['i1']}/calificaciones", idDeSesion: $sesion)->assertOk();

    expect(array_column($respuesta->json('datos'), 'id'))->toBe([$p1, $p2, $p10, $final])
        ->and($respuesta->json('meta'))->toBe(['pagina' => 1, 'por_pagina' => 15, 'total' => 4, 'total_paginas' => 1])
        ->and($respuesta->json('datos.3'))->toMatchArray(['tipo' => 'final', 'numero_parcial' => null, 'valor' => '8.00']);
    sesionesPeticion($this, 'GET', "/api/v1/inscripciones/{$m['i1']}/calificaciones?por_pagina=1&pagina=4", idDeSesion: $sesion)
        ->assertJsonPath('datos.0.id', $final)
        ->assertJsonPath('meta', ['pagina' => 4, 'por_pagina' => 1, 'total' => 4, 'total_paginas' => 4]);
    sesionesPeticion($this, 'GET', "/api/v1/inscripciones/{$m['i1']}/calificaciones?por_pagina=101", idDeSesion: $sesion)->assertUnprocessable();
});

test('DD-47 (§7.7 fila 14): con criterio propio 6.00 se captura y, al subirlo a 8.00, la calificación conserva el de 6.00', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    sesionesPeticion($this, 'PUT', "/api/v1/materias/{$m['m1']}/criterio", ['calificacion_minima' => '6.00'], $sesion)->assertOk();

    $id = calificacionesCapturar($this, $m['i1'], ['tipo' => 'final', 'valor' => '7.00'])
        ->assertCreated()->assertJsonPath('datos.criterio', ['ambito' => 'materia', 'calificacion_minima' => '6.00'])->json('datos.id');
    $criterioAlCapturar = DB::table('calificaciones')->where('id', $id)->value('criterio_id');
    sesionesPeticion($this, 'PUT', "/api/v1/materias/{$m['m1']}/criterio", ['calificacion_minima' => '8.00'], $sesion)->assertOk();

    sesionesPeticion($this, 'GET', "/api/v1/inscripciones/{$m['i1']}/calificaciones", idDeSesion: $sesion)
        ->assertJsonPath('datos.0.criterio', ['ambito' => 'materia', 'calificacion_minima' => '6.00']);
    calificacionesCorregir($this, $id, ['valor' => '7.50'])->assertOk()->assertJsonPath('datos.criterio.calificacion_minima', '6.00');
    expect(DB::table('calificaciones')->where('id', $id)->value('criterio_id'))->toBe($criterioAlCapturar);
    // Una captura nueva toma el criterio vigente en ese momento.
    calificacionesCapturar($this, $m['i1'], ['tipo' => 'parcial', 'numero_parcial' => 1, 'valor' => '7.00'])
        ->assertJsonPath('datos.criterio', ['ambito' => 'materia', 'calificacion_minima' => '8.00']);
});

test('DD-55 (§7.7 fila 15): una fecha explícita se respeta, sin ella es hoy de negocio y una fecha futura no se rechaza', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    calificacionesCapturar($this, $m['i1'], ['tipo' => 'parcial', 'numero_parcial' => 1, 'valor' => '8.00', 'fecha' => '2026-03-01'])->assertCreated()->assertJsonPath('datos.fecha', '2026-03-01');
    calificacionesCapturar($this, $m['i1'], ['tipo' => 'parcial', 'numero_parcial' => 2, 'valor' => '8.00'])->assertCreated()->assertJsonPath('datos.fecha', '2026-03-10');
    calificacionesCapturar($this, $m['i1'], ['tipo' => 'parcial', 'numero_parcial' => 3, 'valor' => '8.00', 'fecha' => '2027-01-15'])->assertCreated()->assertJsonPath('datos.fecha', '2027-01-15');
});

test('RNF-02 (§7.7 fila 16): si falla el historial al capturar no queda la calificación ni se publica el evento', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    esquemaMarcarSucio();
    DB::connection('propietario')->statement('alter table calificaciones_historial add constraint ck_prueba_fallo check (false)');
    Event::fake([CalificacionRegistrada::class]);

    sesionesPeticion($this, 'POST', "/api/v1/inscripciones/{$m['i1']}/calificaciones", ['tipo' => 'final', 'valor' => '8.00'], $sesion)
        ->assertStatus(500)->assertJsonPath('codigo', 'error_interno');

    expect(DB::table('calificaciones')->count())->toBe(0)->and(DB::table('calificaciones_historial')->count())->toBe(0);
    Event::assertNotDispatched(CalificacionRegistrada::class);
});

test('RNF-02 (§7.11 fila 16): si falla el historial al corregir el valor no cambia ni se publica el evento', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $id = calificacionesCapturar($this, $m['i1'], ['tipo' => 'final', 'valor' => '8.00'])->json('datos.id');
    $sesion = sesionesIniciar($this, 'd1@x.com');
    esquemaMarcarSucio();
    DB::connection('propietario')->statement("alter table calificaciones_historial add constraint ck_prueba_fallo check (operacion <> 'actualizar')");
    Event::fake([CalificacionRegistrada::class]);

    sesionesPeticion($this, 'PATCH', "/api/v1/calificaciones/$id", ['valor' => '9.00'], $sesion)->assertStatus(500)->assertJsonPath('codigo', 'error_interno');

    expect(DB::table('calificaciones')->where('id', $id)->first())->toMatchObject(['valor' => '8.00', 'actualizado_en' => '2026-03-10 12:00:00+00'])
        ->and(DB::table('calificaciones_historial')->where('calificacion_id', $id)->count())->toBe(1);
    Event::assertNotDispatched(CalificacionRegistrada::class);
});

test('HU-01 (§7.7 fila 17): sin sesión las rutas de calificaciones responden 401', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $id = Identificador::generar()->valor();

    sesionesPeticion($this, 'GET', "/api/v1/inscripciones/{$m['i1']}/calificaciones")->assertUnauthorized();
    sesionesPeticion($this, 'POST', "/api/v1/inscripciones/{$m['i1']}/calificaciones", ['tipo' => 'final', 'valor' => '8.00'])->assertUnauthorized();
    sesionesPeticion($this, 'PATCH', "/api/v1/calificaciones/$id", ['valor' => '8.00'])->assertUnauthorized();
});

test('HU-07 (§7.10 fila 2): D1 captura la final 8.00 de J1 y el resumen queda en 8.00, sin reprobadas ni pendientes, sin_riesgo', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    calificacionesCapturar($this, $m['i1'], ['tipo' => 'final', 'valor' => '8.00'])->assertCreated();

    expect(calificacionesResumen($m['j1']))->toMatchObject([
        'periodo_id' => $m['p1'], 'promedio' => '8.00', 'materias_reprobadas' => 0, 'materias_sin_calificar' => 0, 'nivel_riesgo' => 'sin_riesgo',
    ]);
});

test('HU-07 (§7.10 filas 3 y 4): D1 corrige la final a 7.20 (en_observacion) y luego a 6.50 (1 reprobada, en_riesgo)', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $id = calificacionesCapturar($this, $m['i1'], ['tipo' => 'final', 'valor' => '8.00'])->json('datos.id');

    calificacionesCorregir($this, $id, ['valor' => '7.20'])->assertOk();
    expect(calificacionesResumen($m['j1']))->toMatchObject(['promedio' => '7.20', 'materias_reprobadas' => 0, 'nivel_riesgo' => 'en_observacion']);

    calificacionesCorregir($this, $id, ['valor' => '6.50'])->assertOk();
    expect(calificacionesResumen($m['j1']))->toMatchObject(['promedio' => '6.50', 'materias_reprobadas' => 1, 'nivel_riesgo' => 'en_riesgo']);
});

test('RN-04 (§7.10 fila 7): J3 con final 9.00 en M1 (D1) y 6.00 en M2 (D2) tiene un único riesgo: 7.50, 1 reprobada, en_observacion', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    calificacionesCapturar($this, $m['i3a'], ['tipo' => 'final', 'valor' => '9.00'], 'd1@x.com')->assertCreated();
    calificacionesCapturar($this, $m['i3b'], ['tipo' => 'final', 'valor' => '6.00'], 'd2@x.com')->assertCreated();

    expect(calificacionesResumen($m['j3']))->toMatchObject(['promedio' => '7.50', 'materias_reprobadas' => 1, 'materias_sin_calificar' => 0, 'nivel_riesgo' => 'en_observacion'])
        ->and(DB::table('resumenes_academicos')->where('jugador_id', $m['j3'])->count())->toBe(1);
});

test('HU-07: dos capturas simultáneas de la misma calificación: el índice único también responde calificacion_duplicada', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $criterio = DB::table('criterios_aprobacion')->where('ambito', 'general')->value('id');
    $repositorio = app(RepositorioDeCalificaciones::class);
    $nueva = fn () => new Calificacion(
        Identificador::generar(),
        Identificador::desdeTexto($m['i1']),
        TipoDeCalificacion::Final,
        null,
        NumeroDecimal::desdeTexto('8.00'),
        Identificador::desdeTexto($criterio),
        new DateTimeImmutable('2026-03-10', new DateTimeZone('UTC')),
        new DateTimeImmutable('2026-03-10 12:00:00', new DateTimeZone('UTC')),
    );
    $repositorio->guardarNueva($nueva());

    expect(fn () => $repositorio->guardarNueva($nueva()))->toThrow(CalificacionDuplicada::class);
    expect(DB::table('calificaciones')->count())->toBe(1)->and(DB::table('calificaciones_historial')->count())->toBe(1);
});
