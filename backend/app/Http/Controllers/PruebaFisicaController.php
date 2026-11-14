<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDePruebaFisica;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\PruebaFisicaRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Src\Compartido\Dominio\Identificador;
use Src\CondicionFisica\Aplicacion\ComprobarJugadorDelEntrenador;
use Src\CondicionFisica\Aplicacion\ConsultarPeriodoDePruebas;
use Src\CondicionFisica\Aplicacion\JugadorEnBajaSinPrueba;
use Src\CondicionFisica\Aplicacion\ListarPruebas;
use Src\CondicionFisica\Aplicacion\RegistrarPrueba;
use Src\CondicionFisica\Dominio\PruebaFisica;

class PruebaFisicaController extends Controller
{
    /** [Decisión por defecto] 15 por página, como en el resto de listados; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    /**
     * Con `filtro[periodo]`, la vista del periodo, sin paginar y sin leer `pagina` ni `por_pagina` (DA-63). Sin él, el
     * listado paginado. Un `filtro[tipo_prueba_id]` que no es UUID v7 da una lista vacía (DA-26 de H3, DA-62).
     */
    public function listar(
        Request $solicitud,
        string $jugador,
        ComprobarJugadorDelEntrenador $comprobarJugador,
        ListarPruebas $listarPruebas,
        ConsultarPeriodoDePruebas $consultarPeriodo,
    ): JsonResponse|ColeccionPaginada {
        $jugadorId = IdentificadorDeRuta::desde($jugador);
        $comprobarJugador->ejecutar($jugadorId);
        $filtros = $solicitud->validate([
            'filtro' => ['array'],
            'filtro.periodo' => ['string', 'date_format:Y-m'],
            'filtro.tipo_prueba_id' => ['string', 'uuid'],
        ]);
        $tipo = self::tipoDelFiltro($filtros['filtro']['tipo_prueba_id'] ?? null);

        if (isset($filtros['filtro']['periodo'])) {
            $periodo = (string) $filtros['filtro']['periodo'];
            $vista = $tipo === false ? [] : $consultarPeriodo->ejecutar($jugadorId, $periodo, $tipo);

            return Respuesta::conDatos(array_map(fn (array $elemento) => [
                'tipo_prueba_id' => $elemento['tipo']->id()->valor(),
                'periodo' => $periodo,
                'prueba' => $elemento['prueba'] === null ? null : (new PruebaFisicaRecurso($elemento['prueba']))->resolve($solicitud),
            ], $vista));
        }

        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);
        $listado = $tipo === false ? ['elementos' => [], 'total' => 0] : $listarPruebas->ejecutar($jugadorId, $tipo, $pagina, $porPagina);

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn (PruebaFisica $prueba) => new PruebaFisicaRecurso($prueba), $listado['elementos']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function registrar(SolicitudDePruebaFisica $solicitud, string $jugador, RegistrarPrueba $registrarPrueba): JsonResponse
    {
        /** @var array{equipo_id: string, tipo_prueba_id: string, resultado: string, fecha?: string, es_correccion?: bool, motivo?: string} $datos */
        $datos = $solicitud->validated();

        try {
            $resultado = $registrarPrueba->ejecutar(IdentificadorDeRuta::desde($jugador), $datos);
        } catch (JugadorEnBajaSinPrueba $e) {
            return new JsonResponse(['codigo' => $e->codigo(), 'mensaje' => __('errores.condicion_fisica.jugador_en_baja_prueba'), 'errores' => []], 409);
        }

        return Respuesta::conDatos((new PruebaFisicaRecurso($resultado['prueba']))->resolve($solicitud), estado: $resultado['es_nueva'] ? 201 : 200);
    }

    /** @return Identificador|false|null nulo sin filtro; falso si el texto no es un UUID v7 y ningún tipo puede tenerlo */
    private static function tipoDelFiltro(?string $texto): Identificador|false|null
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
