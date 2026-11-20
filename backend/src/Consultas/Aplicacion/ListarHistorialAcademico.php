<?php

namespace Src\Consultas\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Historial de calificaciones o de asistencia a clase de un jugador (HU-26). Quien llama comprueba antes `puedeVer`: un
 * docente sin ninguna materia con el jugador recibe 404, igual que la lectura de una inscripción ajena (DD-50, DA-124).
 */
final class ListarHistorialAcademico
{
    public function __construct(private readonly ConsultaDeHistorialAcademico $consulta) {}

    public function puedeVer(Identificador $jugador): bool
    {
        return $this->consulta->jugadorConMateriaDelDocente($jugador);
    }

    /** @return array{entradas: list<EntradaDeHistorial>, total: int} */
    public function calificaciones(Identificador $jugador, int $pagina, int $porPagina): array
    {
        return $this->consulta->calificaciones($jugador, $pagina, $porPagina);
    }

    /** @return array{entradas: list<EntradaDeHistorial>, total: int} */
    public function asistenciasClase(Identificador $jugador, int $pagina, int $porPagina): array
    {
        return $this->consulta->asistenciasClase($jugador, $pagina, $porPagina);
    }
}
