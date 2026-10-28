<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Src\Academico\Aplicacion\AsistenciaDuplicada;
use Src\Academico\Dominio\AsistenciaDeClase;
use Src\Academico\Dominio\EstadoDeAsistencia;
use Src\Academico\Dominio\RepositorioDeAsistencias;
use Src\Compartido\Dominio\Eventos\AsistenciaDeClaseRegistrada;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/** Asistencia a clase (HU-07 criterio 3, RN-05, DD-44, DD-50, DD-52, DD-55). Datos de partida de docs/hitos/f1-h2.md §7.0. */

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function asistenciasCapturar(TestCase $prueba, string $inscripcionId, array $cuerpo, string $correo = 'd1@x.com'): TestResponse
{
    return sesionesPeticion($prueba, 'POST', "/api/v1/inscripciones/$inscripcionId/asistencias", $cuerpo, sesionesIniciar($prueba, $correo));
}

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function asistenciasCorregir(TestCase $prueba, string $asistenciaId, array $cuerpo, string $correo = 'd1@x.com'): TestResponse
{
    return sesionesPeticion($prueba, 'PATCH', "/api/v1/asistencias-clase/$asistenciaId", $cuerpo, sesionesIniciar($prueba, $correo));
}

test('HU-07 (§7.8 fila 1): D1 registra presente el 2026-03-09 en I1, con historial crear y evento', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    Event::fake([AsistenciaDeClaseRegistrada::class]);

    $respuesta = asistenciasCapturar($this, $m['i1'], ['fecha' => '2026-03-09', 'estado' => 'presente'])->assertCreated();

    $id = $respuesta->json('datos.id');
    expect($respuesta->json('datos'))->toBe([
        'id' => $id, 'inscripcion_id' => $m['i1'], 'fecha' => '2026-03-09', 'estado' => 'presente', 'actualizado_en' => '2026-03-10T12:00:00+00:00',
    ]);
    expect(DB::table('asistencias_clase')->where('id', $id)->value('creado_por'))->toBe($m['d1']);
    $historial = DB::table('asistencias_clase_historial')->where('asistencia_clase_id', $id)->get();
    expect($historial)->toHaveCount(1)
        ->and($historial[0]->operacion)->toBe('crear')
        ->and($historial[0]->version)->toBe(1)
        ->and($historial[0]->usuario_id)->toBe($m['d1'])
        ->and(json_decode($historial[0]->datos_nuevos, true))->toEqual(['inscripcion_id' => $m['i1'], 'fecha' => '2026-03-09', 'estado' => 'presente']);
    Event::assertDispatchedTimes(AsistenciaDeClaseRegistrada::class, 1);
    Event::assertDispatched(AsistenciaDeClaseRegistrada::class, fn (AsistenciaDeClaseRegistrada $e) => $e->inscripcionId === $m['i1'] && $e->materiaId === $m['m1'] && $e->jugadorId === $m['j1']);
});

test('HU-07 (§7.8 fila 2): un estado inválido o una fecha con formato inválido dan 422 con el campo señalado', function (array $cuerpo, string $campo) {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    asistenciasCapturar($this, $m['i1'], $cuerpo)
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', $campo);
    expect(DB::table('asistencias_clase')->count())->toBe(0);
})->with([
    'estado desconocido' => [['fecha' => '2026-03-09', 'estado' => 'tarde'], 'estado'],
    'sin estado' => [['fecha' => '2026-03-09'], 'estado'],
    'estado numérico' => [['estado' => 1], 'estado'],
    'fecha con barras' => [['fecha' => '09/03/2026', 'estado' => 'presente'], 'fecha'],
    'fecha inexistente' => [['fecha' => '2026-02-30', 'estado' => 'presente'], 'fecha'],
]);

test('HU-07 (§7.8 fila 3): la misma inscripción y fecha otra vez da 409 asistencia_duplicada', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    asistenciasCapturar($this, $m['i1'], ['fecha' => '2026-03-09', 'estado' => 'presente'])->assertCreated();

    asistenciasCapturar($this, $m['i1'], ['fecha' => '2026-03-09', 'estado' => 'falta'])
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'asistencia_duplicada', 'mensaje' => 'Ya existe una asistencia de esa inscripción en esa fecha.', 'errores' => []]);
    asistenciasCapturar($this, $m['i3a'], ['fecha' => '2026-03-09', 'estado' => 'falta'])->assertCreated();
    expect(DB::table('asistencias_clase')->count())->toBe(2)->and(DB::table('asistencias_clase_historial')->count())->toBe(2);
});

test('HU-07 (§7.8 fila 4): PATCH a falta_justificada responde 200 y escribe actualizar con el valor anterior y el nuevo', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $id = asistenciasCapturar($this, $m['i1'], ['fecha' => '2026-03-09', 'estado' => 'falta'])->json('datos.id');
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-03-11 09:30:00', new DateTimeZone('UTC'))));
    Event::fake([AsistenciaDeClaseRegistrada::class]);

    asistenciasCorregir($this, $id, ['estado' => 'falta_justificada'])
        ->assertOk()
        ->assertJsonPath('datos.estado', 'falta_justificada')
        ->assertJsonPath('datos.fecha', '2026-03-09')
        ->assertJsonPath('datos.actualizado_en', '2026-03-11T09:30:00+00:00');

    $historial = DB::table('asistencias_clase_historial')->where('asistencia_clase_id', $id)->orderBy('version')->get();
    expect($historial)->toHaveCount(2)
        ->and($historial[1]->operacion)->toBe('actualizar')
        ->and($historial[1]->version)->toBe(2)
        ->and($historial[1]->usuario_id)->toBe($m['d1'])
        ->and(json_decode($historial[1]->datos_anteriores, true))->toEqual(['inscripcion_id' => $m['i1'], 'fecha' => '2026-03-09', 'estado' => 'falta'])
        ->and(json_decode($historial[1]->datos_nuevos, true))->toEqual(['inscripcion_id' => $m['i1'], 'fecha' => '2026-03-09', 'estado' => 'falta_justificada']);
    Event::assertDispatchedTimes(AsistenciaDeClaseRegistrada::class, 1);
});

test('HU-07 (§7.8 fila 5): PATCH con el mismo estado responde 200 sin historial, sin tocar actualizado_en y sin evento', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $id = asistenciasCapturar($this, $m['i1'], ['fecha' => '2026-03-09', 'estado' => 'presente'])->json('datos.id');
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-03-11 09:30:00', new DateTimeZone('UTC'))));
    Event::fake([AsistenciaDeClaseRegistrada::class]);

    asistenciasCorregir($this, $id, ['estado' => 'presente'])->assertOk()->assertJsonPath('datos.actualizado_en', '2026-03-10T12:00:00+00:00');

    expect(DB::table('asistencias_clase_historial')->where('asistencia_clase_id', $id)->count())->toBe(1);
    Event::assertNotDispatched(AsistenciaDeClaseRegistrada::class);
});

test('HU-07 (§7.8 fila 6): PATCH con otro campo (fecha) da 422 prohibited y no cambia nada', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $id = asistenciasCapturar($this, $m['i1'], ['fecha' => '2026-03-09', 'estado' => 'presente'])->json('datos.id');

    foreach ([['fecha' => '2026-03-08'], ['estado' => 'falta', 'fecha' => '2026-03-08'], ['estado' => 'falta', 'inscripcion_id' => $m['i3a']]] as $cuerpo) {
        $campo = array_values(array_diff(array_keys($cuerpo), ['estado']))[0];
        $respuesta = asistenciasCorregir($this, $id, $cuerpo)->assertUnprocessable()->assertJsonPath('codigo', 'validacion_fallida');
        /** @var list<array{campo: string, codigo: string}> $errores */
        $errores = $respuesta->json('errores');
        expect(array_values(array_filter($errores, fn (array $error): bool => $error['campo'] === $campo))[0] ?? null)->toMatchArray(['campo' => $campo, 'codigo' => 'prohibited']);
    }
    asistenciasCorregir($this, $id, ['estado' => 'tarde'])->assertUnprocessable()->assertJsonPath('errores.0.campo', 'estado');
    asistenciasCorregir($this, $id, [])->assertUnprocessable()->assertJsonPath('errores.0.campo', 'estado');

    expect(DB::table('asistencias_clase')->where('id', $id)->first())->toMatchObject(['estado' => 'presente', 'fecha' => '2026-03-09'])
        ->and(DB::table('asistencias_clase_historial')->where('asistencia_clase_id', $id)->count())->toBe(1);
});

test('DD-50 (§7.8 fila 7): D2 sobre I1 o sus asistencias recibe un 404 idéntico al de un recurso inexistente, con cualquier cuerpo', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $id = asistenciasCapturar($this, $m['i1'], ['fecha' => '2026-03-09', 'estado' => 'presente'])->json('datos.id');
    $sesion = sesionesIniciar($this, 'd2@x.com');
    $inexistente = Identificador::generar()->valor();
    $noV7 = '123e4567-e89b-42d3-a456-426614174000';
    $referencia = sesionesPeticion($this, 'POST', "/api/v1/inscripciones/$inexistente/asistencias", ['estado' => 'falta'], $sesion)->assertNotFound();
    expect($referencia->json())->toBe(['codigo' => 'recurso_no_encontrado', 'mensaje' => 'El recurso solicitado no existe.', 'errores' => []]);

    foreach ([['estado' => 'falta'], ['fecha' => '2026-03-09', 'estado' => 'presente'], ['estado' => 'tarde'], ['fecha' => 'x'], []] as $cuerpo) {
        foreach (['POST' => ["/api/v1/inscripciones/{$m['i1']}/asistencias", "/api/v1/inscripciones/$noV7/asistencias"], 'PATCH' => ["/api/v1/asistencias-clase/$id", "/api/v1/asistencias-clase/$inexistente", "/api/v1/asistencias-clase/$noV7"]] as $metodo => $rutas) {
            foreach ($rutas as $ruta) {
                $respuesta = sesionesPeticion($this, $metodo, $ruta, $cuerpo, $sesion)->assertNotFound();
                expect($respuesta->getContent())->toBe($referencia->getContent());
            }
        }
    }
    foreach (["/api/v1/inscripciones/{$m['i1']}/asistencias", "/api/v1/inscripciones/$inexistente/asistencias", "/api/v1/inscripciones/{$m['i1']}/asistencias?pagina=abc", "/api/v1/inscripciones/{$m['i3a']}/asistencias"] as $ruta) {
        $respuesta = sesionesPeticion($this, 'GET', $ruta, idDeSesion: $sesion)->assertNotFound();
        expect($respuesta->getContent())->toBe($referencia->getContent());
    }
    expect(DB::table('asistencias_clase')->count())->toBe(1)
        ->and(DB::table('asistencias_clase')->where('id', $id)->value('estado'))->toBe('presente');
});

test('HU-01 (§7.8 fila 8): un entrenador sin el rol docente recibe 403 en las tres rutas de asistencias', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $id = asistenciasCapturar($this, $m['i1'], ['fecha' => '2026-03-09', 'estado' => 'presente'])->json('datos.id');
    $sesion = sesionesIniciar($this, 'a@x.com');

    sesionesPeticion($this, 'GET', "/api/v1/inscripciones/{$m['i1']}/asistencias", idDeSesion: $sesion)->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    sesionesPeticion($this, 'POST', "/api/v1/inscripciones/{$m['i1']}/asistencias", ['estado' => 'falta'], $sesion)->assertForbidden();
    sesionesPeticion($this, 'PATCH', "/api/v1/asistencias-clase/$id", ['estado' => 'falta'], $sesion)->assertForbidden();
    expect(DB::table('asistencias_clase')->where('id', $id)->value('estado'))->toBe('presente');
});

test('DD-52 (§7.8 fila 9): la asistencia de I5 (J5 en baja) da 409 jugador_en_baja, y tampoco se corrige una previa', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $previa = mundoAsistir($m['i5'], '2026-03-02', 'falta');

    asistenciasCapturar($this, $m['i5'], ['estado' => 'presente'])
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'jugador_en_baja', 'mensaje' => 'El jugador está en baja: no se puede asignar, inscribir ni capturar calificaciones o asistencias.', 'errores' => []]);
    asistenciasCorregir($this, $previa, ['estado' => 'falta_justificada'])->assertStatus(409)->assertJsonPath('codigo', 'jugador_en_baja');

    expect(DB::table('asistencias_clase')->where('inscripcion_id', $m['i5'])->count())->toBe(1)
        ->and(DB::table('asistencias_clase')->where('id', $previa)->value('estado'))->toBe('falta');
    sesionesPeticion($this, 'GET', "/api/v1/inscripciones/{$m['i5']}/asistencias", idDeSesion: sesionesIniciar($this, 'd1@x.com'))
        ->assertOk()->assertJsonPath('datos.0.id', $previa);
});

test('HU-07 (§7.8 fila 10): el listado de I1 va por -fecha, solo de I1 y paginado', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $segunda = mundoAsistir($m['i1'], '2026-03-02', 'falta');
    $tercera = mundoAsistir($m['i1'], '2026-03-09', 'presente');
    $primera = mundoAsistir($m['i1'], '2026-02-23', 'falta_justificada');
    mundoAsistir($m['i3a'], '2026-03-05', 'presente');
    $eliminada = mundoAsistir($m['i1'], '2026-03-06', 'presente');
    DB::connection('propietario')->table('asistencias_clase')->where('id', $eliminada)->update(['eliminado_en' => '2026-03-07 00:00:00+00', 'eliminado_por' => $m['d1']]);
    $sesion = sesionesIniciar($this, 'd1@x.com');

    $respuesta = sesionesPeticion($this, 'GET', "/api/v1/inscripciones/{$m['i1']}/asistencias", idDeSesion: $sesion)->assertOk();

    expect(array_column($respuesta->json('datos'), 'id'))->toBe([$tercera, $segunda, $primera])
        ->and(array_column($respuesta->json('datos'), 'fecha'))->toBe(['2026-03-09', '2026-03-02', '2026-02-23'])
        ->and($respuesta->json('meta'))->toBe(['pagina' => 1, 'por_pagina' => 15, 'total' => 3, 'total_paginas' => 1]);
    sesionesPeticion($this, 'GET', "/api/v1/inscripciones/{$m['i1']}/asistencias?por_pagina=1&pagina=3", idDeSesion: $sesion)
        ->assertJsonPath('datos.0.id', $primera)
        ->assertJsonPath('meta', ['pagina' => 3, 'por_pagina' => 1, 'total' => 3, 'total_paginas' => 3]);
    sesionesPeticion($this, 'GET', "/api/v1/inscripciones/{$m['i1']}/asistencias?pagina=0", idDeSesion: $sesion)->assertUnprocessable();
});

test('DD-55 (§7.8 fila 11): sin fecha se usa hoy de negocio; una fecha futura no se rechaza', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    asistenciasCapturar($this, $m['i1'], ['estado' => 'presente'])->assertCreated()->assertJsonPath('datos.fecha', '2026-03-10');
    asistenciasCapturar($this, $m['i1'], ['estado' => 'presente', 'fecha' => '2027-01-15'])->assertCreated()->assertJsonPath('datos.fecha', '2027-01-15');
});

test('RNF-02 (§7.8 fila 12): si falla el historial al capturar no queda la asistencia ni se publica el evento', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    esquemaMarcarSucio();
    DB::connection('propietario')->statement('alter table asistencias_clase_historial add constraint ck_prueba_fallo check (false)');
    Event::fake([AsistenciaDeClaseRegistrada::class]);

    sesionesPeticion($this, 'POST', "/api/v1/inscripciones/{$m['i1']}/asistencias", ['fecha' => '2026-03-09', 'estado' => 'presente'], $sesion)
        ->assertStatus(500)->assertJsonPath('codigo', 'error_interno');

    expect(DB::table('asistencias_clase')->count())->toBe(0)->and(DB::table('asistencias_clase_historial')->count())->toBe(0);
    Event::assertNotDispatched(AsistenciaDeClaseRegistrada::class);
});

test('RNF-02 (§7.11 fila 16): si falla el historial al corregir el estado no cambia ni se publica el evento', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $id = asistenciasCapturar($this, $m['i1'], ['fecha' => '2026-03-09', 'estado' => 'falta'])->json('datos.id');
    $sesion = sesionesIniciar($this, 'd1@x.com');
    esquemaMarcarSucio();
    DB::connection('propietario')->statement("alter table asistencias_clase_historial add constraint ck_prueba_fallo check (operacion <> 'actualizar')");
    Event::fake([AsistenciaDeClaseRegistrada::class]);

    sesionesPeticion($this, 'PATCH', "/api/v1/asistencias-clase/$id", ['estado' => 'falta_justificada'], $sesion)->assertStatus(500)->assertJsonPath('codigo', 'error_interno');

    expect(DB::table('asistencias_clase')->where('id', $id)->first())->toMatchObject(['estado' => 'falta', 'actualizado_en' => '2026-03-10 12:00:00+00'])
        ->and(DB::table('asistencias_clase_historial')->where('asistencia_clase_id', $id)->count())->toBe(1);
    Event::assertNotDispatched(AsistenciaDeClaseRegistrada::class);
});

test('HU-01: sin sesión las rutas de asistencias responden 401', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $id = Identificador::generar()->valor();

    sesionesPeticion($this, 'GET', "/api/v1/inscripciones/{$m['i1']}/asistencias")->assertUnauthorized();
    sesionesPeticion($this, 'POST', "/api/v1/inscripciones/{$m['i1']}/asistencias", ['estado' => 'presente'])->assertUnauthorized();
    sesionesPeticion($this, 'PATCH', "/api/v1/asistencias-clase/$id", ['estado' => 'presente'])->assertUnauthorized();
});

test('RN-05 (§7.10 fila 8): con 7 presentes, 1 falta justificada y 2 faltas el porcentaje es 80.00 y se recalcula en cada asistencia', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $estados = [...array_fill(0, 7, 'presente'), 'falta_justificada', 'falta', 'falta'];
    $esperados = ['100.00', '100.00', '100.00', '100.00', '100.00', '100.00', '100.00', '100.00', '88.89', '80.00'];

    foreach ($estados as $i => $estado) {
        asistenciasCapturar($this, $m['i1'], ['fecha' => sprintf('2026-02-%02d', $i + 1), 'estado' => $estado])->assertCreated();
        expect(DB::table('resumenes_academicos')->where('jugador_id', $m['j1'])->value('porcentaje_asistencia'))->toBe($esperados[$i]);
    }

    // RN-05: la asistencia no entra en el riesgo (sin calificaciones, nivel nulo).
    expect(DB::table('resumenes_academicos')->where('jugador_id', $m['j1'])->first())->toMatchObject(['porcentaje_asistencia' => '80.00', 'nivel_riesgo' => null]);
});

test('RN-05: corregir una falta a falta justificada recalcula el porcentaje', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    asistenciasCapturar($this, $m['i1'], ['fecha' => '2026-03-02', 'estado' => 'presente'])->assertCreated();
    $falta = asistenciasCapturar($this, $m['i1'], ['fecha' => '2026-03-09', 'estado' => 'falta'])->json('datos.id');
    expect(DB::table('resumenes_academicos')->where('jugador_id', $m['j1'])->value('porcentaje_asistencia'))->toBe('50.00');

    asistenciasCorregir($this, $falta, ['estado' => 'falta_justificada'])->assertOk();

    expect(DB::table('resumenes_academicos')->where('jugador_id', $m['j1'])->value('porcentaje_asistencia'))->toBe('100.00');
});

test('HU-07: dos capturas simultáneas de la misma fecha: el índice único también responde asistencia_duplicada', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $repositorio = app(RepositorioDeAsistencias::class);
    $nueva = fn () => new AsistenciaDeClase(
        Identificador::generar(),
        Identificador::desdeTexto($m['i1']),
        new DateTimeImmutable('2026-03-09', new DateTimeZone('UTC')),
        EstadoDeAsistencia::Presente,
        new DateTimeImmutable('2026-03-10 12:00:00', new DateTimeZone('UTC')),
    );
    $repositorio->guardarNueva($nueva());

    expect(fn () => $repositorio->guardarNueva($nueva()))->toThrow(AsistenciaDuplicada::class);
    expect(DB::table('asistencias_clase')->count())->toBe(1)->and(DB::table('asistencias_clase_historial')->count())->toBe(1);
});
