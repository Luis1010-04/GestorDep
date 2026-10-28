<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * La situación académica de un jugador en el periodo vigente (HU-09). `periodoId` es nulo si no hay periodo vigente
 * (DD-45); `resumen`, si aún no se calculó. `materias` es nula para quien no es docente del jugador: el entrenador recibe
 * solo el resumen (RN-13, DD-48).
 */
final class SituacionAcademica
{
    /** @param  list<MateriaDeLaSituacion>|null  $materias */
    public function __construct(
        public readonly ?Identificador $periodoId,
        public readonly ?string $periodoNombre,
        public readonly ?ResumenLeido $resumen,
        public readonly ?array $materias,
    ) {}

    /** DD-43: sin resumen, sin promedio o con alguna materia sin calificar. */
    public function datoIncompleto(): bool
    {
        return $this->resumen === null || $this->resumen->promedio === null || $this->resumen->materiasSinCalificar > 0;
    }
}
