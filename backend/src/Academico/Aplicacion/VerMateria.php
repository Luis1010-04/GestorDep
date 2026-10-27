<?php

namespace Src\Academico\Aplicacion;

use Src\Academico\Dominio\CriterioDeAprobacion;
use Src\Academico\Dominio\RepositorioDeCriterios;
use Src\Compartido\Dominio\Identificador;

/** Consulta de una materia por su dueño. Ajena o inexistente: nulo (la API responde 404 igual en ambos casos). */
final class VerMateria
{
    public function __construct(
        private readonly ConsultaDeMaterias $consulta,
        private readonly RepositorioDeCriterios $criterios,
    ) {}

    public function ejecutar(Identificador $materia, Identificador $docente): ?MateriaConCriterio
    {
        $detalle = $this->consulta->buscarDelDocente($materia, $docente);

        return $detalle === null
            ? null
            : new MateriaConCriterio($detalle, CriterioDeAprobacion::resolver(null, $this->criterios->generalVigente()));
    }
}
