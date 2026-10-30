<?php

namespace Src\Competencia\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Src\Compartido\Aplicacion\InicioDePartidos;
use Src\Compartido\Dominio\Identificador;

/** Adaptador del puerto de Compartido: `inicia_en` de los partidos no eliminados (docs/hitos/f1-h4.md §8, T04). */
final class InicioDePartidosCompetencia implements InicioDePartidos
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function de(array $partidos): array
    {
        if ($partidos === []) {
            return [];
        }

        return $this->conexion->table('partidos')
            ->whereNull('eliminado_en')
            ->whereIn('id', array_map(fn (Identificador $partido) => $partido->valor(), $partidos))
            ->pluck('inicia_en', 'id')
            ->map(fn (string $inicio) => (new DateTimeImmutable($inicio))->setTimezone(new DateTimeZone('UTC')))
            ->all();
    }
}
