<?php

namespace Src\Salud\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/**
 * Una entrada de un historial de Salud (HU-26; docs/hitos/f1-h7.md §6.2). Sin autor: en los historiales de H5 no se
 * entrega (DA-18, DA-125). Los datos anteriores y nuevos llegan sin los campos que DA-18 no deja revelar.
 */
final readonly class EntradaDeHistorialDeSalud
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
        public DateTimeImmutable $ocurridoEn,
        public ?string $motivo,
        public ?array $datosAnteriores,
        public ?array $datosNuevos,
    ) {}

    /**
     * @param  array<string, mixed>|null  $datosAnteriores
     * @param  array<string, mixed>|null  $datosNuevos
     */
    public function conDatos(?array $datosAnteriores, ?array $datosNuevos): self
    {
        return new self($this->recurso, $this->entidadId, $this->version, $this->operacion, $this->ocurridoEn, $this->motivo, $datosAnteriores, $datosNuevos);
    }
}
