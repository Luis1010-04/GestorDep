<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Competencia\Dominio\RepositorioDeRivales;
use Src\Competencia\Dominio\Rival;

/** Alta de un rival (HU-28). */
final class CrearRival
{
    public function __construct(
        private readonly RepositorioDeRivales $rivales,
        private readonly Reloj $reloj,
    ) {}

    /** @throws RivalDuplicado */
    public function ejecutar(string $nombre): Rival
    {
        if ($this->rivales->existeNombre($nombre)) {
            throw new RivalDuplicado;
        }

        $rival = new Rival(Identificador::generar(), $nombre, Instante::truncadoASegundos($this->reloj->ahora()));
        $this->rivales->guardarNuevo($rival);

        return $rival;
    }
}
