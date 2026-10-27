<?php

use Illuminate\Support\Facades\Schedule;

// Conciliación diaria de los resúmenes académicos (arquitectura §11, punto 5).
Schedule::command('alertas:recalcular')->dailyAt('02:00')->timezone('America/Mexico_City');
