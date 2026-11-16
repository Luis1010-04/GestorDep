<?php

namespace Src\Consultas\Aplicacion;

use DateTimeImmutable;

/** Una serie de gráfico lista para responder: sus puntos, su última actualización y si está posiblemente desactualizada (HU-25, RNF-04). */
final class SerieDeGrafico
{
    /** @param  list<PuntoDeSerie>  $puntos */
    private function __construct(
        public readonly array $puntos,
        public readonly ?DateTimeImmutable $actualizadoEn,
        public readonly bool $posiblementeDesactualizado,
    ) {}

    public static function desde(DatosDeSerie $datos, MarcaDeDesactualizacion $marca): self
    {
        return new self($datos->puntos, $datos->actualizadoEn, $marca->de($datos->actualizadoEn));
    }
}
