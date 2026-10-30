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
use Src\Competencia\Aplicacion\CrearRival;
use Src\Competencia\Aplicacion\EliminarRival;
use Src\Competencia\Aplicacion\ListarRivales;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RivalController extends Controller
{
    /** [Decisión por defecto] 15 por página, como en el resto de listados; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function listar(Request $solicitud, ListarRivales $listarRivales): ColeccionPaginada
    {
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
            'orden' => ['in:nombre,-nombre'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);

        $listado = $listarRivales->ejecutar($pagina, $porPagina, ($datos['orden'] ?? 'nombre') === '-nombre');

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn ($rival) => new LugarRecurso($rival), $listado['elementos']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function crear(SolicitudDeNombreDeCatalogo $solicitud, CrearRival $crearRival): JsonResponse
    {
        $rival = $crearRival->ejecutar((string) $solicitud->input('nombre'));

        return Respuesta::conDatos((new LugarRecurso($rival))->resolve($solicitud), estado: 201);
    }

    public function eliminar(string $rival, EliminarRival $eliminarRival): Response
    {
        if (! $eliminarRival->ejecutar(IdentificadorDeRuta::desde($rival))) {
            throw new NotFoundHttpException;
        }

        return response()->noContent();
    }
}
