<?php

namespace App\Http\Controllers;

use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\EntradaDeHistorialDeSaludRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Salud\Aplicacion\ComprobarJugadorDelEntrenador;
use Src\Salud\Aplicacion\ComprobarJugadorVisible;
use Src\Salud\Aplicacion\EntradaDeHistorialDeSalud;
use Src\Salud\Aplicacion\ListarHistorialDeSalud;

/**
 * Historial de avisos, lesiones y estados de un jugador (HU-26; docs/hitos/f1-h7.md §6). Cada ruta responde el 404 como su
 * ruta de lectura, antes de validar los parámetros: avisos y estados con `ComprobarJugadorVisible` (bajas incluidas);
 * lesiones con `ComprobarJugadorDelEntrenador`.
 */
class HistorialDeSaludController extends Controller
{
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function avisos(Request $solicitud, string $jugador, ComprobarJugadorVisible $comprobar, ListarHistorialDeSalud $listar): ColeccionPaginada
    {
        $jugadorId = IdentificadorDeRuta::desde($jugador);
        $comprobar->ejecutar($jugadorId);

        return $this->pagina($solicitud, fn (int $pagina, int $porPagina) => $listar->avisos($jugadorId, $pagina, $porPagina));
    }

    public function lesiones(Request $solicitud, string $jugador, ComprobarJugadorDelEntrenador $comprobar, ListarHistorialDeSalud $listar): ColeccionPaginada
    {
        $jugadorId = IdentificadorDeRuta::desde($jugador);
        $comprobar->ejecutar($jugadorId);

        return $this->pagina($solicitud, fn (int $pagina, int $porPagina) => $listar->lesiones($jugadorId, $pagina, $porPagina));
    }

    public function estados(Request $solicitud, string $jugador, ComprobarJugadorVisible $comprobar, ListarHistorialDeSalud $listar): ColeccionPaginada
    {
        $jugadorId = IdentificadorDeRuta::desde($jugador);
        $comprobar->ejecutar($jugadorId);

        return $this->pagina($solicitud, fn (int $pagina, int $porPagina) => $listar->estados($jugadorId, $pagina, $porPagina));
    }

    /**
     * @param  Closure(int, int): array{entradas: list<EntradaDeHistorialDeSalud>, total: int}  $entradas
     */
    private function pagina(Request $solicitud, Closure $entradas): ColeccionPaginada
    {
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);

        $listado = $entradas($pagina, $porPagina);

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn (EntradaDeHistorialDeSalud $entrada) => new EntradaDeHistorialDeSaludRecurso($entrada), $listado['entradas']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }
}
