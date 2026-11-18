<?php

namespace Src\Consultas\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use LogicException;
use Src\Consultas\Aplicacion\ParametroDeDesactualizacion;

/** Lee el parámetro `desactualizacion` vigente de `parametros_institucionales` (DA-100, RNF-09), de solo lectura (§4.3). */
final class ParametroDeDesactualizacionSql implements ParametroDeDesactualizacion
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function dias(): int
    {
        $valor = $this->conexion->table('parametros_institucionales')
            ->where('clave', 'desactualizacion')
            ->whereNull('vigente_hasta')
            ->value('valor');

        if ($valor === null) {
            throw new LogicException('parametro_desactualizacion_inexistente');
        }

        /** @var array{dias: int|numeric-string} $parametro */
        $parametro = json_decode((string) $valor, true, flags: JSON_THROW_ON_ERROR);

        return (int) $parametro['dias'];
    }
}
