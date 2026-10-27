<?php

namespace Src\Academico\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use LogicException;
use Src\Academico\Dominio\AmbitoDeCriterio;
use Src\Academico\Dominio\CriterioDeAprobacion;
use Src\Academico\Dominio\RepositorioDeCriterios;
use Src\Compartido\Dominio\NumeroDecimal;

final class RepositorioDeCriteriosEloquent implements RepositorioDeCriterios
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function generalVigente(): CriterioDeAprobacion
    {
        $minimo = $this->conexion->table('criterios_aprobacion')
            ->where('ambito', 'general')
            ->whereNull('vigente_hasta')
            ->whereNull('eliminado_en')
            ->value('calificacion_minima');

        if ($minimo === null) {
            throw new LogicException('criterio_general_inexistente');
        }

        return new CriterioDeAprobacion(AmbitoDeCriterio::General, NumeroDecimal::desdeTexto((string) $minimo));
    }
}
