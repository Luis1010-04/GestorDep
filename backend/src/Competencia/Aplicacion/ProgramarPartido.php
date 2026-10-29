<?php

namespace Src\Competencia\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Competencia\Dominio\CondicionDePartido;
use Src\Competencia\Dominio\EstadoDePartido;
use Src\Competencia\Dominio\Partido;
use Src\Competencia\Dominio\RepositorioDePartidos;
use Src\Competencia\Dominio\TipoDePartido;

/**
 * Alta de un partido programado (HU-12, criterio 1). La temporada se deriva del día local de `inicia_en` (§3.4) y la
 * categoría se toma del equipo (DD-18). La pertenencia del equipo se comprobó antes, en la solicitud (404 antes que 422).
 */
final class ProgramarPartido
{
    public function __construct(
        private readonly RepositorioDePartidos $partidos,
        private readonly ComprobarEquipoEntrenado $comprobarEquipo,
        private readonly ReferenciasDePartido $referencias,
        private readonly Reloj $reloj,
    ) {}

    /** @throws RecursoNoEncontrado|SinTemporada|TorneoObligatorio|TorneoInexistente|TorneoDeOtraTemporada|LugarInexistente|RivalInexistente */
    public function ejecutar(
        Identificador $equipoId,
        DateTimeImmutable $iniciaEn,
        string $lugarId,
        string $rivalId,
        ?string $torneoId,
        TipoDePartido $tipo,
        CondicionDePartido $condicion,
    ): Partido {
        $this->comprobarEquipo->ejecutar($equipoId);
        $temporada = $this->referencias->temporadaDe($iniciaEn);

        $partido = new Partido(
            Identificador::generar(),
            $equipoId,
            $temporada,
            $this->referencias->torneo($tipo, $torneoId, $temporada),
            $this->referencias->rival($rivalId),
            $this->referencias->lugar($lugarId),
            $iniciaEn,
            $tipo,
            $condicion,
            EstadoDePartido::Programado,
            null,
            null,
            Instante::truncadoASegundos($this->reloj->ahora()),
        );
        $this->partidos->guardarNuevo($partido);

        return $partido;
    }
}
