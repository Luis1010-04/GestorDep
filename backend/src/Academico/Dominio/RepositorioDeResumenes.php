<?php

namespace Src\Academico\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/** Persistencia de `resumenes_academicos`, tabla derivada sin historial (arquitectura §7.1). */
interface RepositorioDeResumenes
{
    /** Idempotente: una fila por jugador y periodo; guardar de nuevo la reemplaza. */
    public function guardar(Identificador $jugadorId, Identificador $periodoId, ResumenAcademico $resumen, DateTimeImmutable $calculadoEn): void;
}
