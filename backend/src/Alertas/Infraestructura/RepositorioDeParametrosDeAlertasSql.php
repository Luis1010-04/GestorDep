<?php

namespace Src\Alertas\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use LogicException;
use Src\Alertas\Aplicacion\RepositorioDeParametrosDeAlertas;
use Src\Alertas\Dominio\ParametrosDeSanciones;

/**
 * Lee de `parametros_institucionales` las claves `sanciones_deportivas` y `alerta_academica` (DA-100, RNF-09): la tabla de
 * parámetros es compartida y aparece junto a `alertas` en arquitectura §7.9, así que Alertas la lee con su propio SQL.
 */
final class RepositorioDeParametrosDeAlertasSql implements RepositorioDeParametrosDeAlertas
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function sanciones(): ParametrosDeSanciones
    {
        /** @var array{tarjetas_amarillas: int, tarjetas_rojas: int, faltas_umbral: int, faltas_ventana_dias: int} $valor */
        $valor = $this->vigente('sanciones_deportivas');

        return new ParametrosDeSanciones(
            (int) $valor['tarjetas_amarillas'],
            (int) $valor['tarjetas_rojas'],
            (int) $valor['faltas_umbral'],
            (int) $valor['faltas_ventana_dias'],
        );
    }

    public function nivelQueActivaLaAlertaAcademica(): string
    {
        /** @var array{nivel: string} $valor */
        $valor = $this->vigente('alerta_academica');

        return $valor['nivel'];
    }

    /** @return array<string, mixed> */
    private function vigente(string $clave): array
    {
        $valor = $this->conexion->table('parametros_institucionales')
            ->where('clave', $clave)
            ->whereNull('vigente_hasta')
            ->value('valor');

        if ($valor === null) {
            throw new LogicException("parametro_inexistente:$clave");
        }

        /** @var array<string, mixed> $parametro */
        $parametro = json_decode((string) $valor, true, flags: JSON_THROW_ON_ERROR);

        return $parametro;
    }
}
