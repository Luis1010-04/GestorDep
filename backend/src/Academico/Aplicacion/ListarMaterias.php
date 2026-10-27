<?php

namespace Src\Academico\Aplicacion;

use Src\Academico\Dominio\CriterioDeAprobacion;
use Src\Academico\Dominio\RepositorioDeCriterios;
use Src\Compartido\Dominio\Identificador;

/** Listado paginado de las materias del docente, con filtro opcional por periodo. */
final class ListarMaterias
{
    public function __construct(
        private readonly ConsultaDeMaterias $consulta,
        private readonly RepositorioDeCriterios $criterios,
    ) {}

    /** @return array{materias: list<MateriaConCriterio>, total: int} */
    public function ejecutar(Identificador $docente, ?string $periodoId, int $pagina, int $porPagina): array
    {
        $general = $this->criterios->generalVigente();
        $materias = $this->consulta->listarDelDocente($docente, $periodoId, $pagina, $porPagina);
        $propios = $this->criterios->propiosVigentes(array_map(fn (MateriaConDetalle $materia): Identificador => $materia->id, $materias));

        return [
            'materias' => array_map(
                fn (MateriaConDetalle $materia) => new MateriaConCriterio($materia, CriterioDeAprobacion::resolver($propios[$materia->id->valor()] ?? null, $general)),
                $materias,
            ),
            'total' => $this->consulta->contarDelDocente($docente, $periodoId),
        ];
    }
}
