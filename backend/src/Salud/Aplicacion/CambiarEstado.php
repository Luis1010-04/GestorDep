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
use Src\Salud\Dominio\TipoDeEstado;

/**
 * Cambia el estado o la fecha estimada del estado vigente (HU-30 c2, DA-14). Un cambio de estado cierra la fila y abre
 * otra; solo la fecha actualiza la vigente; sin cambios reales no escribe nada (plan de T03). Orden: 409 sin lesión,
 * 403 no registrante, 409 jugador en baja, 422 fecha. La solicitud HTTP ya comprobó que el usuario entrena al jugador.
 */
final class CambiarEstado
{
    public function __construct(
        private readonly RepositorioDeEstados $estados,
        private readonly RepositorioDeLesiones $lesiones,
        private readonly VisibilidadDeJugador $visibilidad,
        private readonly Transaccion $transaccion,
        private readonly UsuarioActual $usuarioActual,
        private readonly Reloj $reloj,
        private readonly HoyDeNegocio $hoyDeNegocio,
    ) {}

    /** @throws RecursoNoEncontrado|SinLesionVigente|NoEsRegistrante|JugadorEnBajaSinCambioDeEstado|FechaAnteriorAlInicio|EstadoEnElMismoInstante|EstadoConcurrente */
    public function ejecutar(Identificador $jugadorId, ?string $estado, ?string $fechaEstimadaRegreso): EstadoVisible
    {
        $autor = $this->usuarioActual->identificador() ?? throw new RecursoNoEncontrado;

        return $this->transaccion->ejecutar(function () use ($jugadorId, $estado, $fechaEstimadaRegreso, $autor): EstadoVisible {
            $vigente = $this->estados->vigenteParaActualizar($jugadorId) ?? throw new SinLesionVigente;
            $vigente->exigirModificablePor($autor);
            if (! $this->visibilidad->esVisible($jugadorId, incluirBajas: false)) {
                throw new JugadorEnBajaSinCambioDeEstado;
            }
            if ($fechaEstimadaRegreso !== null) {
                $this->comprobarFecha($vigente, $fechaEstimadaRegreso);
            }

            $resultado = $this->aplicar($vigente, $estado === null ? $vigente->tipo() : TipoDeEstado::from($estado), $fechaEstimadaRegreso ?? $vigente->fechaEstimadaRegreso());

            return EstadoVisible::para($resultado, $autor, $this->hoyDeNegocio->hoy()->format('Y-m-d'));
        });
    }

    /** @throws RecursoNoEncontrado|FechaAnteriorAlInicio */
    private function comprobarFecha(EstadoOficial $vigente, string $fecha): void
    {
        $lesionId = $vigente->lesionId() ?? throw new SinLesionVigente;
        $lesion = $this->lesiones->buscar($lesionId) ?? throw new RecursoNoEncontrado;
        $lesion->comprobarFechaDeRegreso($fecha);
    }

    /** @throws EstadoEnElMismoInstante|EstadoConcurrente */
    private function aplicar(EstadoOficial $vigente, TipoDeEstado $tipo, ?string $fecha): EstadoOficial
    {
        if ($tipo !== $vigente->tipo()) {
            $instante = Instante::truncadoASegundos($this->reloj->ahora());
            $this->estados->guardarCambio($vigente, $vigente->cerrar($instante));
            $sucesor = $vigente->sucesor(Identificador::generar(), $tipo, $fecha, $instante);
            $this->estados->guardarNuevo($sucesor);

            return $sucesor;
        }

        if ($fecha === null || $fecha === $vigente->fechaEstimadaRegreso()) {
            return $vigente;
        }

        $cambiado = $vigente->conFecha($fecha, Instante::truncadoASegundos($this->reloj->ahora()));
        $this->estados->guardarCambio($vigente, $cambiado);

        return $cambiado;
    }
}
