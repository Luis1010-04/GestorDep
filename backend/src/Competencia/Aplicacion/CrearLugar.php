<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Competencia\Dominio\Lugar;
use Src\Competencia\Dominio\RepositorioDeLugares;

/** Alta de un lugar (HU-28). */
final class CrearLugar
{
    public function __construct(
        private readonly RepositorioDeLugares $lugares,
        private readonly Reloj $reloj,
    ) {}

    /** @throws LugarDuplicado */
    public function ejecutar(string $nombre): Lugar
    {
        if ($this->lugares->existeNombre($nombre)) {
            throw new LugarDuplicado;
        }

        $lugar = new Lugar(Identificador::generar(), $nombre, Instante::truncadoASegundos($this->reloj->ahora()));
        $this->lugares->guardarNuevo($lugar);

        return $lugar;
    }
}
