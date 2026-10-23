<?php

use App\Http\Controllers\SaludController;
use App\Http\Controllers\SesionController;
use Illuminate\Support\Facades\Route;

Route::get('/salud', SaludController::class);

Route::post('/sesion', [SesionController::class, 'iniciar']);
