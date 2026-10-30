<?php

namespace Src\Rendimiento\Aplicacion;

/** El catálogo de métricas activas con sus posiciones (HU-14), para mostrar la captura. */
interface ConsultaDeMetricas
{
    /** @return list<MetricaDelCatalogo> ordenadas por código */
    public function activas(): array;
}
