<?php

namespace Src\Rendimiento\Dominio;

use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;

/** Métrica del catálogo; su rango y su tipo vienen de la tabla `metricas`, no del código (RNF-09, DA-07). */
final class Metrica
{
    public function __construct(
        private readonly Identificador $id,
        private readonly TipoDeDato $tipo,
        private readonly NumeroDecimal $minimo,
        private readonly NumeroDecimal $maximo,
    ) {}

    /** Dentro de `minimo` y `maximo`, ambos incluidos; si la métrica es entera, sin parte decimal. */
    public function admite(NumeroDecimal $valor): bool
    {
        if ($this->tipo === TipoDeDato::Entero && ! $valor->igualA($valor->redondear(0))) {
            return false;
        }

        return $valor->mayorOIgualQue($this->minimo) && $valor->menorOIgualQue($this->maximo);
    }

    public function id(): Identificador
    {
        return $this->id;
    }

    public function tipo(): TipoDeDato
    {
        return $this->tipo;
    }
}
