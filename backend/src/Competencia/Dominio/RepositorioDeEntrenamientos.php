<?php

namespace Src\Competencia\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/** Persistencia de entrenamientos. Nunca se borran: se cancelan. Cada alta y cada cambio escriben su historial. */
interface RepositorioDeEntrenamientos
{
    public function guardarNuevo(Entrenamiento $entrenamiento): void;

    /** Escribe `$nuevo` y el historial con la instantánea completa de antes y de después. */
    public function actualizar(Entrenamiento $anterior, Entrenamiento $nuevo): void;

    public function buscar(Identificador $id): ?Entrenamiento;

    /**
     * Entrenamientos del equipo con `inicia_en` en [`$desde`, `$hasta`), ordenados por `inicia_en`.
     *
     * @return list<Entrenamiento>
     */
    public function listarDelEquipo(Identificador $equipoId, ?DateTimeImmutable $desde, ?DateTimeImmutable $hasta, int $pagina, int $porPagina, bool $descendente): array;

    public function contarDelEquipo(Identificador $equipoId, ?DateTimeImmutable $desde, ?DateTimeImmutable $hasta): int;

    /** Otro entrenamiento no cancelado, de cualquier equipo, en el mismo lugar y con un horario que se cruza (DA-09). */
    public function hayEmpalme(Entrenamiento $entrenamiento): bool;
}
