<?php

use Tests\TestCase;

require_once __DIR__.'/Ayudantes/EsquemaDePrueba.php';
require_once __DIR__.'/Ayudantes/SesionesDePrueba.php';
require_once __DIR__.'/Ayudantes/MundoDeJugadores.php';
require_once __DIR__.'/Ayudantes/MundoDeDocentes.php';
require_once __DIR__.'/Ayudantes/DeteccionDeConsultasDeJugadores.php';

pest()->extend(TestCase::class)->in('Feature', 'Arch', 'Integration');
