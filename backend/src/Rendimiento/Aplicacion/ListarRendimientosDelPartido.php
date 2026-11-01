<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Rendimiento\Dominio\RendimientoDePartido;

/** Los rendimientos de un partido que registró el usuario (HU-14, RN-14); un partido ajeno da 404. */
final class ListarRendimientosDelPartido
{
    public function __construct(
        private readonly BuscarPartidoDelEntrenador $buscarPartido,
        private readonly ConsultaDeRendimientos $consulta,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    /**
     * @return array{elementos: list<RendimientoDePartido>, total: int}
     *
     * @throws RecursoNoEncontrado
     */
    public function ejecutar(Identificador $partidoId, int $pagina, int $porPagina): array
    {
        $this->buscarPartido->ejecutar($partidoId);
        $usuario = $this->usuarioActual->identificador() ?? throw new RecursoNoEncontrado;

        return [
            'elementos' => $this->consulta->delPartidoPor($partidoId, $usuario, $pagina, $porPagina),
            'total' => $this->consulta->contarDelPartidoPor($partidoId, $usuario),
        ];
    }
}
