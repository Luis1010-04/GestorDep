<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/** Entrenamientos (HU-10, docs/hitos/f1-h3.md §7.2). Las horas locales son de America/Mexico_City (UTC-6). */
beforeEach(function () {
    sesionesPrepararBase();
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-09-01 15:00:00', new DateTimeZone('UTC'))));
});

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function entrenamientoPeticion(TestCase $prueba, string $metodo, string $ruta, array $cuerpo = [], string $correo = 'e1@x.com'): TestResponse
{
    return sesionesPeticion($prueba, $metodo, '/api/v1'.$ruta, $cuerpo, sesionesIniciar($prueba, $correo));
}

/**
 * @param  array<string, string>  $m
 * @param  array<string, mixed>  $extra
 * @return array<string, mixed>
 */
function entrenamientoCuerpo(array $m, array $extra = []): array
{
    return [
        'inicia_en' => '2026-09-10T17:00:00-06:00', 'termina_en' => '2026-09-10T18:00:00-06:00',
        'lugar_id' => $m['l1'], 'responsable_id' => $m['e1'], ...$extra,
    ];
}

/**
 * Entrenamiento de A en L1 del 10 de septiembre de 17:00 a 18:00 locales.
 *
 * @param  array<string, string>  $m
 * @param  array<string, mixed>  $atributos
 */
function entrenamientoDeA(array $m, array $atributos = []): string
{
    return mundoEntrenamiento($m['a'], $m['l1'], $m['e1'], '2026-09-10 23:00:00+00', '2026-09-11 00:00:00+00', $atributos);
}

test('HU-10 (§7.2 fila 1): E1 crea un entrenamiento de A con L1 y responsable E1; lista J1, J2 y J3, pero no J5', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();

    $respuesta = entrenamientoPeticion($this, 'POST', "/equipos/{$m['a']}/entrenamientos", entrenamientoCuerpo($m))->assertCreated();

    $id = $respuesta->json('datos.id');
    expect($respuesta->json())->toBe(['datos' => [
        'id' => $id, 'equipo_id' => $m['a'], 'lugar_id' => $m['l1'],
        'inicia_en' => '2026-09-10T23:00:00+00:00', 'termina_en' => '2026-09-11T00:00:00+00:00',
        'responsable_id' => $m['e1'], 'estado' => 'programado', 'actualizado_en' => '2026-09-01T15:00:00+00:00',
        'jugadores_del_equipo' => [
            ['id' => $m['j2'], 'nombres' => 'J2', 'apellidos' => 'Dos'],
            ['id' => $m['j3'], 'nombres' => 'J3', 'apellidos' => 'Tres'],
            ['id' => $m['j1'], 'nombres' => 'J1', 'apellidos' => 'Uno'],
        ],
    ]]);
    $historial = DB::table('entrenamientos_historial')->where('entrenamiento_id', $id)->get();
    expect($historial)->toHaveCount(1)
        ->and($historial[0]->operacion)->toBe('crear')
        ->and($historial[0]->usuario_id)->toBe($m['e1'])
        ->and(json_decode($historial[0]->datos_nuevos, true))->toMatchArray(['equipo_id' => $m['a'], 'estado' => 'programado', 'responsable_id' => $m['e1']]);
});

test('HU-10 (§7.2 fila 2): otro entrenamiento en L1 de 17:30 a 18:30 se guarda con la advertencia empalme_de_lugar', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    entrenamientoDeA($m);

    $respuesta = entrenamientoPeticion($this, 'POST', "/equipos/{$m['a']}/entrenamientos", entrenamientoCuerpo($m, [
        'inicia_en' => '2026-09-10T17:30:00-06:00', 'termina_en' => '2026-09-10T18:30:00-06:00',
    ]))->assertCreated();

    expect($respuesta->json('advertencias'))->toBe([['codigo' => 'empalme_de_lugar', 'mensaje' => 'Otro entrenamiento usa el mismo lugar en ese horario.']])
        ->and(DB::table('entrenamientos')->count())->toBe(2);
});

test('HU-10 (§7.2 fila 3): E1 cambia la hora y entrenamientos_historial registra el cambio con autor y fecha', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = entrenamientoDeA($m);

    $respuesta = entrenamientoPeticion($this, 'PATCH', "/entrenamientos/$id", [
        'inicia_en' => '2026-09-10T18:00:00-06:00', 'termina_en' => '2026-09-10T19:30:00-06:00',
    ])->assertOk();

    expect($respuesta->json('datos.inicia_en'))->toBe('2026-09-11T00:00:00+00:00')
        ->and($respuesta->json('datos.termina_en'))->toBe('2026-09-11T01:30:00+00:00')
        ->and($respuesta->json('datos.jugadores_del_equipo'))->toHaveCount(3);
    $fila = DB::table('entrenamientos_historial')->where('entrenamiento_id', $id)->first();
    expect($fila->operacion)->toBe('actualizar')
        ->and($fila->usuario_id)->toBe($m['e1'])
        ->and($fila->ocurrido_en)->toBe('2026-09-01 15:00:00+00')
        ->and(json_decode($fila->datos_anteriores, true))->toMatchArray(['inicia_en' => '2026-09-10T23:00:00+00:00', 'termina_en' => '2026-09-11T00:00:00+00:00'])
        ->and(json_decode($fila->datos_nuevos, true))->toMatchArray(['inicia_en' => '2026-09-11T00:00:00+00:00', 'termina_en' => '2026-09-11T01:30:00+00:00'])
        ->and(DB::table('entrenamientos')->where('id', $id)->value('actualizado_por'))->toBe($m['e1']);
});

test('HU-10 (§7.2 fila 4): E1 cancela un entrenamiento programado y ya no cuenta para empalmes', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = entrenamientoDeA($m);

    entrenamientoPeticion($this, 'PATCH', "/entrenamientos/$id", ['estado' => 'cancelado'])->assertOk()->assertJsonPath('datos.estado', 'cancelado');

    $otro = entrenamientoPeticion($this, 'POST', "/equipos/{$m['a']}/entrenamientos", entrenamientoCuerpo($m))->assertCreated();
    expect($otro->json())->not->toHaveKey('advertencias')
        ->and(DB::table('entrenamientos_historial')->where('entrenamiento_id', $id)->value('operacion'))->toBe('actualizar');
});

test('HU-10 (§7.2 fila 5): poner realizado un entrenamiento cancelado da 409 transicion_no_permitida', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = entrenamientoDeA($m, ['estado' => 'cancelado']);

    entrenamientoPeticion($this, 'PATCH', "/entrenamientos/$id", ['estado' => 'realizado'])
        ->assertStatus(409)
        ->assertExactJson([
            'codigo' => 'transicion_no_permitida', 'mensaje' => 'El cambio de estado no está permitido.',
            'errores' => [['campo' => 'estado', 'codigo' => 'transicion_no_permitida', 'mensaje' => 'El cambio de estado no está permitido.']],
        ]);
    expect(DB::table('entrenamientos')->where('id', $id)->value('estado'))->toBe('cancelado')
        ->and(DB::table('entrenamientos_historial')->count())->toBe(0);
});

test('HU-10 (§7.2 fila 6): E1 crea un entrenamiento de B y recibe 404', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();

    $ajeno = entrenamientoPeticion($this, 'POST', "/equipos/{$m['b']}/entrenamientos", entrenamientoCuerpo($m))->assertNotFound();
    $inexistente = entrenamientoPeticion($this, 'POST', '/equipos/'.Identificador::generar()->valor().'/entrenamientos', entrenamientoCuerpo($m))->assertNotFound();

    expect($ajeno->getContent())->toBe($inexistente->getContent())
        ->and($ajeno->json('codigo'))->toBe('recurso_no_encontrado')
        ->and(DB::table('entrenamientos')->count())->toBe(0);
});

test('HU-10 (§7.2 fila 7): un responsable que no entrena A da 422 en responsable_id', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();

    entrenamientoPeticion($this, 'POST', "/equipos/{$m['a']}/entrenamientos", entrenamientoCuerpo($m, ['responsable_id' => $m['e2']]))
        ->assertUnprocessable()
        ->assertExactJson([
            'codigo' => 'responsable_no_asignado', 'mensaje' => 'El responsable debe ser un entrenador asignado al equipo.',
            'errores' => [['campo' => 'responsable_id', 'codigo' => 'responsable_no_asignado', 'mensaje' => 'El responsable debe ser un entrenador asignado al equipo.']],
        ]);
    expect(DB::table('entrenamientos')->count())->toBe(0);
});

test('HU-10: el responsable puede ser otro entrenador del equipo, pero no un docente, un usuario inexistente ni uno con la asignación cerrada', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $cerrado = sesionesCrearUsuario('cerrado@x.com', ['entrenador']);
    mundoInsertar('entrenador_equipo', ['usuario_id' => $cerrado, 'equipo_id' => $m['a'], 'desde' => '2026-01-01', 'hasta' => '2026-06-30']);
    $sinRol = sesionesCrearUsuario('sinrol@x.com', ['docente']);
    mundoInsertar('entrenador_equipo', ['usuario_id' => $sinRol, 'equipo_id' => $m['a'], 'desde' => '2026-01-01']);
    $ruta = "/equipos/{$m['a']}/entrenamientos";

    entrenamientoPeticion($this, 'POST', $ruta, entrenamientoCuerpo($m, ['responsable_id' => $m['e3']]))->assertCreated()->assertJsonPath('datos.responsable_id', $m['e3']);
    foreach ([$cerrado, $sinRol, $m['d'], Identificador::generar()->valor(), '123e4567-e89b-42d3-a456-426614174000'] as $responsable) {
        entrenamientoPeticion($this, 'POST', $ruta, entrenamientoCuerpo($m, ['responsable_id' => $responsable]))
            ->assertUnprocessable()->assertJsonPath('codigo', 'responsable_no_asignado');
    }
});

test('HU-10: cambiar el responsable a quien no entrena el equipo da 422; a otro entrenador del equipo, 200', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = entrenamientoDeA($m);

    entrenamientoPeticion($this, 'PATCH', "/entrenamientos/$id", ['responsable_id' => $m['e2']])
        ->assertUnprocessable()->assertJsonPath('errores.0.campo', 'responsable_id');
    entrenamientoPeticion($this, 'PATCH', "/entrenamientos/$id", ['responsable_id' => $m['e3']])
        ->assertOk()->assertJsonPath('datos.responsable_id', $m['e3']);
});

test('HU-10: el empalme cuenta entrenamientos de otro equipo en el mismo lugar; tocarse en la hora no es empalme', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    mundoEntrenamiento($m['b'], $m['l1'], $m['e2'], '2026-09-10 23:00:00+00', '2026-09-11 00:00:00+00');
    $otroLugar = mundoInsertar('lugares', ['nombre' => 'Cancha sur']);
    $ruta = "/equipos/{$m['a']}/entrenamientos";

    expect(entrenamientoPeticion($this, 'POST', $ruta, entrenamientoCuerpo($m))->assertCreated()->json('advertencias.0.codigo'))->toBe('empalme_de_lugar')
        ->and(entrenamientoPeticion($this, 'POST', $ruta, entrenamientoCuerpo($m, [
            'inicia_en' => '2026-09-10T18:00:00-06:00', 'termina_en' => '2026-09-10T19:00:00-06:00',
        ]))->assertCreated()->json())->not->toHaveKey('advertencias')
        ->and(entrenamientoPeticion($this, 'POST', $ruta, entrenamientoCuerpo($m, [
            'inicia_en' => '2026-09-10T16:00:00-06:00', 'termina_en' => '2026-09-10T17:00:00-06:00',
        ]))->assertCreated()->json())->not->toHaveKey('advertencias')
        ->and(entrenamientoPeticion($this, 'POST', $ruta, entrenamientoCuerpo($m, ['lugar_id' => $otroLugar]))->assertCreated()->json())->not->toHaveKey('advertencias')
        ->and(entrenamientoPeticion($this, 'POST', $ruta, entrenamientoCuerpo($m, [
            'inicia_en' => '2026-09-10T16:00:00-06:00', 'termina_en' => '2026-09-10T20:00:00-06:00',
        ]))->assertCreated()->json('advertencias.0.codigo'))->toBe('empalme_de_lugar');
});

test('HU-10: modificar un entrenamiento hacia un horario ocupado advierte el empalme, sin contarse a sí mismo', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = entrenamientoDeA($m);
    mundoEntrenamiento($m['a'], $m['l1'], $m['e1'], '2026-09-12 23:00:00+00', '2026-09-13 00:00:00+00');

    expect(entrenamientoPeticion($this, 'PATCH', "/entrenamientos/$id", ['termina_en' => '2026-09-10T18:30:00-06:00'])->assertOk()->json())->not->toHaveKey('advertencias')
        ->and(entrenamientoPeticion($this, 'PATCH', "/entrenamientos/$id", [
            'inicia_en' => '2026-09-12T17:30:00-06:00', 'termina_en' => '2026-09-12T19:00:00-06:00',
        ])->assertOk()->json('advertencias.0.codigo'))->toBe('empalme_de_lugar');
});

test('HU-10: de programado se pasa a realizado; de realizado no se pasa a cancelado ni se repite el estado', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = entrenamientoDeA($m);
    $ruta = "/entrenamientos/$id";

    entrenamientoPeticion($this, 'PATCH', $ruta, ['estado' => 'realizado'])->assertOk()->assertJsonPath('datos.estado', 'realizado');
    entrenamientoPeticion($this, 'PATCH', $ruta, ['estado' => 'cancelado'])->assertStatus(409)->assertJsonPath('codigo', 'transicion_no_permitida');
    entrenamientoPeticion($this, 'PATCH', $ruta, ['estado' => 'realizado'])->assertStatus(409)->assertJsonPath('codigo', 'transicion_no_permitida');
    entrenamientoPeticion($this, 'PATCH', $ruta, ['estado' => 'programado'])->assertUnprocessable()->assertJsonPath('errores.0.campo', 'estado');
});

test('HU-10 (DA-08): un entrenamiento cancelado o realizado no admite cambios de hora, lugar ni responsable', function (string $estado) {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = entrenamientoDeA($m, ['estado' => $estado]);

    entrenamientoPeticion($this, 'PATCH', "/entrenamientos/$id", ['responsable_id' => $m['e3']])
        ->assertStatus(409)
        ->assertExactJson(['codigo' => 'entrenamiento_no_programado', 'mensaje' => 'El entrenamiento ya no está programado.', 'errores' => []]);
    expect(DB::table('entrenamientos')->where('id', $id)->value('responsable_id'))->toBe($m['e1']);
})->with(['cancelado', 'realizado']);

test('HU-10: cambiar datos y cancelar en la misma petición aplica los dos cambios en una sola fila de historial', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = entrenamientoDeA($m);

    entrenamientoPeticion($this, 'PATCH', "/entrenamientos/$id", ['responsable_id' => $m['e3'], 'estado' => 'cancelado'])
        ->assertOk()->assertJsonPath('datos.estado', 'cancelado')->assertJsonPath('datos.responsable_id', $m['e3']);

    expect(DB::table('entrenamientos_historial')->where('entrenamiento_id', $id)->count())->toBe(1);
});

test('HU-10: un horario invertido da 422 en termina_en al crear y rango_de_fechas_invalido al modificar una sola hora', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = entrenamientoDeA($m);

    foreach (['2026-09-10T17:00:00-06:00', '2026-09-10T16:59:59-06:00'] as $termina) {
        expect(entrenamientoPeticion($this, 'POST', "/equipos/{$m['a']}/entrenamientos", entrenamientoCuerpo($m, ['termina_en' => $termina]))
            ->assertUnprocessable()->json('errores.*.campo'))->toBe(['termina_en']);
    }
    entrenamientoPeticion($this, 'PATCH', "/entrenamientos/$id", ['termina_en' => '2026-09-10T16:00:00-06:00'])
        ->assertUnprocessable()->assertJsonPath('codigo', 'rango_de_fechas_invalido');
    entrenamientoPeticion($this, 'PATCH', "/entrenamientos/$id", ['inicia_en' => '2026-09-10T18:00:00-06:00'])
        ->assertUnprocessable()->assertJsonPath('codigo', 'rango_de_fechas_invalido');
    expect(DB::table('entrenamientos')->count())->toBe(1);
});

test('HU-10: un lugar inexistente o eliminado da 422 lugar_inexistente', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = entrenamientoDeA($m);
    $eliminado = mundoInsertar('lugares', ['nombre' => 'Vieja', 'eliminado_en' => '2026-08-01 10:00:00+00']);

    foreach ([$eliminado, Identificador::generar()->valor(), '123e4567-e89b-42d3-a456-426614174000'] as $lugar) {
        entrenamientoPeticion($this, 'POST', "/equipos/{$m['a']}/entrenamientos", entrenamientoCuerpo($m, ['lugar_id' => $lugar]))
            ->assertUnprocessable()
            ->assertExactJson(['codigo' => 'lugar_inexistente', 'mensaje' => 'El lugar indicado no existe.', 'errores' => []]);
    }
    entrenamientoPeticion($this, 'PATCH', "/entrenamientos/$id", ['lugar_id' => $eliminado])->assertUnprocessable()->assertJsonPath('codigo', 'lugar_inexistente');
});

test('HU-10: los campos faltantes o mal formados dan 422 con el campo señalado', function (array $cuerpo, array $campos) {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $cuerpo = array_map(fn ($valor) => $valor === '{l1}' ? $m['l1'] : ($valor === '{e1}' ? $m['e1'] : $valor), $cuerpo);

    expect(entrenamientoPeticion($this, 'POST', "/equipos/{$m['a']}/entrenamientos", $cuerpo)->assertUnprocessable()->json('errores.*.campo'))->toBe($campos);
})->with([
    'vacío' => [[], ['inicia_en', 'termina_en', 'lugar_id', 'responsable_id']],
    'sin zona' => [['inicia_en' => '2026-09-10T17:00:00', 'termina_en' => '2026-09-10T18:00:00-06:00', 'lugar_id' => '{l1}', 'responsable_id' => '{e1}'], ['inicia_en']],
    'lugar no uuid' => [['inicia_en' => '2026-09-10T17:00:00Z', 'termina_en' => '2026-09-10T18:00:00Z', 'lugar_id' => 'x', 'responsable_id' => '{e1}'], ['lugar_id']],
]);

test('HU-10: un PATCH vacío, con un campo no admitido o con un estado inválido da 422', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $ruta = '/entrenamientos/'.entrenamientoDeA($m);

    entrenamientoPeticion($this, 'PATCH', $ruta)->assertUnprocessable()->assertJsonPath('errores.0.campo', 'cuerpo');
    entrenamientoPeticion($this, 'PATCH', $ruta, ['equipo_id' => $m['b']])->assertUnprocessable()
        ->assertJsonPath('errores.0.campo', 'equipo_id')->assertJsonPath('errores.0.codigo', 'prohibited');
    entrenamientoPeticion($this, 'PATCH', $ruta, ['estado' => 'suspendido'])->assertUnprocessable()->assertJsonPath('errores.0.campo', 'estado');
});

test('HU-10: el detalle lista los jugadores del equipo; E2 recibe 404 y E3 lo ve', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = entrenamientoDeA($m);

    expect(entrenamientoPeticion($this, 'GET', "/entrenamientos/$id")->assertOk()->json('datos.jugadores_del_equipo.*.id'))->toBe([$m['j2'], $m['j3'], $m['j1']])
        ->and(entrenamientoPeticion($this, 'GET', "/entrenamientos/$id", correo: 'e3@x.com')->assertOk()->json('datos.id'))->toBe($id);

    $ajeno = entrenamientoPeticion($this, 'GET', "/entrenamientos/$id", correo: 'e2@x.com')->assertNotFound();
    $inexistente = entrenamientoPeticion($this, 'GET', '/entrenamientos/'.Identificador::generar()->valor(), correo: 'e2@x.com')->assertNotFound();
    $v4 = entrenamientoPeticion($this, 'GET', '/entrenamientos/123e4567-e89b-42d3-a456-426614174000', correo: 'e2@x.com')->assertNotFound();
    expect($ajeno->getContent())->toBe($inexistente->getContent())->and($v4->getContent())->toBe($inexistente->getContent());
});

test('HU-10: el 404 de un entrenamiento ajeno gana al 422 y no cambia nada', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = entrenamientoDeA($m);

    $valido = entrenamientoPeticion($this, 'PATCH', "/entrenamientos/$id", ['estado' => 'cancelado'], 'e2@x.com')->assertNotFound();
    $invalido = entrenamientoPeticion($this, 'PATCH', "/entrenamientos/$id", ['estado' => 'x', 'equipo_id' => 1], 'e2@x.com')->assertNotFound();
    $alta = entrenamientoPeticion($this, 'POST', "/equipos/{$m['a']}/entrenamientos", [], 'e2@x.com')->assertNotFound();

    expect($invalido->getContent())->toBe($valido->getContent())
        ->and($alta->getContent())->toBe($valido->getContent())
        ->and(DB::table('entrenamientos')->where('id', $id)->value('estado'))->toBe('programado')
        ->and(DB::table('entrenamientos_historial')->count())->toBe(0);
});

test('HU-10: el listado de un equipo filtra por días locales y ordena por inicia_en', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $antes = mundoEntrenamiento($m['a'], $m['l1'], $m['e1'], '2026-09-10 05:00:00+00', '2026-09-10 05:30:00+00');
    $primero = mundoEntrenamiento($m['a'], $m['l1'], $m['e1'], '2026-09-10 06:00:00+00', '2026-09-10 07:00:00+00');
    $ultimo = mundoEntrenamiento($m['a'], $m['l1'], $m['e1'], '2026-09-12 05:30:00+00', '2026-09-12 05:45:00+00');
    $despues = mundoEntrenamiento($m['a'], $m['l1'], $m['e1'], '2026-09-12 06:00:00+00', '2026-09-12 07:00:00+00');
    mundoEntrenamiento($m['b'], $m['l1'], $m['e2'], '2026-09-11 06:00:00+00', '2026-09-11 07:00:00+00');
    $ruta = "/equipos/{$m['a']}/entrenamientos";

    $filtrado = entrenamientoPeticion($this, 'GET', "$ruta?filtro[desde]=2026-09-10&filtro[hasta]=2026-09-11")->assertOk();

    expect($filtrado->json('datos.*.id'))->toBe([$primero, $ultimo])
        ->and($filtrado->json('datos.0'))->not->toHaveKey('jugadores_del_equipo')
        ->and($filtrado->json('meta.total'))->toBe(2)
        ->and(entrenamientoPeticion($this, 'GET', "$ruta?orden=-inicia_en")->json('datos.*.id'))->toBe([$despues, $ultimo, $primero, $antes])
        ->and(entrenamientoPeticion($this, 'GET', "$ruta?filtro[desde]=2026-09-12")->json('datos.*.id'))->toBe([$despues]);
    entrenamientoPeticion($this, 'GET', "$ruta?filtro[desde]=10-09-2026")->assertUnprocessable()->assertJsonPath('errores.0.campo', 'filtro.desde');
    entrenamientoPeticion($this, 'GET', "$ruta?filtro[desde]=2026-09-12&filtro[hasta]=2026-09-11")->assertUnprocessable()->assertJsonPath('errores.0.campo', 'filtro.hasta');
    entrenamientoPeticion($this, 'GET', "/equipos/{$m['b']}/entrenamientos")->assertNotFound();
});

test('HU-10: un docente recibe 403 y sin sesión 401 en las rutas de entrenamientos', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    $id = entrenamientoDeA($m);
    $rutas = [
        ['GET', "/equipos/{$m['a']}/entrenamientos"], ['POST', "/equipos/{$m['a']}/entrenamientos"],
        ['GET', "/entrenamientos/$id"], ['PATCH', "/entrenamientos/$id"],
    ];

    foreach ($rutas as [$metodo, $ruta]) {
        entrenamientoPeticion($this, $metodo, $ruta, entrenamientoCuerpo($m), 'd@x.com')->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
        sesionesPeticion($this, $metodo, '/api/v1'.$ruta, entrenamientoCuerpo($m))->assertUnauthorized()->assertJsonPath('codigo', 'no_autenticado');
    }
});
