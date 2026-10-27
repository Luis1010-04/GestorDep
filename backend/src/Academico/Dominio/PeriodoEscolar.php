<?php

namespace Src\Academico\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Periodo escolar (HU-28): un catálogo compartido por todos los docentes. Las fechas son días, sin hora. */
final class PeriodoEscolar
{
    /** @throws ReglaDeNegocioViolada `rango_de_fechas_invalido` si `fecha_fin` no es posterior a `fecha_inicio` */
    public function __construct(
        private readonly Identificador $id,
        private readonly string $nombre,
        private readonly DateTimeImmutable $fechaInicio,
        private readonly DateTimeImmutable $fechaFin,
        private readonly DateTimeImmutable $actualizadoEn,
    ) {
        if ($fechaFin->format('Y-m-d') <= $fechaInicio->format('Y-m-d')) {
            throw new ReglaDeNegocioViolada('rango_de_fechas_invalido');
        }
    }

    public function id(): Identificador
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function fechaInicio(): DateTimeImmutable
    {
        return $this->fechaInicio;
    }

    public function fechaFin(): DateTimeImmutable
    {
        return $this->fechaFin;
    }

    public function actualizadoEn(): DateTimeImmutable
    {
        return $this->actualizadoEn;
    }
}
