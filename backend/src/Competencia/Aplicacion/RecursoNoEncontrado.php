<?php

namespace Src\Competencia\Aplicacion;

use RuntimeException;

/** El recurso no existe o es de un equipo que el usuario no entrena: se responde igual, 404, para no revelarlo (§6.1). */
final class RecursoNoEncontrado extends RuntimeException {}
