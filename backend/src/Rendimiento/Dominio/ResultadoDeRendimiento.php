<?php

namespace Src\Rendimiento\Dominio;

/** Resultado de RN-08: los valores del `CHECK` de `evaluaciones_rendimiento` (§5.1). */
enum ResultadoDeRendimiento: string
{
    case Pasa = 'pasa';
    case NoPasa = 'no_pasa';
    case DatosInsuficientes = 'datos_insuficientes';
}
