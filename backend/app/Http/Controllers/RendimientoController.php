<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeCorreccionDeRendimiento;
use App\Http\Requests\SolicitudDeRendimiento;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\PartidoPendienteDeRendimientoRecurso;
use App\Http\Resources\RendimientoRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Consultas\Aplicacion\ConsultaDePendientesDeRendimiento;
use Src\Consultas\Aplicacion\PartidoPendienteDeRendimiento;
use Src\Rendimiento\Aplicacion\BuscarPartidoDelEntrenador;
use Src\Rendimiento\Aplicacion\CapturarRendimiento;
use Src\Rendimiento\Aplicacion\ConsultaDeMetricas;
use Src\Rendimiento\Aplicacion\CorregirRendimiento;
use Src\Rendimiento\Aplicacion\ListarRendimientosDelPartido;
use Src\Rendimiento\Aplicacion\MetricaDelCatalogo;

class RendimientoController extends Controller
{
    /** [Decisión por defecto] 15 por página, como en el resto de listados; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function metricas(ConsultaDeMetricas $consulta): JsonResponse
    {
        return Respuesta::conDatos(array_map(fn (MetricaDelCatalogo $metrica) => [
            'id' => $metrica->id->valor(),
            'codigo' => $metrica->codigo,
            'nombre' => $metrica->nombre,
            'tipo_dato' => $metrica->tipo->value,
            'minimo' => RendimientoRecurso::numero($metrica->minimo, $metrica->tipo),
            'maximo' => RendimientoRecurso::numero($metrica->maximo, $metrica->tipo),
            'posiciones' => array_map(fn (array $posicion) => [
                'posicion_id' => $posicion['posicion_id']->valor(),
                'obligatoria' => $posicion['obligatoria'],
                'orden' => $posicion['orden'],
            ], $metrica->posiciones),
        ], $consulta->activas()));
    }

    public function pendientes(Request $solicitud, ConsultaDePendientesDeRendimiento $consulta): ColeccionPaginada
    {
        [$pagina, $porPagina] = $this->paginacion($solicitud);
        $listado = $consulta->delUsuario($pagina, $porPagina);

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn (PartidoPendienteDeRendimiento $partido) => new PartidoPendienteDeRendimientoRecurso($partido), $listado['elementos']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function listar(Request $solicitud, string $partido, BuscarPartidoDelEntrenador $buscarPartido, ListarRendimientosDelPartido $listarRendimientos): ColeccionPaginada
    {
        $partidoId = IdentificadorDeRuta::desde($partido);
        $buscarPartido->ejecutar($partidoId);
        [$pagina, $porPagina] = $this->paginacion($solicitud);
        $listado = $listarRendimientos->ejecutar($partidoId, $pagina, $porPagina);

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn ($rendimiento) => new RendimientoRecurso($rendimiento), $listado['elementos']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function crear(SolicitudDeRendimiento $solicitud, string $partido, CapturarRendimiento $capturarRendimiento): JsonResponse
    {
        /** @var array{jugador_id: string, minutos: int, fue_titular: bool, tarjetas_amarillas: int, tarjetas_rojas: int, calificacion_general: int, observacion?: string|null, metricas: list<array{metrica_id: string, valor: int}>} $datos */
        $datos = $solicitud->validated();

        $rendimiento = $capturarRendimiento->ejecutar(
            IdentificadorDeRuta::desde($partido),
            [...$datos, 'observacion' => $datos['observacion'] ?? null],
            $datos['metricas'],
        );

        return Respuesta::conDatos((new RendimientoRecurso($rendimiento))->resolve($solicitud), estado: 201);
    }

    public function corregir(SolicitudDeCorreccionDeRendimiento $solicitud, string $rendimiento, CorregirRendimiento $corregirRendimiento): JsonResponse
    {
        /** @var array{minutos?: int, fue_titular?: bool, tarjetas_amarillas?: int, tarjetas_rojas?: int, calificacion_general?: int, observacion?: string|null, metricas?: list<array{metrica_id: string, valor: int}>} $datos */
        $datos = $solicitud->validated();
        $metricas = $datos['metricas'] ?? null;
        unset($datos['metricas']);

        $corregido = $corregirRendimiento->ejecutar(IdentificadorDeRuta::desde($rendimiento), $datos, $metricas);

        return Respuesta::conDatos((new RendimientoRecurso($corregido))->resolve($solicitud));
    }

    /** @return array{0: int, 1: int} */
    private function paginacion(Request $solicitud): array
    {
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
        ]);

        return [(int) ($datos['pagina'] ?? 1), (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO)];
    }
}
