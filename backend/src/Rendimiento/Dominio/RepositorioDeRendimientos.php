<?php

namespace Src\Rendimiento\Dominio;

use Src\Compartido\Dominio\Identificador;

/** Persistencia de `rendimientos_partido` y `valores_metrica`, con su historial en la misma transacción (RNF-02). */
interface RepositorioDeRendimientos
{
    /** Si ese entrenador ya capturó a ese jugador en ese partido; lo de otro entrenador no cuenta (v1.0.8, RN-14). */
    public function existeDe(Identificador $partidoId, Identificador $jugadorId, Identificador $registradoPor): bool;

    /** Lanza la excepción de rendimiento duplicado si otra captura del mismo entrenador ganó la carrera (UQ parcial). */
    public function guardarNuevo(RendimientoDePartido $rendimiento): void;

    public function buscar(Identificador $id): ?RendimientoDePartido;

    public function actualizar(RendimientoDePartido $anterior, RendimientoDePartido $nuevo): void;
}
