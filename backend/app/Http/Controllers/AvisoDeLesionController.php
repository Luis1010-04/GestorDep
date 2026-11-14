<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeConfirmacionDeAviso;
use App\Http\Requests\SolicitudDeDescarteDeAviso;
use App\Http\Requests\SolicitudDeNuevoAvisoDeLesion;
use App\Http\Resources\AvisoDeLesionRecurso;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Salud\Aplicacion\AvisoVisible;
use Src\Salud\Aplicacion\ComprobarJugadorVisible;
use Src\Salud\Aplicacion\ConfirmarAviso;
use Src\Salud\Aplicacion\CrearAviso;
use Src\Salud\Aplicacion\DescartarAviso;
use Src\Salud\Aplicacion\ListarAvisos;

class AvisoDeLesionController extends ControladorDeSalud
{
    /** [Decisión por defecto] 15 por página, como en el resto de listados; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function listar(Request $solicitud, string $jugador, ComprobarJugadorVisible $comprobarJugador, ListarAvisos $listarAvisos): ColeccionPaginada
    {
        $jugadorId = IdentificadorDeRuta::desde($jugador);
        $comprobarJugador->ejecutar($jugadorId);
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);
        $listado = $listarAvisos->ejecutar($jugadorId, $pagina, $porPagina);

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn (AvisoVisible $aviso) => new AvisoDeLesionRecurso($aviso), $listado['elementos']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function crear(SolicitudDeNuevoAvisoDeLesion $solicitud, string $jugador, CrearAviso $crearAviso): JsonResponse
    {
        /** @var array{nota: string, fecha: string, reportado_por_jugador?: bool} $datos */
        $datos = $solicitud->validated();

        return $this->conMensajesDeSalud(function () use ($solicitud, $jugador, $crearAviso, $datos): JsonResponse {
            $aviso = $crearAviso->ejecutar(IdentificadorDeRuta::desde($jugador), $datos['nota'], $datos['fecha'], $datos['reportado_por_jugador'] ?? false);

            return Respuesta::conDatos((new AvisoDeLesionRecurso($aviso))->resolve($solicitud), estado: 201);
        });
    }

    public function descartar(SolicitudDeDescarteDeAviso $solicitud, string $aviso, DescartarAviso $descartarAviso): JsonResponse
    {
        $descartado = $descartarAviso->ejecutar(IdentificadorDeRuta::desde($aviso), (string) $solicitud->validated('nota_descarte'));

        return Respuesta::conDatos((new AvisoDeLesionRecurso($descartado))->resolve($solicitud));
    }

    public function confirmar(SolicitudDeConfirmacionDeAviso $solicitud, string $aviso, ConfirmarAviso $confirmarAviso): JsonResponse
    {
        /** @var array{estado: string, tipo?: string, zona?: string, causa?: string, fecha_estimada_regreso?: string, nota?: string} $datos */
        $datos = $solicitud->validated();

        return $this->conMensajesDeSalud(function () use ($solicitud, $aviso, $confirmarAviso, $datos): JsonResponse {
            $confirmado = $confirmarAviso->ejecutar(IdentificadorDeRuta::desde($aviso), $datos);

            return Respuesta::conDatos((new AvisoDeLesionRecurso($confirmado))->resolve($solicitud));
        });
    }
}
