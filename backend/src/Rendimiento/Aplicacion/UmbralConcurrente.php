<?php

namespace Src\Rendimiento\Aplicacion;

use RuntimeException;

/** Dos cambios simultáneos del mismo umbral chocaron con el índice del umbral vigente: 409 `conflicto` (DA-27). */
final class UmbralConcurrente extends RuntimeException {}
