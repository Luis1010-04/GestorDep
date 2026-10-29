<?php

use Database\Seeders\PosicionesSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Aplicacion\PosicionInexistente;
use Src\Competencia\Dominio\Convocatoria;
use Src\Competencia\Dominio\RepositorioDeConvocatorias;
use Src\Competencia\Dominio\RolDeAlineacion;

/** DA-31: el repositorio solo traduce a `posicion_inexistente` la clave foránea de `posicion_id`. */
beforeEach(function () {
    sesionesPrepararBase();
    (new PosicionesSeeder)->run();
});

function claveForaneaComoUsuario(string $usuarioId): void
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

/** @return array{convocatoria: Convocatoria, posicion: string} */
function claveForaneaConvocatoria(): array
{
    $categoria = mundoInsertar('categorias', ['nombre' => 'Sub-17']);
    $equipo = mundoInsertar('equipos', ['nombre' => 'A', 'categoria_id' => $categoria]);
    $partido = mundoInsertar('partidos', [
        'equipo_id' => $equipo, 'temporada_id' => mundoInsertar('temporadas', ['nombre' => 'T1', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2026-12-15']),
        'rival_id' => mundoInsertar('rivales', ['nombre' => 'R1']), 'lugar_id' => mundoInsertar('lugares', ['nombre' => 'L1']),
        'inicia_en' => '2026-09-12 23:00:00+00', 'tipo' => 'amistoso', 'condicion' => 'local', 'estado' => 'programado',
    ]);
    $jugador = mundoJugador('M-1', 'Uno');
    $id = mundoInsertar('convocatorias', ['partido_id' => $partido, 'jugador_id' => $jugador]);

    return [
        'convocatoria' => new Convocatoria(
            Identificador::desdeTexto($id), Identificador::desdeTexto($partido), Identificador::desdeTexto($jugador),
            null, null, new DateTimeImmutable('2026-09-05 15:00:00', new DateTimeZone('UTC')),
        ),
        'posicion' => (string) DB::table('posiciones')->where('codigo', 'medio')->value('id'),
    ];
}

test('HU-13 (DA-31): una posición que no está en el catálogo se traduce a PosicionInexistente y no deja cambios', function () {
    claveForaneaComoUsuario(sesionesCrearUsuario('e1@x.com', ['entrenador']));
    ['convocatoria' => $convocatoria] = claveForaneaConvocatoria();
    $nueva = $convocatoria->conAlineacion(Identificador::generar(), RolDeAlineacion::Titular, new DateTimeImmutable('2026-09-13 01:00:00', new DateTimeZone('UTC')));

    expect(fn () => app(RepositorioDeConvocatorias::class)->actualizarAlineacion([['anterior' => $convocatoria, 'nueva' => $nueva]]))
        ->toThrow(PosicionInexistente::class);
    expect(DB::table('convocatorias')->whereNotNull('rol_alineacion')->count())->toBe(0)
        ->and(DB::table('convocatorias_historial')->count())->toBe(0);
});

test('HU-13 (DA-31): otra clave foránea inválida (el autor en actualizado_por) no se traduce a posicion_inexistente', function () {
    claveForaneaComoUsuario(Identificador::generar()->valor());
    ['convocatoria' => $convocatoria, 'posicion' => $posicion] = claveForaneaConvocatoria();
    $nueva = $convocatoria->conAlineacion(Identificador::desdeTexto($posicion), RolDeAlineacion::Titular, new DateTimeImmutable('2026-09-13 01:00:00', new DateTimeZone('UTC')));

    $lanzada = null;
    try {
        app(RepositorioDeConvocatorias::class)->actualizarAlineacion([['anterior' => $convocatoria, 'nueva' => $nueva]]);
    } catch (Throwable $e) {
        $lanzada = $e;
    }

    expect($lanzada)->toBeInstanceOf(QueryException::class)
        ->and($lanzada)->not->toBeInstanceOf(PosicionInexistente::class)
        ->and((string) $lanzada?->getCode())->toBe('23503')
        ->and((string) $lanzada?->getMessage())->toContain('fk_convocatorias_actualizado_por')
        ->and(DB::table('convocatorias')->whereNotNull('rol_alineacion')->count())->toBe(0);
});
