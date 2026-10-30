<?php

namespace Src\Competencia\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Src\Compartido\Aplicacion\ConvocatoriaDePartido;
use Src\Compartido\Aplicacion\PartidoConConvocatoria;
use Src\Compartido\Dominio\Identificador;

/** Adaptador del puerto de Compartido: el partido no eliminado y sus convocatorias no eliminadas (docs/hitos/f1-h4.md §8, T02). */
final class ConvocatoriaDePartidoCompetencia implements ConvocatoriaDePartido
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function de(Identificador $partido): ?PartidoConConvocatoria
    {
        $fila = $this->conexion->table('partidos')->whereNull('eliminado_en')->where('id', $partido->valor())->first(['equipo_id', 'estado']);
        if ($fila === null) {
            return null;
        }

        $convocados = $this->conexion->table('convocatorias')
            ->whereNull('eliminado_en')
            ->where('partido_id', $partido->valor())
            ->orderBy('jugador_id')
            ->pluck('jugador_id')
            ->map(fn (string $jugador) => Identificador::desdeTexto($jugador))
            ->all();

        return new PartidoConConvocatoria($partido, Identificador::desdeTexto($fila->equipo_id), $fila->estado === 'jugado', array_values($convocados));
    }
}
