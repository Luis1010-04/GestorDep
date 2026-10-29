<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeNuevaCategoria;
use App\Http\Resources\CategoriaRecurso;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Plantel\Aplicacion\CrearCategoria;
use Src\Plantel\Aplicacion\EliminarCategoria;
use Src\Plantel\Aplicacion\ListarCategorias;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategoriaController extends Controller
{
    /** [Decisión por defecto] 15 por página; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function listar(Request $solicitud, ListarCategorias $listarCategorias): ColeccionPaginada
    {
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
            'orden' => ['in:nombre,-nombre'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);

        $listado = $listarCategorias->ejecutar($pagina, $porPagina, ($datos['orden'] ?? 'nombre') === '-nombre');

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn ($categoria) => new CategoriaRecurso($categoria), $listado['categorias']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function crear(SolicitudDeNuevaCategoria $solicitud, CrearCategoria $crearCategoria): JsonResponse
    {
        $categoria = $crearCategoria->ejecutar((string) $solicitud->input('nombre'));

        return Respuesta::conDatos((new CategoriaRecurso($categoria))->resolve($solicitud), estado: 201);
    }

    public function eliminar(string $id, EliminarCategoria $eliminarCategoria): Response
    {
        if (! $eliminarCategoria->ejecutar(IdentificadorDeRuta::desde($id))) {
            throw new NotFoundHttpException;
        }

        return response()->noContent();
    }
}
