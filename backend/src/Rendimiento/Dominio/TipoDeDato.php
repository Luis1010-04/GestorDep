<?php

namespace Src\Rendimiento\Dominio;

/** Tipo de dato de una métrica (`metricas.tipo_dato`). */
enum TipoDeDato: string
{
    case Entero = 'entero';
    case Decimal = 'decimal';
}
