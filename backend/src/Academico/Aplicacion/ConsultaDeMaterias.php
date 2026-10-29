<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Lecturas de las materias de un docente. Una materia ajena o inexistente es indistinguible: no se devuelve (DD-50). */
interface ConsultaDeMaterias
{
    public function buscarDelDocente(Identificador $materia, Identificador $docente): ?MateriaConDetalle;

    /** @return list<MateriaConDetalle> ordenadas por clave de asignatura */
    public function listarDelDocente(Identificador $docente, ?string $periodoId, int $pagina, int $porPagina): array;

    public function contarDelDocente(Identificador $docente, ?string $periodoId): int;
}
