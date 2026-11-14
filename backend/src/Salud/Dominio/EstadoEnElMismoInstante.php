<?php

namespace Src\Salud\Dominio;

use RuntimeException;

/**
 * La vigencia guarda segundos y exige `vigente_hasta > vigente_desde`: un estado no se cierra en el segundo en que se
 * abrió. Responde 409 `conflicto` (DA-30); quien lo recibe repite la operación.
 */
final class EstadoEnElMismoInstante extends RuntimeException {}
