<?php

namespace Src\Rendimiento\Aplicacion;

use RuntimeException;

/**
 * El recurso no existe, es de un equipo que el usuario no entrena, el jugador está fuera de su alcance o el rendimiento
 * lo registró otro entrenador: se responde igual, 404, para no revelarlo (§6.1, RN-14).
 */
final class RecursoNoEncontrado extends RuntimeException {}
