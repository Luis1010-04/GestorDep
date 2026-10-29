<?php

namespace Src\Compartido\Dominio\Eventos;

/** Cambió el criterio de aprobación propio de una materia (HU-08, DD-47): afecta a todos sus inscritos. Solo identificadores. */
final class CriterioDeMateriaModificado
{
    public function __construct(public readonly string $materiaId) {}
}
