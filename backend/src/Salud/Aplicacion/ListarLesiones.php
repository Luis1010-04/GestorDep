<?php

namespace Src\Salud\Aplicacion;

use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Salud\Dominio\Lesion;
use Src\Salud\Dominio\RepositorioDeLesiones;

/** Las lesiones de un jugador que registró el usuario (RN-15). La solicitud HTTP ya comprobó que lo entrena. */
final class ListarLesiones
{
    public function __construct(
        private readonly RepositorioDeLesiones $lesiones,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    /**
     * @return array{elementos: list<Lesion>, total: int}
     *
     * @throws RecursoNoEncontrado
     */
    public function ejecutar(Identificador $jugadorId, int $pagina, int $porPagina): array
    {
        $usuario = $this->usuarioActual->identificador() ?? throw new RecursoNoEncontrado;

        return $this->lesiones->delRegistrante($jugadorId, $usuario, $pagina, $porPagina);
    }
}
