<?php

namespace Src\Competencia\Dominio;

use Src\Compartido\Dominio\Identificador;

/** Persistencia de convocatorias. Solo ve las no eliminadas; el borrado es lógico y cada cambio escribe su historial. */
interface RepositorioDeConvocatorias
{
    public function estaConvocado(Identificador $partidoId, Identificador $jugadorId): bool;

    /** Si otra petición convocó al mismo jugador entre la comprobación y la escritura, lanza JugadorYaConvocado (Aplicacion). */
    public function guardarNueva(Convocatoria $convocatoria): void;

    public function buscar(Identificador $id): ?Convocatoria;

    /** Borrado lógico con su historial; la fila conserva su posición y su rol, pero deja de contar. */
    public function eliminar(Convocatoria $convocatoria): void;
}
