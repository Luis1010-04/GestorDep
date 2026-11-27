<?php

/*
 * Capacidad (RNF-06; docs/hitos/f1-h7.md v0.6 §8, T08; D-6): 15 equipos, 25 usuarios y 375 jugadores, el supuesto de
 * AB-02 [Propuesta]. Corre dentro de `make verificar`. El volumen lo siembra `VolumenSeeder`, que no forma parte de
 * `DatabaseSeeder`; sus escrituras por jugador pasan por los candados de T06 (`CandadoDeJugadores`). El reloj queda fijo
 * para que el periodo escolar y la temporada sean siempre los vigentes.
 */

use Database\Seeders\VolumenSeeder;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Tests\TestCase;

/** Base vacía, sesiones en la tabla `sessions`, reloj fijo y el volumen sembrado. */
function volumenSembrar(): void
{
    esquemaReiniciar();
    config(['session.driver' => 'database']);
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-11-16 18:00:00', new DateTimeZone('UTC'))));
    (new VolumenSeeder)->setContainer(app())->run();
}

/** Inicia sesión con un usuario de `VolumenSeeder` y devuelve el identificador de su sesión. */
function volumenIniciar(TestCase $prueba, string $correo): string
{
    $antes = DB::table('sessions')->pluck('id')->all();
    sesionesPeticion($prueba, 'POST', '/api/v1/sesion', ['correo' => $correo, 'contrasena' => VolumenSeeder::CLAVE])->assertOk();

    return (string) DB::table('sessions')->whereNotNull('user_id')->whereNotIn('id', $antes)->value('id');
}

function volumenRecalcular(): void
{
    consolaArtisan('alertas:recalcular')->assertExitCode(0)->run();
    consolaArtisan('resumenes:conciliar')->assertExitCode(0)->run();
}

test('RNF-06: con 15 equipos, 25 usuarios y 375 jugadores, `alertas:recalcular` y `resumenes:conciliar` terminan con código 0', function () {
    /** @var TestCase $this */
    volumenSembrar();

    expect(DB::table('equipos')->count())->toBe(15)
        ->and(DB::table('usuarios')->count())->toBe(25)
        ->and(DB::table('jugadores')->count())->toBe(375)
        ->and(DB::table('jugador_equipo')->whereNull('hasta')->count())->toBe(375)
        ->and(DB::table('inscripciones')->count())->toBe(375)
        ->and(DB::table('calificaciones')->count())->toBe(375)
        ->and(DB::table('mediciones_corporales')->count())->toBe(375)
        ->and(DB::table('convocatorias')->count())->toBe(15 * VolumenSeeder::CONVOCADOS_POR_PARTIDO)
        ->and(DB::table('asistencias_entrenamiento')->count())->toBe(375);

    volumenRecalcular();

    expect(DB::table('resumenes_academicos')->count())->toBe(375)
        ->and(DB::table('alertas')->whereNull('cerrada_en')->where('tipo', 'riesgo_baja_academica')->count())->toBe(75);
});

test('RNF-06: con ese volumen, el listado de estatus, la vista para convocar, las alertas y el historial responden 200 sin errores', function () {
    /** @var TestCase $this */
    volumenSembrar();
    volumenRecalcular();

    $equipo = (string) DB::table('equipos')->where('nombre', 'Volumen 01')->value('id');
    $reprobado = (string) DB::table('jugadores')->where('matricula', 'VOL-0001')->value('id');
    $entrenador = volumenIniciar($this, 'volumen-e01@ejemplo.test');
    $docente = volumenIniciar($this, 'volumen-d01@ejemplo.test');

    $estatus = sesionesPeticion($this, 'GET', '/api/v1/estatus-de-jugadores?por_pagina=100', idDeSesion: $entrenador)->assertOk();
    $convocables = sesionesPeticion($this, 'GET', "/api/v1/equipos/$equipo/jugadores/para-convocar?por_pagina=100", idDeSesion: $entrenador)->assertOk();
    sesionesPeticion($this, 'GET', "/api/v1/jugadores/$reprobado/vista-para-convocar", idDeSesion: $entrenador)->assertOk();
    $alertasDelEntrenador = sesionesPeticion($this, 'GET', "/api/v1/jugadores/$reprobado/alertas", idDeSesion: $entrenador)->assertOk();
    $mediciones = sesionesPeticion($this, 'GET', "/api/v1/jugadores/$reprobado/historial/mediciones", idDeSesion: $entrenador)->assertOk();

    $estatusDelDocente = sesionesPeticion($this, 'GET', '/api/v1/estatus-de-jugadores?por_pagina=100', idDeSesion: $docente)->assertOk();
    $alertasDelDocente = sesionesPeticion($this, 'GET', "/api/v1/jugadores/$reprobado/alertas", idDeSesion: $docente)->assertOk();
    $calificaciones = sesionesPeticion($this, 'GET', "/api/v1/jugadores/$reprobado/historial/calificaciones", idDeSesion: $docente)->assertOk();

    expect($estatus->json('meta.total'))->toBe(25)
        ->and($convocables->json('meta.total'))->toBe(25)
        ->and(array_column($alertasDelEntrenador->json('datos'), 'tipo'))->toContain('riesgo_baja_academica')
        ->and($mediciones->json('meta.total'))->toBe(1)
        ->and($estatusDelDocente->json('meta.total'))->toBe(38)
        ->and(array_column($alertasDelDocente->json('datos'), 'tipo'))->toBe(['riesgo_baja_academica'])
        ->and($calificaciones->json('meta.total'))->toBe(1);
});
