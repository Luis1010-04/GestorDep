<?php

namespace Src\Consultas\Aplicacion;

/**
 * Un punto de una serie de gráfico (DA-105): `{fecha, valor}` en las series por fecha y `{periodo, valor}` en las series por
 * periodo, donde el periodo es un mes `AAAA-MM` o, en las académicas, el periodo escolar `{id, nombre}`. El valor es una
 * cadena con los decimales que corresponden al dato, o un entero donde el dato lo es (`docs/convenciones-api.md` §1).
 */
final class PuntoDeSerie
{
    /** @param  string|array{id: string, nombre: string}  $referencia */
    private function __construct(
        public readonly string $campo,
        public readonly string|array $referencia,
        public readonly string|int $valor,
    ) {}

    public static function enFecha(string $fecha, string|int $valor): self
    {
        return new self('fecha', $fecha, $valor);
    }

    /** @param  string|array{id: string, nombre: string}  $periodo */
    public static function enPeriodo(string|array $periodo, string|int $valor): self
    {
        return new self('periodo', $periodo, $valor);
    }
}
