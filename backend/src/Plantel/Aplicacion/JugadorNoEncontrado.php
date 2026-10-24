<?php

namespace Src\Plantel\Aplicacion;

use RuntimeException;

/** El jugador no existe o está fuera del alcance del usuario: se responde igual para no revelar expedientes ajenos (404). */
final class JugadorNoEncontrado extends RuntimeException {}
