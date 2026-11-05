<?php

namespace Src\Salud\Dominio;

use Src\Compartido\Dominio\Identificador;

/** Lee y escribe avisos de lesión; cada escritura lleva su historial en la misma transacción (RNF-02). */
interface RepositorioDeAvisos
{
    public function guardarNuevo(AvisoDeLesion $aviso): void;

    /** Escribe el descarte; `$anterior` es la instantánea que queda en el historial. */
    public function guardarDescarte(AvisoDeLesion $anterior, AvisoDeLesion $descartado): void;

    /** Escribe la confirmación; `$lesionId` es nulo si se confirmó `apto`. */
    public function guardarConfirmacion(AvisoDeLesion $anterior, AvisoDeLesion $confirmado, ?Identificador $lesionId): void;

    public function buscar(Identificador $id): ?AvisoDeLesion;

    /** Bloquea la fila hasta el fin de la transacción: de dos descartes simultáneos, solo uno lo encuentra pendiente. */
    public function buscarParaActualizar(Identificador $id): ?AvisoDeLesion;

    /**
     * Por `fecha_aviso` y por `id`, descendentes (DD-56).
     *
     * @param  Identificador|null  $creadoPor  solo los avisos de ese usuario; nulo, todos
     * @return array{elementos: list<AvisoDeLesion>, total: int}
     */
    public function delJugador(Identificador $jugadorId, ?Identificador $creadoPor, int $pagina, int $porPagina): array;
}
