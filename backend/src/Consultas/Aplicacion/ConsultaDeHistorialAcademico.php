<?php

namespace Src\Consultas\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Historial de calificaciones y de asistencia a clase de un jugador (HU-26; docs/hitos/f1-h7.md §5.3). Lo ve solo el
 * docente, y solo de sus propias materias (DD-48); un docente sin ninguna materia con el jugador no lo ve (DA-124).
 */
interface ConsultaDeHistorialAcademico
{
    /** Si el usuario actual tiene al jugador inscrito en alguna de sus materias; en baja también (DD-52). */
    public function jugadorConMateriaDelDocente(Identificador $jugador): bool;

    /** @return array{entradas: list<EntradaDeHistorial>, total: int} de la más reciente a la más antigua */
    public function calificaciones(Identificador $jugador, int $pagina, int $porPagina): array;

    /** @return array{entradas: list<EntradaDeHistorial>, total: int} de la más reciente a la más antigua */
    public function asistenciasClase(Identificador $jugador, int $pagina, int $porPagina): array;
}
