<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Competencia\Dominio\Partido;
use Src\Competencia\Dominio\PartidoNoProgramado;
use Src\Competencia\Dominio\RepositorioDePartidos;
use Src\Competencia\Dominio\ValorFueraDeRango;

/**
 * Captura el resultado de un partido programado, que pasa a `jugado` (HU-13, criterio 2). No se corrige ni se borra
 * (§2.3). La habilitación de la captura de rendimiento es de H4.
 */
final class CapturarResultado
{
    public function __construct(
        private readonly RepositorioDePartidos $partidos,
        private readonly BuscarPartidoDelEntrenador $buscarPartido,
        private readonly Reloj $reloj,
    ) {}

    /** @throws RecursoNoEncontrado|PartidoNoProgramado|ValorFueraDeRango */
    public function ejecutar(Identificador $partidoId, int $golesFavor, int $golesContra): Partido
    {
        $anterior = $this->buscarPartido->ejecutar($partidoId);
        $jugado = $anterior->conResultado($golesFavor, $golesContra, Instante::truncadoASegundos($this->reloj->ahora()));
        $this->partidos->actualizar($anterior, $jugado);

        return $jugado;
    }
}
