<?php

namespace Src\Rendimiento\Dominio;

use Src\Compartido\Dominio\Identificador;

/** Persistencia de `rendimientos_partido` y `valores_metrica`, con su historial en la misma transacción (RNF-02). */
interface RepositorioDeRendimientos
{
    public function existeDe(Identificador $partidoId, Identificador $jugadorId): bool;

    /** Lanza la excepción de rendimiento duplicado si otra captura ganó la carrera (UQ parcial). */
    public function guardarNuevo(RendimientoDePartido $rendimiento): void;

    public function buscar(Identificador $id): ?RendimientoDePartido;

    public function actualizar(RendimientoDePartido $anterior, RendimientoDePartido $nuevo): void;
}
