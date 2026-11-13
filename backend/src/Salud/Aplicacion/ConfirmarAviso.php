<?php

namespace Src\Salud\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Salud\Dominio\AvisoDeLesion;
use Src\Salud\Dominio\AvisoNoPendiente;
use Src\Salud\Dominio\CausaDeLesion;
use Src\Salud\Dominio\EstadoEnElMismoInstante;
use Src\Salud\Dominio\EstadoOficial;
use Src\Salud\Dominio\FechaAnteriorAlInicio;
use Src\Salud\Dominio\Lesion;
use Src\Salud\Dominio\RepositorioDeAvisos;
use Src\Salud\Dominio\RepositorioDeEstados;
use Src\Salud\Dominio\RepositorioDeLesiones;
use Src\Salud\Dominio\TipoDeEstado;

/**
 * Confirma un aviso como estado oficial (HU-20). Todo en una transacción: crea la lesión si no es `apto`, cierra el estado
 * vigente, sea de quien sea, y abre el nuevo con quien confirma como único registrante (DD-63, §3.4). La lesión anterior
 * se conserva sin fecha de alta, salvo al confirmar `apto` sobre la lesión propia (DA-76). La solicitud HTTP ya comprobó
 * que el usuario entrena al jugador.
 */
final class ConfirmarAviso
{
    public function __construct(
        private readonly RepositorioDeAvisos $avisos,
        private readonly RepositorioDeEstados $estados,
        private readonly RepositorioDeLesiones $lesiones,
        private readonly VisibilidadDeJugador $visibilidad,
        private readonly Transaccion $transaccion,
        private readonly UsuarioActual $usuarioActual,
        private readonly Reloj $reloj,
        private readonly HoyDeNegocio $hoyDeNegocio,
    ) {}

    /**
     * @param  array{estado: string, tipo?: string, zona?: string, causa?: string, fecha_estimada_regreso?: string, nota?: string}  $datos
     *
     * @throws RecursoNoEncontrado|AvisoNoPendiente|JugadorEnBajaSinCambioDeEstado|FechaAnteriorAlInicio|EstadoEnElMismoInstante|EstadoConcurrente
     */
    public function ejecutar(Identificador $avisoId, array $datos): AvisoVisible
    {
        $autor = $this->usuarioActual->identificador() ?? throw new RecursoNoEncontrado;

        return $this->transaccion->ejecutar(function () use ($avisoId, $datos, $autor): AvisoVisible {
            $aviso = $this->avisos->buscarParaActualizar($avisoId) ?? throw new RecursoNoEncontrado;
            $instante = Instante::truncadoASegundos($this->reloj->ahora());
            $confirmado = $aviso->confirmar($autor, $instante);
            if (! $this->visibilidad->esVisible($aviso->jugadorId(), incluirBajas: false)) {
                throw new JugadorEnBajaSinCambioDeEstado;
            }

            $tipo = TipoDeEstado::from($datos['estado']);
            $lesion = $tipo === TipoDeEstado::Apto ? null : self::lesionDe($aviso, $autor, $datos, $instante);

            $vigente = $this->estados->vigenteParaActualizar($aviso->jugadorId());
            if ($vigente !== null) {
                if ($tipo === TipoDeEstado::Apto) {
                    $this->cerrarLesionPropia($vigente, $autor, $instante);
                }
                $this->estados->guardarCambio($vigente, $vigente->cerrar($instante));
            }

            if ($lesion === null) {
                $estado = EstadoOficial::apto(Identificador::generar(), $aviso->jugadorId(), $autor, $instante);
            } else {
                $this->lesiones->guardarNueva($lesion);
                $estado = EstadoOficial::conLesion(Identificador::generar(), $aviso->jugadorId(), $tipo, $lesion->id(), $lesion->fechaEstimadaRegreso(), $autor, $instante);
            }
            $this->estados->guardarNuevo($estado);
            $this->avisos->guardarConfirmacion($aviso, $confirmado, $lesion?->id());

            return AvisoVisible::para($confirmado, $autor);
        });
    }

    /**
     * Al confirmar `apto`, la lesión abierta del estado vigente se cierra con el hoy de negocio si la registró quien confirma
     * (DA-76, DD-36). La de otro registrante se conserva sin alta (DD-63) y una lesión que ya tenía alta no se toca. Una
     * lesión que empieza después del hoy de negocio tampoco se cierra, y la confirmación sigue (subdecisión de DA-76).
     */
    private function cerrarLesionPropia(EstadoOficial $vigente, Identificador $autor, DateTimeImmutable $instante): void
    {
        $lesionId = $vigente->lesionId();
        $lesion = $lesionId === null ? null : $this->lesiones->buscarParaActualizar($lesionId);
        if ($lesion === null || ! $lesion->esDe($autor) || $lesion->fechaAlta() !== null) {
            return;
        }

        $hoy = $this->hoyDeNegocio->hoy()->format('Y-m-d');
        if ($lesion->fechaInicio() > $hoy) {
            return;
        }

        $this->lesiones->guardarCambio($lesion, $lesion->darDeAlta($hoy, $instante));
    }

    /**
     * La solicitud HTTP exige estos campos si el estado no es `apto` (DD-57).
     *
     * @param  array{estado: string, tipo?: string, zona?: string, causa?: string, fecha_estimada_regreso?: string, nota?: string}  $datos
     */
    private static function lesionDe(AvisoDeLesion $aviso, Identificador $autor, array $datos, DateTimeImmutable $instante): Lesion
    {
        return Lesion::registrar(
            Identificador::generar(), $aviso->jugadorId(), $autor, (string) ($datos['tipo'] ?? ''), (string) ($datos['zona'] ?? ''),
            CausaDeLesion::from((string) ($datos['causa'] ?? '')), $aviso->fechaAviso(), (string) ($datos['fecha_estimada_regreso'] ?? ''),
            $datos['nota'] ?? null, $instante,
        );
    }
}
