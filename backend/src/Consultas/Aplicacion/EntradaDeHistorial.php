<?php

namespace Src\Consultas\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/**
 * Una entrada de un `{tabla}_historial` (HU-26; docs/arquitectura.md §9.1), tal como la ve quien consulta. Los datos
 * anteriores y nuevos van completos: solo se entregan a quien ve la fila completa (D-2 de docs/hitos/f1-h7.md).
 */
final readonly class EntradaDeHistorial
{
    /**
     * @param  array<string, mixed>|null  $datosAnteriores
     * @param  array<string, mixed>|null  $datosNuevos
     */
    public function __construct(
        public string $recurso,
        public Identificador $entidadId,
        public int $version,
        public string $operacion,
        public ?Identificador $autorId,
        public ?string $autorNombre,
        public DateTimeImmutable $ocurridoEn,
        public ?string $motivo,
        public ?array $datosAnteriores,
        public ?array $datosNuevos,
    ) {}
}
