<?php

namespace Src\Competencia\Infraestructura;

use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Src\Compartido\Aplicacion\TemporadaDeFecha;
use Src\Compartido\Dominio\Identificador;

/** Adaptador del puerto de Compartido para Alertas (DA-112, DA-91, P-08b): la temporada no eliminada que contiene la fecha. */
final class TemporadaDeFechaSql implements TemporadaDeFecha
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function de(DateTimeImmutable $fecha): ?Identificador
    {
        $dia = $fecha->format('Y-m-d');
        $id = $this->conexion->table('temporadas')
            ->whereNull('eliminado_en')
            ->where('fecha_inicio', '<=', $dia)
            ->where('fecha_fin', '>=', $dia)
            ->value('id');

        return $id === null ? null : Identificador::desdeTexto((string) $id);
    }
}
