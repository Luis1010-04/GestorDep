<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeCambioDePartido;
use App\Http\Requests\SolicitudDeNuevoPartido;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\PartidoRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Aplicacion\BuscarPartidoDelEntrenador;
use Src\Competencia\Aplicacion\CambiosDePartido;
use Src\Competencia\Aplicacion\ComprobarEquipoEntrenado;
use Src\Competencia\Aplicacion\ListarPartidos;
use Src\Competencia\Aplicacion\ModificarPartido;
use Src\Competencia\Aplicacion\ProgramarPartido;
use Src\Competencia\Dominio\CondicionDePartido;
use Src\Competencia\Dominio\EstadoDePartido;
use Src\Competencia\Dominio\TipoDePartido;

class PartidoController extends Controller
{
    /** [Decisión por defecto] 15 por página, como en el resto de listados; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    /** Un `filtro[temporada_id]` que no es de ninguna temporada da una lista vacía, no 404 (DA-26). */
    public function listar(Request $solicitud, string $equipo, ComprobarEquipoEntrenado $comprobarEquipo, ListarPartidos $listarPartidos): ColeccionPaginada
    {
        $equipoId = IdentificadorDeRuta::desde($equipo);
        $comprobarEquipo->ejecutar($equipoId);
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
            'orden' => ['in:inicia_en,-inicia_en'],
            'filtro' => ['array'],
            'filtro.temporada_id' => ['string', 'uuid'],
            'filtro.estado' => ['string', 'in:programado,jugado,cancelado'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);

        $listado = ['elementos' => [], 'total' => 0];
        $temporada = $this->temporadaDelFiltro($datos['filtro']['temporada_id'] ?? null);
        if ($temporada !== false) {
            $listado = $listarPartidos->ejecutar(
                $equipoId,
                $temporada,
                isset($datos['filtro']['estado']) ? EstadoDePartido::from($datos['filtro']['estado']) : null,
                $pagina,
                $porPagina,
                ($datos['orden'] ?? 'inicia_en') === '-inicia_en',
            );
        }

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn ($partido) => new PartidoRecurso($partido), $listado['elementos']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function crear(SolicitudDeNuevoPartido $solicitud, string $equipo, ProgramarPartido $programarPartido): JsonResponse
    {
        $datos = $solicitud->validated();

        $partido = $programarPartido->ejecutar(
            IdentificadorDeRuta::desde($equipo),
            new DateTimeImmutable($datos['inicia_en']),
            $datos['lugar_id'],
            $datos['rival_id'],
            $datos['torneo_id'] ?? null,
            TipoDePartido::from($datos['tipo']),
            CondicionDePartido::from($datos['condicion']),
        );

        return Respuesta::conDatos((new PartidoRecurso($partido))->resolve($solicitud), estado: 201);
    }

    public function ver(Request $solicitud, string $partido, BuscarPartidoDelEntrenador $buscarPartido): JsonResponse
    {
        return Respuesta::conDatos((new PartidoRecurso($buscarPartido->ejecutar(IdentificadorDeRuta::desde($partido))))->resolve($solicitud));
    }

    public function modificar(SolicitudDeCambioDePartido $solicitud, string $partido, ModificarPartido $modificarPartido): JsonResponse
    {
        $datos = $solicitud->validated();

        $modificado = $modificarPartido->ejecutar(IdentificadorDeRuta::desde($partido), new CambiosDePartido(
            iniciaEn: isset($datos['inicia_en']) ? new DateTimeImmutable($datos['inicia_en']) : null,
            lugarId: $datos['lugar_id'] ?? null,
            rivalId: $datos['rival_id'] ?? null,
            cambiaTorneo: array_key_exists('torneo_id', $datos),
            torneoId: $datos['torneo_id'] ?? null,
            tipo: isset($datos['tipo']) ? TipoDePartido::from($datos['tipo']) : null,
            condicion: isset($datos['condicion']) ? CondicionDePartido::from($datos['condicion']) : null,
            cancelar: isset($datos['estado']),
        ));

        return Respuesta::conDatos((new PartidoRecurso($modificado))->resolve($solicitud));
    }

    /** @return Identificador|false|null nulo sin filtro; falso si el texto no es un UUID v7 y ninguna temporada puede tenerlo */
    private function temporadaDelFiltro(?string $texto): Identificador|false|null
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
