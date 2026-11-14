<?php

namespace Src\Salud\Aplicacion;

use RuntimeException;

/**
 * Dos escrituras simultáneas chocaron con un índice único: el de un solo estado vigente por jugador, o el de la versión
 * del historial de un aviso o de una lesión (respaldo de DA-79). Responde 409 `conflicto` (§6.3).
 */
final class EstadoConcurrente extends RuntimeException {}
