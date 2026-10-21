<?php

use App\Http\Controllers\SaludController;
use Illuminate\Support\Facades\Route;

Route::get('/salud', SaludController::class);
