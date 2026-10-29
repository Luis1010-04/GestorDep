<?php

/*
 * RNF-01: pruebas de la detección estática de consultas de jugadores sin AlcanceDeJugadores. La lista cerrada de usos
 * autorizados, sus constantes y el detector viven en tests/Ayudantes/DeteccionDeConsultasDeJugadores.php, que tests/Pest.php
 * carga siempre: así las pruebas que los usan desde otras carpetas no dependen de que se ejecute esta (auditoría del Lote E, M-7).
 * Agregar un uso nuevo a la lista requiere aprobación.
 */

test('RNF-01: la lista de usos autorizados sin alcance es exactamente la acordada', function () {
    expect(array_keys(USOS_AUTORIZADOS_SIN_ALCANCE))->toBe([
        'src/Plantel/Infraestructura/RepositorioDeJugadoresEloquent.php',
        'src/Plantel/Infraestructura/BusquedaDeJugadorPorMatricula.php',
    ]);
    foreach (USOS_AUTORIZADOS_SIN_ALCANCE as $motivo) {
        expect($motivo)->not->toBeEmpty();
    }
});

test('RNF-01: el detector marca consultas de jugadores sin alcance y deja pasar las que lo usan', function () {
    $archivos = [
        'app/Http/Controllers/JugadorController.php' => <<<'PHP'
            <?php
            use Src\Plantel\Infraestructura\Modelos\JugadorModelo;
            $jugadores = JugadorModelo::query()->get();
            PHP,
        'src/Consultas/Aplicacion/ListarJugadores.php' => <<<'PHP'
            <?php
            $filas = DB::table('jugadores')->where('matricula', 'M-1')->get();
            PHP,
        'src/Consultas/Aplicacion/UnirJugadores.php' => <<<'PHP'
            <?php
            $filas = DB::table('jugador_equipo')->join('jugadores as j', 'j.id', '=', 'jugador_equipo.jugador_id')->get();
            $otras = DB::table("jugadores.id");
            PHP,
        'src/Consultas/Aplicacion/ConAlcance.php' => <<<'PHP'
            <?php
            use Src\Compartido\Aplicacion\AlcanceDeJugadores;
            $consulta = $this->alcance->restringir($usuario, JugadorModelo::query(), 'jugadores.id');
            PHP,
        'src/Consultas/Aplicacion/SoloLoNombra.php' => <<<'PHP'
            <?php
            use Src\Compartido\Aplicacion\AlcanceDeJugadores;
            $consulta = DB::table('jugadores')->get();
            PHP,
        'src/Plantel/Infraestructura/Otro.php' => <<<'PHP'
            <?php
            // JugadorModelo y 'jugadores' en un comentario no cuentan
            $tabla = 'jugador_equipo';
            PHP,
    ];

    expect(consultasDeJugadoresSinAlcance($archivos, []))->toBe([
        'app/Http/Controllers/JugadorController.php: Src\\Plantel\\Infraestructura\\Modelos\\JugadorModelo en linea 2, JugadorModelo en linea 3',
        "src/Consultas/Aplicacion/ListarJugadores.php: 'jugadores' en linea 2",
        "src/Consultas/Aplicacion/UnirJugadores.php: 'jugadores as j' en linea 2, \"jugadores.id\" en linea 3",
        "src/Consultas/Aplicacion/SoloLoNombra.php: 'jugadores' en linea 3",
    ]);
});

test('RNF-01: un archivo exento no se revisa', function () {
    $archivos = ['src/Plantel/Infraestructura/Uno.php' => "<?php\n\$x = DB::table('jugadores');"];

    expect(consultasDeJugadoresSinAlcance($archivos, ['src/Plantel/Infraestructura/Uno.php']))->toBe([]);
});

test('RNF-01: ninguna consulta de jugadores en src ni app evita AlcanceDeJugadores', function () {
    $exentos = [IMPLEMENTACION_DEL_ALCANCE, DECLARACION_DEL_MODELO_JUGADOR, ...array_keys(USOS_AUTORIZADOS_SIN_ALCANCE)];

    expect(consultasDeJugadoresSinAlcance(archivosPhpDeLaAplicacion(), $exentos))->toBe([]);
});

test('RNF-01: la implementación del alcance existe donde la lista la espera', function () {
    expect(is_file(base_path(IMPLEMENTACION_DEL_ALCANCE)))->toBeTrue();
});
