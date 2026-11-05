<?php

namespace Src\Salud\Dominio;

use Src\Compartido\Dominio\Identificador;

/** Lee y escribe `lesiones`; cada escritura lleva su historial en la misma transacción (RNF-02). */
interface RepositorioDeLesiones
{
    public function guardarNueva(Lesion $lesion): void;

    /** `$anterior` es la instantánea que queda en el historial. */
    public function guardarCambio(Lesion $anterior, Lesion $nueva): void;

    public function buscar(Identificador $id): ?Lesion;

    /** Bloquea la fila hasta el fin de la transacción. */
    public function buscarParaActualizar(Identificador $id): ?Lesion;

    /**
     * Las lesiones de un jugador que registró un usuario, por `fecha_inicio` y `id` descendentes (RN-15).
     *
     * @return array{elementos: list<Lesion>, total: int}
     */
    public function delRegistrante(Identificador $jugadorId, Identificador $registradaPor, int $pagina, int $porPagina): array;
}
