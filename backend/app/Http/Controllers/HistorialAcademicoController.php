<?php

namespace App\Http\Controllers;

use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\EntradaDeHistorialRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Compartido\Dominio\Identificador;
use Src\Consultas\Aplicacion\EntradaDeHistorial;
use Src\Consultas\Aplicacion\ListarHistorialAcademico;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Historial de calificaciones y de asistencia a clase de un jugador (HU-26; docs/hitos/f1-h7.md §6). Solo el docente,
 * con la misma respuesta que la lectura de su inscripción: 403 sin el rol (ruta) y 404 sin materia con el jugador
 * (DA-124), antes de validar los parámetros.
 */
class HistorialAcademicoController extends Controller
{
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function calificaciones(Request $solicitud, string $jugador, ListarHistorialAcademico $listar): ColeccionPaginada
    {
        return $this->responder($solicitud, $jugador, $listar, fn (Identificador $id, int $pagina, int $porPagina) => $listar->calificaciones($id, $pagina, $porPagina));
    }

    public function asistenciasClase(Request $solicitud, string $jugador, ListarHistorialAcademico $listar): ColeccionPaginada
    {
        return $this->responder($solicitud, $jugador, $listar, fn (Identificador $id, int $pagina, int $porPagina) => $listar->asistenciasClase($id, $pagina, $porPagina));
    }

    /**
     * @param  Closure(Identificador, int, int): array{entradas: list<EntradaDeHistorial>, total: int}  $entradas
     */
    private function responder(Request $solicitud, string $jugador, ListarHistorialAcademico $listar, Closure $entradas): ColeccionPaginada
    {
        $jugadorId = IdentificadorDeRuta::desde($jugador);
        if (! $listar->puedeVer($jugadorId)) {
            throw new NotFoundHttpException;
        }

        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);

        $listado = $entradas($jugadorId, $pagina, $porPagina);

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn (EntradaDeHistorial $entrada) => new EntradaDeHistorialRecurso($entrada), $listado['entradas']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }
}
