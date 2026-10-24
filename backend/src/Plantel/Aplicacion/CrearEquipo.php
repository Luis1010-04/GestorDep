<?php

namespace Src\Plantel\Aplicacion;

use LogicException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Plantel\Dominio\Equipo;
use Src\Plantel\Dominio\RepositorioDeCategorias;
use Src\Plantel\Dominio\RepositorioDeEquipos;

/** Alta de un equipo (HU-03); el creador queda asignado como entrenador (RN-22). */
final class CrearEquipo
{
    public function __construct(
        private readonly RepositorioDeEquipos $equipos,
        private readonly RepositorioDeCategorias $categorias,
        private readonly UsuarioActual $usuarioActual,
        private readonly Reloj $reloj,
    ) {}

    /** @throws CategoriaInexistente|EquipoDuplicado */
    public function ejecutar(string $nombre, Identificador $categoriaId): EquipoConCategoria
    {
        $entrenador = $this->usuarioActual->identificador() ?? throw new LogicException('usuario_no_identificado');

        $categoria = $this->categorias->buscarVigente($categoriaId) ?? throw new CategoriaInexistente;
        if ($this->equipos->existeEquipo($nombre, $categoriaId)) {
            throw new EquipoDuplicado;
        }

        $equipo = new Equipo(Identificador::generar(), $nombre, $categoriaId, Instante::truncadoASegundos($this->reloj->ahora()));
        $this->equipos->guardarNuevo($equipo, $entrenador);

        return new EquipoConCategoria($equipo, $categoria->nombre());
    }
}
