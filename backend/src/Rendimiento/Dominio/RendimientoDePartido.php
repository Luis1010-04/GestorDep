<?php

namespace Src\Rendimiento\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/**
 * Rendimiento de un jugador en un partido jugado (HU-14). Inmutable. `posicionId` es la posición con la que jugó, para
 * que las estadísticas pasadas no cambien; `registradoPor` decide quién lo ve y lo corrige (RN-14).
 */
final class RendimientoDePartido
{
    /** @param  list<ValorDeMetrica>  $valores */
    public function __construct(
        private readonly Identificador $id,
        private readonly Identificador $partidoId,
        private readonly Identificador $jugadorId,
        private readonly Identificador $equipoId,
        private readonly Identificador $registradoPor,
        private readonly Identificador $posicionId,
        private readonly DatosDeJuego $datos,
        private readonly array $valores,
        private readonly DateTimeImmutable $actualizadoEn,
    ) {}

    /** @param  list<ValorDeMetrica>|null  $valores  nulo conserva los actuales; una lista los reemplaza */
    public function corregido(DatosDeJuego $datos, ?array $valores, DateTimeImmutable $instante): self
    {
        return new self(
            $this->id, $this->partidoId, $this->jugadorId, $this->equipoId, $this->registradoPor, $this->posicionId,
            $datos, $valores ?? $this->valores, $instante,
        );
    }

    public function id(): Identificador
    {
        return $this->id;
    }

    public function partidoId(): Identificador
    {
        return $this->partidoId;
    }

    public function jugadorId(): Identificador
    {
        return $this->jugadorId;
    }

    public function equipoId(): Identificador
    {
        return $this->equipoId;
    }

    public function registradoPor(): Identificador
    {
        return $this->registradoPor;
    }

    public function posicionId(): Identificador
    {
        return $this->posicionId;
    }

    public function datos(): DatosDeJuego
    {
        return $this->datos;
    }

    /** @return list<ValorDeMetrica> */
    public function valores(): array
    {
        return $this->valores;
    }

    public function actualizadoEn(): DateTimeImmutable
    {
        return $this->actualizadoEn;
    }
}
