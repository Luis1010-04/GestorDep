<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/** Datos de partida de docs/hitos/f1-h2.md §7.0. Las inscripciones se siembran directo: su endpoint llega en T10. */

/** @return list<string> ids de los jugadores de una página de `GET /jugadores`, en el orden en que llegan */
function alcanceIdsListados(TestCase $prueba, string $correo, string $consulta = ''): array
{
    $respuesta = sesionesPeticion($prueba, 'GET', '/api/v1/jugadores'.$consulta, idDeSesion: sesionesIniciar($prueba, $correo))->assertOk();

    return array_column($respuesta->json('datos'), 'id');
}

test('HU-02 (§7.4 fila 1): D1 lista a J1 y J3, no a J2, J4 ni J5 (en baja)', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    expect(alcanceIdsListados($this, 'd1@x.com'))->toBe([$m['j1'], $m['j3']]);
});

test('RN-13 (§7.4 fila 2): D1 consulta a J3 y sus equipos salen vacíos', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    mundoMembresia($m['j3'], $m['ea']);

    sesionesPeticion($this, 'GET', '/api/v1/jugadores/'.$m['j3'], idDeSesion: sesionesIniciar($this, 'd1@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.id', $m['j3'])
        ->assertJsonPath('datos.equipos', []);
});

test('RNF-01 (§7.4 filas 3 y 4): D1 consulta a J2 y a J4 y recibe un 404 idéntico al de un id inexistente', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'd1@x.com');

    $inexistente = sesionesPeticion($this, 'GET', '/api/v1/jugadores/'.Identificador::generar()->valor(), idDeSesion: $sesion)->assertNotFound();
    foreach (['j2', 'j4'] as $ajeno) {
        $respuesta = sesionesPeticion($this, 'GET', '/api/v1/jugadores/'.$m[$ajeno], idDeSesion: $sesion)->assertNotFound();
        expect($respuesta->getContent())->toBe($inexistente->getContent());
    }
});

test('DD-30 (§7.4 fila 5): D1 consulta a J5 en baja y recibe 200 con estatus baja (estatus_deportivo se mantiene para el docente)', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    sesionesPeticion($this, 'GET', '/api/v1/jugadores/'.$m['j5'], idDeSesion: sesionesIniciar($this, 'd1@x.com'))
        ->assertOk()
        ->assertJsonPath('datos.estatus_academico', 'baja')
        ->assertJsonPath('datos.estatus_deportivo', 'baja');
});

test('RN-20 (§7.4 fila 6): D1 busca por matrícula a J4 y recibe exactamente nombres, apellidos y matrícula', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    $respuesta = sesionesPeticion($this, 'GET', '/api/v1/jugadores/busqueda?matricula=M-4', idDeSesion: sesionesIniciar($this, 'd1@x.com'))->assertOk();

    expect($respuesta->json('datos'))->toBe(['nombres' => 'Cuatro', 'apellidos' => 'Dominguez', 'matricula' => 'M-4'])
        ->and(DB::table('inscripciones')->where('jugador_id', $m['j4'])->count())->toBe(0);
});

test('HU-02 (§7.4 fila 7): D2 lista a J2 y J3', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    expect(alcanceIdsListados($this, 'd2@x.com'))->toBe([$m['j2'], $m['j3']]);
});

test('RNF-01 (§7.4 fila 8): J3 inscrito en dos materias de D1 sale una vez y meta.total cuenta uno', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $otraMateria = mundoInsertar('materias', ['asignatura_id' => $m['s2'], 'periodo_id' => $m['p1'], 'docente_id' => $m['d1']]);
    mundoInscribir($otraMateria, $m['j3']);

    $respuesta = sesionesPeticion($this, 'GET', '/api/v1/jugadores', idDeSesion: sesionesIniciar($this, 'd1@x.com'))->assertOk();

    expect(array_column($respuesta->json('datos'), 'id'))->toBe([$m['j1'], $m['j3']])
        ->and($respuesta->json('meta.total'))->toBe(2);
});

test('RNF-01 (§7.4 fila 9): DE con el equipo ED (J4) y la materia M3 (J1) lista a J1 y J4 sin duplicados', function () {
    /** @var TestCase $this */
    $m = mundoAgregarEquipoYMateriaDeDocenteEntrenador(mundoDeDocentes());
    mundoInscribir($m['m3'], $m['j4']);
    $sesion = sesionesIniciar($this, 'de@x.com');

    $lista = sesionesPeticion($this, 'GET', '/api/v1/jugadores', idDeSesion: $sesion)->assertOk();
    expect(array_column($lista->json('datos'), 'id'))->toBe([$m['j1'], $m['j4']])
        ->and($lista->json('meta.total'))->toBe(2);

    sesionesPeticion($this, 'GET', '/api/v1/jugadores/'.$m['j1'], idDeSesion: $sesion)->assertOk()->assertJsonPath('datos.equipos', []);
    sesionesPeticion($this, 'GET', '/api/v1/jugadores/'.$m['j4'], idDeSesion: $sesion)->assertOk()->assertJsonPath('datos.equipos.0.equipo_id', $m['ed']);
});

test('HU-02 (§7.4 fila 10): A lista solo a los de sus equipos, sin cambios respecto a H1', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    expect(alcanceIdsListados($this, 'a@x.com'))->toBe([$m['j1']]);
});

test('HU-01 (§7.4 fila 11): D1 recibe 403 al crear equipo, categoría y jugador', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'd1@x.com');

    sesionesPeticion($this, 'POST', '/api/v1/equipos', ['nombre' => 'X', 'categoria_id' => $m['categoria']], $sesion)->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    sesionesPeticion($this, 'POST', '/api/v1/categorias', ['nombre' => 'X'], $sesion)->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    sesionesPeticion($this, 'POST', '/api/v1/jugadores', [], $sesion)->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    expect(DB::table('equipos')->count())->toBe(1)->and(DB::table('categorias')->count())->toBe(1);
});

test('HU-01 (§7.4 fila 12): A recibe 403 en materias, periodos y asignaturas', function () {
    /** @var TestCase $this */
    mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'a@x.com');

    foreach (['/api/v1/materias', '/api/v1/periodos-escolares', '/api/v1/asignaturas'] as $ruta) {
        sesionesPeticion($this, 'GET', $ruta, idDeSesion: $sesion)->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    }
});

test('RNF-01 (§7.4 fila 13): el filtro por equipo no amplía el alcance de D1', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();

    $respuesta = sesionesPeticion($this, 'GET', '/api/v1/jugadores?filtro[equipo_id]='.$m['ea'], idDeSesion: sesionesIniciar($this, 'd1@x.com'))->assertOk();

    expect($respuesta->json('datos'))->toBe([])->and($respuesta->json('meta.total'))->toBe(0);
});

test('RNF-01 (§7.4 fila 14): las lecturas de jugadores siguen pasando por AlcanceDeJugadores y la lista cerrada no crece', function () {
    expect(array_keys(USOS_AUTORIZADOS_SIN_ALCANCE))->toBe([
        'src/Plantel/Infraestructura/RepositorioDeJugadoresEloquent.php',
        'src/Plantel/Infraestructura/BusquedaDeJugadorPorMatricula.php',
    ])->and(consultasDeJugadoresSinAlcance(
        archivosPhpDeLaAplicacion(),
        [IMPLEMENTACION_DEL_ALCANCE, DECLARACION_DEL_MODELO_JUGADOR, ...array_keys(USOS_AUTORIZADOS_SIN_ALCANCE)],
    ))->toBe([]);
});

test('RNF-01: una inscripción eliminada o una materia eliminada ya no otorgan alcance', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'd1@x.com');
    $marca = ['eliminado_en' => '2026-03-01 00:00:00+00', 'eliminado_por' => $m['d1']];

    DB::connection('propietario')->table('inscripciones')->where('id', $m['i1'])->update($marca);
    expect(alcanceIdsListados($this, 'd1@x.com'))->toBe([$m['j3']]);

    DB::connection('propietario')->table('materias')->where('id', $m['m1'])->update($marca);
    expect(alcanceIdsListados($this, 'd1@x.com'))->toBe([]);
    sesionesPeticion($this, 'GET', '/api/v1/jugadores/'.$m['j3'], idDeSesion: $sesion)->assertNotFound();
});

test('RNF-01: un docente sin ninguna inscripción recibe una lista vacía con 200, no un 403', function () {
    /** @var TestCase $this */
    mundoDeDocentes();
    sesionesCrearUsuario('d3@x.com', ['docente']);

    $respuesta = sesionesPeticion($this, 'GET', '/api/v1/jugadores', idDeSesion: sesionesIniciar($this, 'd3@x.com'))->assertOk();

    expect($respuesta->json('datos'))->toBe([])->and($respuesta->json('meta.total'))->toBe(0);
});

test('RNF-01: un usuario sin los roles docente ni entrenador no ve a nadie aunque tenga inscripciones sembradas', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sinRol = sesionesCrearUsuario('s@x.com', []);
    $materia = mundoInsertar('materias', ['asignatura_id' => $m['s1'], 'periodo_id' => $m['p0'], 'docente_id' => $sinRol]);
    mundoInscribir($materia, $m['j4']);

    expect(alcanceIdsListados($this, 's@x.com'))->toBe([]);
});
