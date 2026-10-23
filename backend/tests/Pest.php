<?php

use Tests\TestCase;

require_once __DIR__.'/Ayudantes/SesionesDePrueba.php';

pest()->extend(TestCase::class)->in('Feature', 'Arch', 'Integration');
