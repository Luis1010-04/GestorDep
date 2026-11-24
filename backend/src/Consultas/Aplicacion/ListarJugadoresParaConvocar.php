<?php

namespace Src\Consultas\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * `GET /equipos/{equipo}/jugadores/para-convocar` (HU-24, RN-18, DD-12, DA-95, DA-118): la vista de cada jugador del equipo,
 * ordenada por desempeño con el promedio de la última evaluación del consultante (DA-106). El orden se calcula sobre todos
 * los jugadores y después se pagina; si se paginara antes, cada página quedaría ordenada por separado.
 */
final class ListarJugadoresParaConvocar
{
    public function __construct(
        private readonly ConsultaDeVistaParaConvocar $consulta,
        private readonly ConstructorDeLaVista $constructor,
        private readonly CalculadorDeDesempeno $calculador,
    ) {}

    /** Si el usuario entrena el equipo: el 404 va antes que el 422 (A6-10, DD-50). */
    public function puedeVer(Identificador $equipo): bool
    {
        return $this->consulta->jugadoresDelEquipo($equipo) !== null;
    }

    /** @return array{elementos: list<VistaParaConvocar>, total: int}|null nulo si el usuario no entrena el equipo (404) */
    public function ejecutar(Identificador $equipo, int $pagina, int $porPagina): ?array
    {
        $jugadores = $this->consulta->jugadoresDelEquipo($equipo);
        if ($jugadores === null) {
            return null;
        }

        $candidatos = array_map(fn (JugadorConvocable $jugador): array => [$jugador, $this->consulta->evaluacionReciente($jugador->id, $equipo)], $jugadores);
        $ordenados = $this->calculador->ordenar($candidatos, fn (array $candidato) => $candidato[1]?->promedio);
        $marca = $this->constructor->marca();

        return [
            'elementos' => array_map(
                fn (array $candidato): VistaParaConvocar => $this->constructor->armar($candidato[0], $equipo, $candidato[1], $marca),
                array_slice($ordenados, ($pagina - 1) * $porPagina, $porPagina),
            ),
            'total' => count($ordenados),
        ];
    }
}
