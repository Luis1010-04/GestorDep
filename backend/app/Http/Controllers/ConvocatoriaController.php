<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeConvocatoria;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\ConvocatoriaRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Competencia\Aplicacion\BuscarPartidoDelEntrenador;
use Src\Competencia\Aplicacion\ConvocarJugador;
use Src\Competencia\Aplicacion\ListarConvocatoria;
use Src\Competencia\Aplicacion\QuitarDeLaConvocatoria;

class ConvocatoriaController extends Controller
{
    /** [Decisión por defecto] 15 por página, como en el resto de listados; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function listar(Request $solicitud, string $partido, BuscarPartidoDelEntrenador $buscarPartido, ListarConvocatoria $listarConvocatoria): ColeccionPaginada
    {
        $partidoId = IdentificadorDeRuta::desde($partido);
        $buscarPartido->ejecutar($partidoId);
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);

        $listado = $listarConvocatoria->ejecutar($partidoId, $pagina, $porPagina);

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn ($convocatoria) => new ConvocatoriaRecurso($convocatoria), $listado['elementos']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function crear(SolicitudDeConvocatoria $solicitud, string $partido, ConvocarJugador $convocarJugador): JsonResponse
    {
        $convocatoria = $convocarJugador->ejecutar(IdentificadorDeRuta::desde($partido), (string) $solicitud->validated('jugador_id'));

        return Respuesta::conDatos((new ConvocatoriaRecurso($convocatoria))->resolve($solicitud), estado: 201);
    }

    public function eliminar(string $convocatoria, QuitarDeLaConvocatoria $quitar): Response
    {
        $quitar->ejecutar(IdentificadorDeRuta::desde($convocatoria));

        return response()->noContent();
    }
}
