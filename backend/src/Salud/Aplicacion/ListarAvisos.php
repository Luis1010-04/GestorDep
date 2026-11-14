<?php

namespace Src\Salud\Aplicacion;

use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Salud\Dominio\AvisoDeLesion;
use Src\Salud\Dominio\RepositorioDeAvisos;

/**
 * Los avisos de un jugador que el usuario ya ve (DD-56). Quien lo entrena los ve todos; quien solo le da clase, los que
 * él creó, aunque tenga también el rol de entrenador (§5.3, DA-25).
 */
final class ListarAvisos
{
    public function __construct(
        private readonly RepositorioDeAvisos $avisos,
        private readonly VisibilidadDeJugador $visibilidad,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    /**
     * @return array{elementos: list<AvisoVisible>, total: int}
     *
     * @throws RecursoNoEncontrado
     */
    public function ejecutar(Identificador $jugadorId, int $pagina, int $porPagina): array
    {
        $lector = $this->usuarioActual->identificador() ?? throw new RecursoNoEncontrado;
        $creadoPor = $this->visibilidad->esVisibleComoEntrenador($jugadorId) ? null : $lector;
        $listado = $this->avisos->delJugador($jugadorId, $creadoPor, $pagina, $porPagina);

        return [
            'elementos' => array_map(fn (AvisoDeLesion $aviso) => AvisoVisible::para($aviso, $lector), $listado['elementos']),
            'total' => $listado['total'],
        ];
    }
}
