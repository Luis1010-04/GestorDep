<?php

namespace Src\Salud\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;
use Src\Salud\Dominio\AvisoDeLesion;

/** Lo que un usuario ve de un aviso: las notas que no puede ver van nulas y no se revela quién lo creó, confirmó o descartó (DA-18). */
final class AvisoVisible
{
    private function __construct(
        public readonly string $id,
        public readonly string $estado,
        public readonly string $fechaAviso,
        public readonly bool $reportadoPorJugador,
        public readonly ?string $nota,
        public readonly ?string $notaDescarte,
        public readonly bool $esPropio,
        public readonly DateTimeImmutable $creadoEn,
        public readonly DateTimeImmutable $actualizadoEn,
    ) {}

    public static function para(AvisoDeLesion $aviso, Identificador $lector): self
    {
        return new self(
            $aviso->id()->valor(),
            $aviso->estado()->value,
            $aviso->fechaAviso(),
            $aviso->reportadoPorJugador(),
            $aviso->notaPara($lector),
            $aviso->notaDescartePara($lector),
            $aviso->esPropioDe($lector),
            $aviso->creadoEn(),
            $aviso->actualizadoEn(),
        );
    }
}
