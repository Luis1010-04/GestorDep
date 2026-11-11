<?php

namespace Src\CondicionFisica\Aplicacion;

use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\CondicionFisica\Dominio\PruebaFisica;
use Src\CondicionFisica\Dominio\RepositorioDePruebasFisicas;

/** Las pruebas del jugador que registró el usuario, opcionalmente de un tipo (DD-59, HU-18 c2). La solicitud HTTP ya comprobó el alcance. */
final class ListarPruebas
{
    public function __construct(
        private readonly RepositorioDePruebasFisicas $pruebas,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    /**
     * @return array{elementos: list<PruebaFisica>, total: int}
     *
     * @throws RecursoNoEncontrado
     */
    public function ejecutar(Identificador $jugadorId, ?Identificador $tipoPruebaId, int $pagina, int $porPagina): array
    {
        $usuario = $this->usuarioActual->identificador() ?? throw new RecursoNoEncontrado;

        return $this->pruebas->delRegistrante($jugadorId, $usuario, $tipoPruebaId, $pagina, $porPagina);
    }
}
