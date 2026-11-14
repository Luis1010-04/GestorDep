<?php

namespace Src\CondicionFisica\Dominio;

/** `es_correccion` sin nada registrado en el periodo (docs/convenciones-api.md §4.1). */
final class NadaQueCorregir extends ReglaDeCampoViolada
{
    public function __construct()
    {
        parent::__construct('nada_que_corregir', 'es_correccion');
    }
}
