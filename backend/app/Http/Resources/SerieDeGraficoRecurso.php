<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Src\Consultas\Aplicacion\PuntoDeSerie;
use Src\Consultas\Aplicacion\SerieDeGrafico;

/**
 * Una serie de gráfico (HU-25, DA-105): `{serie: [{fecha | periodo, valor}], actualizado_en, posiblemente_desactualizado}`.
 * Sin datos, la serie va vacía y `actualizado_en` nulo.
 */
final class SerieDeGraficoRecurso
{
    /** @return array{serie: list<array<string, mixed>>, actualizado_en: string|null, posiblemente_desactualizado: bool} */
    public static function de(SerieDeGrafico $serie): array
    {
        return [
            'serie' => array_map(fn (PuntoDeSerie $punto): array => [$punto->campo => $punto->referencia, 'valor' => $punto->valor], $serie->puntos),
            'actualizado_en' => $serie->actualizadoEn?->format(DateTimeInterface::ATOM),
            'posiblemente_desactualizado' => $serie->posiblementeDesactualizado,
        ];
    }
}
