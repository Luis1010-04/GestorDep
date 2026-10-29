<?php

namespace Src\Competencia\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;
use Src\Compartido\Dominio\Reloj;
use Src\Competencia\Dominio\RepositorioDeTemporadas;
use Src\Competencia\Dominio\Temporada;

/** Alta de una temporada (HU-28). Se rechaza si se cruza con otra no eliminada, para que cada fecha caiga en a lo más una (DD-07). */
final class CrearTemporada
{
    public function __construct(
        private readonly RepositorioDeTemporadas $temporadas,
        private readonly Reloj $reloj,
    ) {}

    /** @throws ReglaDeNegocioViolada `rango_de_fechas_invalido` | TemporadaDuplicada | TemporadaSolapada */
    public function ejecutar(string $nombre, DateTimeImmutable $fechaInicio, DateTimeImmutable $fechaFin): Temporada
    {
        $temporada = new Temporada(Identificador::generar(), $nombre, $fechaInicio, $fechaFin, Instante::truncadoASegundos($this->reloj->ahora()));

        if ($this->temporadas->existeNombre($nombre)) {
            throw new TemporadaDuplicada;
        }

        $this->temporadas->guardarNueva($temporada);

        return $temporada;
    }
}
