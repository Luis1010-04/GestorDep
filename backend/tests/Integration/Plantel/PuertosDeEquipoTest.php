<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Aplicacion\AlcanceDeJugadores;
use Src\Compartido\Aplicacion\EquiposDelEntrenador;
use Src\Compartido\Aplicacion\JugadoresDelEquipo;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;

/** Adaptadores en Plantel de los puertos de Compartido que usa Competencia (docs/hitos/f1-h3.md §5.3). */
beforeEach(function () {
    sesionesPrepararBase();
});

function puertosComoUsuario(string $usuarioId): void
{
    app()->instance(UsuarioActual::class, new class($usuarioId) implements UsuarioActual
    {
        public function __construct(private readonly string $id) {}

        public function identificador(): Identificador
        {
            return Identificador::desdeTexto($this->id);
        }

        public function roles(): array
        {
            return ['entrenador'];
        }
    });
}

/**
 * @param  list<Identificador>  $identificadores
 * @return list<string>
 */
function puertosValores(array $identificadores): array
{
    $valores = array_map(fn (Identificador $id) => $id->valor(), $identificadores);
    sort($valores);

    return $valores;
}

test('RN-22: EquiposDelEntrenador devuelve los equipos con asignación vigente del entrenador', function () {
    $m = mundoDeCompetencia();
    $equipos = app(EquiposDelEntrenador::class);
    $esperado = [$m['a'], $m['b']];
    sort($esperado);

    expect(puertosValores($equipos->equiposDe(Identificador::desdeTexto($m['e1']))))->toBe([$m['a']])
        ->and(puertosValores($equipos->equiposDe(Identificador::desdeTexto($m['e3']))))->toBe($esperado)
        ->and($equipos->equiposDe(Identificador::desdeTexto($m['d'])))->toBe([])
        ->and($equipos->entrena(Identificador::desdeTexto($m['e1']), Identificador::desdeTexto($m['a'])))->toBeTrue()
        ->and($equipos->entrena(Identificador::desdeTexto($m['e1']), Identificador::desdeTexto($m['b'])))->toBeFalse()
        ->and($equipos->entrena(Identificador::desdeTexto($m['e3']), Identificador::desdeTexto($m['b'])))->toBeTrue();
});

test('RN-22 (DA-13): una asignación cerrada o eliminada, un equipo eliminado, un usuario eliminado o sin rol entrenador no cuentan', function () {
    $m = mundoDeCompetencia();
    $equipos = app(EquiposDelEntrenador::class);
    $categoria = mundoInsertar('categorias', ['nombre' => 'Sub-15']);
    $c = mundoEquipo('C', $categoria, $m['e1']);
    $cerrada = sesionesCrearUsuario('cerrada@x.com', ['entrenador']);
    mundoInsertar('entrenador_equipo', ['usuario_id' => $cerrada, 'equipo_id' => $m['a'], 'desde' => '2026-01-01', 'hasta' => '2026-06-30']);
    $eliminada = sesionesCrearUsuario('eliminada@x.com', ['entrenador']);
    mundoInsertar('entrenador_equipo', ['usuario_id' => $eliminada, 'equipo_id' => $m['a'], 'desde' => '2026-01-01', 'eliminado_en' => '2026-06-30 10:00:00+00']);
    $sinRol = sesionesCrearUsuario('sinrol@x.com', ['docente']);
    mundoInsertar('entrenador_equipo', ['usuario_id' => $sinRol, 'equipo_id' => $m['a'], 'desde' => '2026-01-01']);
    $usuarioEliminado = sesionesCrearUsuario('baja@x.com', ['entrenador']);
    mundoInsertar('entrenador_equipo', ['usuario_id' => $usuarioEliminado, 'equipo_id' => $m['a'], 'desde' => '2026-01-01']);
    DB::connection('propietario')->table('usuarios')->where('id', $usuarioEliminado)->update(['eliminado_en' => '2026-06-30 10:00:00+00']);
    DB::connection('propietario')->table('equipos')->where('id', $c)->update(['eliminado_en' => '2026-06-30 10:00:00+00']);
    $a = Identificador::desdeTexto($m['a']);

    expect($equipos->entrena(Identificador::desdeTexto($m['e1']), Identificador::desdeTexto($c)))->toBeFalse()
        ->and(puertosValores($equipos->equiposDe(Identificador::desdeTexto($m['e1']))))->toBe([$m['a']]);
    foreach ([$cerrada, $eliminada, $sinRol, $usuarioEliminado] as $usuario) {
        expect($equipos->entrena(Identificador::desdeTexto($usuario), $a))->toBeFalse($usuario)
            ->and($equipos->equiposDe(Identificador::desdeTexto($usuario)))->toBe([]);
    }
});

test('HU-10 (DA-12): JugadoresDelEquipo devuelve los jugadores con membresía vigente y sin baja, solo con id, nombres y apellidos', function () {
    $m = mundoDeCompetencia();
    $j6 = mundoJugador('M-6', 'Seis', nombres: 'J6');
    mundoMembresia($j6, $m['a'], 'medio', '2026-06-30');
    puertosComoUsuario($m['e1']);

    $jugadores = app(JugadoresDelEquipo::class)->de(Identificador::desdeTexto($m['a']));

    expect(array_map(fn ($j) => [$j->id->valor(), $j->nombres, $j->apellidos], $jugadores))->toBe([
        [$m['j2'], 'J2', 'Dos'], [$m['j3'], 'J3', 'Tres'], [$m['j1'], 'J1', 'Uno'],
    ]);
});

test('RNF-01 (DA-12): a quien no entrena el equipo no le devuelve a nadie, ni a los jugadores que ve por otro equipo', function () {
    $m = mundoDeCompetencia();
    puertosComoUsuario($m['e2']);

    expect(app(JugadoresDelEquipo::class)->de(Identificador::desdeTexto($m['a'])))->toBe([]);
});

test('RNF-01 (DA-12): con la asignación del equipo pero sin alcance sobre el jugador, el jugador no aparece', function () {
    $m = mundoDeCompetencia();
    puertosComoUsuario($m['e1']);
    $alcance = Mockery::mock(AlcanceDeJugadores::class);
    $alcance->shouldReceive('restringir')->once()->andReturnUsing(fn ($usuario, $consulta) => $consulta->whereRaw('false'));
    app()->instance(AlcanceDeJugadores::class, $alcance);

    expect(app(JugadoresDelEquipo::class)->de(Identificador::desdeTexto($m['a'])))->toBe([]);
});
