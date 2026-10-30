<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeNombreDeCatalogo;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\LugarRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Competencia\Aplicacion\CrearLugar;
use Src\Competencia\Aplicacion\EliminarLugar;
use Src\Competencia\Aplicacion\ListarLugares;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LugarController extends Controller
{
    /** [Decisión por defecto] 15 por página, como en el resto de listados; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function listar(Request $solicitud, ListarLugares $listarLugares): ColeccionPaginada
    {
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
            'orden' => ['in:nombre,-nombre'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);

        $listado = $listarLugares->ejecutar($pagina, $porPagina, ($datos['orden'] ?? 'nombre') === '-nombre');

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn ($lugar) => new LugarRecurso($lugar), $listado['elementos']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function crear(SolicitudDeNombreDeCatalogo $solicitud, CrearLugar $crearLugar): JsonResponse
    {
        $lugar = $crearLugar->ejecutar((string) $solicitud->input('nombre'));

        return Respuesta::conDatos((new LugarRecurso($lugar))->resolve($solicitud), estado: 201);
    }

    public function eliminar(string $lugar, EliminarLugar $eliminarLugar): Response
    {
        if (! $eliminarLugar->ejecutar(IdentificadorDeRuta::desde($lugar))) {
            throw new NotFoundHttpException;
        }

        return response()->noContent();
    }
}
