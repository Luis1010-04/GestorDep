<?php

namespace Src\Academico\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/** Persistencia de periodos escolares. Solo ve los no eliminados; el borrado es lógico. */
interface RepositorioDePeriodos
{
    public function existeNombre(string $nombre): bool;

    /** Si otra petición registró el mismo nombre entre la comprobación y la escritura, lanza PeriodoDuplicado (Aplicacion). */
    public function guardarNuevo(PeriodoEscolar $periodo): void;

    /** @return list<PeriodoEscolar> */
    public function listar(int $pagina, int $porPagina, bool $descendente): array;

    public function contar(): int;

    /** @return bool falso si el periodo no existe o ya estaba eliminado */
    public function eliminar(Identificador $id): bool;

    /** DD-45: el de `fecha_inicio` más reciente entre los que contienen el día (extremos incluidos). */
    public function identificadorVigenteEn(DateTimeImmutable $dia): ?Identificador;
}
