<?php

namespace Src\Competencia\Aplicacion;

use InvalidArgumentException;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Competencia\Dominio\RepositorioDeTemporadas;
use Src\Competencia\Dominio\RepositorioDeTorneos;
use Src\Competencia\Dominio\Torneo;

/** Alta de un torneo en una temporada no eliminada (HU-28). */
final class CrearTorneo
{
    public function __construct(
        private readonly RepositorioDeTorneos $torneos,
        private readonly RepositorioDeTemporadas $temporadas,
        private readonly Reloj $reloj,
    ) {}

    /** @throws TemporadaInexistente|TorneoDuplicado */
    public function ejecutar(string $nombre, string $temporadaId): Torneo
    {
        try {
            $temporada = Identificador::desdeTexto($temporadaId);
        } catch (InvalidArgumentException) {
            throw new TemporadaInexistente;
        }
        if (! $this->temporadas->existe($temporada)) {
            throw new TemporadaInexistente;
        }
        if ($this->torneos->existeNombre($nombre, $temporada)) {
            throw new TorneoDuplicado;
        }

        $torneo = new Torneo(Identificador::generar(), $nombre, $temporada, Instante::truncadoASegundos($this->reloj->ahora()));
        $this->torneos->guardarNuevo($torneo);

        return $torneo;
    }
}
