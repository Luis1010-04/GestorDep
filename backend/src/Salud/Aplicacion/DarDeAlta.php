<?php

namespace Src\Salud\Aplicacion;

use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Salud\Dominio\EstadoEnElMismoInstante;
use Src\Salud\Dominio\EstadoOficial;
use Src\Salud\Dominio\FechaAnteriorAlInicio;
use Src\Salud\Dominio\NoEsRegistrante;
use Src\Salud\Dominio\RepositorioDeEstados;
use Src\Salud\Dominio\RepositorioDeLesiones;
use Src\Salud\Dominio\SinLesionVigente;

/**
 * Da de alta al jugador (HU-30 c1): pone `fecha_alta` en la lesión, cierra el estado vigente y abre uno `apto` con el
 * registrante. Se permite con el jugador en baja (DA-12). Sin fecha, usa el hoy de negocio; con un aviso de fecha futura
 * eso puede dar 422 (punto 6 del plan de T03). La solicitud HTTP ya comprobó que el usuario entrena al jugador.
 */
final class DarDeAlta
{
    public function __construct(
        private readonly RepositorioDeEstados $estados,
        private readonly RepositorioDeLesiones $lesiones,
        private readonly Transaccion $transaccion,
        private readonly UsuarioActual $usuarioActual,
        private readonly Reloj $reloj,
        private readonly HoyDeNegocio $hoyDeNegocio,
    ) {}

    /** @throws RecursoNoEncontrado|SinLesionVigente|NoEsRegistrante|FechaAnteriorAlInicio|EstadoEnElMismoInstante|EstadoConcurrente */
    public function ejecutar(Identificador $jugadorId, ?string $fechaAlta): EstadoVisible
    {
        $autor = $this->usuarioActual->identificador() ?? throw new RecursoNoEncontrado;

        return $this->transaccion->ejecutar(function () use ($jugadorId, $fechaAlta, $autor): EstadoVisible {
            $vigente = $this->estados->vigenteParaActualizar($jugadorId) ?? throw new SinLesionVigente;
            $vigente->exigirModificablePor($autor);
            $lesionId = $vigente->lesionId() ?? throw new SinLesionVigente;
            $lesion = $this->lesiones->buscarParaActualizar($lesionId) ?? throw new RecursoNoEncontrado;

            $instante = Instante::truncadoASegundos($this->reloj->ahora());
            $hoy = $this->hoyDeNegocio->hoy()->format('Y-m-d');
            $conAlta = $lesion->darDeAlta($fechaAlta ?? $hoy, $instante);
            $cerrado = $vigente->cerrar($instante);

            $this->lesiones->guardarCambio($lesion, $conAlta);
            $this->estados->guardarCambio($vigente, $cerrado);
            $apto = EstadoOficial::apto(Identificador::generar(), $jugadorId, $autor, $instante);
            $this->estados->guardarNuevo($apto);

            return EstadoVisible::para($apto, $autor, $hoy);
        });
    }
}
