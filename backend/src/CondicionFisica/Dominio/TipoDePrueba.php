<?php

namespace Src\CondicionFisica\Dominio;

use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;

/** Un tipo de prueba del catálogo sembrado, con su unidad, su sentido de mejora y su rango (DD-62, DA-16). */
final class TipoDePrueba
{
    public function __construct(
        private readonly Identificador $id,
        private readonly string $codigo,
        private readonly string $nombre,
        private readonly string $unidad,
        private readonly string $mejorResultado,
        private readonly RangoDeMedida $rango,
    ) {}

    /** @throws ValorFueraDeRango */
    public function exigirResultado(NumeroDecimal $resultado): void
    {
        $this->rango->exigir($resultado, 'resultado');
    }

    public function id(): Identificador
    {
        return $this->id;
    }

    public function codigo(): string
    {
        return $this->codigo;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function unidad(): string
    {
        return $this->unidad;
    }

    public function mejorResultado(): string
    {
        return $this->mejorResultado;
    }
}
