<?php

use App\Http\Controllers\AsignaturaController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AsistenciaDeEntrenamientoController;
use App\Http\Controllers\CalificacionController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ContrasenaController;
use App\Http\Controllers\EntrenamientoController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\EquipoJugadorController;
use App\Http\Controllers\InscripcionController;
use App\Http\Controllers\JugadorController;
use App\Http\Controllers\LugarController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\PartidoController;
use App\Http\Controllers\PeriodoEscolarController;
use App\Http\Controllers\RivalController;
use App\Http\Controllers\SaludController;
use App\Http\Controllers\SesionController;
use App\Http\Controllers\SituacionAcademicaController;
use App\Http\Controllers\TemporadaController;
use App\Http\Controllers\TorneoController;
use Illuminate\Support\Facades\Route;

Route::get('/salud', SaludController::class);

Route::post('/sesion', [SesionController::class, 'iniciar']);

Route::middleware('auth:web')->group(function () {
    Route::delete('/sesion', [SesionController::class, 'cerrar']);
    Route::put('/contrasena', [ContrasenaController::class, 'cambiar']);

    Route::get('/jugadores', [JugadorController::class, 'listar']);
    Route::get('/jugadores/busqueda', [JugadorController::class, 'buscar']);
    Route::get('/jugadores/{jugador}', [JugadorController::class, 'ver'])->whereUuid('jugador');
    Route::get('/jugadores/{jugador}/situacion-academica', [SituacionAcademicaController::class, 'ver'])->whereUuid('jugador');
    Route::get('/jugadores/{jugador}/asistencia-entrenamientos', [AsistenciaDeEntrenamientoController::class, 'delJugador'])->whereUuid('jugador');

    Route::middleware('rol:docente')->group(function () {
        Route::get('/periodos-escolares', [PeriodoEscolarController::class, 'listar']);
        Route::post('/periodos-escolares', [PeriodoEscolarController::class, 'crear']);
        Route::delete('/periodos-escolares/{id}', [PeriodoEscolarController::class, 'eliminar'])->whereUuid('id');

        Route::get('/asignaturas', [AsignaturaController::class, 'listar']);
        Route::post('/asignaturas', [AsignaturaController::class, 'crear']);
        Route::delete('/asignaturas/{id}', [AsignaturaController::class, 'eliminar'])->whereUuid('id');

        Route::get('/materias', [MateriaController::class, 'listar']);
        Route::post('/materias', [MateriaController::class, 'crear']);
        Route::get('/materias/{materia}', [MateriaController::class, 'ver'])->whereUuid('materia');
        Route::put('/materias/{materia}/criterio', [MateriaController::class, 'definirCriterio'])->whereUuid('materia');
        Route::get('/materias/{materia}/inscripciones', [InscripcionController::class, 'listar'])->whereUuid('materia');
        Route::post('/materias/{materia}/inscripciones', [InscripcionController::class, 'crear'])->whereUuid('materia');

        Route::get('/inscripciones/{inscripcion}/calificaciones', [CalificacionController::class, 'listar'])->whereUuid('inscripcion');
        Route::post('/inscripciones/{inscripcion}/calificaciones', [CalificacionController::class, 'crear'])->whereUuid('inscripcion');
        Route::patch('/calificaciones/{calificacion}', [CalificacionController::class, 'corregir'])->whereUuid('calificacion');

        Route::get('/inscripciones/{inscripcion}/asistencias', [AsistenciaController::class, 'listar'])->whereUuid('inscripcion');
        Route::post('/inscripciones/{inscripcion}/asistencias', [AsistenciaController::class, 'crear'])->whereUuid('inscripcion');
        Route::patch('/asistencias-clase/{asistencia}', [AsistenciaController::class, 'corregir'])->whereUuid('asistencia');
    });

    Route::middleware('rol:entrenador')->group(function () {
        Route::get('/categorias', [CategoriaController::class, 'listar']);
        Route::post('/categorias', [CategoriaController::class, 'crear']);
        Route::delete('/categorias/{id}', [CategoriaController::class, 'eliminar'])->whereUuid('id');

        Route::get('/equipos', [EquipoController::class, 'listar']);
        Route::post('/equipos', [EquipoController::class, 'crear']);

        Route::post('/equipos/{equipo}/jugadores', [EquipoJugadorController::class, 'asignar'])->whereUuid('equipo');
        Route::patch('/equipos/{equipo}/jugadores/{jugador}', [EquipoJugadorController::class, 'cambiarPosicion'])->whereUuid(['equipo', 'jugador']);

        Route::post('/jugadores', [JugadorController::class, 'crear']);
        Route::patch('/jugadores/{jugador}', [JugadorController::class, 'actualizar'])->whereUuid('jugador');
        Route::post('/jugadores/{jugador}/baja', [JugadorController::class, 'darDeBaja'])->whereUuid('jugador');

        Route::get('/lugares', [LugarController::class, 'listar']);
        Route::post('/lugares', [LugarController::class, 'crear']);
        Route::delete('/lugares/{lugar}', [LugarController::class, 'eliminar'])->whereUuid('lugar');

        Route::get('/rivales', [RivalController::class, 'listar']);
        Route::post('/rivales', [RivalController::class, 'crear']);
        Route::delete('/rivales/{rival}', [RivalController::class, 'eliminar'])->whereUuid('rival');

        Route::get('/temporadas', [TemporadaController::class, 'listar']);
        Route::post('/temporadas', [TemporadaController::class, 'crear']);
        Route::delete('/temporadas/{temporada}', [TemporadaController::class, 'eliminar'])->whereUuid('temporada');

        Route::get('/torneos', [TorneoController::class, 'listar']);
        Route::post('/torneos', [TorneoController::class, 'crear']);
        Route::delete('/torneos/{torneo}', [TorneoController::class, 'eliminar'])->whereUuid('torneo');

        Route::get('/equipos/{equipo}/entrenamientos', [EntrenamientoController::class, 'listar'])->whereUuid('equipo');
        Route::post('/equipos/{equipo}/entrenamientos', [EntrenamientoController::class, 'crear'])->whereUuid('equipo');
        Route::get('/entrenamientos/{entrenamiento}', [EntrenamientoController::class, 'ver'])->whereUuid('entrenamiento');
        Route::patch('/entrenamientos/{entrenamiento}', [EntrenamientoController::class, 'modificar'])->whereUuid('entrenamiento');

        Route::get('/entrenamientos/{entrenamiento}/asistencias', [AsistenciaDeEntrenamientoController::class, 'listar'])->whereUuid('entrenamiento');
        Route::post('/entrenamientos/{entrenamiento}/asistencias', [AsistenciaDeEntrenamientoController::class, 'crear'])->whereUuid('entrenamiento');
        Route::patch('/asistencias-entrenamiento/{asistencia}', [AsistenciaDeEntrenamientoController::class, 'corregir'])->whereUuid('asistencia');
        Route::get('/equipos/{equipo}/asistencia-entrenamientos', [AsistenciaDeEntrenamientoController::class, 'delEquipo'])->whereUuid('equipo');

        Route::get('/equipos/{equipo}/partidos', [PartidoController::class, 'listar'])->whereUuid('equipo');
        Route::post('/equipos/{equipo}/partidos', [PartidoController::class, 'crear'])->whereUuid('equipo');
        Route::get('/partidos/{partido}', [PartidoController::class, 'ver'])->whereUuid('partido');
        Route::patch('/partidos/{partido}', [PartidoController::class, 'modificar'])->whereUuid('partido');
    });
});
