<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeCorreccionDeLesion;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\LesionRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Salud\Aplicacion\BuscarLesionDelRegistrante;
use Src\Salud\Aplicacion\ComprobarJugadorDelEntrenador;
use Src\Salud\Aplicacion\CorregirLesion;
use Src\Salud\Aplicacion\ListarLesiones;
use Src\Salud\Dominio\Lesion;

class LesionController extends Controller
{
    /** [Decisión por defecto] 15 por página, como en el resto de listados; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function listar(Request $solicitud, string $jugador, ComprobarJugadorDelEntrenador $comprobarJugador, ListarLesiones $listarLesiones): ColeccionPaginada
    {
        $jugadorId = IdentificadorDeRuta::desde($jugador);
        $comprobarJugador->ejecutar($jugadorId);
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);
        $listado = $listarLesiones->ejecutar($jugadorId, $pagina, $porPagina);

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn (Lesion $lesion) => new LesionRecurso($lesion), $listado['elementos']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function ver(Request $solicitud, string $lesion, BuscarLesionDelRegistrante $buscarLesion): JsonResponse
    {
        return Respuesta::conDatos((new LesionRecurso($buscarLesion->ejecutar(IdentificadorDeRuta::desde($lesion))))->resolve($solicitud));
    }

    public function corregir(SolicitudDeCorreccionDeLesion $solicitud, string $lesion, CorregirLesion $corregirLesion): JsonResponse
    {
        /** @var array{tipo?: string, zona?: string, causa?: string, nota?: string} $datos */
        $datos = $solicitud->validated();

        return Respuesta::conDatos((new LesionRecurso($corregirLesion->ejecutar(IdentificadorDeRuta::desde($lesion), $datos)))->resolve($solicitud));
    }
}
