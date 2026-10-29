<?php

namespace Src\Competencia\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Src\Compartido\Aplicacion\AlcanceDeJugadores;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Aplicacion\ConsultaDeConvocatoria;
use stdClass;

/** Convocados no eliminados del partido, por `AlcanceDeJugadores` sin bajas y solo con la rama del entrenador (HU-29 criterio 3). */
final class ConsultaDeConvocatoriaSql implements ConsultaDeConvocatoria
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly AlcanceDeJugadores $alcance,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function delPartido(Identificador $partidoId, int $pagina, int $porPagina): array
    {
        $convocatorias = $this->visibles($partidoId)
            ->orderBy('convocatorias.creado_en')
            ->orderBy('convocatorias.id')
            ->offset(($pagina - 1) * $porPagina)
            ->limit($porPagina)
            ->get(array_map(fn (string $columna) => "convocatorias.$columna", RepositorioDeConvocatoriasEloquent::COLUMNAS))
            ->map(fn (stdClass $fila) => RepositorioDeConvocatoriasEloquent::aConvocatoria($fila))
            ->all();

        return array_values($convocatorias);
    }

    public function todasDelPartido(Identificador $partidoId): array
    {
        return $this->delPartido($partidoId, 1, max(1, $this->contarDelPartido($partidoId)));
    }

    public function contarDelPartido(Identificador $partidoId): int
    {
        return $this->visibles($partidoId)->count();
    }

    private function visibles(Identificador $partidoId): Builder
    {
        $consulta = $this->conexion->table('convocatorias')
            ->where('convocatorias.partido_id', $partidoId->valor())
            ->whereNull('convocatorias.eliminado_en');

        return $this->alcance->restringir($this->usuarioActual, $consulta, 'convocatorias.jugador_id', incluirBajas: false, soloEntrenador: true);
    }
}
