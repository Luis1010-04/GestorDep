<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Listado paginado de los alumnos de una materia del docente (HU-06). */
final class ListarInscripciones
{
    public function __construct(private readonly ConsultaDeInscripciones $consulta) {}

    /** @return array{inscripciones: list<InscripcionConJugador>, total: int} */
    public function ejecutar(Identificador $materiaId, int $pagina, int $porPagina): array
    {
        return [
            'inscripciones' => $this->consulta->listarDeLaMateria($materiaId, $pagina, $porPagina),
            'total' => $this->consulta->contarDeLaMateria($materiaId),
        ];
    }
}
