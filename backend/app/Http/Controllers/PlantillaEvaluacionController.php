<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDePlantilla;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\PlantillaRecurso;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Src\Compartido\Dominio\Identificador;
use Src\Rendimiento\Aplicacion\ConsultaDePlantillas;
use Src\Rendimiento\Aplicacion\CrearPlantilla;

class PlantillaEvaluacionController extends Controller
{
    /** [Decisión por defecto] 15 por página, como en el resto de listados; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    /** Un `filtro[posicion_id]` con UUID válido que no es v7 da una lista vacía, no 404 (DA-26 de H3, v1.0.4 §6.1). */
    public function listar(Request $solicitud, ConsultaDePlantillas $consulta): ColeccionPaginada
    {
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
            'filtro' => ['array'],
            'filtro.posicion_id' => ['string', 'uuid'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);

        $listado = ['elementos' => [], 'total' => 0];
        $posicion = $this->posicionDelFiltro($datos['filtro']['posicion_id'] ?? null);
        if ($posicion !== false) {
            $listado = $consulta->visiblesDelUsuario($posicion, $pagina, $porPagina);
        }

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn ($plantilla) => new PlantillaRecurso($plantilla), $listado['elementos']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function crear(SolicitudDePlantilla $solicitud, CrearPlantilla $crearPlantilla): JsonResponse
    {
        /** @var array{nombre: string, posicion_id: string, calificacion_minima: string, ventana_partidos: int} $datos */
        $datos = $solicitud->validated();

        $plantilla = $crearPlantilla->ejecutar($datos['nombre'], $datos['posicion_id'], $datos['calificacion_minima'], $datos['ventana_partidos']);

        return Respuesta::conDatos((new PlantillaRecurso($plantilla))->resolve($solicitud), estado: 201);
    }

    /** @return Identificador|false|null nulo sin filtro; falso si el texto no es un UUID v7 y ninguna posición puede tenerlo */
    private function posicionDelFiltro(?string $texto): Identificador|false|null
    {
        if ($texto === null) {
            return null;
        }

        try {
            return Identificador::desdeTexto($texto);
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
