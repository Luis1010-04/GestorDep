<?php

namespace Src\Plantel\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Plantel\Dominio\Categoria;
use Src\Plantel\Dominio\RepositorioDeCategorias;

/** Alta de una categoría (HU-28). */
final class CrearCategoria
{
    public function __construct(
        private readonly RepositorioDeCategorias $categorias,
        private readonly Reloj $reloj,
    ) {}

    /** @throws CategoriaDuplicada */
    public function ejecutar(string $nombre): Categoria
    {
        if ($this->categorias->existeNombre($nombre)) {
            throw new CategoriaDuplicada;
        }

        $categoria = new Categoria(Identificador::generar(), $nombre, Instante::truncadoASegundos($this->reloj->ahora()));
        $this->categorias->guardarNueva($categoria);

        return $categoria;
    }
}
