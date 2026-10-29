<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeAlineacion;
use App\Http\Requests\SolicitudDeResultado;
use App\Http\Resources\ConvocatoriaRecurso;
use App\Http\Resources\PartidoRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Src\Competencia\Aplicacion\CapturarResultado;
use Src\Competencia\Aplicacion\DefinirAlineacion;
use Src\Competencia\Dominio\RolDeAlineacion;

class AlineacionYResultadoController extends Controller
{
    public function alineacion(SolicitudDeAlineacion $solicitud, string $partido, DefinirAlineacion $definirAlineacion): JsonResponse
    {
        /** @var list<array{jugador_id: string, posicion_id: string, rol_alineacion: string}> $alineacion */
        $alineacion = $solicitud->validated('alineacion');

        $convocatoria = $definirAlineacion->ejecutar(
            IdentificadorDeRuta::desde($partido),
            array_map(fn (array $entrada) => [
                'jugador_id' => $entrada['jugador_id'],
                'posicion_id' => $entrada['posicion_id'],
                'rol_alineacion' => RolDeAlineacion::from($entrada['rol_alineacion']),
            ], $alineacion),
        );

        return Respuesta::conDatos(array_map(fn ($convocado) => (new ConvocatoriaRecurso($convocado))->resolve($solicitud), $convocatoria));
    }

    public function resultado(SolicitudDeResultado $solicitud, string $partido, CapturarResultado $capturarResultado): JsonResponse
    {
        $jugado = $capturarResultado->ejecutar(
            IdentificadorDeRuta::desde($partido),
            (int) $solicitud->validated('goles_favor'),
            (int) $solicitud->validated('goles_contra'),
        );

        return Respuesta::conDatos((new PartidoRecurso($jugado))->resolve($solicitud));
    }
}
