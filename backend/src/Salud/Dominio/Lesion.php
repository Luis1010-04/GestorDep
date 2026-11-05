<?php

namespace Src\Salud\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/**
 * Detalle de una lesión, visible solo para quien la registró (RN-15). `fechaEstimadaRegreso` es la de registro y no cambia
 * después: la vigente vive en el estado oficial (DA-02). `fechaInicio` es la fecha del aviso confirmado (DA-17).
 */
final class Lesion
{
    public function __construct(
        private readonly Identificador $id,
        private readonly Identificador $jugadorId,
        private readonly Identificador $registradaPor,
        private readonly string $tipo,
        private readonly string $zona,
        private readonly CausaDeLesion $causa,
        private readonly string $fechaInicio,
        private readonly string $fechaEstimadaRegreso,
        private readonly ?string $fechaAlta,
        private readonly ?string $nota,
        private readonly DateTimeImmutable $creadoEn,
        private readonly DateTimeImmutable $actualizadoEn,
    ) {}

    /** @throws FechaAnteriorAlInicio */
    public static function registrar(
        Identificador $id,
        Identificador $jugadorId,
        Identificador $registradaPor,
        string $tipo,
        string $zona,
        CausaDeLesion $causa,
        string $fechaInicio,
        string $fechaEstimadaRegreso,
        ?string $nota,
        DateTimeImmutable $instante,
    ): self {
        $lesion = new self($id, $jugadorId, $registradaPor, $tipo, $zona, $causa, $fechaInicio, $fechaEstimadaRegreso, null, $nota, $instante, $instante);
        $lesion->comprobarFechaDeRegreso($fechaEstimadaRegreso);

        return $lesion;
    }

    /** @throws FechaAnteriorAlInicio */
    public function comprobarFechaDeRegreso(string $fecha): void
    {
        $this->exigirDesdeElInicio($fecha);
    }

    /** @throws FechaAnteriorAlInicio */
    public function darDeAlta(string $fechaAlta, DateTimeImmutable $instante): self
    {
        $this->exigirDesdeElInicio($fechaAlta);

        return new self(
            $this->id, $this->jugadorId, $this->registradaPor, $this->tipo, $this->zona, $this->causa, $this->fechaInicio,
            $this->fechaEstimadaRegreso, $fechaAlta, $this->nota, $this->creadoEn, $instante,
        );
    }

    /** @param  array{tipo?: string, zona?: string, causa?: string, nota?: string}  $cambios  solo lo enviado */
    public function corregir(array $cambios, DateTimeImmutable $instante): self
    {
        return new self(
            $this->id, $this->jugadorId, $this->registradaPor,
            $cambios['tipo'] ?? $this->tipo,
            $cambios['zona'] ?? $this->zona,
            isset($cambios['causa']) ? CausaDeLesion::from($cambios['causa']) : $this->causa,
            $this->fechaInicio, $this->fechaEstimadaRegreso, $this->fechaAlta,
            $cambios['nota'] ?? $this->nota,
            $this->creadoEn, $instante,
        );
    }

    public function tieneElMismoDetalleQue(self $otra): bool
    {
        return $this->tipo === $otra->tipo && $this->zona === $otra->zona && $this->causa === $otra->causa && $this->nota === $otra->nota;
    }

    public function esDe(Identificador $usuario): bool
    {
        return $this->registradaPor->igualA($usuario);
    }

    public function id(): Identificador
    {
        return $this->id;
    }

    public function jugadorId(): Identificador
    {
        return $this->jugadorId;
    }

    public function registradaPor(): Identificador
    {
        return $this->registradaPor;
    }

    public function tipo(): string
    {
        return $this->tipo;
    }

    public function zona(): string
    {
        return $this->zona;
    }

    public function causa(): CausaDeLesion
    {
        return $this->causa;
    }

    public function fechaInicio(): string
    {
        return $this->fechaInicio;
    }

    public function fechaEstimadaRegreso(): string
    {
        return $this->fechaEstimadaRegreso;
    }

    public function fechaAlta(): ?string
    {
        return $this->fechaAlta;
    }

    public function nota(): ?string
    {
        return $this->nota;
    }

    public function creadoEn(): DateTimeImmutable
    {
        return $this->creadoEn;
    }

    public function actualizadoEn(): DateTimeImmutable
    {
        return $this->actualizadoEn;
    }

    /** @throws FechaAnteriorAlInicio */
    private function exigirDesdeElInicio(string $fecha): void
    {
        if ($fecha < $this->fechaInicio) {
            throw new FechaAnteriorAlInicio;
        }
    }
}
