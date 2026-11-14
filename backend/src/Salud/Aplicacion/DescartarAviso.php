<?php

namespace Src\Salud\Aplicacion;

use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Salud\Dominio\AvisoNoPendiente;
use Src\Salud\Dominio\RepositorioDeAvisos;

/**
 * Descarta un aviso pendiente (HU-30 c3). La solicitud HTTP ya comprobó que el usuario entrena al jugador. El estado
 * oficial no cambia. Se permite con el jugador en baja, para cerrar lo que quedó abierto (DA-12).
 */
final class DescartarAviso
{
    public function __construct(
        private readonly RepositorioDeAvisos $avisos,
        private readonly Transaccion $transaccion,
        private readonly UsuarioActual $usuarioActual,
        private readonly Reloj $reloj,
    ) {}

    /** @throws RecursoNoEncontrado|AvisoNoPendiente */
    public function ejecutar(Identificador $avisoId, string $notaDescarte): AvisoVisible
    {
        $autor = $this->usuarioActual->identificador() ?? throw new RecursoNoEncontrado;

        return $this->transaccion->ejecutar(function () use ($avisoId, $notaDescarte, $autor): AvisoVisible {
            $aviso = $this->avisos->buscarParaActualizar($avisoId) ?? throw new RecursoNoEncontrado;
            $descartado = $aviso->descartar($autor, $notaDescarte, Instante::truncadoASegundos($this->reloj->ahora()));
            $this->avisos->guardarDescarte($aviso, $descartado);

            return AvisoVisible::para($descartado, $autor);
        });
    }
}
