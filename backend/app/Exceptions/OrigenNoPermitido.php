<?php

namespace App\Exceptions;

use RuntimeException;

/** La solicitud de inicio de sesión no trae un origen de un dominio con estado, así que Sanctum no abrió sesión; se responde 403 (PT-11). */
class OrigenNoPermitido extends RuntimeException {}
