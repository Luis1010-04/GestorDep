<?php

namespace Src\Consultas\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Src\Compartido\Dominio\Identificador;
use Src\Consultas\Aplicacion\AlertaVisible;
use Src\Consultas\Aplicacion\ConsultaDeAlertas;
use stdClass;

/** SQL de solo lectura sobre `alertas` (§4.3); la visibilidad la define `VisibilidadDeAlertasSql`. */
final class ConsultaDeAlertasSql implements ConsultaDeAlertas
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly VisibilidadDeAlertasSql $visibilidad,
    ) {}

    public function delJugador(Identificador $jugador): array
    {
        return $this->deLosJugadores([$jugador->valor()])[$jugador->valor()] ?? [];
    }

    public function deLosJugadores(array $jugadores): array
    {
        if ($jugadores === []) {
            return [];
        }

        $filas = $this->visibilidad->restringir($this->conexion->table('alertas')->whereIn('alertas.jugador_id', $jugadores))
            ->orderBy('alertas.activada_en')
            ->orderBy('alertas.id')
            ->get(['alertas.id', 'alertas.tipo', 'alertas.jugador_id', 'alertas.equipo_id', 'alertas.causa', 'alertas.activada_en']);

        $porJugador = [];
        foreach ($filas as $fila) {
            /** @var stdClass $fila */
            $porJugador[$fila->jugador_id][] = new AlertaVisible(
                Identificador::desdeTexto($fila->id),
                $fila->tipo,
                Identificador::desdeTexto($fila->jugador_id),
                $fila->equipo_id === null ? null : Identificador::desdeTexto($fila->equipo_id),
                $this->causa($fila->causa),
                (new DateTimeImmutable($fila->activada_en))->setTimezone(new DateTimeZone('UTC')),
            );
        }

        return $porJugador;
    }

    /** @return array<string, mixed> */
    private function causa(string $json): array
    {
        /** @var array<string, mixed> */
        return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    }
}
