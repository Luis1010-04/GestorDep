<?php

namespace App\Http\Controllers;

use App\Http\Reglas\FiltroInvalido;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\FilaDeEstatusRecurso;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Src\Academico\Aplicacion\VerSituacionAcademica;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Consultas\Aplicacion\FilaDeEstatus;
use Src\Consultas\Aplicacion\FiltrosDeEstatus;
use Src\Consultas\Aplicacion\ListarEstatusDeJugadores;
use Src\Salud\Aplicacion\ConsultarEstado;

class EstatusDeJugadoresController extends Controller
{
    /** [Decisión por defecto] 15 por página, como en el resto de listados; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    private const TIPOS_DE_ALERTA = ['riesgo_baja_academica', 'riesgo_baja_deportiva', 'acumulacion_sanciones_deportivas'];

    /**
     * El estado oficial y la situación académica los arman Salud y Académico con sus propias reglas de visibilidad (RN-13,
     * RN-15); Consultas aporta los jugadores, el aviso pendiente y las alertas.
     */
    public function listar(
        Request $solicitud,
        ListarEstatusDeJugadores $listar,
        ConsultarEstado $consultarEstado,
        VerSituacionAcademica $verSituacion,
        UsuarioActual $usuarioActual,
    ): ColeccionPaginada {
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
            'filtro' => ['array'],
            'filtro.equipo_id' => [FiltroInvalido::uuid()],
            'filtro.materia_id' => [FiltroInvalido::uuid()],
            'filtro.tipo_alerta' => [FiltroInvalido::unoDe(self::TIPOS_DE_ALERTA)],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);

        $listado = $listar->ejecutar($this->filtros($datos), $pagina, $porPagina);
        $roles = $usuarioActual->roles();

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn (FilaDeEstatus $fila) => new FilaDeEstatusRecurso(
                $fila,
                $consultarEstado->ejecutar($fila->jugadorId),
                $verSituacion->ejecutar($fila->jugadorId),
                in_array('entrenador', $roles, true),
                in_array('docente', $roles, true),
            ), $listado['elementos']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    /**
     * Un id con forma de UUID que no es un UUID v7 no puede ser de ningún equipo ni materia: da una lista vacía, no un error (P-05).
     *
     * @param  array<string, mixed>  $datos
     */
    private function filtros(array $datos): FiltrosDeEstatus
    {
        /** @var array{equipo_id?: string, materia_id?: string, tipo_alerta?: string} $filtro */
        $filtro = $datos['filtro'] ?? [];
        $sinResultados = false;

        $equipo = $this->identificador($filtro['equipo_id'] ?? null, $sinResultados);
        $materia = $this->identificador($filtro['materia_id'] ?? null, $sinResultados);

        return new FiltrosDeEstatus($equipo, $materia, $filtro['tipo_alerta'] ?? null, $sinResultados);
    }

    private function identificador(?string $texto, bool &$sinResultados): ?Identificador
    {
        if ($texto === null) {
            return null;
        }

        try {
            return Identificador::desdeTexto($texto);
        } catch (InvalidArgumentException) {
            $sinResultados = true;

            return null;
        }
    }
}
