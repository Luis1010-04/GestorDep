<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Aplicacion\ConvocatoriaDePartido;
use Src\Compartido\Aplicacion\InicioDePartidos;
use Src\Compartido\Aplicacion\PosicionEnEquipo;
use Src\Compartido\Dominio\Identificador;

/** Adaptadores de los puertos de Compartido que usa Rendimiento (docs/hitos/f1-h4.md §8, T02). */
beforeEach(function () {
    sesionesPrepararBase();
});

test('HU-14 (DD-05): PosicionEnEquipo devuelve la posición de la membresía vigente del jugador en ese equipo', function () {
    $m = mundoDeCompetencia();
    $posicion = app(PosicionEnEquipo::class);
    $idDe = fn (string $codigo) => (string) DB::table('posiciones')->where('codigo', $codigo)->value('id');

    expect($posicion->de(Identificador::desdeTexto($m['j3']), Identificador::desdeTexto($m['a']))?->valor())->toBe($idDe('medio'))
        ->and($posicion->de(Identificador::desdeTexto($m['j3']), Identificador::desdeTexto($m['b']))?->valor())->toBe($idDe('delantero'))
        ->and($posicion->de(Identificador::desdeTexto($m['j4']), Identificador::desdeTexto($m['a'])))->toBeNull()
        ->and($posicion->de(Identificador::generar(), Identificador::desdeTexto($m['a'])))->toBeNull();
});

test('HU-14 (DD-05): PosicionEnEquipo no devuelve una membresía cerrada ni una eliminada', function () {
    $m = mundoDeCompetencia();
    $cerrado = mundoJugador('M-9', 'Nueve');
    mundoMembresia($cerrado, $m['a'], 'portero', '2026-06-30');
    $eliminado = mundoJugador('M-10', 'Diez');
    $membresia = mundoMembresia($eliminado, $m['a'], 'portero');
    DB::connection('propietario')->table('jugador_equipo')->where('id', $membresia)->update(['eliminado_en' => '2026-09-01 10:00:00+00']);
    $posicion = app(PosicionEnEquipo::class);

    expect($posicion->de(Identificador::desdeTexto($cerrado), Identificador::desdeTexto($m['a'])))->toBeNull()
        ->and($posicion->de(Identificador::desdeTexto($eliminado), Identificador::desdeTexto($m['a'])))->toBeNull();
});

test('HU-14: ConvocatoriaDePartido devuelve el equipo, si está jugado y los convocados no eliminados', function () {
    $m = mundoDeCompetencia();
    $datos = ['temporada_id' => $m['t1'], 'rival_id' => $m['r1'], 'lugar_id' => $m['l1'], 'inicia_en' => '2026-09-12 23:00:00+00', 'tipo' => 'amistoso', 'condicion' => 'local'];
    $jugado = mundoInsertar('partidos', [...$datos, 'equipo_id' => $m['a'], 'estado' => 'jugado', 'goles_favor' => 1, 'goles_contra' => 0]);
    $programado = mundoInsertar('partidos', [...$datos, 'equipo_id' => $m['b'], 'estado' => 'programado']);
    mundoInsertar('convocatorias', ['partido_id' => $jugado, 'jugador_id' => $m['j1']]);
    mundoInsertar('convocatorias', ['partido_id' => $jugado, 'jugador_id' => $m['j2'], 'eliminado_en' => '2026-09-10 10:00:00+00']);
    $convocatoria = app(ConvocatoriaDePartido::class);

    $dePartidoJugado = $convocatoria->de(Identificador::desdeTexto($jugado));
    $dePartidoProgramado = $convocatoria->de(Identificador::desdeTexto($programado));

    expect($dePartidoJugado?->equipoId->valor())->toBe($m['a'])
        ->and($dePartidoJugado?->jugado)->toBeTrue()
        ->and(array_map(fn (Identificador $id) => $id->valor(), $dePartidoJugado->convocados ?? []))->toBe([$m['j1']])
        ->and($dePartidoProgramado?->jugado)->toBeFalse()
        ->and($dePartidoProgramado?->convocados)->toBe([])
        ->and($convocatoria->de(Identificador::generar()))->toBeNull();
});

test('RN-08 (v1.0.5): InicioDePartidos devuelve el inicio de cada partido no eliminado, por identificador', function () {
    $m = mundoDeCompetencia();
    $datos = ['equipo_id' => $m['a'], 'temporada_id' => $m['t1'], 'rival_id' => $m['r1'], 'lugar_id' => $m['l1'], 'tipo' => 'amistoso', 'condicion' => 'local', 'estado' => 'programado'];
    $primero = mundoInsertar('partidos', [...$datos, 'inicia_en' => '2026-09-12 23:00:00+00']);
    $segundo = mundoInsertar('partidos', [...$datos, 'inicia_en' => '2026-09-19 18:30:00+00']);
    $eliminado = mundoInsertar('partidos', [...$datos, 'inicia_en' => '2026-09-26 23:00:00+00', 'eliminado_en' => '2026-09-10 10:00:00+00']);
    $inicios = app(InicioDePartidos::class);

    $resultado = $inicios->de(array_map(fn (string $id) => Identificador::desdeTexto($id), [$primero, $segundo, $eliminado]));
    $resultado += $inicios->de([Identificador::generar()]);

    expect(array_map(fn (DateTimeImmutable $inicio) => $inicio->format(DateTimeInterface::ATOM), $resultado))->toEqualCanonicalizing([
        $primero => '2026-09-12T23:00:00+00:00', $segundo => '2026-09-19T18:30:00+00:00',
    ])
        ->and($inicios->de([]))->toBe([]);
});

test('HU-14: ConvocatoriaDePartido no devuelve un partido eliminado', function () {
    $m = mundoDeCompetencia();
    $partido = mundoInsertar('partidos', [
        'equipo_id' => $m['a'], 'temporada_id' => $m['t1'], 'rival_id' => $m['r1'], 'lugar_id' => $m['l1'], 'inicia_en' => '2026-09-12 23:00:00+00',
        'tipo' => 'amistoso', 'condicion' => 'local', 'estado' => 'programado', 'eliminado_en' => '2026-09-10 10:00:00+00',
    ]);

    expect(app(ConvocatoriaDePartido::class)->de(Identificador::desdeTexto($partido)))->toBeNull();
});
