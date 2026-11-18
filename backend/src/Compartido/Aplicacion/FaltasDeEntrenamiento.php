<?php

namespace Src\Compartido\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/**
 * Faltas de un jugador a los entrenamientos `realizado` de un equipo, para Alertas, que no lee las tablas de Competencia
 * (DA-112). El adaptador vive en Competencia. Decidir qué falta cuenta (las justificadas no) es de la regla de Alertas.
 */
interface FaltasDeEntrenamiento
{
    /**
     * @param  DateTimeImmutable  $desde  primer día de la ventana, en la zona de negocio
     * @param  DateTimeImmutable  $hasta  último día de la ventana, inclusive
     * @return list<FaltaDeEntrenamiento>
     */
    public function de(Identificador $jugador, Identificador $equipo, DateTimeImmutable $desde, DateTimeImmutable $hasta): array;
}
