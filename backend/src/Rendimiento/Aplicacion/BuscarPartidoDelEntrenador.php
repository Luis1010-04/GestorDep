<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Compartido\Aplicacion\ConvocatoriaDePartido;
use Src\Compartido\Aplicacion\EquiposDelEntrenador;
use Src\Compartido\Aplicacion\PartidoConConvocatoria;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;

/** Un partido de un equipo que el usuario entrena; si no existe o es ajeno, 404 antes de validar el cuerpo (§6.1). */
final class BuscarPartidoDelEntrenador
{
    public function __construct(
        private readonly ConvocatoriaDePartido $partidos,
        private readonly EquiposDelEntrenador $equipos,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    /** @throws RecursoNoEncontrado */
    public function ejecutar(Identificador $partidoId): PartidoConConvocatoria
    {
        $partido = $this->partidos->de($partidoId) ?? throw new RecursoNoEncontrado;
        $usuario = $this->usuarioActual->identificador();
        if ($usuario === null || ! $this->equipos->entrena($usuario, $partido->equipoId)) {
            throw new RecursoNoEncontrado;
        }

        return $partido;
    }
}
