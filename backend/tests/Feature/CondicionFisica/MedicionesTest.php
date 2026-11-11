<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;
use Tests\Ayudantes\NoNulo;
use Tests\TestCase;

const CAMPOS_DE_LA_MEDICION = ['id', 'equipo_id', 'fecha', 'periodo', 'peso_kg', 'estatura_cm', 'imc', 'actualizado_en'];

const MENSAJE_DE_RANGO_DE_MEDICION = 'El valor está fuera del rango permitido.';

const MENSAJE_DE_BAJA_EN_MEDICION = 'El jugador está en baja: no se pueden registrar ni corregir mediciones.';

const UUID_V4_EN_MEDICIONES = '3f2b8c1e-7a4d-4e9b-8f10-2c5d6e7f8a90';

function medicionesCuenta(string $jugador): int
{
    return DB::table('mediciones_corporales')->where('jugador_id', $jugador)->count();
}

/** @return list<stdClass> */
function medicionesHistorial(string $medicion): array
{
    return array_values(DB::table('mediciones_corporales_historial')->where('medicion_id', $medicion)->orderBy('version')->get()->all());
}

test('§7.5 fila 1 (HU-17 c1, DA-05): E1 registra; 201 con el periodo y el IMC calculado', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();

    $respuesta = sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a']), sesionesIniciar($this, 'e1@x.com'))
        ->assertCreated()
        ->assertJsonPath('datos.equipo_id', $m['a'])
        ->assertJsonPath('datos.fecha', '2026-11-15')
        ->assertJsonPath('datos.periodo', '2026-11')
        ->assertJsonPath('datos.peso_kg', '70.00')
        ->assertJsonPath('datos.estatura_cm', '175.0')
        ->assertJsonPath('datos.imc', '22.86');

    expect(array_keys($respuesta->json('datos')))->toEqualCanonicalizing(CAMPOS_DE_LA_MEDICION);
});

test('§7.5 fila 2 (DA-05): 72.50 kg y 180.0 cm dan un IMC de 22.38', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['peso_kg' => '72.50', 'estatura_cm' => '180.0']), sesionesIniciar($this, 'e1@x.com'))
        ->assertCreated()
        ->assertJsonPath('datos.imc', '22.38');
});

test('§7.5 fila 3 (DA-05): 12.505 se redondea HALF_UP a 12.51 y la base da el mismo valor', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();

    $respuesta = sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['peso_kg' => '50.02', 'estatura_cm' => '200.0']), sesionesIniciar($this, 'e1@x.com'))
        ->assertCreated()
        ->assertJsonPath('datos.imc', '12.51');

    expect(DB::table('mediciones_corporales')->where('id', $respuesta->json('datos.id'))->value('imc'))->toBe('12.51');
});

test('§7.5 fila 4 (HU-17 c2): otra medición en el periodo sin es_correccion responde 409 medicion_ya_registrada', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    medicionInsertar($m['f1'], $m['a'], $m['e1']);

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['peso_kg' => '71.00']), sesionesIniciar($this, 'e1@x.com'))
        ->assertConflict()
        ->assertJsonPath('codigo', 'medicion_ya_registrada')
        ->assertJsonPath('mensaje', __('errores.reglas.medicion_ya_registrada'))
        ->assertJsonPath('errores', []);

    expect(medicionesCuenta($m['f1']))->toBe(1);
});

test('§7.5 fila 5 (HU-17 c2): la corrección con motivo responde 200, reemplaza y deja el historial con los valores anteriores', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    $medicion = medicionInsertar($m['f1'], $m['a'], $m['e1']);

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['peso_kg' => '71.00', 'es_correccion' => true, 'motivo' => 'Báscula mal calibrada']), sesionesIniciar($this, 'e1@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.id', $medicion)
        ->assertJsonPath('datos.peso_kg', '71.00')
        ->assertJsonPath('datos.imc', '23.18');

    $historial = medicionesHistorial($medicion);
    $anteriores = json_decode(NoNulo::exigir($historial[0], 'la fila de historial')->datos_anteriores, true);

    expect(medicionesCuenta($m['f1']))->toBe(1)
        ->and($historial)->toHaveCount(1)
        ->and(NoNulo::exigir($historial[0], 'la fila de historial')->operacion)->toBe('actualizar')
        ->and(NoNulo::exigir($historial[0], 'la fila de historial')->motivo)->toBe('Báscula mal calibrada')
        ->and($anteriores['peso_kg'])->toBe('70.00')
        ->and($anteriores['estatura_cm'])->toBe('175.0');
});

test('§7.5 fila 6 (convenciones §4.1): es_correccion sin motivo responde 422 en motivo', function (array $cambios) {
    /** @var TestCase $this */
    $m = medicionesMundo();
    medicionInsertar($m['f1'], $m['a'], $m['e1']);

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['es_correccion' => true, ...$cambios]), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', 'motivo');
})->with([
    'ausente' => [[]],
    'en blanco' => [['motivo' => '   ']],
]);

test('§7.5 fila 7 (convenciones §4.1): es_correccion sin nada en el periodo responde 422 nada_que_corregir', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    medicionInsertar($m['f1'], $m['a'], $m['e1']);

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['fecha' => '2026-10-05', 'es_correccion' => true, 'motivo' => 'Error']), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'nada_que_corregir')
        ->assertJsonPath('errores.0.campo', 'es_correccion');

    expect(medicionesCuenta($m['f1']))->toBe(1);
});

test('§7.5 fila 8 (DD-59): E2 registra la suya en el mismo periodo y no choca con la de E1', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    medicionInsertar($m['f1'], $m['a'], $m['e1']);

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['b']), sesionesIniciar($this, 'e2@x.com'))->assertCreated();

    expect(medicionesCuenta($m['f1']))->toBe(2);
});

test('§7.5 fila 9 (DD-59): E2 lista solo la suya', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    medicionInsertar($m['f1'], $m['a'], $m['e1']);
    $suya = medicionInsertar($m['f1'], $m['b'], $m['e2']);

    $respuesta = sesionesPeticion($this, 'GET', medicionesRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'e2@x.com'))->assertOk();

    expect(array_column($respuesta->json('datos'), 'id'))->toBe([$suya]);
});

test('§7.5 fila 10 (HU-17 c3, DA-06): E1 lista tres periodos en orden ascendente', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    medicionInsertar($m['f1'], $m['a'], $m['e1'], '2026-11-02');
    medicionInsertar($m['f1'], $m['a'], $m['e1'], '2026-09-02');
    medicionInsertar($m['f1'], $m['a'], $m['e1'], '2026-10-02');

    $respuesta = sesionesPeticion($this, 'GET', medicionesRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'e1@x.com'))
        ->assertOk()
        ->assertJsonPath('meta.total', 3);

    expect(array_column($respuesta->json('datos'), 'periodo'))->toBe(['2026-09', '2026-10', '2026-11'])
        ->and(array_keys($respuesta->json('datos.0')))->toEqualCanonicalizing(CAMPOS_DE_LA_MEDICION);
});

test('§7.5 fila 11 (DD-62): 19.99 kg responde 422 valor_fuera_de_rango en peso_kg', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['peso_kg' => '19.99']), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'valor_fuera_de_rango')
        ->assertJsonPath('errores.0.campo', 'peso_kg');
});

test('§7.5 fila 12 (DD-62): 230.1 cm responde 422 valor_fuera_de_rango en estatura_cm', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['estatura_cm' => '230.1']), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'valor_fuera_de_rango')
        ->assertJsonPath('errores.0.campo', 'estatura_cm');
});

test('§7.5 fila 13 (DD-62): 200.00 kg y 100.0 cm son válidos, y también los otros dos límites', function (string $peso, string $estatura) {
    /** @var TestCase $this */
    $m = medicionesMundo();

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['peso_kg' => $peso, 'estatura_cm' => $estatura]), sesionesIniciar($this, 'e1@x.com'))
        ->assertCreated();
})->with([
    'máximo de peso y mínimo de estatura' => ['200.00', '100.0'],
    'mínimo de peso y máximo de estatura' => ['20.00', '230.0'],
]);

test('§7.5 fila 14 (DA-13): E1 con el equipo B responde 422 equipo_inexistente', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['b']), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'equipo_inexistente')
        ->assertJsonPath('errores.0.campo', 'equipo_id');

    expect(medicionesCuenta($m['f1']))->toBe(0);
});

test('§7.5 fila 15 (DD-60, DA-41): registrar una medición de JB responde 409 jugador_en_baja con su mensaje', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();

    sesionesPeticion($this, 'POST', medicionesRuta($m['jb']), medicionCuerpo($m['a']), sesionesIniciar($this, 'e1@x.com'))
        ->assertConflict()
        ->assertExactJson(['codigo' => 'jugador_en_baja', 'mensaje' => MENSAJE_DE_BAJA_EN_MEDICION, 'errores' => []]);

    expect(medicionesCuenta($m['jb']))->toBe(0);
});

test('§7.5 fila 16 (§5.3): D1 no lista las mediciones: 403 sin_permiso', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();

    sesionesPeticion($this, 'GET', medicionesRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'd1@x.com'))
        ->assertForbidden()
        ->assertJsonPath('codigo', 'sin_permiso');
});

test('§7.5 fila 17 (RNF-09, DA-16): el máximo cambiado en la base se aplica sin tocar el código', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    DB::connection('propietario')->table('rangos_mediciones_corporales')->where('codigo', 'peso_kg')->update(['maximo' => '150.00']);

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['peso_kg' => '160.00']), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'valor_fuera_de_rango')
        ->assertJsonPath('errores.0.campo', 'peso_kg');
});

test('T05 (condición del 03/11/2026): un valor fuera de rango responde 422 con su campo, nunca 500, y no escribe nada', function (string $campo, string $valor) {
    /** @var TestCase $this */
    $m = medicionesMundo();

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], [$campo => $valor]), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertExactJson([
            'codigo' => 'valor_fuera_de_rango',
            'mensaje' => MENSAJE_DE_RANGO_DE_MEDICION,
            'errores' => [['campo' => $campo, 'codigo' => 'valor_fuera_de_rango', 'mensaje' => MENSAJE_DE_RANGO_DE_MEDICION]],
        ]);

    expect(medicionesCuenta($m['f1']))->toBe(0);
})->with([
    'peso cero' => ['peso_kg', '0'],
    'peso negativo' => ['peso_kg', '-1'],
    'peso bajo el mínimo' => ['peso_kg', '19.99'],
    'peso sobre el máximo' => ['peso_kg', '200.01'],
    'peso que desborda la columna' => ['peso_kg', '99999.99'],
    'estatura cero' => ['estatura_cm', '0'],
    'estatura negativa' => ['estatura_cm', '-1'],
    'estatura bajo el mínimo' => ['estatura_cm', '99.9'],
    'estatura sobre el máximo' => ['estatura_cm', '230.1'],
    'estatura que desborda la columna' => ['estatura_cm', '99999.9'],
]);

test('§6.2: peso y estatura exigen cadena con sus decimales; lo demás responde 422 validacion_fallida', function (string $campo, mixed $valor) {
    /** @var TestCase $this */
    $m = medicionesMundo();

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], [$campo => $valor]), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', $campo);
})->with([
    'peso con tres decimales' => ['peso_kg', '70.123'],
    'peso con texto' => ['peso_kg', 'setenta'],
    'peso numérico' => ['peso_kg', 70],
    'peso nulo' => ['peso_kg', null],
    'estatura con dos decimales' => ['estatura_cm', '175.12'],
    'estatura con texto' => ['estatura_cm', 'alta'],
    'estatura numérica' => ['estatura_cm', 175],
    'estatura nula' => ['estatura_cm', null],
]);

test('§6.2: campos obligatorios, fecha, booleano estricto y campos desconocidos', function (array $cuerpo, string $campo, string $codigo) {
    /** @var TestCase $this */
    $m = medicionesMundo();
    $base = medicionCuerpo($m['a']);
    unset($base[$cuerpo['quitar'] ?? '']);
    unset($cuerpo['quitar']);

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), [...$base, ...$cuerpo], sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('errores.0.campo', $campo)
        ->assertJsonPath('errores.0.codigo', $codigo);
})->with([
    'sin equipo' => [['quitar' => 'equipo_id'], 'equipo_id', 'required'],
    'sin peso' => [['quitar' => 'peso_kg'], 'peso_kg', 'required'],
    'sin estatura' => [['quitar' => 'estatura_cm'], 'estatura_cm', 'required'],
    'equipo que no es UUID' => [['equipo_id' => 'x'], 'equipo_id', 'uuid'],
    'fecha con otro formato' => [['fecha' => '15/11/2026'], 'fecha', 'date_format'],
    'es_correccion como texto' => [['es_correccion' => 'true', 'motivo' => 'x'], 'es_correccion', 'boolean'],
    'campo desconocido' => [['imc' => '22.86'], 'imc', 'prohibited'],
    'campo desconocido vacío' => [['periodo' => ''], 'periodo', 'prohibited'],
]);

test('DA-03: la fecha es opcional, puede ser futura y decide el periodo', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['fecha' => '2026-10-05']), $sesion)
        ->assertCreated()
        ->assertJsonPath('datos.fecha', '2026-10-05')
        ->assertJsonPath('datos.periodo', '2026-10');
    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['fecha' => '2027-01-20']), $sesion)
        ->assertCreated()
        ->assertJsonPath('datos.periodo', '2027-01');
});

test('DA-44: el motivo admite 255 caracteres; 256 responden 422 validacion_fallida y no llegan a la base', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    $medicion = medicionInsertar($m['f1'], $m['a'], $m['e1']);
    $sesion = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['peso_kg' => '71.00', 'es_correccion' => true, 'motivo' => str_repeat('a', 256)]), $sesion)
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', 'motivo');

    expect(medicionesHistorial($medicion))->toHaveCount(0)
        ->and(DB::table('mediciones_corporales')->where('id', $medicion)->value('peso_kg'))->toBe('70.00');

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['peso_kg' => '71.00', 'es_correccion' => true, 'motivo' => str_repeat('a', 255)]), $sesion)
        ->assertOk();

    expect(NoNulo::exigir(medicionesHistorial($medicion)[0] ?? null, 'la fila de historial')->motivo)->toBe(str_repeat('a', 255));
});

test('DA-39: motivo sin es_correccion responde 422 prohibited, aunque esté vacío o es_correccion sea falso', function (array $cambios) {
    /** @var TestCase $this */
    $m = medicionesMundo();

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], $cambios), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('errores.0.campo', 'motivo')
        ->assertJsonPath('errores.0.codigo', 'prohibited');

    expect(medicionesCuenta($m['f1']))->toBe(0);
})->with([
    'sin es_correccion' => [['motivo' => 'Error']],
    'es_correccion falso' => [['es_correccion' => false, 'motivo' => 'Error']],
    'motivo vacío' => [['motivo' => '']],
]);

test('DA-39: es_correccion falso sin motivo registra como una medición normal', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['es_correccion' => false]), sesionesIniciar($this, 'e1@x.com'))
        ->assertCreated();
});

test('DA-40: una corrección con valores idénticos responde 200 sin historial ni cambio de actualizado_en', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    $medicion = medicionInsertar($m['f1'], $m['a'], $m['e1'], '2026-11-15');
    $antes = DB::table('mediciones_corporales')->where('id', $medicion)->value('actualizado_en');

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['es_correccion' => true, 'motivo' => 'Sin cambios']), sesionesIniciar($this, 'e1@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.id', $medicion);

    expect(medicionesHistorial($medicion))->toHaveCount(0)
        ->and(DB::table('mediciones_corporales')->where('id', $medicion)->value('actualizado_en'))->toBe($antes);
});

test('DA-12: corregir una medición de un jugador en baja responde 409 jugador_en_baja con su mensaje', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    $medicion = medicionInsertar($m['jb'], $m['a'], $m['e1']);

    sesionesPeticion($this, 'POST', medicionesRuta($m['jb']), medicionCuerpo($m['a'], ['peso_kg' => '71.00', 'es_correccion' => true, 'motivo' => 'Error']), sesionesIniciar($this, 'e1@x.com'))
        ->assertConflict()
        ->assertExactJson(['codigo' => 'jugador_en_baja', 'mensaje' => MENSAJE_DE_BAJA_EN_MEDICION, 'errores' => []]);

    expect(medicionesHistorial($medicion))->toHaveCount(0);
});

test('DA-12, DD-30: las mediciones de un jugador en baja se siguen listando', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    $medicion = medicionInsertar($m['jb'], $m['a'], $m['e1']);

    $respuesta = sesionesPeticion($this, 'GET', medicionesRuta($m['jb']), idDeSesion: sesionesIniciar($this, 'e1@x.com'))->assertOk();

    expect(array_column($respuesta->json('datos'), 'id'))->toBe([$medicion]);
});

test('DA-13: inexistente, eliminado, ajeno, sin el jugador o UUID no v7 responden con el mismo cuerpo', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    $sesion = sesionesIniciar($this, 'e3@x.com');
    $eliminado = mundoEquipo('Eliminado', (string) DB::table('equipos')->where('id', $m['a'])->value('categoria_id'), $m['e3']);
    DB::connection('propietario')->table('equipos')->where('id', $eliminado)->update(['eliminado_en' => '2026-11-01 10:00:00+00']);
    $ajeno = mundoEquipo('Ajeno', (string) DB::table('equipos')->where('id', $m['a'])->value('categoria_id'), $m['e1']);
    $sinElJugador = mundoEquipo('Sin F1', (string) DB::table('equipos')->where('id', $m['a'])->value('categoria_id'), $m['e3']);

    $cuerpos = array_map(
        fn (string $equipo) => sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($equipo), $sesion)->assertUnprocessable()->getContent(),
        [Identificador::generar()->valor(), $eliminado, $ajeno, $sinElJugador, UUID_V4_EN_MEDICIONES],
    );

    expect(array_unique($cuerpos))->toHaveCount(1)
        ->and(json_decode((string) $cuerpos[0], true)['codigo'])->toBe('equipo_inexistente')
        ->and(medicionesCuenta($m['f1']))->toBe(0);
});

test('DA-13: con el jugador en dos equipos suyos hay una sola medición por periodo, y la corrección cambia el equipo', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    $sesion = sesionesIniciar($this, 'e3@x.com');

    $creada = sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a']), $sesion)->assertCreated();
    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['b']), $sesion)
        ->assertConflict()
        ->assertJsonPath('codigo', 'medicion_ya_registrada');
    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['b'], ['es_correccion' => true, 'motivo' => 'Era el otro equipo']), $sesion)
        ->assertOk()
        ->assertJsonPath('datos.id', $creada->json('datos.id'))
        ->assertJsonPath('datos.equipo_id', $m['b']);
});

test('DA-38: el 422 del cuerpo va antes que el 409 jugador_en_baja', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();

    sesionesPeticion($this, 'POST', medicionesRuta($m['jb']), medicionCuerpo($m['a'], ['peso_kg' => 'x']), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida');
});

test('DA-38: jugador_en_baja va antes que equipo_inexistente y que valor_fuera_de_rango', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();

    sesionesPeticion($this, 'POST', medicionesRuta($m['jb']), medicionCuerpo($m['b'], ['peso_kg' => '1.00']), sesionesIniciar($this, 'e1@x.com'))
        ->assertConflict()
        ->assertJsonPath('codigo', 'jugador_en_baja');
});

test('DA-38: equipo_inexistente va antes que valor_fuera_de_rango', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['b'], ['peso_kg' => '1.00']), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'equipo_inexistente');
});

test('DA-38: valor_fuera_de_rango va antes que nada_que_corregir y que medicion_ya_registrada', function (bool $yaRegistrada, array $cambios) {
    /** @var TestCase $this */
    $m = medicionesMundo();
    if ($yaRegistrada) {
        medicionInsertar($m['f1'], $m['a'], $m['e1'], '2026-11-15');
    }

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['peso_kg' => '1.00', ...$cambios]), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'valor_fuera_de_rango');
})->with([
    'sin nada que corregir' => [false, ['es_correccion' => true, 'motivo' => 'Error']],
    'ya registrada' => [true, []],
]);

test('§6.1: fuera del alcance, el 404 gana sobre el 422 y es igual al de un jugador inexistente', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    $sesion = sesionesIniciar($this, 'e2@x.com');

    $ajeno = sesionesPeticion($this, 'POST', medicionesRuta($m['j1']), ['peso_kg' => 'x'], $sesion)->assertNotFound();
    $inexistente = sesionesPeticion($this, 'POST', medicionesRuta(Identificador::generar()->valor()), ['peso_kg' => 'x'], $sesion)->assertNotFound();
    $listado = sesionesPeticion($this, 'GET', medicionesRuta($m['j1']), idDeSesion: $sesion)->assertNotFound();
    $listadoInexistente = sesionesPeticion($this, 'GET', medicionesRuta(Identificador::generar()->valor()), idDeSesion: $sesion)->assertNotFound();

    expect($ajeno->getContent())->toBe($inexistente->getContent())
        ->and($listado->getContent())->toBe($listadoInexistente->getContent())
        ->and($listado->getContent())->toBe($ajeno->getContent());
});

test('§6.1: un UUID válido que no es v7 en {jugador} responde 404, nunca 500', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'GET', medicionesRuta(UUID_V4_EN_MEDICIONES), idDeSesion: $sesion)->assertNotFound();
    sesionesPeticion($this, 'POST', medicionesRuta(UUID_V4_EN_MEDICIONES), medicionCuerpo($m['a']), $sesion)->assertNotFound();
});

test('§6.1: sin sesión responde 401 y el docente no registra', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    $docente = sesionesIniciar($this, 'd1@x.com');

    sesionesPeticion($this, 'GET', medicionesRuta($m['f1']))->assertUnauthorized();
    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a']))->assertUnauthorized();
    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a']), $docente)
        ->assertForbidden()
        ->assertJsonPath('codigo', 'sin_permiso');
});

test('DD-61: un usuario con los dos roles solo registra con el alcance de entrenador', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    DB::connection('propietario')->table('materias')->where('id', $m['materia'])->update(['docente_id' => $m['de']]);
    DB::connection('propietario')->table('entrenador_equipo')->where('usuario_id', $m['de'])->update(['hasta' => '2026-02-01']);
    $sesion = sesionesIniciar($this, 'de@x.com');

    sesionesPeticion($this, 'GET', medicionesRuta($m['f1']), idDeSesion: $sesion)->assertNotFound();
    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a']), $sesion)->assertNotFound();
});

test('§6.1: el listado pagina y valida sus parámetros', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    medicionInsertar($m['f1'], $m['a'], $m['e1'], '2026-09-02');
    medicionInsertar($m['f1'], $m['a'], $m['e1'], '2026-10-02');
    $sesion = sesionesIniciar($this, 'e1@x.com');

    $segunda = sesionesPeticion($this, 'GET', medicionesRuta($m['f1']).'?pagina=2&por_pagina=1', idDeSesion: $sesion)
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('meta.pagina', 2)
        ->assertJsonPath('meta.por_pagina', 1)
        ->assertJsonPath('meta.total_paginas', 2);

    expect(array_column($segunda->json('datos'), 'periodo'))->toBe(['2026-10']);

    sesionesPeticion($this, 'GET', medicionesRuta($m['f1']).'?por_pagina=101', idDeSesion: $sesion)
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida');
});

test('RNF-02: una medición borrada lógicamente no se lista ni cuenta para el periodo', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    medicionInsertar($m['f1'], $m['a'], $m['e1'], '2026-11-02', ['eliminado_en' => '2026-11-03 10:00:00+00']);
    $sesion = sesionesIniciar($this, 'e1@x.com');

    $respuesta = sesionesPeticion($this, 'GET', medicionesRuta($m['f1']), idDeSesion: $sesion)->assertOk();

    expect($respuesta->json('datos'))->toBe([]);

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a']), $sesion)->assertCreated();
});

test('AU-09: con el máximo de peso en 1500.00, 1200.00 responde 422 valor_fuera_de_rango, no 500; 999.99 cabe en la columna', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    DB::connection('propietario')->table('rangos_mediciones_corporales')->where('codigo', 'peso_kg')->update(['maximo' => '1500.00']);
    $sesion = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['peso_kg' => '1200.00']), $sesion)
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'valor_fuera_de_rango')
        ->assertJsonPath('errores.0.campo', 'peso_kg');

    expect(medicionesCuenta($m['f1']))->toBe(0);

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['peso_kg' => '999.99']), $sesion)->assertCreated();
});

test('AU-09: con el máximo de estatura en 9999.99, sobre la columna, 9999.9 se guarda y 10000.0 responde 422, nunca 500', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    DB::connection('propietario')->table('rangos_mediciones_corporales')->where('codigo', 'estatura_cm')->update(['maximo' => '9999.99']);
    $sesion = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['estatura_cm' => '10000.0']), $sesion)
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'valor_fuera_de_rango')
        ->assertJsonPath('errores.0.campo', 'estatura_cm');
    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['estatura_cm' => '9999.9']), $sesion)->assertCreated();
});

test('AU-09: por HTTP, 9999.95 cm responde 422 validacion_fallida por sus dos decimales, nunca 500', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    DB::connection('propietario')->table('rangos_mediciones_corporales')->where('codigo', 'estatura_cm')->update(['maximo' => '9999.99']);

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['estatura_cm' => '9999.95']), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', 'estatura_cm');

    expect(medicionesCuenta($m['f1']))->toBe(0);
});

test('DA-71: un IMC que no cabe en la columna responde 422 valor_fuera_de_rango en estatura_cm, nunca 500', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    DB::connection('propietario')->table('rangos_mediciones_corporales')->where('codigo', 'estatura_cm')->update(['minimo' => '40.00']);
    $sesion = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['peso_kg' => '200.00', 'estatura_cm' => '44.7']), $sesion)
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'valor_fuera_de_rango')
        ->assertJsonPath('errores.0.campo', 'estatura_cm');

    expect(medicionesCuenta($m['f1']))->toBe(0);

    sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['peso_kg' => '200.00', 'estatura_cm' => '44.8']), $sesion)
        ->assertCreated()
        ->assertJsonPath('datos.imc', '996.49');
});
