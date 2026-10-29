<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeNuevoTorneo;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\TorneoRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Competencia\Aplicacion\CrearTorneo;
use Src\Competencia\Aplicacion\EliminarTorneo;
use Src\Competencia\Aplicacion\ListarTorneos;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TorneoController extends Controller
{
    /** [Decisión por defecto] 15 por página, como en el resto de listados; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function listar(Request $solicitud, ListarTorneos $listarTorneos): ColeccionPaginada
    {
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
            'orden' => ['in:nombre,-nombre'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);

        $listado = $listarTorneos->ejecutar($pagina, $porPagina, ($datos['orden'] ?? 'nombre') === '-nombre');

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn ($torneo) => new TorneoRecurso($torneo), $listado['elementos']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function crear(SolicitudDeNuevoTorneo $solicitud, CrearTorneo $crearTorneo): JsonResponse
    {
        $torneo = $crearTorneo->ejecutar((string) $solicitud->input('nombre'), (string) $solicitud->input('temporada_id'));

        return Respuesta::conDatos((new TorneoRecurso($torneo))->resolve($solicitud), estado: 201);
    }

    public function eliminar(string $torneo, EliminarTorneo $eliminarTorneo): Response
    {
        if (! $eliminarTorneo->ejecutar(IdentificadorDeRuta::desde($torneo))) {
            throw new NotFoundHttpException;
        }

        return response()->noContent();
    }
}
