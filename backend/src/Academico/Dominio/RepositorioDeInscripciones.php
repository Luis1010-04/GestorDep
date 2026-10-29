<?php

namespace Src\Academico\Dominio;

use Src\Compartido\Dominio\Identificador;

/** Persistencia de inscripciones. Solo ve las no eliminadas. */
interface RepositorioDeInscripciones
{
    public function existe(Identificador $materiaId, Identificador $jugadorId): bool;

    /**
     * Escribe la inscripción y su registro `crear` de historial en una sola transacción. Si otra petición inscribió al
     * mismo jugador entre la comprobación y la escritura, lanza JugadorYaInscrito (Aplicacion).
     */
    public function guardarNueva(Inscripcion $inscripcion): void;
}
