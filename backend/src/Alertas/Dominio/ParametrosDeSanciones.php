<?php

namespace Src\Alertas\Dominio;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/**
 * Umbrales de la acumulación de sanciones deportivas (RN-09). Los arma la capa de aplicación desde
 * `parametros_institucionales` (`sanciones_deportivas`, DA-100); el dominio solo valida que tengan sentido.
 */
final class ParametrosDeSanciones
{
    /** @throws ReglaDeNegocioViolada si algún umbral es menor que 1 (`parametros_de_sanciones_invalidos`) */
    public function __construct(
        private readonly int $tarjetasAmarillas,
        private readonly int $tarjetasRojas,
        private readonly int $faltasUmbral,
        private readonly int $faltasVentanaDias,
    ) {
        if ($tarjetasAmarillas < 1 || $tarjetasRojas < 1 || $faltasUmbral < 1 || $faltasVentanaDias < 1) {
            throw new ReglaDeNegocioViolada('parametros_de_sanciones_invalidos');
        }
    }

    public function tarjetasAmarillas(): int
    {
        return $this->tarjetasAmarillas;
    }

    public function tarjetasRojas(): int
    {
        return $this->tarjetasRojas;
    }

    public function faltasUmbral(): int
    {
        return $this->faltasUmbral;
    }

    public function faltasVentanaDias(): int
    {
        return $this->faltasVentanaDias;
    }
}
