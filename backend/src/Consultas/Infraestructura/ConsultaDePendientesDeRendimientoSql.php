<?php

namespace Src\Consultas\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Src\Compartido\Aplicacion\AlcanceDeJugadores;
use Src\Compartido\Aplicacion\EquiposDelEntrenador;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Consultas\Aplicacion\ConsultaDePendientesDeRendimiento;
use Src\Consultas\Aplicacion\PartidoPendienteDeRendimiento;
use stdClass;

/**
 * SQL de solo lectura sobre `partidos`, `convocatorias` y `rendimientos_partido` (§4.3). Cuenta los convocados no
 * eliminados, visibles por `AlcanceDeJugadores` sin bajas (como DA-32 de H3), que no tienen ningún rendimiento no
 * eliminado en el partido: si otro entrenador ya lo capturó, no se puede capturar de nuevo y no queda pendiente (DA-13).
 */
final class ConsultaDePendientesDeRendimientoSql implements ConsultaDePendientesDeRendimiento
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly AlcanceDeJugadores $alcance,
        private readonly EquiposDelEntrenador $equipos,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function delUsuario(int $pagina, int $porPagina): array
    {
        $usuario = $this->usuarioActual->identificador();
        $equipos = $usuario === null ? [] : array_map(fn (Identificador $equipo) => $equipo->valor(), $this->equipos->equiposDe($usuario));
        if ($equipos === []) {
            return ['elementos' => [], 'total' => 0];
        }

        $pendientes = $this->pendientes($equipos);
        $elementos = (clone $pendientes)
            ->orderByDesc('partidos.inicia_en')
            ->orderBy('partidos.id')
            ->offset(($pagina - 1) * $porPagina)
            ->limit($porPagina)
            ->get()
            ->map(fn (stdClass $fila) => new PartidoPendienteDeRendimiento(
                Identificador::desdeTexto($fila->partido_id),
                Identificador::desdeTexto($fila->equipo_id),
                (new DateTimeImmutable($fila->inicia_en))->setTimezone(new DateTimeZone('UTC')),
                (int) $fila->convocados_sin_rendimiento,
            ))
            ->all();

        return [
            'elementos' => array_values($elementos),
            'total' => (clone $pendientes)->getCountForPagination(),
        ];
    }

    /** @param  list<string>  $equipos */
    private function pendientes(array $equipos): Builder
    {
        $convocados = $this->conexion->table('partidos')
            ->join('convocatorias', 'convocatorias.partido_id', '=', 'partidos.id')
            ->where('partidos.estado', 'jugado')
            ->whereNull('partidos.eliminado_en')
            ->whereIn('partidos.equipo_id', $equipos)
            ->whereNull('convocatorias.eliminado_en')
            ->whereNotExists(fn (Builder $rendimiento) => $rendimiento->from('rendimientos_partido')
                ->whereColumn('rendimientos_partido.partido_id', 'partidos.id')
                ->whereColumn('rendimientos_partido.jugador_id', 'convocatorias.jugador_id')
                ->whereNull('rendimientos_partido.eliminado_en'));

        return $this->alcance->restringir($this->usuarioActual, $convocados, 'convocatorias.jugador_id', incluirBajas: false, soloEntrenador: true)
            ->groupBy('partidos.id', 'partidos.equipo_id', 'partidos.inicia_en')
            ->select('partidos.id as partido_id', 'partidos.equipo_id', 'partidos.inicia_en')
            ->selectRaw('count(*) as convocados_sin_rendimiento');
    }
}
