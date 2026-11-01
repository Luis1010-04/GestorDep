<?php

namespace Src\Rendimiento\Dominio;

/**
 * Datos comunes de la captura (HU-14). Se validan en el orden de la petición y se señala el primero fuera de rango
 * (§3.3, DA-11): minutos de 0 a 150, tarjetas amarillas de 0 a 2 y rojas de 0 a 1, calificación entera de 1 a 10
 * (DD-09) y observación de hasta 500 caracteres. Son los mismos límites que los `CHECK` de la tabla.
 */
final class DatosDeJuego
{
    private const MINUTOS_MAXIMOS = 150;

    private const AMARILLAS_MAXIMAS = 2;

    private const ROJAS_MAXIMAS = 1;

    private const CALIFICACION_MINIMA = 1;

    private const CALIFICACION_MAXIMA = 10;

    private const OBSERVACION_MAXIMA = 500;

    /** @throws ValorFueraDeRango */
    public function __construct(
        private readonly int $minutos,
        private readonly bool $fueTitular,
        private readonly int $tarjetasAmarillas,
        private readonly int $tarjetasRojas,
        private readonly int $calificacionGeneral,
        private readonly ?string $observacion,
    ) {
        self::exigir($minutos >= 0 && $minutos <= self::MINUTOS_MAXIMOS, 'minutos');
        self::exigir($tarjetasAmarillas >= 0 && $tarjetasAmarillas <= self::AMARILLAS_MAXIMAS, 'tarjetas_amarillas');
        self::exigir($tarjetasRojas >= 0 && $tarjetasRojas <= self::ROJAS_MAXIMAS, 'tarjetas_rojas');
        self::exigir($calificacionGeneral >= self::CALIFICACION_MINIMA && $calificacionGeneral <= self::CALIFICACION_MAXIMA, 'calificacion_general');
        self::exigir($observacion === null || mb_strlen($observacion) <= self::OBSERVACION_MAXIMA, 'observacion');
    }

    /**
     * Los mismos datos con los cambios de una corrección, por nombre de campo de la petición; se vuelven a validar.
     *
     * @param  array{minutos?: int, fue_titular?: bool, tarjetas_amarillas?: int, tarjetas_rojas?: int, calificacion_general?: int, observacion?: string|null}  $cambios
     *
     * @throws ValorFueraDeRango
     */
    public function con(array $cambios): self
    {
        return new self(
            $cambios['minutos'] ?? $this->minutos,
            $cambios['fue_titular'] ?? $this->fueTitular,
            $cambios['tarjetas_amarillas'] ?? $this->tarjetasAmarillas,
            $cambios['tarjetas_rojas'] ?? $this->tarjetasRojas,
            $cambios['calificacion_general'] ?? $this->calificacionGeneral,
            array_key_exists('observacion', $cambios) ? $cambios['observacion'] : $this->observacion,
        );
    }

    public function minutos(): int
    {
        return $this->minutos;
    }

    public function fueTitular(): bool
    {
        return $this->fueTitular;
    }

    public function tarjetasAmarillas(): int
    {
        return $this->tarjetasAmarillas;
    }

    public function tarjetasRojas(): int
    {
        return $this->tarjetasRojas;
    }

    public function calificacionGeneral(): int
    {
        return $this->calificacionGeneral;
    }

    public function observacion(): ?string
    {
        return $this->observacion;
    }

    /** @throws ValorFueraDeRango */
    private static function exigir(bool $cumple, string $campo): void
    {
        if (! $cumple) {
            throw new ValorFueraDeRango($campo);
        }
    }
}
