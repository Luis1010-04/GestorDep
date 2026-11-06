<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeMedicion;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\MedicionRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\CondicionFisica\Aplicacion\ComprobarJugadorDelEntrenador;
use Src\CondicionFisica\Aplicacion\JugadorEnBajaSinMedicion;
use Src\CondicionFisica\Aplicacion\ListarMediciones;
use Src\CondicionFisica\Aplicacion\RegistrarMedicion;
use Src\CondicionFisica\Dominio\MedicionCorporal;

class MedicionController extends Controller
{
    /** [Decisión por defecto] 15 por página, como en el resto de listados; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function listar(Request $solicitud, string $jugador, ComprobarJugadorDelEntrenador $comprobarJugador, ListarMediciones $listarMediciones): ColeccionPaginada
    {
        $jugadorId = IdentificadorDeRuta::desde($jugador);
        $comprobarJugador->ejecutar($jugadorId);
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);
        $listado = $listarMediciones->ejecutar($jugadorId, $pagina, $porPagina);

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn (MedicionCorporal $medicion) => new MedicionRecurso($medicion), $listado['elementos']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function registrar(SolicitudDeMedicion $solicitud, string $jugador, RegistrarMedicion $registrarMedicion): JsonResponse
    {
        /** @var array{equipo_id: string, peso_kg: string, estatura_cm: string, fecha?: string, es_correccion?: bool, motivo?: string} $datos */
        $datos = $solicitud->validated();

        try {
            $resultado = $registrarMedicion->ejecutar(IdentificadorDeRuta::desde($jugador), $datos);
        } catch (JugadorEnBajaSinMedicion $e) {
            return new JsonResponse(['codigo' => $e->codigo(), 'mensaje' => __('errores.condicion_fisica.jugador_en_baja'), 'errores' => []], 409);
        }

        return Respuesta::conDatos((new MedicionRecurso($resultado['medicion']))->resolve($solicitud), estado: $resultado['es_nueva'] ? 201 : 200);
    }
}
