<?php

namespace Src\CondicionFisica\Aplicacion;

use RuntimeException;

/** Dos registros simultáneos chocaron con el índice único del periodo: 409 `conflicto` (§6.3). */
final class MedicionConcurrente extends RuntimeException {}
