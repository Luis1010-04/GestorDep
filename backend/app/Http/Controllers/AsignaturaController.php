<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeNuevaAsignatura;
use App\Http\Resources\AsignaturaRecurso;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Academico\Aplicacion\CrearAsignatura;
use Src\Academico\Aplicacion\EliminarAsignatura;
use Src\Academico\Aplicacion\ListarAsignaturas;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AsignaturaController extends Controller
{
    /** [Decisión por defecto] 15 por página, como en el resto de listados; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function listar(Request $solicitud, ListarAsignaturas $listarAsignaturas): ColeccionPaginada
    {
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
            'orden' => ['in:clave,-clave'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);

        $listado = $listarAsignaturas->ejecutar($pagina, $porPagina, ($datos['orden'] ?? 'clave') === '-clave');

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn ($asignatura) => new AsignaturaRecurso($asignatura), $listado['asignaturas']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function crear(SolicitudDeNuevaAsignatura $solicitud, CrearAsignatura $crearAsignatura): JsonResponse
    {
        $asignatura = $crearAsignatura->ejecutar(
            (string) $solicitud->input('clave'),
            (string) $solicitud->input('nombre'),
            (int) $solicitud->input('grado'),
        );

        return Respuesta::conDatos((new AsignaturaRecurso($asignatura))->resolve($solicitud), estado: 201);
    }

    public function eliminar(string $id, EliminarAsignatura $eliminarAsignatura): Response
    {
        if (! $eliminarAsignatura->ejecutar(IdentificadorDeRuta::desde($id))) {
            throw new NotFoundHttpException;
        }

        return response()->noContent();
    }
}
