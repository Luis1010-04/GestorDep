<?php

namespace Src\CondicionFisica\Aplicacion;

use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\CondicionFisica\Dominio\MedicionCorporal;
use Src\CondicionFisica\Dominio\RepositorioDeMediciones;

/** Las mediciones del jugador que registró el usuario, por periodo ascendente (DD-59, DA-06). La solicitud HTTP ya comprobó el alcance. */
final class ListarMediciones
{
    public function __construct(
        private readonly RepositorioDeMediciones $mediciones,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    /**
     * @return array{elementos: list<MedicionCorporal>, total: int}
     *
     * @throws RecursoNoEncontrado
     */
    public function ejecutar(Identificador $jugadorId, int $pagina, int $porPagina): array
    {
        $usuario = $this->usuarioActual->identificador() ?? throw new RecursoNoEncontrado;

        return $this->mediciones->delRegistrante($jugadorId, $usuario, $pagina, $porPagina);
    }
}
