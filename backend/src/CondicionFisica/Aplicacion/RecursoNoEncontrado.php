<?php

namespace Src\CondicionFisica\Aplicacion;

use RuntimeException;

/** El jugador no existe o queda fuera del alcance de entrenador del usuario: se responde igual, 404 (§6.1, RNF-01). */
final class RecursoNoEncontrado extends RuntimeException {}
