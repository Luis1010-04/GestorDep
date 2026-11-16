<?php

use Illuminate\Support\Facades\Schedule;

// Conciliación diaria de los resúmenes académicos (arquitectura §11, punto 5; DA-113).
Schedule::command('resumenes:conciliar')->dailyAt('02:00')->timezone('America/Mexico_City');

// Recálculo diario de las tres alertas (arquitectura §11, punto 5; DA-88, DA-113). Es independiente del anterior: no lo llama.
Schedule::command('alertas:recalcular')->dailyAt('02:00')->timezone('America/Mexico_City');
