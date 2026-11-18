<?php

/*
 * Funciones de ayuda que usan varios archivos de pruebas unitarias (DA-101). Vivían dentro de un archivo de prueba y
 * los demás las encontraban solo porque, en serie, Pest carga todos los archivos en un mismo proceso. En paralelo cada
 * proceso carga solo sus archivos, así que se definen aquí y `tests/Pest.php` las carga siempre.
 */

use Src\Academico\Dominio\ParametrosDeRiesgo;
use Src\Compartido\Dominio\NumeroDecimal;

function decimal(string $texto): NumeroDecimal
{
    return NumeroDecimal::desdeTexto($texto);
}

function parametrosDePrueba(string $sinRiesgo = '7.50', string $observacion = '7.00', int $reprobadasObservacion = 1, int $reprobadasRiesgo = 2): ParametrosDeRiesgo
{
    return new ParametrosDeRiesgo(
        NumeroDecimal::desdeTexto($sinRiesgo),
        NumeroDecimal::desdeTexto($observacion),
        $reprobadasObservacion,
        $reprobadasRiesgo,
    );
}
