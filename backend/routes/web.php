<?php

use App\Http\Controllers\Web\PaginaController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/panel');
Route::get('/ingreso', [PaginaController::class, 'ingreso'])->middleware('guest:web');
Route::get('/panel', [PaginaController::class, 'panel'])->middleware('auth:web');
