<?php

namespace App\Exceptions;

use RuntimeException;

/** La aplicacion no alcanza un servicio que necesita; se responde 503 (docs/convenciones-api.md, seccion 2). */
class ServicioNoDisponible extends RuntimeException {}
