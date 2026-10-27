<?php

namespace Src\Academico\Dominio;

use Src\Compartido\Dominio\Identificador;

/** Persistencia de asignaturas. Solo ve las no eliminadas; el borrado es lógico. */
interface RepositorioDeAsignaturas
{
    public function existeClave(string $clave): bool;

    /** Si otra petición registró la misma clave entre la comprobación y la escritura, lanza AsignaturaDuplicada (Aplicacion). */
    public function guardarNueva(Asignatura $asignatura): void;

    /** @return list<Asignatura> */
    public function listar(int $pagina, int $porPagina, bool $descendente): array;

    public function contar(): int;

    /** @return bool falso si la asignatura no existe o ya estaba eliminada */
    public function eliminar(Identificador $id): bool;
}
