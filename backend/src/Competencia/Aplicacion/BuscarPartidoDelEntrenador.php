<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\Partido;
use Src\Competencia\Dominio\RepositorioDePartidos;

/** Un partido de un equipo que el usuario entrena; si no existe o es ajeno, 404 (§6.1). */
final class BuscarPartidoDelEntrenador
{
    public function __construct(
        private readonly RepositorioDePartidos $partidos,
        private readonly ComprobarEquipoEntrenado $comprobarEquipo,
    ) {}

    /** @throws RecursoNoEncontrado */
    public function ejecutar(Identificador $id): Partido
    {
        $partido = $this->partidos->buscar($id) ?? throw new RecursoNoEncontrado;
        $this->comprobarEquipo->ejecutar($partido->equipoId());

        return $partido;
    }
}
