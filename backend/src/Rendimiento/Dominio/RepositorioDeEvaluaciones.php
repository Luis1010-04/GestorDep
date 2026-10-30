<?php

namespace Src\Rendimiento\Dominio;

use Src\Compartido\Dominio\Identificador;

interface RepositorioDeEvaluaciones
{
    public function insertar(EvaluacionDeRendimiento $evaluacion): void;

    /** La de `calculado_en` más reciente; el `id` (UUID v7) desempata dos cálculos del mismo segundo. */
    public function ultimaDe(Identificador $jugador, Identificador $equipo, Identificador $entrenador): ?EvaluacionDeRendimiento;
}
