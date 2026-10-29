<?php

namespace Src\Competencia\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Competencia\Dominio\EstadoDePartido;
use Src\Competencia\Dominio\Partido;
use Src\Competencia\Dominio\PartidoNoProgramado;
use Src\Competencia\Dominio\RepositorioDePartidos;
use Src\Competencia\Dominio\TransicionNoPermitida;

/**
 * Cambio de datos o cancelación de un partido (HU-12). Si cambia la fecha se vuelve a derivar la temporada; si
 * cambian la fecha, el tipo o el torneo, se revalida el torneo sobre el resultado final (DA-25). Sin cambio de fecha,
 * el partido conserva su temporada aunque se haya eliminado. Datos y cancelación van en una sola fila de historial.
 */
final class ModificarPartido
{
    public function __construct(
        private readonly RepositorioDePartidos $partidos,
        private readonly BuscarPartidoDelEntrenador $buscar,
        private readonly ReferenciasDePartido $referencias,
        private readonly Reloj $reloj,
    ) {}

    /** @throws RecursoNoEncontrado|PartidoNoProgramado|TransicionNoPermitida|SinTemporada|TorneoObligatorio|TorneoInexistente|TorneoDeOtraTemporada|LugarInexistente|RivalInexistente */
    public function ejecutar(Identificador $id, CambiosDePartido $cambios): Partido
    {
        $anterior = $this->buscar->ejecutar($id);
        $instante = Instante::truncadoASegundos($this->reloj->ahora());
        $nuevo = $anterior;

        if ($cambios->cambiaDatos()) {
            if ($anterior->estado() !== EstadoDePartido::Programado) {
                throw new PartidoNoProgramado;
            }
            $nuevo = $this->conDatos($anterior, $cambios, $instante);
        }
        if ($cambios->cancelar) {
            $nuevo = $nuevo->cancelado($instante);
        }

        $this->partidos->actualizar($anterior, $nuevo);

        return $nuevo;
    }

    private function conDatos(Partido $anterior, CambiosDePartido $cambios, DateTimeImmutable $instante): Partido
    {
        $iniciaEn = $cambios->iniciaEn ?? $anterior->iniciaEn();
        $tipo = $cambios->tipo ?? $anterior->tipo();
        $temporada = $cambios->iniciaEn === null ? $anterior->temporadaId() : $this->referencias->temporadaDe($iniciaEn);
        $torneo = $anterior->torneoId();
        if ($cambios->iniciaEn !== null || $cambios->tipo !== null || $cambios->cambiaTorneo) {
            $torneo = $this->referencias->torneo($tipo, $cambios->cambiaTorneo ? $cambios->torneoId : $torneo?->valor(), $temporada);
        }

        return $anterior->conDatos(
            $temporada,
            $torneo,
            $cambios->rivalId === null ? $anterior->rivalId() : $this->referencias->rival($cambios->rivalId),
            $cambios->lugarId === null ? $anterior->lugarId() : $this->referencias->lugar($cambios->lugarId),
            $iniciaEn,
            $tipo,
            $cambios->condicion ?? $anterior->condicion(),
            $instante,
        );
    }
}
