<?php

namespace Src\Consultas\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * `GET /partidos/{partido}/convocatoria/vista` (HU-12 c2, DA-96): la vista de DA-94 de cada convocado, en orden de
 * convocatoria y paginada como la ruta de H3. El rendimiento y el progreso de cada convocado son los del equipo del
 * partido. Un partido inexistente, eliminado o de un equipo que el usuario no entrena da nulo (404, RNF-01).
 */
final class ConsultarVistaDeLaConvocatoria
{
    public function __construct(
        private readonly ConsultaDeVistaParaConvocar $consulta,
        private readonly ConstructorDeLaVista $constructor,
    ) {}

    /** Si el partido existe y es de un equipo que el usuario entrena: el 404 va antes que el 422 (A6-10, DD-50). */
    public function puedeVer(Identificador $partido): bool
    {
        return $this->consulta->convocatoriaDelPartido($partido, 1, 1) !== null;
    }

    /** @return array{elementos: list<VistaParaConvocar>, total: int}|null */
    public function ejecutar(Identificador $partido, int $pagina, int $porPagina): ?array
    {
        $convocatoria = $this->consulta->convocatoriaDelPartido($partido, $pagina, $porPagina);
        if ($convocatoria === null) {
            return null;
        }

        $marca = $this->constructor->marca();

        return [
            'elementos' => array_map(
                fn (JugadorConvocable $jugador): VistaParaConvocar => $this->constructor->armar(
                    $jugador,
                    $convocatoria->equipo,
                    $this->consulta->evaluacionReciente($jugador->id, $convocatoria->equipo),
                    $marca,
                ),
                $convocatoria->jugadores,
            ),
            'total' => $convocatoria->total,
        ];
    }
}
