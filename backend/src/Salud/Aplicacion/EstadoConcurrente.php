<?php

namespace Src\Salud\Aplicacion;

use RuntimeException;

/** Dos escrituras simultáneas chocaron con el índice de un solo estado vigente por jugador: 409 `conflicto` (§6.3). */
final class EstadoConcurrente extends RuntimeException {}
