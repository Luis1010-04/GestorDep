<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeNuevaTemporada;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\TemporadaRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Competencia\Aplicacion\CrearTemporada;
use Src\Competencia\Aplicacion\EliminarTemporada;
use Src\Competencia\Aplicacion\ListarTemporadas;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TemporadaController extends Controller
{
    /** [Decisión por defecto] 15 por página, como en el resto de listados; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function listar(Request $solicitud, ListarTemporadas $listarTemporadas): ColeccionPaginada
    {
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
            'orden' => ['in:fecha_inicio,-fecha_inicio'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);

        $listado = $listarTemporadas->ejecutar($pagina, $porPagina, ($datos['orden'] ?? '-fecha_inicio') === '-fecha_inicio');

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn ($temporada) => new TemporadaRecurso($temporada), $listado['elementos']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function crear(SolicitudDeNuevaTemporada $solicitud, CrearTemporada $crearTemporada): JsonResponse
    {
        $utc = new DateTimeZone('UTC');
        $temporada = $crearTemporada->ejecutar(
            (string) $solicitud->input('nombre'),
            new DateTimeImmutable((string) $solicitud->input('fecha_inicio'), $utc),
            new DateTimeImmutable((string) $solicitud->input('fecha_fin'), $utc),
        );

        return Respuesta::conDatos((new TemporadaRecurso($temporada))->resolve($solicitud), estado: 201);
    }

    public function eliminar(string $temporada, EliminarTemporada $eliminarTemporada): Response
    {
        if (! $eliminarTemporada->ejecutar(IdentificadorDeRuta::desde($temporada))) {
            throw new NotFoundHttpException;
        }

        return response()->noContent();
    }
}
