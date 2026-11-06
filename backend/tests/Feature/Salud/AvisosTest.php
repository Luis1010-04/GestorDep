<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Tests\Ayudantes\NoNulo;
use Tests\TestCase;

const CAMPOS_DEL_AVISO = ['id', 'estado', 'fecha_aviso', 'reportado_por_jugador', 'nota', 'nota_descarte', 'es_propio', 'creado_en', 'actualizado_en'];

const UUID_V4_DE_PRUEBA = '3f2b8c1e-7a4d-4e9b-8f10-2c5d6e7f8a90';

/**
 * @param  array<string, mixed>  $cambios
 * @return array<string, mixed>
 */
function avisoCuerpo(array $cambios = []): array
{
    return ['nota' => 'Molestia en el tobillo', 'fecha' => '2026-11-14', ...$cambios];
}

test('§7.1 fila 1 (HU-19 c1, c3): E1 crea un aviso, queda pendiente y el estado oficial no cambia', function () {
    /** @var TestCase $this */
    $m = avisosMundo();

    $respuesta = sesionesPeticion($this, 'POST', avisosRuta($m['f1']), avisoCuerpo(), sesionesIniciar($this, 'e1@x.com'));

    $respuesta->assertCreated()
        ->assertJsonPath('datos.estado', 'pendiente')
        ->assertJsonPath('datos.fecha_aviso', '2026-11-14')
        ->assertJsonPath('datos.nota', 'Molestia en el tobillo')
        ->assertJsonPath('datos.reportado_por_jugador', false)
        ->assertJsonPath('datos.es_propio', true);
    expect(DB::table('estados_disponibilidad')->where('jugador_id', $m['f1'])->count())->toBe(0)
        ->and(DB::table('avisos_lesion')->where('id', $respuesta->json('datos.id'))->value('creado_por'))->toBe($m['e1']);
});

test('§7.1 fila 2 (HU-19 c2): D1 crea un aviso reportado por el jugador', function () {
    /** @var TestCase $this */
    $m = avisosMundo();

    sesionesPeticion($this, 'POST', avisosRuta($m['f1']), avisoCuerpo(['reportado_por_jugador' => true]), sesionesIniciar($this, 'd1@x.com'))
        ->assertCreated()
        ->assertJsonPath('datos.reportado_por_jugador', true)
        ->assertJsonPath('datos.estado', 'pendiente');
});

test('§7.1 fila 3 (RNF-01): D2, sin relación con F1, recibe el mismo 404 que con un jugador inexistente', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $sesion = sesionesIniciar($this, 'd2@x.com');

    $ajeno = sesionesPeticion($this, 'POST', avisosRuta($m['f1']), avisoCuerpo(), $sesion)->assertNotFound();
    $inexistente = sesionesPeticion($this, 'POST', avisosRuta(Identificador::generar()->valor()), avisoCuerpo(), $sesion)->assertNotFound();

    expect($ajeno->getContent())->toBe($inexistente->getContent())
        ->and(DB::table('avisos_lesion')->count())->toBe(0);
});

test('§7.1 fila 4 (DD-58): E2 ve el aviso pendiente de D1 con la nota nula', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['d1']);

    sesionesPeticion($this, 'GET', avisosRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'e2@x.com'))
        ->assertOk()
        ->assertJsonCount(1, 'datos')
        ->assertJsonPath('datos.0.id', $aviso)
        ->assertJsonPath('datos.0.nota', null)
        ->assertJsonPath('datos.0.es_propio', false);
});

test('§7.1 fila 5 (§5.3): D1 no ve el aviso pendiente de E1', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    avisoInsertar($m['f1'], $m['e1']);

    sesionesPeticion($this, 'GET', avisosRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'd1@x.com'))
        ->assertOk()
        ->assertJsonCount(0, 'datos')
        ->assertJsonPath('meta.total', 0);
});

test('§7.1 fila 6 (DD-56): E1 lista los tres avisos, del más reciente al más antiguo, cada uno con su estado', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $pendiente = avisoInsertar($m['f1'], $m['e1'], ['fecha_aviso' => '2026-11-10']);
    $descartado = avisoInsertar($m['f1'], $m['e1'], ['fecha_aviso' => '2026-11-12', 'estado' => 'descartado', 'descartado_por' => $m['e2'], 'nota_descarte' => 'No aplica']);
    $confirmado = avisoInsertar($m['f1'], $m['e1'], ['fecha_aviso' => '2026-11-14', 'estado' => 'confirmado', 'confirmado_por' => $m['e2']]);

    $respuesta = sesionesPeticion($this, 'GET', avisosRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'e1@x.com'))->assertOk();

    expect(array_column($respuesta->json('datos'), 'id'))->toBe([$confirmado, $descartado, $pendiente])
        ->and(array_column($respuesta->json('datos'), 'estado'))->toBe(['confirmado', 'descartado', 'pendiente']);
});

test('§7.1 fila 7 (HU-30 c3): E2 descarta con nota; el estado oficial no cambia y queda el historial con la fila anterior', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['d1']);
    $estado = mundoInsertar('estados_disponibilidad', ['jugador_id' => $m['f1'], 'estado' => 'apto', 'confirmado_por' => $m['e1'], 'vigente_desde' => '2026-11-01 10:00:00+00']);
    $antes = (array) NoNulo::exigir(DB::table('estados_disponibilidad')->where('id', $estado)->first(), 'el estado oficial');

    sesionesPeticion($this, 'POST', avisoDescarteRuta($aviso), ['nota_descarte' => 'No fue una lesión'], sesionesIniciar($this, 'e2@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.estado', 'descartado')
        ->assertJsonPath('datos.nota_descarte', 'No fue una lesión');

    $historial = NoNulo::exigir(DB::table('avisos_lesion_historial')->where('aviso_id', $aviso)->first(), 'el historial del aviso');
    expect((array) NoNulo::exigir(DB::table('estados_disponibilidad')->where('id', $estado)->first(), 'el estado oficial'))->toBe($antes)
        ->and(DB::table('estados_disponibilidad')->where('jugador_id', $m['f1'])->count())->toBe(1)
        ->and(DB::table('avisos_lesion')->where('id', $aviso)->value('descartado_por'))->toBe($m['e2'])
        ->and($historial->operacion)->toBe('actualizar')
        ->and($historial->usuario_id)->toBe($m['e2'])
        ->and(json_decode((string) $historial->datos_anteriores, true)['estado'])->toBe('pendiente');
});

test('§7.1 fila 8 (§6.3): descartar un aviso ya descartado responde 409 aviso_no_pendiente', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['e1'], ['estado' => 'descartado', 'descartado_por' => $m['e2'], 'nota_descarte' => 'No aplica']);

    sesionesPeticion($this, 'POST', avisoDescarteRuta($aviso), ['nota_descarte' => 'Otra vez'], sesionesIniciar($this, 'e1@x.com'))
        ->assertConflict()
        ->assertJsonPath('codigo', 'aviso_no_pendiente')
        ->assertJsonPath('mensaje', __('errores.reglas.aviso_no_pendiente'));
    expect(DB::table('avisos_lesion')->where('id', $aviso)->value('nota_descarte'))->toBe('No aplica');
});

test('§6.3: descartar un aviso confirmado responde 409 aviso_no_pendiente', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['e1'], ['estado' => 'confirmado', 'confirmado_por' => $m['e2']]);

    sesionesPeticion($this, 'POST', avisoDescarteRuta($aviso), ['nota_descarte' => 'No aplica'], sesionesIniciar($this, 'e1@x.com'))
        ->assertConflict()
        ->assertJsonPath('codigo', 'aviso_no_pendiente');
});

test('DA-28: descartar un aviso no pendiente con el cuerpo inválido responde 422, no 409', function (string $estado, array $cuerpo, string $campo) {
    /** @var TestCase $this */
    $m = avisosMundo();
    $atributos = $estado === 'descartado'
        ? ['estado' => 'descartado', 'descartado_por' => $m['e2'], 'nota_descarte' => 'No aplica']
        : ['estado' => 'confirmado', 'confirmado_por' => $m['e2']];
    $aviso = avisoInsertar($m['f1'], $m['e1'], $atributos);
    $antes = (array) DB::table('avisos_lesion')->where('id', $aviso)->first();

    sesionesPeticion($this, 'POST', avisoDescarteRuta($aviso), $cuerpo, sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', $campo);
    expect((array) DB::table('avisos_lesion')->where('id', $aviso)->first())->toBe($antes);
})->with([
    'descartado sin nota' => ['descartado', [], 'nota_descarte'],
    'descartado con nota larga' => ['descartado', ['nota_descarte' => str_repeat('a', 501)], 'nota_descarte'],
    'descartado con campo desconocido' => ['descartado', ['nota_descarte' => 'No aplica', 'estado' => 'apto'], 'estado'],
    'confirmado sin nota' => ['confirmado', [], 'nota_descarte'],
    'confirmado con nota larga' => ['confirmado', ['nota_descarte' => str_repeat('a', 501)], 'nota_descarte'],
    'confirmado con campo desconocido' => ['confirmado', ['nota_descarte' => 'No aplica', 'estado' => 'apto'], 'estado'],
]);

test('§7.1 fila 9 (DD-61): D1 no descarta: 403 sin_permiso, también con un aviso inexistente', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['d1']);
    $sesion = sesionesIniciar($this, 'd1@x.com');

    sesionesPeticion($this, 'POST', avisoDescarteRuta($aviso), ['nota_descarte' => 'No aplica'], $sesion)
        ->assertForbidden()
        ->assertJsonPath('codigo', 'sin_permiso');
    sesionesPeticion($this, 'POST', avisoDescarteRuta(Identificador::generar()->valor()), [], $sesion)
        ->assertForbidden();
    expect(DB::table('avisos_lesion')->where('id', $aviso)->value('estado'))->toBe('pendiente');
});

test('§7.1 fila 10 (DA-11): la nota y la nota del descarte según quién lista', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    avisoInsertar($m['f1'], $m['d1'], ['estado' => 'descartado', 'descartado_por' => $m['e2'], 'nota_descarte' => 'No aplica']);

    sesionesPeticion($this, 'GET', avisosRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'e1@x.com'))
        ->assertJsonPath('datos.0.nota', null)
        ->assertJsonPath('datos.0.nota_descarte', null);
    sesionesPeticion($this, 'GET', avisosRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'd1@x.com'))
        ->assertJsonPath('datos.0.nota', 'Molestia en el tobillo')
        ->assertJsonPath('datos.0.nota_descarte', 'No aplica');
    sesionesPeticion($this, 'GET', avisosRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'e2@x.com'))
        ->assertJsonPath('datos.0.nota', null)
        ->assertJsonPath('datos.0.nota_descarte', 'No aplica');
});

test('§7.1 fila 11 (DD-60, DA-27): crear un aviso de JB en baja responde 409 jugador_en_baja con su mensaje propio', function (string $correo) {
    /** @var TestCase $this */
    $m = avisosMundo();

    sesionesPeticion($this, 'POST', avisosRuta($m['jb']), avisoCuerpo(), sesionesIniciar($this, $correo))
        ->assertConflict()
        ->assertExactJson(['codigo' => 'jugador_en_baja', 'mensaje' => 'El jugador está en baja: no se pueden crear avisos.', 'errores' => []]);
    expect(DB::table('avisos_lesion')->count())->toBe(0);
})->with(['entrenador' => 'e1@x.com', 'docente' => 'd1@x.com']);

test('DA-27: el mensaje existente de jugador_en_baja no cambia', function () {
    expect(__('errores.reglas.jugador_en_baja'))->toBe('El jugador está en baja: no se puede asignar, inscribir ni capturar calificaciones o asistencias.');
});

test('§7.1 fila 12 (HU-19): un aviso sin nota responde 422 validacion_fallida en nota', function () {
    /** @var TestCase $this */
    $m = avisosMundo();

    sesionesPeticion($this, 'POST', avisosRuta($m['f1']), ['fecha' => '2026-11-14'], sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', 'nota');
});

test('§6.2: la nota se recorta y admite hasta 500 caracteres', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'POST', avisosRuta($m['f1']), avisoCuerpo(['nota' => '  Molestia  ']), $sesion)
        ->assertCreated()->assertJsonPath('datos.nota', 'Molestia');
    sesionesPeticion($this, 'POST', avisosRuta($m['f1']), avisoCuerpo(['nota' => str_repeat('a', 500)]), $sesion)->assertCreated();
    sesionesPeticion($this, 'POST', avisosRuta($m['f1']), avisoCuerpo(['nota' => str_repeat('a', 501)]), $sesion)->assertUnprocessable();
    sesionesPeticion($this, 'POST', avisosRuta($m['f1']), avisoCuerpo(['nota' => '   ']), $sesion)->assertUnprocessable();
});

test('§6.2: la fecha es obligatoria y con formato AAAA-MM-DD', function (array $cuerpo) {
    /** @var TestCase $this */
    $m = avisosMundo();

    sesionesPeticion($this, 'POST', avisosRuta($m['f1']), $cuerpo, sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('errores.0.campo', 'fecha');
})->with([
    'sin fecha' => [['nota' => 'Molestia']],
    'otro formato' => [['nota' => 'Molestia', 'fecha' => '14/11/2026']],
    'fecha imposible' => [['nota' => 'Molestia', 'fecha' => '2026-02-31']],
]);

test('DA-23: la fecha de un aviso puede ser futura', function () {
    /** @var TestCase $this */
    $m = avisosMundo();

    sesionesPeticion($this, 'POST', avisosRuta($m['f1']), avisoCuerpo(['fecha' => '2026-12-31']), sesionesIniciar($this, 'e1@x.com'))
        ->assertCreated()
        ->assertJsonPath('datos.fecha_aviso', '2026-12-31');
});

test('DA-24: con el jugador en baja, un cuerpo inválido responde 422 antes que el 409', function () {
    /** @var TestCase $this */
    $m = avisosMundo();

    sesionesPeticion($this, 'POST', avisosRuta($m['jb']), [], sesionesIniciar($this, 'e1@x.com'))->assertUnprocessable();
});

test('§6.2: los campos desconocidos y los booleanos no estrictos responden 422', function (array $cambios, string $campo) {
    /** @var TestCase $this */
    $m = avisosMundo();

    sesionesPeticion($this, 'POST', avisosRuta($m['f1']), avisoCuerpo($cambios), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('errores.0.campo', $campo);
})->with([
    'campo desconocido' => [['estado' => 'confirmado'], 'estado'],
    'campo desconocido vacío' => [['creado_por' => ''], 'creado_por'],
    'booleano como texto' => [['reportado_por_jugador' => 'true'], 'reportado_por_jugador'],
    'booleano como número' => [['reportado_por_jugador' => 1], 'reportado_por_jugador'],
]);

test('§6.1: fuera del alcance, el 404 gana sobre el 422 al crear y al descartar', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['j1'], $m['e1']);
    $sesion = sesionesIniciar($this, 'e2@x.com');

    sesionesPeticion($this, 'POST', avisosRuta($m['j1']), [], $sesion)->assertNotFound();
    sesionesPeticion($this, 'POST', avisoDescarteRuta($aviso), [], $sesion)->assertNotFound();
    expect(DB::table('avisos_lesion')->where('id', $aviso)->value('estado'))->toBe('pendiente');
});

test('§6.1: un aviso ajeno responde igual que uno inexistente', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['j1'], $m['e1']);
    $sesion = sesionesIniciar($this, 'e2@x.com');

    $ajeno = sesionesPeticion($this, 'POST', avisoDescarteRuta($aviso), ['nota_descarte' => 'No'], $sesion)->assertNotFound();
    $inexistente = sesionesPeticion($this, 'POST', avisoDescarteRuta(Identificador::generar()->valor()), ['nota_descarte' => 'No'], $sesion)->assertNotFound();

    expect($ajeno->getContent())->toBe($inexistente->getContent());
});

test('§6.1: un UUID válido que no es v7 responde 404, nunca 500', function () {
    /** @var TestCase $this */
    avisosMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'GET', avisosRuta(UUID_V4_DE_PRUEBA), idDeSesion: $sesion)->assertNotFound();
    sesionesPeticion($this, 'POST', avisosRuta(UUID_V4_DE_PRUEBA), avisoCuerpo(), $sesion)->assertNotFound();
    sesionesPeticion($this, 'POST', avisoDescarteRuta(UUID_V4_DE_PRUEBA), ['nota_descarte' => 'No'], $sesion)->assertNotFound();
});

test('RNF-01: listar a un jugador fuera del alcance responde el mismo 404 que uno inexistente', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $sesion = sesionesIniciar($this, 'e2@x.com');

    $ajeno = sesionesPeticion($this, 'GET', avisosRuta($m['j1']), idDeSesion: $sesion)->assertNotFound();
    $inexistente = sesionesPeticion($this, 'GET', avisosRuta(Identificador::generar()->valor()), idDeSesion: $sesion)->assertNotFound();

    expect($ajeno->getContent())->toBe($inexistente->getContent());
});

test('DA-12: con el jugador en baja, E1 lista los avisos y descarta uno pendiente', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['jb'], $m['e1']);
    $sesion = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'GET', avisosRuta($m['jb']), idDeSesion: $sesion)->assertOk()->assertJsonCount(1, 'datos');
    sesionesPeticion($this, 'POST', avisoDescarteRuta($aviso), ['nota_descarte' => 'Ya no aplica'], $sesion)
        ->assertOk()
        ->assertJsonPath('datos.estado', 'descartado');
});

test('DA-12: el docente lista sus avisos de un jugador en baja', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    avisoInsertar($m['jb'], $m['d1']);

    sesionesPeticion($this, 'GET', avisosRuta($m['jb']), idDeSesion: sesionesIniciar($this, 'd1@x.com'))
        ->assertOk()->assertJsonCount(1, 'datos');
});

test('DD-61: DE descarta como entrenador del jugador', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['d1']);

    sesionesPeticion($this, 'POST', avisoDescarteRuta($aviso), ['nota_descarte' => 'No aplica'], sesionesIniciar($this, 'de@x.com'))->assertOk();
});

test('DD-61: un usuario con los dos roles que solo le da clase al jugador recibe 404 al descartar', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    sesionesCrearUsuario('dx@x.com', ['docente', 'entrenador']);
    $dx = (string) DB::table('usuarios')->where('correo', 'dx@x.com')->value('id');
    mundoInscribir(mundoInsertar('materias', ['asignatura_id' => $m['asignatura'], 'periodo_id' => $m['periodo'], 'docente_id' => $dx]), $m['j1']);
    $aviso = avisoInsertar($m['j1'], $dx);

    sesionesPeticion($this, 'POST', avisoDescarteRuta($aviso), ['nota_descarte' => 'No aplica'], sesionesIniciar($this, 'dx@x.com'))->assertNotFound();
});

test('DA-25: un usuario con los dos roles ve todos los avisos si entrena al jugador', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    avisoInsertar($m['f1'], $m['e1']);
    avisoInsertar($m['f1'], $m['d1']);

    sesionesPeticion($this, 'GET', avisosRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'de@x.com'))
        ->assertOk()->assertJsonCount(2, 'datos');
});

test('DA-25: un usuario con los dos roles ve solo los suyos si únicamente le da clase al jugador', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    sesionesCrearUsuario('dx@x.com', ['docente', 'entrenador']);
    $dx = (string) DB::table('usuarios')->where('correo', 'dx@x.com')->value('id');
    mundoInscribir(mundoInsertar('materias', ['asignatura_id' => $m['asignatura'], 'periodo_id' => $m['periodo'], 'docente_id' => $dx]), $m['j1']);
    avisoInsertar($m['j1'], $m['e1']);
    $propio = avisoInsertar($m['j1'], $dx);

    sesionesPeticion($this, 'GET', avisosRuta($m['j1']), idDeSesion: sesionesIniciar($this, 'dx@x.com'))
        ->assertOk()
        ->assertJsonCount(1, 'datos')
        ->assertJsonPath('datos.0.id', $propio);
});

test('RN-16 frente a §6.1 (opción A): un docente sin alcance recibe 404 aunque haya creado el aviso', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    avisoInsertar($m['f1'], $m['d1']);
    DB::connection('propietario')->table('inscripciones')->where('id', $m['inscripcion_f1'])->update(['eliminado_en' => '2026-11-15 10:00:00+00']);
    $sesion = sesionesIniciar($this, 'd1@x.com');

    sesionesPeticion($this, 'GET', avisosRuta($m['f1']), idDeSesion: $sesion)->assertNotFound();
    sesionesPeticion($this, 'POST', avisosRuta($m['f1']), avisoCuerpo(), $sesion)->assertNotFound();
});

test('DD-58: la nota de un aviso confirmado la ve quien lo confirmó, no otro entrenador', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    avisoInsertar($m['f1'], $m['d1'], ['estado' => 'confirmado', 'confirmado_por' => $m['e2']]);

    sesionesPeticion($this, 'GET', avisosRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'e2@x.com'))
        ->assertJsonPath('datos.0.nota', 'Molestia en el tobillo');
    sesionesPeticion($this, 'GET', avisosRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'e1@x.com'))
        ->assertJsonPath('datos.0.nota', null);
});

test('DA-18: la respuesta trae solo los campos del recurso, al crear, listar y descartar', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    $creado = sesionesPeticion($this, 'POST', avisosRuta($m['f1']), avisoCuerpo(), $sesion)->assertCreated();
    $listado = sesionesPeticion($this, 'GET', avisosRuta($m['f1']), idDeSesion: $sesion)->assertOk();
    $descartado = sesionesPeticion($this, 'POST', avisoDescarteRuta($creado->json('datos.id')), ['nota_descarte' => 'No aplica'], $sesion)->assertOk();

    expect(array_keys($creado->json('datos')))->toBe(CAMPOS_DEL_AVISO)
        ->and(array_keys($listado->json('datos.0')))->toBe(CAMPOS_DEL_AVISO)
        ->and(array_keys($descartado->json('datos')))->toBe(CAMPOS_DEL_AVISO);
});

test('RNF-04: el aviso devuelve sus instantes y el descarte actualiza actualizado_en', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    $creado = sesionesPeticion($this, 'POST', avisosRuta($m['f1']), avisoCuerpo(), $sesion)
        ->assertJsonPath('datos.creado_en', '2026-11-15T18:00:00+00:00')
        ->assertJsonPath('datos.actualizado_en', '2026-11-15T18:00:00+00:00');

    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-11-16 09:00:00+00:00')));

    sesionesPeticion($this, 'POST', avisoDescarteRuta($creado->json('datos.id')), ['nota_descarte' => 'No aplica'], $sesion)
        ->assertJsonPath('datos.creado_en', '2026-11-15T18:00:00+00:00')
        ->assertJsonPath('datos.actualizado_en', '2026-11-16T09:00:00+00:00');
});

test('§6.1: el listado pagina con pagina y por_pagina', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    avisoInsertar($m['f1'], $m['e1'], ['fecha_aviso' => '2026-11-10']);
    $reciente = avisoInsertar($m['f1'], $m['e1'], ['fecha_aviso' => '2026-11-14']);
    $sesion = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'GET', avisosRuta($m['f1']).'?pagina=1&por_pagina=1', idDeSesion: $sesion)
        ->assertOk()
        ->assertJsonCount(1, 'datos')
        ->assertJsonPath('datos.0.id', $reciente)
        ->assertJsonPath('meta', ['pagina' => 1, 'por_pagina' => 1, 'total' => 2, 'total_paginas' => 2]);
    sesionesPeticion($this, 'GET', avisosRuta($m['f1']).'?pagina=0', idDeSesion: $sesion)->assertUnprocessable();
    sesionesPeticion($this, 'GET', avisosRuta($m['f1']).'?por_pagina=101', idDeSesion: $sesion)->assertUnprocessable();
});

test('§6.2: nota_descarte es obligatoria, se recorta, admite hasta 500 caracteres y no acepta otros campos', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['e1']);
    $otro = avisoInsertar($m['f1'], $m['e1']);
    $sesion = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'POST', avisoDescarteRuta($aviso), [], $sesion)
        ->assertUnprocessable()->assertJsonPath('errores.0.campo', 'nota_descarte');
    sesionesPeticion($this, 'POST', avisoDescarteRuta($aviso), ['nota_descarte' => str_repeat('a', 501)], $sesion)->assertUnprocessable();
    sesionesPeticion($this, 'POST', avisoDescarteRuta($aviso), ['nota_descarte' => 'No aplica', 'estado' => 'apto'], $sesion)
        ->assertUnprocessable()->assertJsonPath('errores.0.campo', 'estado');
    sesionesPeticion($this, 'POST', avisoDescarteRuta($aviso), ['nota_descarte' => '  No aplica  '], $sesion)
        ->assertOk()->assertJsonPath('datos.nota_descarte', 'No aplica');
    sesionesPeticion($this, 'POST', avisoDescarteRuta($otro), ['nota_descarte' => str_repeat('a', 500)], $sesion)->assertOk();
});

test('RNF-02: crear escribe la auditoría y el historial con su autor', function () {
    /** @var TestCase $this */
    $m = avisosMundo();

    $id = sesionesPeticion($this, 'POST', avisosRuta($m['f1']), avisoCuerpo(), sesionesIniciar($this, 'd1@x.com'))->json('datos.id');

    $historial = NoNulo::exigir(DB::table('avisos_lesion_historial')->where('aviso_id', $id)->first(), 'el historial del aviso');
    expect($historial->operacion)->toBe('crear')
        ->and($historial->usuario_id)->toBe($m['d1'])
        ->and(DB::table('avisos_lesion')->where('id', $id)->value('actualizado_por'))->toBe($m['d1']);
});

test('DA-26 (§7.1 fila 1): tras crear un aviso, la API sigue dando a F1 apto implícito', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'POST', avisosRuta($m['f1']), avisoCuerpo(), $sesion)->assertCreated();

    sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: $sesion)
        ->assertOk()
        ->assertJsonPath('datos.estado', 'apto')
        ->assertJsonPath('datos.es_implicito', true);
});

test('DA-26 (§7.1 fila 7): tras descartar un aviso, la API da el mismo estado oficial', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e1']);
    $aviso = avisoInsertar($m['f1'], $m['d1']);
    $sesion = sesionesIniciar($this, 'e2@x.com');
    $antes = sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: $sesion)->assertOk()->json('datos');

    sesionesPeticion($this, 'POST', avisoDescarteRuta($aviso), ['nota_descarte' => 'No aplica'], $sesion)->assertOk();

    expect(sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: $sesion)->assertOk()->json('datos'))->toBe($antes);
});
