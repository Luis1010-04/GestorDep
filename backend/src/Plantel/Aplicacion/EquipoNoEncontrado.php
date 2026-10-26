<?php

namespace Src\Plantel\Aplicacion;

use RuntimeException;

/** El equipo no existe o el usuario no es su entrenador: se responde igual para no revelar equipos ajenos (404). */
final class EquipoNoEncontrado extends RuntimeException {}
