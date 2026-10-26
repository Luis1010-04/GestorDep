<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeActualizacionDeJugador;
use App\Http\Requests\SolicitudDeBajaDeJugador;
use App\Http\Requests\SolicitudDeNuevoJugador;
use App\Http\Resources\BajaRecurso;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\JugadorBuscadoRecurso;
use App\Http\Resources\JugadorConsultadoRecurso;
use App\Http\Resources\JugadorRecurso;
use App\Http\Respuestas\Respuesta;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Src\Compartido\Dominio\Identificador;
use Src\Plantel\Aplicacion\ActualizarExpedienteDeJugador;
use Src\Plantel\Aplicacion\BuscarJugadorPorMatricula;
use Src\Plantel\Aplicacion\DarDeBajaAJugador;
use Src\Plantel\Aplicacion\JugadorNoEncontrado;
use Src\Plantel\Aplicacion\ListarJugadores;
use Src\Plantel\Aplicacion\RegistrarJugador;
use Src\Plantel\Aplicacion\VerJugador;

class JugadorController extends Controller
{
    /** [Decisión por defecto] 15 por página; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    public function listar(Request $solicitud, ListarJugadores $listarJugadores): ColeccionPaginada
    {
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
            'orden' => ['in:apellidos,-apellidos,nombres,-nombres'],
            'filtro' => ['array'],
            'filtro.equipo_id' => ['string', 'uuid:7'],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);
        $orden = (string) ($datos['orden'] ?? 'apellidos');
        $equipoId = isset($datos['filtro']['equipo_id']) ? Identificador::desdeTexto($datos['filtro']['equipo_id']) : null;

        $campoDeOrden = ltrim($orden, '-');
        $listado = $listarJugadores->ejecutar($equipoId, $campoDeOrden === 'nombres' ? 'nombres' : 'apellidos', str_starts_with($orden, '-'), $pagina, $porPagina);

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn ($jugador) => new JugadorConsultadoRecurso($jugador), $listado['encontrados']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function ver(Request $solicitud, string $jugador, VerJugador $verJugador): JsonResponse
    {
        try {
            $jugadorId = Identificador::desdeTexto($jugador);
        } catch (InvalidArgumentException) {
            throw new JugadorNoEncontrado;
        }

        return Respuesta::conDatos((new JugadorConsultadoRecurso($verJugador->ejecutar($jugadorId)))->resolve($solicitud));
    }

    public function actualizar(SolicitudDeActualizacionDeJugador $solicitud, string $jugador, ActualizarExpedienteDeJugador $actualizarExpediente): JsonResponse
    {
        $datos = $solicitud->validated();

        $actualizado = $actualizarExpediente->ejecutar(
            Identificador::desdeTexto($jugador),
            $datos['nombres'] ?? null,
            $datos['apellidos'] ?? null,
            isset($datos['fecha_nacimiento']) ? new DateTimeImmutable($datos['fecha_nacimiento']) : null,
        );

        return Respuesta::conDatos((new JugadorConsultadoRecurso($actualizado))->resolve($solicitud));
    }

    public function darDeBaja(SolicitudDeBajaDeJugador $solicitud, string $jugador, DarDeBajaAJugador $darDeBaja): JsonResponse
    {
        $baja = $darDeBaja->ejecutar(
            Identificador::desdeTexto($jugador),
            (string) $solicitud->validated('motivo'),
            new DateTimeImmutable((string) $solicitud->validated('fecha')),
        );

        return Respuesta::conDatos((new BajaRecurso($baja))->resolve($solicitud), estado: 201);
    }

    public function buscar(Request $solicitud, BuscarJugadorPorMatricula $buscarJugador): JsonResponse
    {
        $datos = $solicitud->validate(['matricula' => ['required', 'string', 'max:30']]);

        return Respuesta::conDatos((new JugadorBuscadoRecurso($buscarJugador->ejecutar($datos['matricula'])))->resolve($solicitud));
    }

    public function crear(SolicitudDeNuevoJugador $solicitud, RegistrarJugador $registrarJugador): JsonResponse
    {
        $jugador = $registrarJugador->ejecutar(
            (string) $solicitud->input('nombres'),
            (string) $solicitud->input('apellidos'),
            new DateTimeImmutable((string) $solicitud->input('fecha_nacimiento')),
            (string) $solicitud->input('matricula'),
            Identificador::desdeTexto((string) $solicitud->input('equipo_id')),
            (string) $solicitud->input('posicion'),
        );

        return Respuesta::conDatos((new JugadorRecurso($jugador))->resolve($solicitud), estado: 201);
    }
}
