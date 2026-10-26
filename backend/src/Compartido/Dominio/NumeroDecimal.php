<?php

namespace Src\Compartido\Dominio;

use InvalidArgumentException;
use RoundingMode;

/**
 * Número decimal exacto e inmutable, basado en `bcmath` (arquitectura §7.1, DD-54). Es el único tipo para promedios,
 * umbrales y porcentajes: nunca `float`. Guarda toda la precisión de entrada; el redondeo es explícito y siempre
 * "al medio hacia arriba" (HALF_UP: 7.495 se vuelve 7.50, y -7.495 se vuelve -7.50).
 */
final class NumeroDecimal
{
    /** @param  numeric-string  $valor */
    private function __construct(private readonly string $valor) {}

    /** @throws NumeroDecimalInvalido si el texto no es un decimal (signo opcional, dígitos, punto y dígitos) */
    public static function desdeTexto(string $texto): self
    {
        if (preg_match('/^-?\d+(\.\d+)?$/D', $texto) !== 1) {
            throw new NumeroDecimalInvalido;
        }

        assert(is_numeric($texto));

        $decimales = str_contains($texto, '.') ? strlen($texto) - strpos($texto, '.') - 1 : 0;

        return new self(bcadd($texto, '0', $decimales));
    }

    /**
     * Media aritmética redondeada a `$escala` decimales (HALF_UP).
     *
     * @param  list<self>  $valores
     */
    public static function media(array $valores, int $escala = 2): self
    {
        if ($valores === []) {
            throw new ReglaDeNegocioViolada('media_sin_valores');
        }

        $suma = self::desdeTexto('0');
        foreach ($valores as $valor) {
            $suma = $suma->sumar($valor);
        }

        return $suma->dividir(self::desdeTexto((string) count($valores)), $escala);
    }

    public function sumar(self $otro): self
    {
        return new self(bcadd($this->valor, $otro->valor, max($this->decimales(), $otro->decimales())));
    }

    public function restar(self $otro): self
    {
        return new self(bcsub($this->valor, $otro->valor, max($this->decimales(), $otro->decimales())));
    }

    public function multiplicar(self $otro): self
    {
        return new self(bcmul($this->valor, $otro->valor, $this->decimales() + $otro->decimales()));
    }

    /** Cociente redondeado a `$escala` decimales (HALF_UP). */
    public function dividir(self $divisor, int $escala): self
    {
        self::exigirEscala($escala);

        if ($divisor->comparar(self::desdeTexto('0')) === 0) {
            throw new ReglaDeNegocioViolada('division_entre_cero');
        }

        // Un decimal más basta: el redondeo al medio decide con ese dígito y, si el cociente termina ahí, no pierde nada.
        return (new self(bcdiv($this->valor, $divisor->valor, $escala + 1)))->redondear($escala);
    }

    public function redondear(int $escala = 2): self
    {
        self::exigirEscala($escala);

        return new self(bcround($this->valor, $escala, RoundingMode::HalfAwayFromZero));
    }

    /** @return int -1, 0 o 1 */
    public function comparar(self $otro): int
    {
        return bccomp($this->valor, $otro->valor, max($this->decimales(), $otro->decimales()));
    }

    public function igualA(self $otro): bool
    {
        return $this->comparar($otro) === 0;
    }

    public function menorQue(self $otro): bool
    {
        return $this->comparar($otro) < 0;
    }

    public function menorOIgualQue(self $otro): bool
    {
        return $this->comparar($otro) <= 0;
    }

    public function mayorQue(self $otro): bool
    {
        return $this->comparar($otro) > 0;
    }

    public function mayorOIgualQue(self $otro): bool
    {
        return $this->comparar($otro) >= 0;
    }

    /** Cantidad de decimales con que se construyó el valor (la validación de la API acepta como máximo dos). */
    public function decimales(): int
    {
        $punto = strpos($this->valor, '.');

        return $punto === false ? 0 : strlen($this->valor) - $punto - 1;
    }

    /** El valor redondeado (HALF_UP) y escrito con exactamente `$decimales` decimales: "7.50". */
    public function comoCadena(int $decimales = 2): string
    {
        return $this->redondear($decimales)->valor;
    }

    private static function exigirEscala(int $escala): void
    {
        if ($escala < 0) {
            throw new InvalidArgumentException('escala_decimal_invalida');
        }
    }
}
