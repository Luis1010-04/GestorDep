<?php

namespace Src\Consultas\Aplicacion;

use DateTimeImmutable;

/**
 * La última medición y la última prueba de cada tipo que registró el consultante. Cada una lleva las claves de
 * `MedicionRecurso` y `PruebaFisicaRecurso` (H5), con `actualizado_en` ya en ISO 8601.
 */
final class DatosDeEstadoFisico
{
    /**
     * @param  array<string, string>|null  $ultimaMedicion
     * @param  list<array<string, string>>  $pruebas
     */
    public function __construct(
        public readonly ?array $ultimaMedicion,
        public readonly array $pruebas,
        public readonly ?DateTimeImmutable $actualizadoEn,
    ) {}
}
