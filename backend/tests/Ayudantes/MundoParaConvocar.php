<?php

/*
 * Datos de partida de la vista para convocar (T05, docs/hitos/f1-h6.md §7.5 y §7.7). Sobre el mundo de H4 (`umbralMundo()`):
 * E1 entrena A, E2 entrena B, E3 entrena A y B; D es docente; P1, D1, M1, F1 y J2 están en A, y F1 también en B. Reloj
 * fijo el 2026-10-01 a las 12:00 en México; `desactualizacion.dias` vale 30 de fábrica.
 */

use Database\Seeders\ParametrosInstitucionalesSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

function convocarFijarReloj(string $momentoUtc = '2026-10-01 18:00:00'): void
{
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable($momentoUtc, new DateTimeZone('UTC'))));
}

/** @return array<string, string> */
function convocarMundo(): array
{
    sesionesPrepararBase();
    $m = umbralMundo();
    (new ParametrosInstitucionalesSeeder)->run();
    convocarFijarReloj();

    return [...$m, 'lugar' => (string) DB::table('lugares')->value('id')];
}

function convocarRuta(string $jugador, string $consulta = ''): string
{
    return "/api/v1/jugadores/$jugador/vista-para-convocar".($consulta === '' ? '' : "?$consulta");
}

function convocarRutaDelEquipo(string $equipo, string $consulta = ''): string
{
    return "/api/v1/equipos/$equipo/jugadores/para-convocar".($consulta === '' ? '' : "?$consulta");
}

/** @return TestResponse<Response> */
function convocarPedir(TestCase $prueba, string $ruta, string $correo = 'e1@x.com'): TestResponse
{
    return sesionesPeticion($prueba, 'GET', $ruta, idDeSesion: sesionesIniciar($prueba, $correo));
}

/** La última evaluación de un entrenador sobre un jugador en un equipo (las demás filas son más antiguas). */
function convocarEvaluar(string $jugador, string $equipo, string $entrenador, string $resultado, ?string $promedio, string $calculadoEn = '2026-09-20 12:00:00+00'): string
{
    return alertasEvaluacion($jugador, $equipo, $entrenador, $resultado, $promedio, $calculadoEn);
}

/** Una medición registrada el día `fecha` (`AAAA-MM-DD`) por el entrenador; su última actualización es ese mismo día al mediodía en México. */
function convocarMedir(string $jugador, string $equipo, string $entrenador, string $fecha, string $peso = '70.00'): string
{
    return medicionInsertar($jugador, $equipo, $entrenador, $fecha, ['peso_kg' => $peso, 'creado_en' => "$fecha 18:00:00+00", 'actualizado_en' => "$fecha 18:00:00+00"]);
}

/**
 * Un entrenamiento realizado del equipo con la asistencia del jugador, capturada por `$capturo`.
 *
 * @param  array<string, string>  $m
 */
function convocarAsistir(array $m, string $equipo, string $jugador, string $estado, string $capturo, string $inicia = '2026-09-10 18:00:00+00'): string
{
    $entrenamiento = mundoEntrenamiento($equipo, $m['lugar'], $capturo, $inicia, date('Y-m-d H:i:sP', strtotime($inicia) + 3600), ['estado' => 'realizado']);

    return mundoInsertar('asistencias_entrenamiento', ['entrenamiento_id' => $entrenamiento, 'jugador_id' => $jugador, 'estado' => $estado, 'creado_por' => $capturo, 'creado_en' => $inicia, 'actualizado_en' => $inicia]);
}

/**
 * Captura a un jugador en un partido jugado nuevo del equipo por cada calificación, por la API de H4; el primero es el más antiguo.
 *
 * @param  list<int>  $calificaciones
 */
function convocarCapturar(TestCase $prueba, string $equipo, string $jugador, string $posicion, array $calificaciones, string $correo = 'e1@x.com'): void
{
    $sesion = sesionesIniciar($prueba, $correo);
    foreach ($calificaciones as $indice => $calificacion) {
        $dia = str_pad((string) (1 + $indice), 2, '0', STR_PAD_LEFT);
        $partido = desempenoPartidoJugado($equipo, "2026-08-$dia 23:00:00+00", [$jugador]);
        sesionesPeticion($prueba, 'POST', "/api/v1/partidos/$partido/rendimientos", capturaCuerpo($jugador, $posicion, ['calificacion_general' => $calificacion]), $sesion)->assertCreated();
    }
}
