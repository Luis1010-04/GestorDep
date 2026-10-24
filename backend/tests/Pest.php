<?php

use Tests\TestCase;

require_once __DIR__.'/Ayudantes/SesionesDePrueba.php';
require_once __DIR__.'/Ayudantes/MundoDeJugadores.php';

pest()->extend(TestCase::class)->in('Feature', 'Arch', 'Integration');
