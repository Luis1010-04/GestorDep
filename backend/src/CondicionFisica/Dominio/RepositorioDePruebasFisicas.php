<?php

namespace Src\CondicionFisica\Dominio;

use Src\Compartido\Dominio\Identificador;

interface RepositorioDePruebasFisicas
{
    public function guardarNueva(PruebaFisica $prueba): void;

    public function guardarCorreccion(PruebaFisica $anterior, PruebaFisica $corregida, string $motivo): void;

    /** La del jugador, de ese tipo y en el periodo, que registró ese entrenador, con la fila bloqueada (DA-04, DD-59). */
    public function delPeriodoParaActualizar(Identificador $jugadorId, Identificador $tipoPruebaId, Identificador $registradoPor, string $periodo): ?PruebaFisica;

    /**
     * Las que registró ese entrenador, opcionalmente de un tipo, por periodo ascendente y, dentro del periodo, por código
     * del tipo (DD-59, HU-18 c2).
     *
     * @return array{elementos: list<PruebaFisica>, total: int}
     */
    public function delRegistrante(Identificador $jugadorId, Identificador $registradoPor, ?Identificador $tipoPruebaId, int $pagina, int $porPagina): array;

    /**
     * Las que registró ese entrenador en un periodo, una por tipo como mucho (DA-04).
     *
     * @return list<PruebaFisica>
     */
    public function delPeriodo(Identificador $jugadorId, Identificador $registradoPor, string $periodo): array;
}
