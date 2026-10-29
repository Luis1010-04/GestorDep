<?php

namespace Src\Competencia\Aplicacion;

use DateTimeImmutable;
use InvalidArgumentException;
use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\Partido;
use Src\Competencia\Dominio\RepositorioDeLugares;
use Src\Competencia\Dominio\RepositorioDeRivales;
use Src\Competencia\Dominio\RepositorioDeTemporadas;
use Src\Competencia\Dominio\RepositorioDeTorneos;
use Src\Competencia\Dominio\TipoDePartido;

/**
 * Valida las referencias de un partido y deriva su temporada (§3.4). La zona del día local es la de `HoyDeNegocio`
 * (DA-23). Un catálogo eliminado o un id que no es UUID v7 cuentan como inexistentes (DA-24).
 */
final class ReferenciasDePartido
{
    public function __construct(
        private readonly RepositorioDeTemporadas $temporadas,
        private readonly RepositorioDeTorneos $torneos,
        private readonly RepositorioDeLugares $lugares,
        private readonly RepositorioDeRivales $rivales,
        private readonly HoyDeNegocio $hoyDeNegocio,
    ) {}

    /** @throws SinTemporada */
    public function temporadaDe(DateTimeImmutable $iniciaEn): Identificador
    {
        $dia = Partido::diaLocalDe($iniciaEn, $this->hoyDeNegocio->hoy()->getTimezone());

        return $this->temporadas->queContiene($dia) ?? throw new SinTemporada;
    }

    /**
     * Orden de §3.4: obligatorio, inexistente, de otra temporada.
     *
     * @throws TorneoObligatorio|TorneoInexistente|TorneoDeOtraTemporada
     */
    public function torneo(TipoDePartido $tipo, ?string $torneoId, Identificador $temporadaId): ?Identificador
    {
        if ($torneoId === null) {
            if ($tipo->exigeTorneo()) {
                throw new TorneoObligatorio;
            }

            return null;
        }

        $torneo = $this->identificador($torneoId) ?? throw new TorneoInexistente;
        $temporadaDelTorneo = $this->torneos->temporadaDe($torneo) ?? throw new TorneoInexistente;
        if (! $temporadaDelTorneo->igualA($temporadaId)) {
            throw new TorneoDeOtraTemporada;
        }

        return $torneo;
    }

    /** @throws LugarInexistente */
    public function lugar(string $lugarId): Identificador
    {
        $lugar = $this->identificador($lugarId) ?? throw new LugarInexistente;
        if (! $this->lugares->existe($lugar)) {
            throw new LugarInexistente;
        }

        return $lugar;
    }

    /** @throws RivalInexistente */
    public function rival(string $rivalId): Identificador
    {
        $rival = $this->identificador($rivalId) ?? throw new RivalInexistente;
        if (! $this->rivales->existe($rival)) {
            throw new RivalInexistente;
        }

        return $rival;
    }

    private function identificador(string $texto): ?Identificador
    {
        try {
            return Identificador::desdeTexto($texto);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
