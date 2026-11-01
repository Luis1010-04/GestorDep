<?php

namespace Src\Rendimiento\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Src\Compartido\Aplicacion\AlcanceDeJugadores;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Rendimiento\Aplicacion\ConsultaDeRendimientos;

/** Rendimientos no eliminados del partido: primero el alcance del entrenador (con bajas, DA-12) y después `registrado_por` (§3.3). */
final class ConsultaDeRendimientosSql implements ConsultaDeRendimientos
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly AlcanceDeJugadores $alcance,
        private readonly UsuarioActual $usuarioActual,
        private readonly RepositorioDeRendimientosEloquent $repositorio,
    ) {}

    public function delPartidoPor(Identificador $partidoId, Identificador $registradoPor, int $pagina, int $porPagina): array
    {
        $filas = $this->visibles($partidoId, $registradoPor)
            ->orderBy('rendimientos_partido.creado_en')
            ->orderBy('rendimientos_partido.id')
            ->offset(($pagina - 1) * $porPagina)
            ->limit($porPagina)
            ->get(array_map(fn (string $columna) => "rendimientos_partido.$columna", RepositorioDeRendimientosEloquent::COLUMNAS));

        return $this->repositorio->aRendimientos($filas);
    }

    public function contarDelPartidoPor(Identificador $partidoId, Identificador $registradoPor): int
    {
        return $this->visibles($partidoId, $registradoPor)->count();
    }

    private function visibles(Identificador $partidoId, Identificador $registradoPor): Builder
    {
        $consulta = $this->conexion->table('rendimientos_partido')
            ->where('rendimientos_partido.partido_id', $partidoId->valor())
            ->whereNull('rendimientos_partido.eliminado_en');

        return $this->alcance->restringir($this->usuarioActual, $consulta, 'rendimientos_partido.jugador_id', incluirBajas: true, soloEntrenador: true)
            ->where('rendimientos_partido.registrado_por', $registradoPor->valor());
    }
}
