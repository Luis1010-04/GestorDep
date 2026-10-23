<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeNuevoEquipo;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\EquipoRecurso;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Compartido\Dominio\Identificador;
use Src\Plantel\Aplicacion\CrearEquipo;
use Src\Plantel\Aplicacion\ListarEquipos;

class EquipoController extends Controller
{
    /** [Decisión por defecto] 15 por página; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function listar(Request $solicitud, ListarEquipos $listarEquipos): ColeccionPaginada
    {
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
            'orden' => ['in:nombre,-nombre'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);

        $listado = $listarEquipos->ejecutar($pagina, $porPagina, ($datos['orden'] ?? 'nombre') === '-nombre');

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn ($equipo) => new EquipoRecurso($equipo), $listado['equipos']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function crear(SolicitudDeNuevoEquipo $solicitud, CrearEquipo $crearEquipo): JsonResponse
    {
        $equipo = $crearEquipo->ejecutar(
            (string) $solicitud->input('nombre'),
            Identificador::desdeTexto((string) $solicitud->input('categoria_id')),
        );

        return Respuesta::conDatos((new EquipoRecurso($equipo))->resolve($solicitud), estado: 201);
    }
}
