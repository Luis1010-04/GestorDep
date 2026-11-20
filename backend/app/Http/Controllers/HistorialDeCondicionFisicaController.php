<?php

namespace App\Http\Controllers;

use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\EntradaDeHistorialDeCondicionFisicaRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\CondicionFisica\Aplicacion\ComprobarJugadorDelEntrenador;
use Src\CondicionFisica\Aplicacion\EntradaDeHistorialDeCondicionFisica;
use Src\CondicionFisica\Aplicacion\ListarHistorialDeCondicionFisica;

/**
 * Historial de mediciones y pruebas físicas de un jugador (HU-26; docs/hitos/f1-h7.md §6). Cada ruta responde el 404 como
 * su ruta de lectura, con `ComprobarJugadorDelEntrenador` y antes de validar los parámetros.
 */
class HistorialDeCondicionFisicaController extends Controller
{
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function mediciones(Request $solicitud, string $jugador, ComprobarJugadorDelEntrenador $comprobar, ListarHistorialDeCondicionFisica $listar): ColeccionPaginada
    {
        $jugadorId = IdentificadorDeRuta::desde($jugador);
        $comprobar->ejecutar($jugadorId);

        return $this->pagina($solicitud, fn (int $pagina, int $porPagina) => $listar->mediciones($jugadorId, $pagina, $porPagina));
    }

    public function pruebas(Request $solicitud, string $jugador, ComprobarJugadorDelEntrenador $comprobar, ListarHistorialDeCondicionFisica $listar): ColeccionPaginada
    {
        $jugadorId = IdentificadorDeRuta::desde($jugador);
        $comprobar->ejecutar($jugadorId);

        return $this->pagina($solicitud, fn (int $pagina, int $porPagina) => $listar->pruebas($jugadorId, $pagina, $porPagina));
    }

    /**
     * @param  Closure(int, int): array{entradas: list<EntradaDeHistorialDeCondicionFisica>, total: int}  $entradas
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
            array_map(fn (EntradaDeHistorialDeCondicionFisica $entrada) => new EntradaDeHistorialDeCondicionFisicaRecurso($entrada), $listado['entradas']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }
}
