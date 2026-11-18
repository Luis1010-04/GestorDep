<?php

namespace Src\Alertas\Infraestructura;

use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Src\Alertas\Aplicacion\RepositorioDeAlertas;
use Src\Alertas\Aplicacion\TipoDeAlerta;
use Src\Compartido\Dominio\Identificador;

/**
 * SQL sobre la tabla `alertas` (DA-87). Abrir es un INSERT que ignora el conflicto con el índice único parcial de las
 * alertas abiertas (T01), así que dos procesos que abren a la vez dejan una sola, sin error que aborte la transacción;
 * cerrar es un UPDATE de `cerrada_en` solo donde sigue nulo, la única modificación que admite el disparador.
 */
final class RepositorioDeAlertasSql implements RepositorioDeAlertas
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function hayAbierta(TipoDeAlerta $tipo, Identificador $jugador, ?Identificador $equipo): bool
    {
        return $this->abiertas($tipo, $jugador, $equipo)->exists();
    }

    public function abrir(TipoDeAlerta $tipo, Identificador $jugador, ?Identificador $equipo, array $causa, DateTimeImmutable $ahora): void
    {
        $this->conexion->table('alertas')->insertOrIgnore([
            'id' => Identificador::generar()->valor(),
            'tipo' => $tipo->value,
            'jugador_id' => $jugador->valor(),
            'equipo_id' => $equipo?->valor(),
            'causa' => json_encode($causa, JSON_THROW_ON_ERROR),
            'activada_en' => $ahora->format('Y-m-d H:i:sP'),
        ]);
    }

    public function cerrar(TipoDeAlerta $tipo, Identificador $jugador, ?Identificador $equipo, DateTimeImmutable $ahora): void
    {
        $this->abiertas($tipo, $jugador, $equipo)->update(['cerrada_en' => $ahora->format('Y-m-d H:i:sP')]);
    }

    private function abiertas(TipoDeAlerta $tipo, Identificador $jugador, ?Identificador $equipo): Builder
    {
        $consulta = $this->conexion->table('alertas')
            ->where('tipo', $tipo->value)
            ->where('jugador_id', $jugador->valor())
            ->whereNull('cerrada_en');

        return $equipo === null ? $consulta->whereNull('equipo_id') : $consulta->where('equipo_id', $equipo->valor());
    }
}
