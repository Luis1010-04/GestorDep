<?php

namespace Src\Salud\Aplicacion;

use RuntimeException;

/**
 * El jugador o el aviso no existen o quedan fuera del alcance del usuario: se responde igual, 404, para no revelarlo
 * (§6.1, RNF-01). Vale también para un docente que creó un aviso y perdió el alcance sobre el jugador (opción A).
 */
final class RecursoNoEncontrado extends RuntimeException {}
