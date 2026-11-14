<?php

namespace Src\CondicionFisica\Aplicacion;

use RuntimeException;

/** Dos registros simultáneos de la misma prueba chocaron con el índice único del periodo: 409 `conflicto` (§6.3, DA-64). */
final class PruebaConcurrente extends RuntimeException {}
