<?php

namespace Src\Academico\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use LogicException;
use Src\Academico\Dominio\ParametrosDeRiesgo;
use Src\Academico\Dominio\RepositorioDeParametros;
use Src\Compartido\Dominio\NumeroDecimal;

/** Lee el parámetro `riesgo_academico` vigente (docs/hitos/f1-h2.md §5.3). */
final class RepositorioDeParametrosEloquent implements RepositorioDeParametros
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function riesgoAcademico(): ParametrosDeRiesgo
    {
        $valor = $this->conexion->table('parametros_institucionales')
            ->where('clave', 'riesgo_academico')
            ->whereNull('vigente_hasta')
            ->value('valor');

        if ($valor === null) {
            throw new LogicException('parametro_riesgo_academico_inexistente');
        }

        /** @var array{promedio_sin_riesgo_minimo: numeric-string, promedio_observacion_minimo: numeric-string, reprobadas_observacion: int, reprobadas_riesgo: int} $parametros */
        $parametros = json_decode((string) $valor, true, flags: JSON_THROW_ON_ERROR);

        return new ParametrosDeRiesgo(
            NumeroDecimal::desdeTexto((string) $parametros['promedio_sin_riesgo_minimo']),
            NumeroDecimal::desdeTexto((string) $parametros['promedio_observacion_minimo']),
            (int) $parametros['reprobadas_observacion'],
            (int) $parametros['reprobadas_riesgo'],
        );
    }
}
