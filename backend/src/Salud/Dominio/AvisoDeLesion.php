<?php

namespace Src\Salud\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/**
 * Aviso de molestia o lesión de un jugador (HU-19, RN-16). No cambia el estado oficial: queda pendiente hasta que un
 * entrenador lo confirme o lo descarte. Decide quién ve sus notas: la `nota`, quien la escribió y quien confirmó el aviso
 * (DD-58); la `nota_descarte`, quien descartó y quien creó el aviso (DA-11).
 */
final class AvisoDeLesion
{
    public function __construct(
        private readonly Identificador $id,
        private readonly Identificador $jugadorId,
        private readonly Identificador $creadoPor,
        private readonly bool $reportadoPorJugador,
        private readonly string $nota,
        private readonly EstadoDeAviso $estado,
        private readonly string $fechaAviso,
        private readonly ?Identificador $confirmadoPor,
        private readonly ?Identificador $descartadoPor,
        private readonly ?string $notaDescarte,
        private readonly DateTimeImmutable $creadoEn,
        private readonly DateTimeImmutable $actualizadoEn,
    ) {}

    public static function crear(
        Identificador $id,
        Identificador $jugadorId,
        Identificador $creadoPor,
        bool $reportadoPorJugador,
        string $nota,
        string $fechaAviso,
        DateTimeImmutable $instante,
    ): self {
        return new self($id, $jugadorId, $creadoPor, $reportadoPorJugador, $nota, EstadoDeAviso::Pendiente, $fechaAviso, null, null, null, $instante, $instante);
    }

    /** @throws AvisoNoPendiente */
    public function confirmar(Identificador $autor, DateTimeImmutable $instante): self
    {
        $this->exigirPendiente();

        return new self(
            $this->id, $this->jugadorId, $this->creadoPor, $this->reportadoPorJugador, $this->nota, EstadoDeAviso::Confirmado,
            $this->fechaAviso, $autor, null, null, $this->creadoEn, $instante,
        );
    }

    /** @throws AvisoNoPendiente */
    public function descartar(Identificador $autor, string $nota, DateTimeImmutable $instante): self
    {
        $this->exigirPendiente();

        return new self(
            $this->id, $this->jugadorId, $this->creadoPor, $this->reportadoPorJugador, $this->nota, EstadoDeAviso::Descartado,
            $this->fechaAviso, null, $autor, $nota, $this->creadoEn, $instante,
        );
    }

    public function notaPara(Identificador $lector): ?string
    {
        return $this->esPropioDe($lector) || self::coincide($this->confirmadoPor, $lector) ? $this->nota : null;
    }

    public function notaDescartePara(Identificador $lector): ?string
    {
        return $this->esPropioDe($lector) || self::coincide($this->descartadoPor, $lector) ? $this->notaDescarte : null;
    }

    public function esPropioDe(Identificador $lector): bool
    {
        return $this->creadoPor->igualA($lector);
    }

    public function id(): Identificador
    {
        return $this->id;
    }

    public function jugadorId(): Identificador
    {
        return $this->jugadorId;
    }

    public function creadoPor(): Identificador
    {
        return $this->creadoPor;
    }

    public function reportadoPorJugador(): bool
    {
        return $this->reportadoPorJugador;
    }

    public function nota(): string
    {
        return $this->nota;
    }

    public function estado(): EstadoDeAviso
    {
        return $this->estado;
    }

    public function fechaAviso(): string
    {
        return $this->fechaAviso;
    }

    public function confirmadoPor(): ?Identificador
    {
        return $this->confirmadoPor;
    }

    public function descartadoPor(): ?Identificador
    {
        return $this->descartadoPor;
    }

    public function notaDescarte(): ?string
    {
        return $this->notaDescarte;
    }

    public function creadoEn(): DateTimeImmutable
    {
        return $this->creadoEn;
    }

    public function actualizadoEn(): DateTimeImmutable
    {
        return $this->actualizadoEn;
    }

    /** @throws AvisoNoPendiente */
    private function exigirPendiente(): void
    {
        if ($this->estado !== EstadoDeAviso::Pendiente) {
            throw new AvisoNoPendiente;
        }
    }

    private static function coincide(?Identificador $usuario, Identificador $lector): bool
    {
        return $usuario !== null && $usuario->igualA($lector);
    }
}
