<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;
use Tests\Ayudantes\NoNulo;
use Tests\TestCase;

const CAMPOS_DEL_ESTADO = ['estado', 'fecha_estimada_regreso', 'vigente_desde', 'es_implicito', 'requiere_actualizacion', 'lesion_id', 'actualizado_en'];

const INSTANTE_DEL_MUNDO = '2026-11-15T18:00:00+00:00';

const INSTANTE_DEL_ESTADO_INSERTADO = '2026-11-10T10:00:00+00:00';

const CAMPOS_DE_LA_LESION = ['id', 'tipo', 'zona', 'causa', 'fecha_inicio', 'fecha_estimada_regreso', 'fecha_alta', 'nota', 'actualizado_en'];

const MENSAJE_DE_RANGO_DE_LESION = 'La fecha no puede ser anterior al inicio de la lesión.';

const MENSAJE_DE_BAJA_EN_ESTADO = 'El jugador está en baja: no se puede confirmar el aviso ni cambiar su estado.';

/** @return array<int, array<string, mixed>> */
function estadosDe(string $jugador): array
{
    return DB::table('estados_disponibilidad')->where('jugador_id', $jugador)->orderBy('vigente_desde')->get()->map(fn (stdClass $fila) => (array) $fila)->all();
}

function estadoVigente(string $jugador): stdClass
{
    return NoNulo::exigir(DB::table('estados_disponibilidad')->where('jugador_id', $jugador)->whereNull('vigente_hasta')->first(), 'el estado vigente');
}

test('§7.2 fila 1 (HU-20 c1, RN-16): E2 confirma lesionado y queda como registrante', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['d1']);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(), sesionesIniciar($this, 'e2@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.estado', 'confirmado')
        ->assertJsonPath('datos.nota', 'Molestia en el tobillo');

    $lesion = NoNulo::exigir(DB::table('lesiones')->where('jugador_id', $m['f1'])->first(), 'la lesión');
    $fila = NoNulo::exigir(DB::table('avisos_lesion')->where('id', $aviso)->first(), 'el aviso');
    expect($lesion->registrada_por)->toBe($m['e2'])
        ->and($lesion->tipo)->toBe('Esguince')
        ->and($lesion->zona)->toBe('Tobillo')
        ->and($lesion->causa)->toBe('partido')
        ->and($lesion->fecha_inicio)->toBe('2026-11-14')
        ->and($lesion->fecha_estimada_regreso)->toBe('2026-11-30')
        ->and(estadoVigente($m['f1'])->estado)->toBe('lesionado')
        ->and(estadoVigente($m['f1'])->lesion_id)->toBe($lesion->id)
        ->and($fila->estado)->toBe('confirmado')
        ->and($fila->lesion_id)->toBe($lesion->id);
});

test('§7.2 fila 2 (HU-04 c3, HU-21 c1): E1 y E2 ven el mismo estado y la misma fecha; lesion_id solo para E2', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = estadoInsertar($m['f1'], $m['e2'])['lesion'];

    sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'e1@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.estado', 'lesionado')
        ->assertJsonPath('datos.fecha_estimada_regreso', '2026-11-30')
        ->assertJsonPath('datos.lesion_id', null);
    sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'e2@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.estado', 'lesionado')
        ->assertJsonPath('datos.fecha_estimada_regreso', '2026-11-30')
        ->assertJsonPath('datos.lesion_id', $lesion);
});

test('§7.2 fila 3 (RN-13, DA-10): D1 ve el estado y la fecha, sin lesion_id', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e2']);

    sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'd1@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.estado', 'lesionado')
        ->assertJsonPath('datos.fecha_estimada_regreso', '2026-11-30')
        ->assertJsonPath('datos.lesion_id', null);
});

test('§7.2 fila 4 (DD-57): confirmar con_molestia sin zona responde 422 en zona', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['e1']);
    $cuerpo = confirmacionCuerpo(['estado' => 'con_molestia']);
    unset($cuerpo['zona']);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), $cuerpo, sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('errores.0.campo', 'zona');
});

test('§7.2 fila 5 (DD-57): confirmar apto con tipo responde 422 en tipo', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['e1']);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), ['estado' => 'apto', 'tipo' => 'Esguince'], sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('errores.0.campo', 'tipo');
});

test('§7.2 fila 6: confirmar apto deja un estado apto explícito, sin lesión, y el aviso confirmado sin lesión', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['e1']);
    $sesion = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), ['estado' => 'apto'], $sesion)->assertOk()->assertJsonPath('datos.estado', 'confirmado');

    sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: $sesion)
        ->assertJsonPath('datos.estado', 'apto')
        ->assertJsonPath('datos.es_implicito', false)
        ->assertJsonPath('datos.vigente_desde', '2026-11-15T18:00:00+00:00')
        ->assertJsonPath('datos.fecha_estimada_regreso', null);
    expect(DB::table('lesiones')->count())->toBe(0)
        ->and(DB::table('avisos_lesion')->where('id', $aviso)->value('lesion_id'))->toBeNull();
});

test('§7.2 fila 7 (HU-20 c2): D1 no confirma: 403 sin_permiso', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['d1']);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(), sesionesIniciar($this, 'd1@x.com'))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'sin_permiso');
    expect(DB::table('avisos_lesion')->where('id', $aviso)->value('estado'))->toBe('pendiente');
});

test('§7.2 fila 8 (DD-61): DE confirma como entrenador', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['d1']);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(), sesionesIniciar($this, 'de@x.com'))->assertOk();
});

test('§7.2 fila 9 (HU-30 c2, DA-14): E2 cambia la fecha; la fila vigente se actualiza y queda el historial con la anterior', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $estado = estadoInsertar($m['f1'], $m['e2'])['estado'];

    sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['fecha_estimada_regreso' => '2026-12-10'], sesionesIniciar($this, 'e2@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.fecha_estimada_regreso', '2026-12-10')
        ->assertJsonPath('datos.estado', 'lesionado');

    $historial = NoNulo::exigir(DB::table('estados_disponibilidad_historial')->where('estado_id', $estado)->first(), 'el historial del estado');
    expect(estadoVigente($m['f1'])->id)->toBe($estado)
        ->and(count(estadosDe($m['f1'])))->toBe(1)
        ->and($historial->operacion)->toBe('actualizar')
        ->and(json_decode((string) $historial->datos_anteriores, true)['fecha_estimada_regreso'])->toBe('2026-11-30')
        ->and(DB::table('lesiones')->where('jugador_id', $m['f1'])->value('fecha_estimada_regreso'))->toBe('2026-11-30');
});

test('§7.2 fila 10 (HU-20 c3): E1 cambia la fecha de un estado de E2: 403 no_es_registrante', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e2']);

    sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['fecha_estimada_regreso' => '2026-12-10'], sesionesIniciar($this, 'e1@x.com'))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'no_es_registrante')
        ->assertJsonPath('mensaje', __('errores.reglas.no_es_registrante'));
    expect(estadoVigente($m['f1'])->fecha_estimada_regreso)->toBe('2026-11-30');
});

test('§7.2 fila 11 (DA-14): E2 cambia a con_molestia; la fila anterior se cierra y se abre otra', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    ['estado' => $estado, 'lesion' => $lesion] = estadoInsertar($m['f1'], $m['e2']);

    sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['estado' => 'con_molestia'], sesionesIniciar($this, 'e2@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.estado', 'con_molestia')
        ->assertJsonPath('datos.fecha_estimada_regreso', '2026-11-30')
        ->assertJsonPath('datos.vigente_desde', '2026-11-15T18:00:00+00:00');

    expect(count(estadosDe($m['f1'])))->toBe(2)
        ->and(DB::table('estados_disponibilidad')->where('id', $estado)->value('vigente_hasta'))->toBe('2026-11-15 18:00:00+00')
        ->and(estadoVigente($m['f1'])->estado)->toBe('con_molestia')
        ->and(estadoVigente($m['f1'])->lesion_id)->toBe($lesion)
        ->and(estadoVigente($m['f1'])->confirmado_por)->toBe($m['e2']);
});

test('§7.2 fila 12 (HU-30 c1): E2 da de alta; estado apto, fecha de alta y el anterior cerrado y en el historial', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    ['estado' => $estado, 'lesion' => $lesion] = estadoInsertar($m['f1'], $m['e2']);

    sesionesPeticion($this, 'POST', altaRuta($m['f1']), ['fecha_alta' => '2026-11-20'], sesionesIniciar($this, 'e2@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.estado', 'apto')
        ->assertJsonPath('datos.es_implicito', false)
        ->assertJsonPath('datos.lesion_id', null);

    $historial = NoNulo::exigir(DB::table('estados_disponibilidad_historial')->where('estado_id', $estado)->first(), 'el historial del estado');
    expect(DB::table('lesiones')->where('id', $lesion)->value('fecha_alta'))->toBe('2026-11-20')
        ->and(DB::table('lesiones_historial')->where('lesion_id', $lesion)->value('operacion'))->toBe('actualizar')
        ->and(DB::table('estados_disponibilidad')->where('id', $estado)->value('vigente_hasta'))->not->toBeNull()
        ->and(estadoVigente($m['f1'])->estado)->toBe('apto')
        ->and(estadoVigente($m['f1'])->confirmado_por)->toBe($m['e2'])
        ->and($historial->operacion)->toBe('actualizar')
        ->and(json_decode((string) $historial->datos_anteriores, true)['estado'])->toBe('lesionado');
});

test('§7.2 fila 13 (§3.4): dar de alta sin estado responde 409 sin_lesion_vigente', function () {
    /** @var TestCase $this */
    $m = avisosMundo();

    sesionesPeticion($this, 'POST', altaRuta($m['f1']), [], sesionesIniciar($this, 'e1@x.com'))
        ->assertConflict()
        ->assertJsonPath('codigo', 'sin_lesion_vigente')
        ->assertJsonPath('mensaje', __('errores.reglas.sin_lesion_vigente'));
});

test('§7.2 fila 14 (HU-21 c3, DA-09): con el regreso vencido, requiere actualización', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e2'], regreso: '2026-11-14');

    sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'e1@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.requiere_actualizacion', true);
});

test('§7.2 fila 15 (DA-09): con el regreso hoy, no requiere actualización', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e2'], regreso: '2026-11-15');

    sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'e1@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.requiere_actualizacion', false);
});

test('§7.2 fila 16 (DA-17): confirmar con el regreso anterior a la fecha del aviso responde 422 con el mensaje de la lesión', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['e1'], ['fecha_aviso' => '2026-11-14']);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(['fecha_estimada_regreso' => '2026-11-13']), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertExactJson(['codigo' => 'rango_de_fechas_invalido', 'mensaje' => MENSAJE_DE_RANGO_DE_LESION, 'errores' => []]);
    expect(DB::table('lesiones')->count())->toBe(0)
        ->and(DB::table('avisos_lesion')->where('id', $aviso)->value('estado'))->toBe('pendiente');
});

test('§7.2 fila 17 (§6.3): confirmar otra vez responde 409 aviso_no_pendiente', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['e1'], ['estado' => 'confirmado', 'confirmado_por' => $m['e2']]);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(), sesionesIniciar($this, 'e1@x.com'))
        ->assertConflict()
        ->assertJsonPath('codigo', 'aviso_no_pendiente');
});

test('§7.2 fila 18 (DA-12): confirmar un aviso de JB en baja responde 409 jugador_en_baja con su mensaje', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['jb'], $m['e1']);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(), sesionesIniciar($this, 'e1@x.com'))
        ->assertConflict()
        ->assertExactJson(['codigo' => 'jugador_en_baja', 'mensaje' => MENSAJE_DE_BAJA_EN_ESTADO, 'errores' => []]);
    expect(DB::table('avisos_lesion')->where('id', $aviso)->value('estado'))->toBe('pendiente');
});

test('§7.2 fila 19 (RN-16): E2 corrige la zona y queda la anterior en el historial', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = NoNulo::exigir(estadoInsertar($m['f1'], $m['e2'])['lesion'], 'la lesión');

    sesionesPeticion($this, 'PATCH', lesionRuta($lesion), ['zona' => 'Rodilla'], sesionesIniciar($this, 'e2@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.zona', 'Rodilla')
        ->assertJsonPath('datos.tipo', 'Esguince');

    $historial = NoNulo::exigir(DB::table('lesiones_historial')->where('lesion_id', $lesion)->first(), 'el historial de la lesión');
    expect($historial->operacion)->toBe('actualizar')
        ->and(json_decode((string) $historial->datos_anteriores, true)['zona'])->toBe('Tobillo')
        ->and(json_decode((string) $historial->datos_nuevos, true)['zona'])->toBe('Rodilla');
});

test('§7.2 fila 20 (DD-63): E2 confirma con un estado vigente de E1; el de E1 se cierra y su lesión sigue sin alta y solo para E1', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    ['estado' => $estadoDeE1, 'lesion' => $lesionDeE1] = estadoInsertar($m['f1'], $m['e1']);
    $lesionDeE1 = NoNulo::exigir($lesionDeE1, 'la lesión de E1');
    $aviso = avisoInsertar($m['f1'], $m['d1']);
    $e2 = sesionesIniciar($this, 'e2@x.com');

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(['estado' => 'con_molestia']), $e2)->assertOk();

    expect(DB::table('estados_disponibilidad')->where('id', $estadoDeE1)->value('vigente_hasta'))->not->toBeNull()
        ->and(estadoVigente($m['f1'])->confirmado_por)->toBe($m['e2'])
        ->and(estadoVigente($m['f1'])->estado)->toBe('con_molestia')
        ->and(DB::table('lesiones')->where('id', $lesionDeE1)->value('fecha_alta'))->toBeNull();
    sesionesPeticion($this, 'GET', lesionRuta($lesionDeE1), idDeSesion: sesionesIniciar($this, 'e1@x.com'))->assertOk();
    sesionesPeticion($this, 'GET', lesionRuta($lesionDeE1), idDeSesion: $e2)->assertNotFound();
});

test('§7.2 fila 21 (DD-63): tras la sustitución, E1 recibe 403 y E2 cambia la fecha', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e1']);
    $aviso = avisoInsertar($m['f1'], $m['d1']);
    $e1 = sesionesIniciar($this, 'e1@x.com');
    $e2 = sesionesIniciar($this, 'e2@x.com');
    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(['estado' => 'con_molestia']), $e2)->assertOk();

    sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['fecha_estimada_regreso' => '2026-12-10'], $e1)
        ->assertForbidden()->assertJsonPath('codigo', 'no_es_registrante');
    sesionesPeticion($this, 'POST', altaRuta($m['f1']), [], $e1)
        ->assertForbidden()->assertJsonPath('codigo', 'no_es_registrante');
    sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['fecha_estimada_regreso' => '2026-12-10'], $e2)->assertOk();
});

test('§7.2 fila 22 (DA-19): al confirmar, las tres columnas son el mismo usuario', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['d1']);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(), sesionesIniciar($this, 'e2@x.com'))->assertOk();

    expect(DB::table('avisos_lesion')->where('id', $aviso)->value('confirmado_por'))->toBe($m['e2'])
        ->and(estadoVigente($m['f1'])->confirmado_por)->toBe($m['e2'])
        ->and(DB::table('lesiones')->where('jugador_id', $m['f1'])->value('registrada_por'))->toBe($m['e2']);
});

test('§7.2 fila 23 (DA-19): tras la sustitución por E1 y su alta, el aviso de E2 sigue con E2 y el estado vigente con E1', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $avisoDeE2 = avisoInsertar($m['f1'], $m['d1']);
    $avisoDeE1 = avisoInsertar($m['f1'], $m['d1']);
    $e1 = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($avisoDeE2), confirmacionCuerpo(), sesionesIniciar($this, 'e2@x.com'))->assertOk();
    relojAdelantar('2026-11-15 18:00:01');
    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($avisoDeE1), confirmacionCuerpo(), $e1)->assertOk();
    relojAdelantar('2026-11-15 18:00:02');
    sesionesPeticion($this, 'POST', altaRuta($m['f1']), [], $e1)->assertOk();

    expect(DB::table('avisos_lesion')->where('id', $avisoDeE2)->value('confirmado_por'))->toBe($m['e2'])
        ->and(estadoVigente($m['f1'])->estado)->toBe('apto')
        ->and(estadoVigente($m['f1'])->confirmado_por)->toBe($m['e1']);
});

test('DD-63: confirmar sustituye también un estado propio o un apto explícito', function (string $quien) {
    /** @var TestCase $this */
    $m = avisosMundo();
    $quien === 'propio' ? estadoInsertar($m['f1'], $m['e2']) : estadoInsertar($m['f1'], $m['e1'], null);
    $aviso = avisoInsertar($m['f1'], $m['d1']);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(), sesionesIniciar($this, 'e2@x.com'))->assertOk();

    expect(count(estadosDe($m['f1'])))->toBe(2)
        ->and(estadoVigente($m['f1'])->confirmado_por)->toBe($m['e2']);
})->with(['propio', 'apto']);

test('DA-45: confirmar otro aviso deja sin alta la lesión propia anterior; el alta solo cierra la vigente', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $anterior = estadoInsertar($m['f1'], $m['e2'])['lesion'];
    $aviso = avisoInsertar($m['f1'], $m['d1']);
    $sesion = sesionesIniciar($this, 'e2@x.com');

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(), $sesion)->assertOk();
    $vigente = estadoVigente($m['f1'])->lesion_id;

    expect($vigente)->not->toBe($anterior)
        ->and(DB::table('lesiones')->where('id', $anterior)->value('fecha_alta'))->toBeNull();

    relojAdelantar('2026-11-15 18:00:01');
    sesionesPeticion($this, 'POST', altaRuta($m['f1']), [], $sesion)->assertOk();

    expect(DB::table('lesiones')->where('id', $vigente)->value('fecha_alta'))->not->toBeNull()
        ->and(DB::table('lesiones')->where('id', $anterior)->value('fecha_alta'))->toBeNull();
});

test('DA-76 (sustituye a DA-45 en apto): confirmar apto cierra la lesión propia con el hoy de negocio, con historial, y el alta responde 409 sin_lesion_vigente', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $anterior = NoNulo::exigir(estadoInsertar($m['f1'], $m['e2'])['lesion'], 'la lesión');
    $aviso = avisoInsertar($m['f1'], $m['d1']);
    $sesion = sesionesIniciar($this, 'e2@x.com');
    // 03:00 UTC del 16 es el 15 a las 21:00 en America/Mexico_City: el hoy de negocio (DD-36) es 2026-11-15, no el día UTC.
    relojAdelantar('2026-11-16 03:00:00');

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), ['estado' => 'apto'], $sesion)->assertOk();
    $historial = DB::table('lesiones_historial')->where('lesion_id', $anterior)->orderBy('version')->get();

    expect(estadoVigente($m['f1'])->estado)->toBe('apto')
        ->and(DB::table('lesiones')->where('id', $anterior)->value('fecha_alta'))->toBe('2026-11-15')
        ->and($historial->pluck('operacion')->all())->toBe(['actualizar'])
        ->and($historial->first()?->usuario_id)->toBe($m['e2'])
        ->and(json_decode((string) $historial->first()?->datos_anteriores, true)['fecha_alta'])->toBeNull()
        ->and(json_decode((string) $historial->first()?->datos_nuevos, true)['fecha_alta'])->toBe('2026-11-15');

    relojAdelantar('2026-11-16 03:00:01');
    sesionesPeticion($this, 'POST', altaRuta($m['f1']), [], $sesion)
        ->assertConflict()
        ->assertJsonPath('codigo', 'sin_lesion_vigente');
});

test('DA-76 (RN-15, RN-16): confirmar apto no cierra la lesión de otro entrenador (DD-63)', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $deE1 = NoNulo::exigir(estadoInsertar($m['f1'], $m['e1'])['lesion'], 'la lesión de E1');
    $aviso = avisoInsertar($m['f1'], $m['d1']);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), ['estado' => 'apto'], sesionesIniciar($this, 'e2@x.com'))->assertOk();

    expect(estadoVigente($m['f1'])->estado)->toBe('apto')
        ->and(DB::table('lesiones')->where('id', $deE1)->value('fecha_alta'))->toBeNull()
        ->and(DB::table('lesiones_historial')->where('lesion_id', $deE1)->count())->toBe(0);
});

test('DA-76: confirmar apto no toca una lesión propia que ya tenía alta', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $cerrada = lesionInsertar($m['f1'], $m['e2'], ['fecha_alta' => '2026-11-12']);
    mundoInsertar('estados_disponibilidad', [
        'jugador_id' => $m['f1'], 'estado' => 'lesionado', 'lesion_id' => $cerrada, 'fecha_estimada_regreso' => '2026-11-30',
        'confirmado_por' => $m['e2'], 'vigente_desde' => '2026-11-10 10:00:00+00', 'actualizado_en' => '2026-11-10 10:00:00+00',
    ]);
    $aviso = avisoInsertar($m['f1'], $m['d1']);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), ['estado' => 'apto'], sesionesIniciar($this, 'e2@x.com'))->assertOk();

    expect(estadoVigente($m['f1'])->estado)->toBe('apto')
        ->and(DB::table('lesiones')->where('id', $cerrada)->value('fecha_alta'))->toBe('2026-11-12')
        ->and(DB::table('lesiones_historial')->where('lesion_id', $cerrada)->count())->toBe(0);
});

/**
 * Estado `lesionado` vigente de E2 sobre una lesión propia con la fecha de inicio indicada (DA-76, subdecisión del 13/11/2026).
 *
 * @param  array<string, string>  $m
 */
function estadoConLesionQueEmpieza(array $m, string $inicio): string
{
    $lesion = lesionInsertar($m['f1'], $m['e2'], ['fecha_inicio' => $inicio, 'fecha_estimada_regreso' => '2026-11-30']);
    mundoInsertar('estados_disponibilidad', [
        'jugador_id' => $m['f1'], 'estado' => 'lesionado', 'lesion_id' => $lesion, 'fecha_estimada_regreso' => '2026-11-30',
        'confirmado_por' => $m['e2'], 'vigente_desde' => '2026-11-10 10:00:00+00', 'actualizado_en' => '2026-11-10 10:00:00+00',
    ]);

    return $lesion;
}

test('DA-76 (inicio posterior a hoy): confirmar apto tiene éxito y no cierra la lesión propia que empieza después del hoy de negocio', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    // Reloj de avisosMundo: 2026-11-15 18:00 UTC; el hoy de negocio (DD-36) es 2026-11-15.
    $futura = estadoConLesionQueEmpieza($m, '2026-11-16');
    $aviso = avisoInsertar($m['f1'], $m['d1']);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), ['estado' => 'apto'], sesionesIniciar($this, 'e2@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.estado', 'confirmado');

    expect(estadoVigente($m['f1'])->estado)->toBe('apto')
        ->and(DB::table('lesiones')->where('id', $futura)->value('fecha_alta'))->toBeNull()
        ->and(DB::table('lesiones_historial')->where('lesion_id', $futura)->count())->toBe(0);
});

test('DA-76 (inicio hoy): confirmar apto cierra la lesión propia que empieza el hoy de negocio, con esa fecha', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $deHoy = estadoConLesionQueEmpieza($m, '2026-11-15');
    $aviso = avisoInsertar($m['f1'], $m['d1']);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), ['estado' => 'apto'], sesionesIniciar($this, 'e2@x.com'))->assertOk();

    expect(DB::table('lesiones')->where('id', $deHoy)->value('fecha_alta'))->toBe('2026-11-15')
        ->and(DB::table('lesiones_historial')->where('lesion_id', $deHoy)->pluck('operacion')->all())->toBe(['actualizar']);
});

test('RNF-02: confirmar escribe el historial del aviso, de la lesión y del estado', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['d1']);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(), sesionesIniciar($this, 'e2@x.com'))->assertOk();

    $lesion = (string) DB::table('lesiones')->where('jugador_id', $m['f1'])->value('id');
    $historialDelAviso = NoNulo::exigir(DB::table('avisos_lesion_historial')->where('aviso_id', $aviso)->first(), 'el historial del aviso');
    expect($historialDelAviso->operacion)->toBe('actualizar')
        ->and(json_decode((string) $historialDelAviso->datos_nuevos, true)['lesion_id'])->toBe($lesion)
        ->and(json_decode((string) $historialDelAviso->datos_anteriores, true)['estado'])->toBe('pendiente')
        ->and(DB::table('lesiones_historial')->where('lesion_id', $lesion)->value('operacion'))->toBe('crear')
        ->and(DB::table('estados_disponibilidad_historial')->where('estado_id', estadoVigente($m['f1'])->id)->value('operacion'))->toBe('crear');
});

test('DA-01: sin registro, el jugador está apto implícito', function () {
    /** @var TestCase $this */
    $m = avisosMundo();

    $respuesta = sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'e1@x.com'))->assertOk();

    expect($respuesta->json('datos'))->toBe([
        'estado' => 'apto', 'fecha_estimada_regreso' => null, 'vigente_desde' => null, 'es_implicito' => true, 'requiere_actualizacion' => false, 'lesion_id' => null, 'actualizado_en' => null,
    ]);
});

test('DA-18: el estado devuelve solo sus campos', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e2']);

    $respuesta = sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'e2@x.com'))->assertOk();

    expect(array_keys($respuesta->json('datos')))->toBe(CAMPOS_DEL_ESTADO);
});

test('RN-15: el estado oficial sigue visible para quien lo consulta sin ser entrenador ni registrante, sin exponer el detalle', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e2']);

    $contenido = (string) sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'e1@x.com'))->assertOk()->getContent();

    foreach (['Esguince', 'Tobillo', 'partido'] as $detalle) {
        expect($contenido)->not->toContain($detalle);
    }
});

test('RNF-01: consultar el estado de un jugador fuera del alcance responde el mismo 404 que uno inexistente', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $sesion = sesionesIniciar($this, 'e2@x.com');

    $ajeno = sesionesPeticion($this, 'GET', estadoRuta($m['j1']), idDeSesion: $sesion)->assertNotFound();
    $inexistente = sesionesPeticion($this, 'GET', estadoRuta(Identificador::generar()->valor()), idDeSesion: $sesion)->assertNotFound();

    expect($ajeno->getContent())->toBe($inexistente->getContent());
});

test('DD-30: el estado de un jugador en baja sigue disponible para quien ya lo tenía', function () {
    /** @var TestCase $this */
    $m = avisosMundo();

    sesionesPeticion($this, 'GET', estadoRuta($m['jb']), idDeSesion: sesionesIniciar($this, 'e1@x.com'))->assertOk();
});

test('§6.1: el docente recibe 403 en cambio de estado, alta y lesiones', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = NoNulo::exigir(estadoInsertar($m['f1'], $m['e2'])['lesion'], 'la lesión');
    $sesion = sesionesIniciar($this, 'd1@x.com');

    sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['estado' => 'con_molestia'], $sesion)->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    sesionesPeticion($this, 'POST', altaRuta($m['f1']), [], $sesion)->assertForbidden();
    sesionesPeticion($this, 'GET', lesionesRuta($m['f1']), idDeSesion: $sesion)->assertForbidden();
    sesionesPeticion($this, 'GET', lesionRuta($lesion), idDeSesion: $sesion)->assertForbidden();
    sesionesPeticion($this, 'PATCH', lesionRuta($lesion), ['zona' => 'Rodilla'], $sesion)->assertForbidden();
});

test('§6.1: fuera del alcance de entrenador, 404 antes que 422, y nunca no_es_registrante', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['j1'], $m['e1']);
    $aviso = avisoInsertar($m['j1'], $m['e1']);
    $sesion = sesionesIniciar($this, 'e2@x.com');

    sesionesPeticion($this, 'PATCH', estadoRuta($m['j1']), [], $sesion)->assertNotFound();
    sesionesPeticion($this, 'PATCH', estadoRuta($m['j1']), ['estado' => 'con_molestia'], $sesion)->assertNotFound();
    sesionesPeticion($this, 'POST', altaRuta($m['j1']), [], $sesion)->assertNotFound();
    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), [], $sesion)->assertNotFound();
    sesionesPeticion($this, 'GET', lesionesRuta($m['j1']), idDeSesion: $sesion)->assertNotFound();
});

test('DD-61: un usuario con los dos roles que solo le da clase al jugador recibe 404 al confirmar y al modificar', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $dx = sesionesCrearUsuario('dx@x.com', ['docente', 'entrenador']);
    mundoInscribir(mundoInsertar('materias', ['asignatura_id' => $m['asignatura'], 'periodo_id' => $m['periodo'], 'docente_id' => $dx]), $m['j1']);
    $aviso = avisoInsertar($m['j1'], $dx);
    $sesion = sesionesIniciar($this, 'dx@x.com');

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(), $sesion)->assertNotFound();
    sesionesPeticion($this, 'PATCH', estadoRuta($m['j1']), ['estado' => 'con_molestia'], $sesion)->assertNotFound();
    sesionesPeticion($this, 'GET', estadoRuta($m['j1']), idDeSesion: $sesion)->assertOk();
});

test('§6.1: un UUID válido que no es v7 responde 404, nunca 500', function () {
    /** @var TestCase $this */
    avisosMundo();
    $v4 = '3f2b8c1e-7a4d-4e9b-8f10-2c5d6e7f8a90';
    $sesion = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'GET', estadoRuta($v4), idDeSesion: $sesion)->assertNotFound();
    sesionesPeticion($this, 'PATCH', estadoRuta($v4), ['estado' => 'lesionado'], $sesion)->assertNotFound();
    sesionesPeticion($this, 'POST', altaRuta($v4), [], $sesion)->assertNotFound();
    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($v4), confirmacionCuerpo(), $sesion)->assertNotFound();
    sesionesPeticion($this, 'GET', lesionesRuta($v4), idDeSesion: $sesion)->assertNotFound();
    sesionesPeticion($this, 'GET', lesionRuta($v4), idDeSesion: $sesion)->assertNotFound();
    sesionesPeticion($this, 'PATCH', lesionRuta($v4), ['zona' => 'Rodilla'], $sesion)->assertNotFound();
});

test('§6.1: en confirmación, descarte y avisos del jugador, un UUID v7 inexistente responde el mismo 404 que un UUID v4', function () {
    /** @var TestCase $this */
    avisosMundo();
    $v4 = '3f2b8c1e-7a4d-4e9b-8f10-2c5d6e7f8a90';
    $v7 = Identificador::generar()->valor();
    $sesion = sesionesIniciar($this, 'e1@x.com');
    $peticiones = [
        'confirmación' => fn (string $id) => sesionesPeticion($this, 'POST', avisoConfirmacionRuta($id), confirmacionCuerpo(), $sesion),
        'descarte' => fn (string $id) => sesionesPeticion($this, 'POST', avisoDescarteRuta($id), ['nota_descarte' => 'No aplica'], $sesion),
        'avisos del jugador' => fn (string $id) => sesionesPeticion($this, 'GET', avisosRuta($id), idDeSesion: $sesion),
    ];

    foreach ($peticiones as $ruta => $peticion) {
        $conV7 = $peticion($v7)->assertNotFound();
        $conV4 = $peticion($v4)->assertNotFound();
        expect($conV7->json('codigo'))->toBe('recurso_no_encontrado', $ruta)
            ->and($conV7->getContent())->toBe($conV4->getContent(), $ruta);
    }
    expect(DB::table('avisos_lesion')->count())->toBe(0)
        ->and(DB::table('lesiones')->count())->toBe(0);
});

test('§6.1: en estado, alta, lesiones del jugador y lesión, un UUID v7 inexistente responde el mismo 404 que un UUID v4', function () {
    /** @var TestCase $this */
    avisosMundo();
    $v4 = '3f2b8c1e-7a4d-4e9b-8f10-2c5d6e7f8a90';
    $v7 = Identificador::generar()->valor();
    $sesion = sesionesIniciar($this, 'e1@x.com');
    $peticiones = [
        'GET estado' => fn (string $id) => sesionesPeticion($this, 'GET', estadoRuta($id), idDeSesion: $sesion),
        'PATCH estado' => fn (string $id) => sesionesPeticion($this, 'PATCH', estadoRuta($id), ['estado' => 'lesionado'], $sesion),
        'POST alta' => fn (string $id) => sesionesPeticion($this, 'POST', altaRuta($id), [], $sesion),
        'GET lesiones del jugador' => fn (string $id) => sesionesPeticion($this, 'GET', lesionesRuta($id), idDeSesion: $sesion),
        'GET lesión' => fn (string $id) => sesionesPeticion($this, 'GET', lesionRuta($id), idDeSesion: $sesion),
        'PATCH lesión' => fn (string $id) => sesionesPeticion($this, 'PATCH', lesionRuta($id), ['zona' => 'Rodilla'], $sesion),
    ];

    foreach ($peticiones as $ruta => $peticion) {
        $conV7 = $peticion($v7)->assertNotFound();
        $conV4 = $peticion($v4)->assertNotFound();
        expect($conV7->json('codigo'))->toBe('recurso_no_encontrado', $ruta)
            ->and($conV7->getContent())->toBe($conV4->getContent(), $ruta);
    }
    expect(DB::table('estados_disponibilidad')->count())->toBe(0)
        ->and(DB::table('lesiones')->count())->toBe(0);
});

test('Plan de T03: PATCH sin campos responde 422 en el estado y en la lesión', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = NoNulo::exigir(estadoInsertar($m['f1'], $m['e2'])['lesion'], 'la lesión');
    $sesion = sesionesIniciar($this, 'e2@x.com');

    sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), [], $sesion)
        ->assertUnprocessable()->assertJsonPath('errores.0.mensaje', __('errores.cuerpo_sin_campos_estado'));
    sesionesPeticion($this, 'PATCH', lesionRuta($lesion), [], $sesion)
        ->assertUnprocessable()->assertJsonPath('errores.0.mensaje', __('errores.cuerpo_sin_campos_lesion'));
});

test('Plan de T03: PATCH del estado con valores idénticos responde 200 sin historial ni fila nueva', function (array $cuerpo) {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e2']);
    $antes = estadosDe($m['f1']);

    sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), $cuerpo, sesionesIniciar($this, 'e2@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.estado', 'lesionado')
        ->assertJsonPath('datos.fecha_estimada_regreso', '2026-11-30');

    expect(estadosDe($m['f1']))->toBe($antes)
        ->and(DB::table('estados_disponibilidad_historial')->count())->toBe(0);
})->with([
    'mismo estado' => [['estado' => 'lesionado']],
    'misma fecha' => [['fecha_estimada_regreso' => '2026-11-30']],
    'los dos' => [['estado' => 'lesionado', 'fecha_estimada_regreso' => '2026-11-30']],
]);

test('Plan de T03: el mismo estado con otra fecha no abre fila nueva, solo aplica la fecha', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e2']);

    sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['estado' => 'lesionado', 'fecha_estimada_regreso' => '2026-12-10'], sesionesIniciar($this, 'e2@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.fecha_estimada_regreso', '2026-12-10');

    expect(count(estadosDe($m['f1'])))->toBe(1);
});

test('DA-14: cambiar estado y fecha a la vez abre otra fila con la fecha nueva', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e2']);

    sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['estado' => 'con_molestia', 'fecha_estimada_regreso' => '2026-12-10'], sesionesIniciar($this, 'e2@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.estado', 'con_molestia')
        ->assertJsonPath('datos.fecha_estimada_regreso', '2026-12-10');

    expect(count(estadosDe($m['f1'])))->toBe(2);
});

test('Plan de T03: PATCH de la lesión con valores idénticos responde 200 sin historial', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = NoNulo::exigir(estadoInsertar($m['f1'], $m['e2'])['lesion'], 'la lesión');
    $antes = DB::table('lesiones')->where('id', $lesion)->value('actualizado_en');

    sesionesPeticion($this, 'PATCH', lesionRuta($lesion), ['zona' => 'Tobillo', 'tipo' => 'Esguince'], sesionesIniciar($this, 'e2@x.com'))
        ->assertOk()->assertJsonPath('datos.zona', 'Tobillo');

    expect(DB::table('lesiones_historial')->count())->toBe(0)
        ->and(DB::table('lesiones')->where('id', $lesion)->value('actualizado_en'))->toBe($antes);
});

test('Punto 2 del plan: con estado apto explícito, cualquier entrenador recibe 409 sin_lesion_vigente, no 403', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e1'], null);
    $sesion = sesionesIniciar($this, 'e2@x.com');

    sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['estado' => 'lesionado'], $sesion)->assertConflict()->assertJsonPath('codigo', 'sin_lesion_vigente');
    sesionesPeticion($this, 'POST', altaRuta($m['f1']), [], $sesion)->assertConflict()->assertJsonPath('codigo', 'sin_lesion_vigente');
});

test('Punto 2 del plan: el 422 va antes que el 409 sin_lesion_vigente', function () {
    /** @var TestCase $this */
    $m = avisosMundo();

    sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['estado' => 'apto'], sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()->assertJsonPath('errores.0.campo', 'estado');
});

test('Punto 2 del plan: con el jugador en baja, el 403 no_es_registrante va antes que el 409 jugador_en_baja', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['jb'], $m['e3']);

    sesionesPeticion($this, 'PATCH', estadoRuta($m['jb']), ['estado' => 'con_molestia'], sesionesIniciar($this, 'e1@x.com'))
        ->assertForbidden()->assertJsonPath('codigo', 'no_es_registrante');
});

test('DA-12: cambiar el estado o la fecha de un jugador en baja responde 409 con su mensaje', function (array $cuerpo) {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['jb'], $m['e1']);

    sesionesPeticion($this, 'PATCH', estadoRuta($m['jb']), $cuerpo, sesionesIniciar($this, 'e1@x.com'))
        ->assertConflict()
        ->assertExactJson(['codigo' => 'jugador_en_baja', 'mensaje' => MENSAJE_DE_BAJA_EN_ESTADO, 'errores' => []]);
})->with([
    'estado' => [['estado' => 'con_molestia']],
    'fecha' => [['fecha_estimada_regreso' => '2026-12-10']],
]);

test('DA-12: con el jugador en baja, el alta y la corrección de la lesión se permiten', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = NoNulo::exigir(estadoInsertar($m['jb'], $m['e1'])['lesion'], 'la lesión');
    $sesion = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'PATCH', lesionRuta($lesion), ['zona' => 'Rodilla'], $sesion)->assertOk();
    sesionesPeticion($this, 'POST', altaRuta($m['jb']), [], $sesion)->assertOk()->assertJsonPath('datos.estado', 'apto');
});

test('DA-17: cambiar la fecha a una anterior al inicio de la lesión responde 422 con su mensaje', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e2']);

    sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['fecha_estimada_regreso' => '2026-11-09'], sesionesIniciar($this, 'e2@x.com'))
        ->assertUnprocessable()
        ->assertExactJson(['codigo' => 'rango_de_fechas_invalido', 'mensaje' => MENSAJE_DE_RANGO_DE_LESION, 'errores' => []]);
    expect(estadoVigente($m['f1'])->fecha_estimada_regreso)->toBe('2026-11-30');
});

test('DA-17: dar de alta con una fecha anterior al inicio responde 422 y no cambia nada', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = estadoInsertar($m['f1'], $m['e2'])['lesion'];

    sesionesPeticion($this, 'POST', altaRuta($m['f1']), ['fecha_alta' => '2026-11-09'], sesionesIniciar($this, 'e2@x.com'))
        ->assertUnprocessable()
        ->assertExactJson(['codigo' => 'rango_de_fechas_invalido', 'mensaje' => MENSAJE_DE_RANGO_DE_LESION, 'errores' => []]);
    expect(DB::table('lesiones')->where('id', $lesion)->value('fecha_alta'))->toBeNull()
        ->and(estadoVigente($m['f1'])->estado)->toBe('lesionado');
});

test('Punto 7 del plan: el mensaje existente de rango_de_fechas_invalido no cambia', function () {
    expect(__('errores.reglas.rango_de_fechas_invalido'))->toStartWith('La fecha de fin debe ser posterior');
});

test('§3.4: el alta sin fecha usa el hoy de negocio', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = estadoInsertar($m['f1'], $m['e2'])['lesion'];

    sesionesPeticion($this, 'POST', altaRuta($m['f1']), [], sesionesIniciar($this, 'e2@x.com'))->assertOk();

    expect(DB::table('lesiones')->where('id', $lesion)->value('fecha_alta'))->toBe('2026-11-15');
});

test('Punto 5 del plan: la fecha de alta y la estimada pueden ser futuras', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = estadoInsertar($m['f1'], $m['e2'])['lesion'];
    $sesion = sesionesIniciar($this, 'e2@x.com');

    sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['fecha_estimada_regreso' => '2027-06-30'], $sesion)->assertOk();
    sesionesPeticion($this, 'POST', altaRuta($m['f1']), ['fecha_alta' => '2027-01-15'], $sesion)->assertOk();

    expect(DB::table('lesiones')->where('id', $lesion)->value('fecha_alta'))->toBe('2027-01-15');
});

test('Punto 6 del plan: con un aviso de fecha futura, el alta sin fecha responde 422', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['e2'], ['fecha_aviso' => '2026-12-01']);
    $sesion = sesionesIniciar($this, 'e2@x.com');
    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(['fecha_estimada_regreso' => '2026-12-20']), $sesion)->assertOk();
    relojAdelantar('2026-11-15 18:00:01');

    sesionesPeticion($this, 'POST', altaRuta($m['f1']), [], $sesion)
        ->assertUnprocessable()->assertJsonPath('codigo', 'rango_de_fechas_invalido');
});

test('DA-30: cambiar el estado en el mismo segundo en que se abrió responde 409 conflicto y no cambia nada', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['e2']);
    $sesion = sesionesIniciar($this, 'e2@x.com');
    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(), $sesion)->assertOk();

    sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['estado' => 'con_molestia'], $sesion)
        ->assertConflict()->assertJsonPath('codigo', 'conflicto');

    expect(count(estadosDe($m['f1'])))->toBe(1)
        ->and(estadoVigente($m['f1'])->estado)->toBe('lesionado');
});

test('DA-30: dar de alta en el mismo segundo responde 409 conflicto y no deja la lesión con alta', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['e2']);
    $sesion = sesionesIniciar($this, 'e2@x.com');
    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(), $sesion)->assertOk();

    sesionesPeticion($this, 'POST', altaRuta($m['f1']), [], $sesion)
        ->assertConflict()->assertJsonPath('codigo', 'conflicto');

    expect(DB::table('lesiones')->where('jugador_id', $m['f1'])->value('fecha_alta'))->toBeNull();
});

test('DA-30: confirmar en el mismo segundo que otra confirmación responde 409 conflicto y el aviso sigue pendiente', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $primero = avisoInsertar($m['f1'], $m['e2']);
    $segundo = avisoInsertar($m['f1'], $m['e2']);
    $sesion = sesionesIniciar($this, 'e2@x.com');
    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($primero), confirmacionCuerpo(), $sesion)->assertOk();

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($segundo), confirmacionCuerpo(), $sesion)
        ->assertConflict()->assertJsonPath('codigo', 'conflicto');

    expect(DB::table('avisos_lesion')->where('id', $segundo)->value('estado'))->toBe('pendiente')
        ->and(DB::table('lesiones')->count())->toBe(1);
});

test('§6.2: confirmar rechaza campos desconocidos, una causa o un estado inválidos, y exige los de la lesión', function (array $cuerpo, string $campo) {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['e1']);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), $cuerpo, sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('errores.0.campo', $campo);
})->with([
    'sin estado' => [[], 'estado'],
    'estado inválido' => [['estado' => 'baja'], 'estado'],
    'campo desconocido' => [fn () => confirmacionCuerpo(['lesion_id' => 'x']), 'lesion_id'],
    'causa inválida' => [fn () => confirmacionCuerpo(['causa' => 'desconocida']), 'causa'],
    'tipo largo' => [fn () => confirmacionCuerpo(['tipo' => str_repeat('a', 101)]), 'tipo'],
    'zona larga' => [fn () => confirmacionCuerpo(['zona' => str_repeat('a', 101)]), 'zona'],
    'nota larga' => [fn () => confirmacionCuerpo(['nota' => str_repeat('a', 501)]), 'nota'],
    'fecha con otro formato' => [fn () => confirmacionCuerpo(['fecha_estimada_regreso' => '30/11/2026']), 'fecha_estimada_regreso'],
    'apto con zona' => [['estado' => 'apto', 'zona' => 'Tobillo'], 'zona'],
    'apto con causa' => [['estado' => 'apto', 'causa' => 'partido'], 'causa'],
    'apto con fecha' => [['estado' => 'apto', 'fecha_estimada_regreso' => '2026-11-30'], 'fecha_estimada_regreso'],
    'apto con nota' => [['estado' => 'apto', 'nota' => 'Nada'], 'nota'],
    'apto con tipo vacío' => [['estado' => 'apto', 'tipo' => ''], 'tipo'],
]);

test('§6.2: confirmar guarda la nota opcional de la lesión', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['e1']);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(['nota' => 'Con hielo']), sesionesIniciar($this, 'e1@x.com'))->assertOk();

    expect(DB::table('lesiones')->where('jugador_id', $m['f1'])->value('nota'))->toBe('Con hielo');
});

test('DA-32: confirmar con la nota nula o vacía responde 422 y no registra nada', function (?string $nota) {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['e1']);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(['nota' => $nota]), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', 'nota');

    expect(DB::table('avisos_lesion')->where('id', $aviso)->value('estado'))->toBe('pendiente')
        ->and(DB::table('lesiones')->where('jugador_id', $m['f1'])->count())->toBe(0)
        ->and(DB::table('estados_disponibilidad')->where('jugador_id', $m['f1'])->count())->toBe(0);
})->with([
    'nula' => [null],
    'vacía' => [''],
    'solo espacios' => ['   '],
]);

test('§6.2: PATCH del estado rechaza campos desconocidos, apto y fechas mal formadas', function (array $cuerpo, string $campo) {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e2']);

    sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), $cuerpo, sesionesIniciar($this, 'e2@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('errores.0.campo', $campo);
})->with([
    'apto' => [['estado' => 'apto'], 'estado'],
    'campo desconocido' => [['estado' => 'lesionado', 'lesion_id' => 'x'], 'lesion_id'],
    'fecha con otro formato' => [['fecha_estimada_regreso' => '30/11/2026'], 'fecha_estimada_regreso'],
]);

test('§6.2: el alta rechaza campos desconocidos y fechas mal formadas', function (array $cuerpo, string $campo) {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e2']);

    sesionesPeticion($this, 'POST', altaRuta($m['f1']), $cuerpo, sesionesIniciar($this, 'e2@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('errores.0.campo', $campo);
})->with([
    'campo desconocido' => [['motivo' => 'x'], 'motivo'],
    'fecha con otro formato' => [['fecha_alta' => '20/11/2026'], 'fecha_alta'],
]);

test('§6.2: PATCH de la lesión rechaza la fecha estimada, campos desconocidos y valores inválidos', function (array $cuerpo, string $campo) {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = NoNulo::exigir(estadoInsertar($m['f1'], $m['e2'])['lesion'], 'la lesión');

    sesionesPeticion($this, 'PATCH', lesionRuta($lesion), $cuerpo, sesionesIniciar($this, 'e2@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('errores.0.campo', $campo);
})->with([
    'fecha estimada' => [['fecha_estimada_regreso' => '2026-12-10'], 'fecha_estimada_regreso'],
    'causa inválida' => [['causa' => 'desconocida'], 'causa'],
    'tipo largo' => [['tipo' => str_repeat('a', 101)], 'tipo'],
    'nota larga' => [['nota' => str_repeat('a', 501)], 'nota'],
]);

test('DA-32: PATCH de la lesión con la nota nula o vacía responde 422; la nota guardada no se borra, solo se reemplaza', function (?string $nota) {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = lesionInsertar($m['f1'], $m['e2'], ['nota' => 'Con hielo']);
    $sesion = sesionesIniciar($this, 'e2@x.com');

    sesionesPeticion($this, 'PATCH', lesionRuta($lesion), ['nota' => $nota], $sesion)
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', 'nota');

    expect(DB::table('lesiones')->where('id', $lesion)->value('nota'))->toBe('Con hielo')
        ->and(DB::table('lesiones_historial')->where('lesion_id', $lesion)->count())->toBe(0);

    sesionesPeticion($this, 'PATCH', lesionRuta($lesion), ['nota' => 'Sin hielo'], $sesion)
        ->assertOk()
        ->assertJsonPath('datos.nota', 'Sin hielo');
})->with([
    'nula' => [null],
    'vacía' => [''],
    'solo espacios' => ['   '],
]);

test('RN-15: GET de la lesión devuelve solo sus campos a quien la registró', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = NoNulo::exigir(estadoInsertar($m['f1'], $m['e2'])['lesion'], 'la lesión');

    $respuesta = sesionesPeticion($this, 'GET', lesionRuta($lesion), idDeSesion: sesionesIniciar($this, 'e2@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.id', $lesion)
        ->assertJsonPath('datos.tipo', 'Esguince')
        ->assertJsonPath('datos.fecha_inicio', '2026-11-10');

    expect(array_keys($respuesta->json('datos')))->toBe(CAMPOS_DE_LA_LESION);
});

test('RN-15: una lesión ajena responde igual que una inexistente, en GET y en PATCH', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = NoNulo::exigir(estadoInsertar($m['f1'], $m['e2'])['lesion'], 'la lesión');
    $sesion = sesionesIniciar($this, 'e1@x.com');
    $inexistente = Identificador::generar()->valor();

    $ajena = sesionesPeticion($this, 'GET', lesionRuta($lesion), idDeSesion: $sesion)->assertNotFound();
    $sinFila = sesionesPeticion($this, 'GET', lesionRuta($inexistente), idDeSesion: $sesion)->assertNotFound();
    sesionesPeticion($this, 'PATCH', lesionRuta($lesion), [], $sesion)->assertNotFound();

    expect($ajena->getContent())->toBe($sinFila->getContent());
});

test('Punto 1 del plan: quien registró la lesión y perdió el alcance sobre el jugador recibe 404', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = NoNulo::exigir(estadoInsertar($m['j1'], $m['e1'])['lesion'], 'la lesión');
    DB::connection('propietario')->table('entrenador_equipo')->where('usuario_id', $m['e1'])->where('equipo_id', $m['a'])->update(['hasta' => '2026-11-14']);
    $sesion = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'GET', lesionRuta($lesion), idDeSesion: $sesion)->assertNotFound();
    sesionesPeticion($this, 'PATCH', lesionRuta($lesion), ['zona' => 'Rodilla'], $sesion)->assertNotFound();
    sesionesPeticion($this, 'GET', lesionesRuta($m['j1']), idDeSesion: $sesion)->assertNotFound();
});

test('RN-15: GET de las lesiones del jugador trae solo las propias, por inicio descendente, y pagina', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $vieja = lesionInsertar($m['f1'], $m['e1'], ['fecha_inicio' => '2026-10-01']);
    $reciente = lesionInsertar($m['f1'], $m['e1'], ['fecha_inicio' => '2026-11-01']);
    lesionInsertar($m['f1'], $m['e2']);
    $sesion = sesionesIniciar($this, 'e1@x.com');

    $respuesta = sesionesPeticion($this, 'GET', lesionesRuta($m['f1']), idDeSesion: $sesion)->assertOk();
    expect(array_column($respuesta->json('datos'), 'id'))->toBe([$reciente, $vieja]);

    sesionesPeticion($this, 'GET', lesionesRuta($m['f1']).'?pagina=2&por_pagina=1', idDeSesion: $sesion)
        ->assertJsonPath('datos.0.id', $vieja)
        ->assertJsonPath('meta', ['pagina' => 2, 'por_pagina' => 1, 'total' => 2, 'total_paginas' => 2]);
    sesionesPeticion($this, 'GET', lesionesRuta($m['f1']).'?pagina=0', idDeSesion: $sesion)->assertUnprocessable();
});

test('RN-15: las lesiones del jugador siguen disponibles con el jugador en baja', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    lesionInsertar($m['jb'], $m['e1']);

    sesionesPeticion($this, 'GET', lesionesRuta($m['jb']), idDeSesion: sesionesIniciar($this, 'e1@x.com'))
        ->assertOk()->assertJsonCount(1, 'datos');
});

test('DA-79: si corregir la lesión choca con un índice único de su historial, PATCH /lesiones/{lesion} responde 409 conflicto, nunca 500', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = NoNulo::exigir(estadoInsertar($m['f1'], $m['e2'])['lesion'], 'la lesión');
    $sesion = sesionesIniciar($this, 'e2@x.com');
    $propietario = DB::connection('propietario');
    // Disparador solo de esta prueba: simula el choque con el índice único (lesion_id, version) de dos escrituras a la vez.
    $propietario->statement("create function public.prueba_t09_choque_lesiones() returns trigger language plpgsql as \$\$ begin raise exception 'choque simulado' using errcode = 'unique_violation'; end \$\$");
    $propietario->statement("create trigger prueba_t09_choque_lesiones before insert on lesiones_historial for each row when (new.operacion = 'actualizar') execute function public.prueba_t09_choque_lesiones()");
    try {
        $respuesta = sesionesPeticion($this, 'PATCH', lesionRuta($lesion), ['zona' => 'Rodilla'], $sesion);
    } finally {
        $propietario->statement('drop trigger prueba_t09_choque_lesiones on lesiones_historial');
        $propietario->statement('drop function public.prueba_t09_choque_lesiones()');
    }

    $respuesta->assertConflict()->assertJsonPath('codigo', 'conflicto');
    expect(DB::table('lesiones')->where('id', $lesion)->value('zona'))->toBe('Tobillo')
        ->and(DB::table('lesiones_historial')->where('lesion_id', $lesion)->where('operacion', 'actualizar')->count())->toBe(0);
});

test('DA-33: un aviso ya confirmado de un jugador en baja responde 409 aviso_no_pendiente, no jugador_en_baja', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['jb'], $m['e1'], ['estado' => 'confirmado', 'confirmado_por' => $m['e2']]);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(), sesionesIniciar($this, 'e1@x.com'))
        ->assertConflict()
        ->assertJsonPath('codigo', 'aviso_no_pendiente');
});

test('DA-33: con el jugador en baja, un cuerpo inválido responde 422 validacion_fallida, no 409', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['jb'], $m['e1']);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), [], sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida');
});

test('DA-33: con el aviso ya confirmado, un cuerpo inválido responde 422 validacion_fallida, no 409', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['e1'], ['estado' => 'confirmado', 'confirmado_por' => $m['e2']]);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), [], sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida');
});

test('DA-33: con el jugador en baja y el regreso anterior al aviso, responde 409 jugador_en_baja, no 422 de fechas', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['jb'], $m['e1'], ['fecha_aviso' => '2026-11-14']);

    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(['fecha_estimada_regreso' => '2026-11-13']), sesionesIniciar($this, 'e1@x.com'))
        ->assertConflict()
        ->assertJsonPath('codigo', 'jugador_en_baja');
});

test('DA-33: con una escritura que chocaría y el regreso anterior al aviso, responde 422 rango_de_fechas_invalido, no 409 conflicto', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['e1'], ['fecha_aviso' => '2026-11-14']);
    $sesion = sesionesIniciar($this, 'e1@x.com');
    $propietario = DB::connection('propietario');
    // Disparador solo de esta prueba: la escritura de la confirmación choca con el índice único del historial del aviso.
    $propietario->statement("create function public.prueba_t09_da33() returns trigger language plpgsql as \$\$ begin raise exception 'choque simulado' using errcode = 'unique_violation'; end \$\$");
    $propietario->statement("create trigger prueba_t09_da33 before insert on avisos_lesion_historial for each row when (new.operacion = 'actualizar') execute function public.prueba_t09_da33()");
    try {
        $conFechasInvalidas = sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(['fecha_estimada_regreso' => '2026-11-13']), $sesion);
        $conFechasValidas = sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(), $sesion);
    } finally {
        $propietario->statement('drop trigger prueba_t09_da33 on avisos_lesion_historial');
        $propietario->statement('drop function public.prueba_t09_da33()');
    }

    $conFechasInvalidas->assertUnprocessable()
        ->assertExactJson(['codigo' => 'rango_de_fechas_invalido', 'mensaje' => MENSAJE_DE_RANGO_DE_LESION, 'errores' => []]);
    // Control: con fechas válidas el mismo choque sí llega, y responde 409 conflicto.
    $conFechasValidas->assertConflict()->assertJsonPath('codigo', 'conflicto');
    expect(DB::table('avisos_lesion')->where('id', $aviso)->value('estado'))->toBe('pendiente')
        ->and(DB::table('lesiones')->where('jugador_id', $m['f1'])->count())->toBe(0)
        ->and(DB::table('estados_disponibilidad')->where('jugador_id', $m['f1'])->count())->toBe(0);
});

test('DA-56: el estado recién confirmado tiene actualizado_en igual a vigente_desde', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['d1']);
    $sesion = sesionesIniciar($this, 'e2@x.com');
    sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(), $sesion)->assertOk();

    sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: $sesion)
        ->assertOk()
        ->assertJsonPath('datos.vigente_desde', INSTANTE_DEL_MUNDO)
        ->assertJsonPath('datos.actualizado_en', INSTANTE_DEL_MUNDO);
});

test('DA-56: cambiar la fecha estimada actualiza actualizado_en, no vigente_desde, y la respuesta coincide con la base', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $estado = estadoInsertar($m['f1'], $m['e2'])['estado'];

    sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['fecha_estimada_regreso' => '2026-12-05'], sesionesIniciar($this, 'e2@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.vigente_desde', INSTANTE_DEL_ESTADO_INSERTADO)
        ->assertJsonPath('datos.actualizado_en', INSTANTE_DEL_MUNDO);

    expect((new DateTimeImmutable((string) DB::table('estados_disponibilidad')->where('id', $estado)->value('actualizado_en')))->format(DATE_ATOM))
        ->toBe(INSTANTE_DEL_MUNDO);
});

test('DA-56: cambiar el estado abre una fila con actualizado_en igual al instante del cambio', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e2']);

    sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['estado' => 'con_molestia'], sesionesIniciar($this, 'e2@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.vigente_desde', INSTANTE_DEL_MUNDO)
        ->assertJsonPath('datos.actualizado_en', INSTANTE_DEL_MUNDO);
});

test('DA-56: un PATCH con valores idénticos no cambia actualizado_en (DA-48)', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e2']);

    sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['estado' => 'lesionado', 'fecha_estimada_regreso' => '2026-11-30'], sesionesIniciar($this, 'e2@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.actualizado_en', INSTANTE_DEL_ESTADO_INSERTADO);
});

test('DA-56: el alta deja un apto con actualizado_en igual al instante del alta', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e2']);

    sesionesPeticion($this, 'POST', altaRuta($m['f1']), [], sesionesIniciar($this, 'e2@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.estado', 'apto')
        ->assertJsonPath('datos.actualizado_en', INSTANTE_DEL_MUNDO);
});

test('DA-56: corregir el detalle de la lesión no cambia actualizado_en del estado', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = NoNulo::exigir(estadoInsertar($m['f1'], $m['e2'])['lesion'], 'la lesión');
    $sesion = sesionesIniciar($this, 'e2@x.com');

    sesionesPeticion($this, 'PATCH', lesionRuta($lesion), ['zona' => 'Rodilla'], $sesion)->assertOk();

    sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: $sesion)
        ->assertOk()
        ->assertJsonPath('datos.actualizado_en', INSTANTE_DEL_ESTADO_INSERTADO);
});

test('DA-56: sin estado registrado, actualizado_en es nulo como vigente_desde (DA-01)', function () {
    /** @var TestCase $this */
    $m = avisosMundo();

    sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'e1@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.es_implicito', true)
        ->assertJsonPath('datos.vigente_desde', null)
        ->assertJsonPath('datos.actualizado_en', null);
});

test('DA-56: D1 y E1 ven el momento de la última actualización, sin lesion_id', function (string $correo) {
    /** @var TestCase $this */
    $m = avisosMundo();
    estadoInsertar($m['f1'], $m['e2']);

    sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: sesionesIniciar($this, $correo))
        ->assertOk()
        ->assertJsonPath('datos.actualizado_en', INSTANTE_DEL_ESTADO_INSERTADO)
        ->assertJsonPath('datos.lesion_id', null);
})->with(['d1@x.com', 'e1@x.com']);
