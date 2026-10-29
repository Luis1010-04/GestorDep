<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeNuevoPeriodo;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\PeriodoEscolarRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Academico\Aplicacion\CrearPeriodo;
use Src\Academico\Aplicacion\EliminarPeriodo;
use Src\Academico\Aplicacion\ListarPeriodos;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PeriodoEscolarController extends Controller
{
    /** [Decisión por defecto] 15 por página, como en el resto de listados; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function listar(Request $solicitud, ListarPeriodos $listarPeriodos): ColeccionPaginada
    {
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
            'orden' => ['in:fecha_inicio,-fecha_inicio'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);

        $listado = $listarPeriodos->ejecutar($pagina, $porPagina, ($datos['orden'] ?? '-fecha_inicio') === '-fecha_inicio');

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn ($periodo) => new PeriodoEscolarRecurso($periodo), $listado['periodos']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function crear(SolicitudDeNuevoPeriodo $solicitud, CrearPeriodo $crearPeriodo): JsonResponse
    {
        $utc = new DateTimeZone('UTC');
        $periodo = $crearPeriodo->ejecutar(
            (string) $solicitud->input('nombre'),
            new DateTimeImmutable((string) $solicitud->input('fecha_inicio'), $utc),
            new DateTimeImmutable((string) $solicitud->input('fecha_fin'), $utc),
        );

        return Respuesta::conDatos((new PeriodoEscolarRecurso($periodo))->resolve($solicitud), estado: 201);
    }

    public function eliminar(string $id, EliminarPeriodo $eliminarPeriodo): Response
    {
        if (! $eliminarPeriodo->ejecutar(IdentificadorDeRuta::desde($id))) {
            throw new NotFoundHttpException;
        }

        return response()->noContent();
    }
}
