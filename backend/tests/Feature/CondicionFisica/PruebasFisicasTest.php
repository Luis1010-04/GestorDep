<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;
use Tests\Ayudantes\NoNulo;
use Tests\TestCase;

const CAMPOS_DE_LA_PRUEBA = ['id', 'equipo_id', 'tipo_prueba_id', 'fecha', 'periodo', 'resultado', 'actualizado_en'];

const CAMPOS_DEL_TIPO_DE_PRUEBA = ['id', 'codigo', 'nombre', 'unidad', 'mejor_resultado'];

const MENSAJE_DE_BAJA_EN_PRUEBA = 'El jugador está en baja: no se pueden registrar ni corregir pruebas físicas.';

const MENSAJE_DE_PRUEBA_YA_REGISTRADA = 'Ya hay una prueba de ese tipo registrada en ese periodo. Si quieres reemplazarla, confírmalo como corrección.';

const MENSAJE_DE_TIPO_INEXISTENTE = 'El tipo de prueba indicado no existe.';

const UUID_V4_EN_PRUEBAS = '3f2b8c1e-7a4d-4e9b-8f10-2c5d6e7f8a90';

function pruebasCuenta(string $jugador): int
{
    return DB::table('pruebas_fisicas')->where('jugador_id', $jugador)->count();
}

/** @return list<stdClass> */
function pruebasHistorial(string $prueba): array
{
    return array_values(DB::table('pruebas_fisicas_historial')->where('prueba_id', $prueba)->orderBy('version')->get()->all());
}

test('§7.6 fila 1 (HU-18 c1): E1 registra velocidad 4.35 con fecha 2026-11-03; 201 con el periodo 2026-11', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();

    $respuesta = sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad']), sesionesIniciar($this, 'e1@x.com'))
        ->assertCreated()
        ->assertJsonPath('datos.tipo_prueba_id', $m['velocidad'])
        ->assertJsonPath('datos.equipo_id', $m['a'])
        ->assertJsonPath('datos.fecha', '2026-11-03')
        ->assertJsonPath('datos.periodo', '2026-11')
        ->assertJsonPath('datos.resultado', '4.35');

    expect(array_keys($respuesta->json('datos')))->toEqualCanonicalizing(CAMPOS_DE_LA_PRUEBA)
        ->and(pruebasHistorial($respuesta->json('datos.id')))->toHaveCount(1);
});

test('§7.6 fila 2 (DA-04, DA-60): otra velocidad de E1 en 2026-11 responde 409 prueba_ya_registrada con su mensaje', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    pruebaInsertar($m['f1'], $m['a'], $m['e1'], $m['velocidad']);

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad'], ['resultado' => '4.20', 'fecha' => '2026-11-10']), sesionesIniciar($this, 'e1@x.com'))
        ->assertConflict()
        ->assertExactJson(['codigo' => 'prueba_ya_registrada', 'mensaje' => MENSAJE_DE_PRUEBA_YA_REGISTRADA, 'errores' => []]);

    expect(pruebasCuenta($m['f1']))->toBe(1);
});

test('§7.6 fila 3 (convenciones §4.1): con es_correccion y motivo responde 200 y deja el historial actualizar con el motivo', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $prueba = pruebaInsertar($m['f1'], $m['a'], $m['e1'], $m['velocidad']);

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad'], ['resultado' => '4.20', 'es_correccion' => true, 'motivo' => 'Cronómetro mal iniciado']), sesionesIniciar($this, 'e1@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.id', $prueba)
        ->assertJsonPath('datos.resultado', '4.20');

    $historial = pruebasHistorial($prueba);
    $fila = NoNulo::exigir($historial[0] ?? null, 'la fila de historial');

    expect($historial)->toHaveCount(1)
        ->and($fila->operacion)->toBe('actualizar')
        ->and($fila->motivo)->toBe('Cronómetro mal iniciado')
        ->and(json_decode($fila->datos_anteriores, true)['resultado'])->toBe('4.35')
        ->and(pruebasCuenta($m['f1']))->toBe(1);
});

test('§7.6 fila 4 (DA-04): con velocidad de E1 en 2026-11, resistencia en 2026-11 responde 201', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    pruebaInsertar($m['f1'], $m['a'], $m['e1'], $m['velocidad']);

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['resistencia'], ['resultado' => '2400.00']), sesionesIniciar($this, 'e1@x.com'))
        ->assertCreated();

    expect(pruebasCuenta($m['f1']))->toBe(2);
});

test('§7.6 fila 5 (HU-18 c3, DA-63): la vista de 2026-11 trae los tres tipos por código, con prueba nula si no hay', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $velocidad = pruebaInsertar($m['f1'], $m['a'], $m['e1'], $m['velocidad']);

    $respuesta = sesionesPeticion($this, 'GET', pruebasRuta($m['f1']).'?filtro[periodo]=2026-11', idDeSesion: sesionesIniciar($this, 'e1@x.com'))->assertOk();

    expect(array_keys($respuesta->json()))->toBe(['datos'])
        ->and(array_column($respuesta->json('datos'), 'tipo_prueba_id'))->toBe([$m['resistencia'], $m['tiempo_prueba'], $m['velocidad']])
        ->and(array_column($respuesta->json('datos'), 'periodo'))->toBe(['2026-11', '2026-11', '2026-11'])
        ->and($respuesta->json('datos.0.prueba'))->toBeNull()
        ->and($respuesta->json('datos.1.prueba'))->toBeNull()
        ->and($respuesta->json('datos.2.prueba.id'))->toBe($velocidad)
        ->and(array_keys($respuesta->json('datos.0')))->toBe(['tipo_prueba_id', 'periodo', 'prueba'])
        ->and(array_keys($respuesta->json('datos.2.prueba')))->toEqualCanonicalizing(CAMPOS_DE_LA_PRUEBA);
});

test('§7.6 fila 6 (HU-18 c2): con filtro[tipo_prueba_id] de velocidad, las tres por periodo ascendente', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    pruebaInsertar($m['f1'], $m['a'], $m['e1'], $m['velocidad'], '2026-11-03');
    pruebaInsertar($m['f1'], $m['a'], $m['e1'], $m['velocidad'], '2026-09-03');
    pruebaInsertar($m['f1'], $m['a'], $m['e1'], $m['velocidad'], '2026-10-03');
    pruebaInsertar($m['f1'], $m['a'], $m['e1'], $m['resistencia'], '2026-10-03', ['resultado' => '2400.00']);

    $respuesta = sesionesPeticion($this, 'GET', pruebasRuta($m['f1'])."?filtro[tipo_prueba_id]={$m['velocidad']}", idDeSesion: sesionesIniciar($this, 'e1@x.com'))
        ->assertOk()
        ->assertJsonPath('meta.total', 3);

    expect(array_column($respuesta->json('datos'), 'periodo'))->toBe(['2026-09', '2026-10', '2026-11'])
        ->and(array_unique(array_column($respuesta->json('datos'), 'tipo_prueba_id')))->toBe([$m['velocidad']]);
});

test('§7.6 fila 7 (DD-62): resistencia 6000.01 responde 422 valor_fuera_de_rango en resultado', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['resistencia'], ['resultado' => '6000.01']), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertExactJson([
            'codigo' => 'valor_fuera_de_rango',
            'mensaje' => 'El valor está fuera del rango permitido.',
            'errores' => [['campo' => 'resultado', 'codigo' => 'valor_fuera_de_rango', 'mensaje' => 'El valor está fuera del rango permitido.']],
        ]);

    expect(pruebasCuenta($m['f1']))->toBe(0);
});

test('§7.6 fila 8 (DA-60): un tipo_prueba_id inexistente responde 422 tipo_prueba_inexistente con su mensaje', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], Identificador::generar()->valor()), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertExactJson([
            'codigo' => 'tipo_prueba_inexistente',
            'mensaje' => MENSAJE_DE_TIPO_INEXISTENTE,
            'errores' => [['campo' => 'tipo_prueba_id', 'codigo' => 'tipo_prueba_inexistente', 'mensaje' => MENSAJE_DE_TIPO_INEXISTENTE]],
        ]);
});

test('§7.6 fila 9 (DD-59): E2 lista las de F1 sin las de E1', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    pruebaInsertar($m['f1'], $m['a'], $m['e1'], $m['velocidad']);
    $suya = pruebaInsertar($m['f1'], $m['b'], $m['e2'], $m['velocidad']);

    $listado = sesionesPeticion($this, 'GET', pruebasRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'e2@x.com'))->assertOk();
    $vista = sesionesPeticion($this, 'GET', pruebasRuta($m['f1']).'?filtro[periodo]=2026-11', idDeSesion: sesionesIniciar($this, 'e2@x.com'))->assertOk();

    expect(array_column($listado->json('datos'), 'id'))->toBe([$suya])
        ->and($vista->json('datos.2.prueba.id'))->toBe($suya);
});

test('§7.6 fila 10 (DD-60, DA-59): registrar una prueba de JB responde 409 jugador_en_baja con su mensaje', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();

    sesionesPeticion($this, 'POST', pruebasRuta($m['jb']), pruebaCuerpo($m['a'], $m['velocidad']), sesionesIniciar($this, 'e1@x.com'))
        ->assertConflict()
        ->assertExactJson(['codigo' => 'jugador_en_baja', 'mensaje' => MENSAJE_DE_BAJA_EN_PRUEBA, 'errores' => []]);

    expect(pruebasCuenta($m['jb']))->toBe(0);
});

test('§7.6 fila 11 (DA-63): GET /tipos-prueba trae los tres tipos por código, con unidad y mejor_resultado, sin paginar', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();

    $respuesta = sesionesPeticion($this, 'GET', '/api/v1/tipos-prueba?por_pagina=1', idDeSesion: sesionesIniciar($this, 'e1@x.com'))->assertOk();

    expect(array_keys($respuesta->json()))->toBe(['datos'])
        ->and(array_column($respuesta->json('datos'), 'codigo'))->toBe(['resistencia', 'tiempo_prueba', 'velocidad'])
        ->and(array_column($respuesta->json('datos'), 'id'))->toBe([$m['resistencia'], $m['tiempo_prueba'], $m['velocidad']])
        ->and(array_column($respuesta->json('datos'), 'unidad'))->toBe(['m', 's', 's'])
        ->and(array_column($respuesta->json('datos'), 'mejor_resultado'))->toBe(['mayor', 'menor', 'menor'])
        ->and(array_keys($respuesta->json('datos.0')))->toEqualCanonicalizing(CAMPOS_DEL_TIPO_DE_PRUEBA);
});

test('§7.6 fila 12 (RNF-09, DA-16): el máximo de resistencia cambiado en la base a 5000.00 rechaza 5500.00', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    DB::connection('propietario')->table('tipos_prueba')->where('codigo', 'resistencia')->update(['maximo' => '5000.00']);

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['resistencia'], ['resultado' => '5500.00']), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'valor_fuera_de_rango')
        ->assertJsonPath('errores.0.campo', 'resultado');
});

test('§7.6 fila 13 (DD-62): velocidad 1.00 y tiempo de prueba 3600.00 son válidos', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad'], ['resultado' => '1.00']), $sesion)->assertCreated();
    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['tiempo_prueba'], ['resultado' => '3600.00']), $sesion)->assertCreated();
});

test('DA-39: motivo sin es_correccion responde 422 prohibited, aunque esté vacío o es_correccion sea falso', function (array $cambios) {
    /** @var TestCase $this */
    $m = pruebasMundo();

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad'], $cambios), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('errores.0.campo', 'motivo')
        ->assertJsonPath('errores.0.codigo', 'prohibited');

    expect(pruebasCuenta($m['f1']))->toBe(0);
})->with([
    'sin es_correccion' => [['motivo' => 'Error']],
    'es_correccion falso' => [['es_correccion' => false, 'motivo' => 'Error']],
    'motivo vacío' => [['motivo' => '']],
]);

test('Convenciones §4.1: es_correccion sin motivo responde 422 en motivo, y sin nada en el periodo, nada_que_corregir', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad'], ['es_correccion' => true]), $sesion)
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', 'motivo');
    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad'], ['es_correccion' => true, 'motivo' => 'Error']), $sesion)
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'nada_que_corregir')
        ->assertJsonPath('errores.0.campo', 'es_correccion');
});

test('DA-44: el motivo admite 255 caracteres; 256 responden 422 validacion_fallida y no llegan a la base', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $prueba = pruebaInsertar($m['f1'], $m['a'], $m['e1'], $m['velocidad']);
    $sesion = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad'], ['resultado' => '4.20', 'es_correccion' => true, 'motivo' => str_repeat('a', 256)]), $sesion)
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', 'motivo');

    expect(pruebasHistorial($prueba))->toHaveCount(0);

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad'], ['resultado' => '4.20', 'es_correccion' => true, 'motivo' => str_repeat('a', 255)]), $sesion)
        ->assertOk();

    expect(NoNulo::exigir(pruebasHistorial($prueba)[0] ?? null, 'la fila de historial')->motivo)->toBe(str_repeat('a', 255));
});

test('DA-40: una corrección con valores idénticos responde 200 sin historial', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $prueba = pruebaInsertar($m['f1'], $m['a'], $m['e1'], $m['velocidad']);

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad'], ['es_correccion' => true, 'motivo' => 'Sin cambios']), sesionesIniciar($this, 'e1@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.id', $prueba);

    expect(pruebasHistorial($prueba))->toHaveCount(0);
});

test('DA-12, DA-59: corregir una prueba de un jugador en baja responde 409 jugador_en_baja con su mensaje', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $prueba = pruebaInsertar($m['jb'], $m['a'], $m['e1'], $m['velocidad']);

    sesionesPeticion($this, 'POST', pruebasRuta($m['jb']), pruebaCuerpo($m['a'], $m['velocidad'], ['resultado' => '4.20', 'es_correccion' => true, 'motivo' => 'Error']), sesionesIniciar($this, 'e1@x.com'))
        ->assertConflict()
        ->assertExactJson(['codigo' => 'jugador_en_baja', 'mensaje' => MENSAJE_DE_BAJA_EN_PRUEBA, 'errores' => []]);

    expect(pruebasHistorial($prueba))->toHaveCount(0);
});

test('DA-12, DD-30: las pruebas de un jugador en baja se siguen listando', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $prueba = pruebaInsertar($m['jb'], $m['a'], $m['e1'], $m['velocidad']);

    $respuesta = sesionesPeticion($this, 'GET', pruebasRuta($m['jb']), idDeSesion: sesionesIniciar($this, 'e1@x.com'))->assertOk();

    expect(array_column($respuesta->json('datos'), 'id'))->toBe([$prueba]);
});

test('DA-58: el 422 del cuerpo va antes que el 409 jugador_en_baja', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();

    sesionesPeticion($this, 'POST', pruebasRuta($m['jb']), pruebaCuerpo($m['a'], $m['velocidad'], ['resultado' => 'x']), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida');
});

test('DA-58: jugador_en_baja va antes que equipo_inexistente, tipo_prueba_inexistente y valor_fuera_de_rango', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();

    sesionesPeticion($this, 'POST', pruebasRuta($m['jb']), pruebaCuerpo($m['b'], Identificador::generar()->valor(), ['resultado' => '0.01']), sesionesIniciar($this, 'e1@x.com'))
        ->assertConflict()
        ->assertJsonPath('codigo', 'jugador_en_baja');
});

test('DA-58: equipo_inexistente va antes que tipo_prueba_inexistente', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['b'], Identificador::generar()->valor()), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'equipo_inexistente');
});

test('DA-58: tipo_prueba_inexistente va antes que valor_fuera_de_rango', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], Identificador::generar()->valor(), ['resultado' => '0.01']), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'tipo_prueba_inexistente');
});

test('DA-58: valor_fuera_de_rango va antes que nada_que_corregir y que prueba_ya_registrada', function (bool $yaRegistrada, array $cambios) {
    /** @var TestCase $this */
    $m = pruebasMundo();
    if ($yaRegistrada) {
        pruebaInsertar($m['f1'], $m['a'], $m['e1'], $m['velocidad']);
    }

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad'], ['resultado' => '0.01', ...$cambios]), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'valor_fuera_de_rango');
})->with([
    'sin nada que corregir' => [false, ['es_correccion' => true, 'motivo' => 'Error']],
    'ya registrada' => [true, []],
]);

test('DA-61: un tipo_prueba_id que no es UUID responde 422 validacion_fallida', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], 'velocidad'), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', 'tipo_prueba_id');
});

test('DA-61: un UUID no v7, un tipo eliminado y uno inexistente responden el mismo 422 tipo_prueba_inexistente', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    DB::connection('propietario')->table('tipos_prueba')->where('codigo', 'tiempo_prueba')->update(['eliminado_en' => '2026-11-01 10:00:00+00']);
    $sesion = sesionesIniciar($this, 'e1@x.com');

    $cuerpos = array_map(
        fn (string $tipo) => sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $tipo), $sesion)->assertUnprocessable()->getContent(),
        [UUID_V4_EN_PRUEBAS, $m['tiempo_prueba'], Identificador::generar()->valor()],
    );

    expect(array_unique($cuerpos))->toHaveCount(1)
        ->and(json_decode((string) $cuerpos[0], true)['codigo'])->toBe('tipo_prueba_inexistente')
        ->and(pruebasCuenta($m['f1']))->toBe(0);
});

test('DA-62: con los dos filtros, la vista del periodo trae solo ese tipo; con un UUID no v7, lista vacía', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $velocidad = pruebaInsertar($m['f1'], $m['a'], $m['e1'], $m['velocidad']);
    $sesion = sesionesIniciar($this, 'e1@x.com');

    $solo = sesionesPeticion($this, 'GET', pruebasRuta($m['f1'])."?filtro[periodo]=2026-11&filtro[tipo_prueba_id]={$m['velocidad']}", idDeSesion: $sesion)->assertOk();
    $vacia = sesionesPeticion($this, 'GET', pruebasRuta($m['f1']).'?filtro[periodo]=2026-11&filtro[tipo_prueba_id]='.UUID_V4_EN_PRUEBAS, idDeSesion: $sesion)->assertOk();
    $listadoVacio = sesionesPeticion($this, 'GET', pruebasRuta($m['f1']).'?filtro[tipo_prueba_id]='.UUID_V4_EN_PRUEBAS, idDeSesion: $sesion)->assertOk();

    expect(array_column($solo->json('datos'), 'tipo_prueba_id'))->toBe([$m['velocidad']])
        ->and($solo->json('datos.0.prueba.id'))->toBe($velocidad)
        ->and($vacia->json())->toBe(['datos' => []])
        ->and($listadoVacio->json('datos'))->toBe([])
        ->and($listadoVacio->json('meta.total'))->toBe(0);
});

test('DA-63: la vista del periodo no lee pagina ni por_pagina', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();

    $respuesta = sesionesPeticion($this, 'GET', pruebasRuta($m['f1']).'?filtro[periodo]=2026-11&pagina=0&por_pagina=101', idDeSesion: sesionesIniciar($this, 'e1@x.com'))->assertOk();

    expect($respuesta->json('datos'))->toHaveCount(3)
        ->and(array_keys($respuesta->json()))->toBe(['datos']);
});

test('DA-64: el doble envío del mismo entrenador deja una sola fila; el segundo responde 409 prueba_ya_registrada', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad']), $sesion)->assertCreated();
    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad']), $sesion)
        ->assertConflict()
        ->assertJsonPath('codigo', 'prueba_ya_registrada');

    expect(pruebasCuenta($m['f1']))->toBe(1);
});

test('DA-64: dos entrenadores distintos no chocan: cada uno tiene su fila', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad']), sesionesIniciar($this, 'e1@x.com'))->assertCreated();
    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['b'], $m['velocidad']), sesionesIniciar($this, 'e2@x.com'))->assertCreated();

    expect(pruebasCuenta($m['f1']))->toBe(2);
});

test('§6.1: filtros inválidos responden 422 validacion_fallida', function (string $consulta, string $campo) {
    /** @var TestCase $this */
    $m = pruebasMundo();

    sesionesPeticion($this, 'GET', pruebasRuta($m['f1']).$consulta, idDeSesion: sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'validacion_fallida')
        ->assertJsonPath('errores.0.campo', $campo);
})->with([
    'mes 13' => ['?filtro[periodo]=2026-13', 'filtro.periodo'],
    'otro formato' => ['?filtro[periodo]=11-2026', 'filtro.periodo'],
    'mes de un dígito' => ['?filtro[periodo]=2026-1', 'filtro.periodo'],
    'tipo que no es UUID' => ['?filtro[tipo_prueba_id]=velocidad', 'filtro.tipo_prueba_id'],
    'paginación inválida sin vista' => ['?por_pagina=101', 'por_pagina'],
]);

test('§6.2: resultado exige cadena con hasta dos decimales; campos obligatorios y desconocidos', function (array $cambios, string $campo, string $codigo) {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $cuerpo = pruebaCuerpo($m['a'], $m['velocidad']);
    foreach ($cambios as $clave => $valor) {
        if ($valor === '__quitar__') {
            unset($cuerpo[$clave]);
        } else {
            $cuerpo[$clave] = $valor;
        }
    }

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), $cuerpo, sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('errores.0.campo', $campo)
        ->assertJsonPath('errores.0.codigo', $codigo);
})->with([
    'tres decimales' => [['resultado' => '4.355'], 'resultado', 'regex'],
    'numérico' => [['resultado' => 4.35], 'resultado', 'string'],
    'sin resultado' => [['resultado' => '__quitar__'], 'resultado', 'required'],
    'sin tipo' => [['tipo_prueba_id' => '__quitar__'], 'tipo_prueba_id', 'required'],
    'sin equipo' => [['equipo_id' => '__quitar__'], 'equipo_id', 'required'],
    'fecha con otro formato' => [['fecha' => '03/11/2026'], 'fecha', 'date_format'],
    'campo desconocido' => [['periodo' => '2026-11'], 'periodo', 'prohibited'],
]);

test('T06: cero y negativos responden 422 valor_fuera_de_rango, nunca 500', function (string $resultado) {
    /** @var TestCase $this */
    $m = pruebasMundo();

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad'], ['resultado' => $resultado]), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'valor_fuera_de_rango');
})->with(['0', '-1', '999999.99']);

test('DA-13: un equipo ajeno responde 422 equipo_inexistente; la corrección puede cambiar el equipo', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $sesion = sesionesIniciar($this, 'e3@x.com');

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo(Identificador::generar()->valor(), $m['velocidad']), $sesion)
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'equipo_inexistente');
    $creada = sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad']), $sesion)->assertCreated();
    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['b'], $m['velocidad'], ['es_correccion' => true, 'motivo' => 'Era el otro equipo']), $sesion)
        ->assertOk()
        ->assertJsonPath('datos.id', $creada->json('datos.id'))
        ->assertJsonPath('datos.equipo_id', $m['b']);
});

test('§6.1: fuera del alcance, el 404 gana sobre el 422 y es igual al de un jugador inexistente', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $sesion = sesionesIniciar($this, 'e2@x.com');

    $ajeno = sesionesPeticion($this, 'POST', pruebasRuta($m['j1']), ['resultado' => 'x'], $sesion)->assertNotFound();
    $inexistente = sesionesPeticion($this, 'POST', pruebasRuta(Identificador::generar()->valor()), ['resultado' => 'x'], $sesion)->assertNotFound();
    $listado = sesionesPeticion($this, 'GET', pruebasRuta($m['j1']), idDeSesion: $sesion)->assertNotFound();
    $vista = sesionesPeticion($this, 'GET', pruebasRuta($m['j1']).'?filtro[periodo]=x', idDeSesion: $sesion)->assertNotFound();

    expect($ajeno->getContent())->toBe($inexistente->getContent())
        ->and($listado->getContent())->toBe($ajeno->getContent())
        ->and($vista->getContent())->toBe($ajeno->getContent());
});

test('§6.1: un UUID válido que no es v7 en {jugador} responde 404, nunca 500', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    sesionesPeticion($this, 'GET', pruebasRuta(UUID_V4_EN_PRUEBAS), idDeSesion: $sesion)->assertNotFound();
    sesionesPeticion($this, 'POST', pruebasRuta(UUID_V4_EN_PRUEBAS), pruebaCuerpo($m['a'], $m['velocidad']), $sesion)->assertNotFound();
});

test('§6.1: sin sesión responde 401 y el docente recibe 403 en las tres rutas', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $docente = sesionesIniciar($this, 'd1@x.com');

    sesionesPeticion($this, 'GET', '/api/v1/tipos-prueba')->assertUnauthorized();
    sesionesPeticion($this, 'GET', pruebasRuta($m['f1']))->assertUnauthorized();
    sesionesPeticion($this, 'GET', '/api/v1/tipos-prueba', idDeSesion: $docente)->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    sesionesPeticion($this, 'GET', pruebasRuta($m['f1']), idDeSesion: $docente)->assertForbidden();
    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad']), $docente)->assertForbidden();
});

test('Orden del listado: por periodo ascendente y, dentro del periodo, por código del tipo', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $velocidad = pruebaInsertar($m['f1'], $m['a'], $m['e1'], $m['velocidad'], '2026-11-03', id: '019a5c00-0000-7000-8000-000000000001');
    $resistencia = pruebaInsertar($m['f1'], $m['a'], $m['e1'], $m['resistencia'], '2026-11-03', ['resultado' => '2400.00'], '019a5c00-0001-7000-8000-000000000002');
    $anterior = pruebaInsertar($m['f1'], $m['a'], $m['e1'], $m['velocidad'], '2026-10-03');

    $respuesta = sesionesPeticion($this, 'GET', pruebasRuta($m['f1']), idDeSesion: sesionesIniciar($this, 'e1@x.com'))->assertOk();

    expect(array_column($respuesta->json('datos'), 'id'))->toBe([$anterior, $resistencia, $velocidad]);
});

test('§6.1: el listado pagina y no muestra pruebas borradas lógicamente', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    pruebaInsertar($m['f1'], $m['a'], $m['e1'], $m['velocidad'], '2026-09-03');
    pruebaInsertar($m['f1'], $m['a'], $m['e1'], $m['velocidad'], '2026-10-03');
    pruebaInsertar($m['f1'], $m['a'], $m['e1'], $m['velocidad'], '2026-11-03', ['eliminado_en' => '2026-11-04 10:00:00+00']);
    $sesion = sesionesIniciar($this, 'e1@x.com');

    $segunda = sesionesPeticion($this, 'GET', pruebasRuta($m['f1']).'?pagina=2&por_pagina=1', idDeSesion: $sesion)
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('meta.total_paginas', 2);

    expect(array_column($segunda->json('datos'), 'periodo'))->toBe(['2026-10']);

    sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad']), $sesion)->assertCreated();
});

test('DA-13: un UUID no v7, un equipo inexistente y uno ajeno responden el mismo 422 equipo_inexistente', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $ajeno = mundoEquipo('Ajeno', (string) DB::table('equipos')->where('id', $m['a'])->value('categoria_id'), $m['e1']);
    $sesion = sesionesIniciar($this, 'e3@x.com');

    $cuerpos = array_map(
        fn (string $equipo) => sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($equipo, $m['velocidad']), $sesion)->assertUnprocessable()->getContent(),
        [UUID_V4_EN_PRUEBAS, Identificador::generar()->valor(), $ajeno],
    );

    expect(array_unique($cuerpos))->toHaveCount(1)
        ->and(json_decode((string) $cuerpos[0], true)['codigo'])->toBe('equipo_inexistente')
        ->and(pruebasCuenta($m['f1']))->toBe(0);
});
