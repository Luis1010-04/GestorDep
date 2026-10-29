<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeCambioDeEntrenamiento;
use App\Http\Requests\SolicitudDeNuevoEntrenamiento;
use App\Http\Resources\ColeccionPaginada;
use App\Http\Resources\EntrenamientoRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Competencia\Aplicacion\ComprobarEquipoEntrenado;
use Src\Competencia\Aplicacion\EntrenamientoConJugadores;
use Src\Competencia\Aplicacion\ListarEntrenamientos;
use Src\Competencia\Aplicacion\ModificarEntrenamiento;
use Src\Competencia\Aplicacion\ProgramarEntrenamiento;
use Src\Competencia\Aplicacion\VerEntrenamiento;
use Src\Competencia\Dominio\EstadoDeEntrenamiento;

class EntrenamientoController extends Controller
{
    /** [Decisión por defecto] 15 por página, como en el resto de listados; docs/convenciones-api.md solo fija el máximo de 100. */
    private const POR_PAGINA_PREDETERMINADO = 15;

    /** Los filtros son días locales (DA-10): `desde` desde su medianoche y `hasta` hasta la medianoche del día siguiente. */
    public function listar(Request $solicitud, string $equipo, ComprobarEquipoEntrenado $comprobarEquipo, ListarEntrenamientos $listarEntrenamientos): ColeccionPaginada
    {
        $equipoId = IdentificadorDeRuta::desde($equipo);
        $comprobarEquipo->ejecutar($equipoId);
        $datos = $solicitud->validate([
            'pagina' => ['integer', 'min:1'],
            'por_pagina' => ['integer', 'min:1', 'max:100'],
            'orden' => ['in:inicia_en,-inicia_en'],
            'filtro' => ['array'],
            'filtro.desde' => ['date_format:Y-m-d'],
            'filtro.hasta' => ['date_format:Y-m-d', ...($solicitud->has('filtro.desde') ? ['after_or_equal:filtro.desde'] : [])],
        ]);
        $pagina = (int) ($datos['pagina'] ?? 1);
        $porPagina = (int) ($datos['por_pagina'] ?? self::POR_PAGINA_PREDETERMINADO);
        $zona = new DateTimeZone((string) config('app.zona_presentacion'));
        $desde = isset($datos['filtro']['desde']) ? new DateTimeImmutable($datos['filtro']['desde'].' 00:00:00', $zona) : null;
        $hasta = isset($datos['filtro']['hasta']) ? (new DateTimeImmutable($datos['filtro']['hasta'].' 00:00:00', $zona))->modify('+1 day') : null;

        $listado = $listarEntrenamientos->ejecutar($equipoId, $desde, $hasta, $pagina, $porPagina, ($datos['orden'] ?? 'inicia_en') === '-inicia_en');

        return new ColeccionPaginada(new LengthAwarePaginator(
            array_map(fn ($entrenamiento) => new EntrenamientoRecurso($entrenamiento), $listado['elementos']),
            $listado['total'],
            $porPagina,
            $pagina,
        ));
    }

    public function crear(SolicitudDeNuevoEntrenamiento $solicitud, string $equipo, ProgramarEntrenamiento $programarEntrenamiento): JsonResponse
    {
        $programado = $programarEntrenamiento->ejecutar(
            IdentificadorDeRuta::desde($equipo),
            new DateTimeImmutable((string) $solicitud->validated('inicia_en')),
            new DateTimeImmutable((string) $solicitud->validated('termina_en')),
            (string) $solicitud->validated('lugar_id'),
            (string) $solicitud->validated('responsable_id'),
        );

        return $this->respuesta($solicitud, $programado, 201);
    }

    public function ver(Request $solicitud, string $entrenamiento, VerEntrenamiento $verEntrenamiento): JsonResponse
    {
        return $this->respuesta($solicitud, $verEntrenamiento->ejecutar(IdentificadorDeRuta::desde($entrenamiento)));
    }

    public function modificar(SolicitudDeCambioDeEntrenamiento $solicitud, string $entrenamiento, ModificarEntrenamiento $modificarEntrenamiento): JsonResponse
    {
        $datos = $solicitud->validated();

        $modificado = $modificarEntrenamiento->ejecutar(
            IdentificadorDeRuta::desde($entrenamiento),
            isset($datos['inicia_en']) ? new DateTimeImmutable($datos['inicia_en']) : null,
            isset($datos['termina_en']) ? new DateTimeImmutable($datos['termina_en']) : null,
            $datos['lugar_id'] ?? null,
            $datos['responsable_id'] ?? null,
            isset($datos['estado']) ? EstadoDeEntrenamiento::from($datos['estado']) : null,
        );

        return $this->respuesta($solicitud, $modificado);
    }

    private function respuesta(Request $solicitud, EntrenamientoConJugadores $entrenamiento, int $estado = 200): JsonResponse
    {
        $advertencias = $entrenamiento->empalmado
            ? [['codigo' => 'empalme_de_lugar', 'mensaje' => (string) __('advertencias.empalme_de_lugar')]]
            : [];

        return Respuesta::conDatos((new EntrenamientoRecurso($entrenamiento))->resolve($solicitud), $advertencias, $estado);
    }
}
