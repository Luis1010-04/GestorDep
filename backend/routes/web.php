<?php

use App\Http\Controllers\Web\PaginaController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/panel');
Route::get('/ingreso', [PaginaController::class, 'ingreso'])->middleware('guest:web');
Route::get('/panel', [PaginaController::class, 'panel'])->middleware('auth:web');
Route::get('/contrasena', [PaginaController::class, 'contrasena'])->middleware('auth:web');
Route::get('/jugadores', [PaginaController::class, 'jugadores'])->middleware('auth:web');
Route::get('/jugadores/crear', [PaginaController::class, 'crearJugador'])->middleware('auth:web');
Route::get('/jugadores/{jugador}', [PaginaController::class, 'jugador'])->middleware('auth:web')->whereUuid('jugador');
Route::get('/jugadores/{jugador}/editar', [PaginaController::class, 'editarJugador'])->middleware('auth:web')->whereUuid('jugador');
Route::get('/estatus', [PaginaController::class, 'estatus'])->middleware('auth:web');
Route::get('/plantel/categorias', [PaginaController::class, 'categorias'])->middleware('auth:web');
Route::get('/plantel/equipos', [PaginaController::class, 'equipos'])->middleware('auth:web');
Route::get('/plantel/equipos/{equipo}', [PaginaController::class, 'equipo'])->middleware('auth:web')->whereUuid('equipo');
