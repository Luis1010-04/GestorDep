<?php

namespace Src\Academico\Dominio;

use DateTimeImmutable;
use InvalidArgumentException;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;

/**
 * Calificación capturada de una inscripción (HU-07). Un parcial lleva número de 1 a 10 y la final ninguno; el valor está en
 * la escala de 0 a 10 (RN-01, DD-51). `criterioId` es el criterio vigente al capturar y no cambia después (DD-47).
 * La capa HTTP y el caso de uso rechazan antes cualquier dato fuera de estas reglas: aquí son invariantes.
 */
final class Calificacion
{
    private const PARCIAL_MINIMO = 1;

    private const PARCIAL_MAXIMO = 10;

    public function __construct(
        private readonly Identificador $id,
        private readonly Identificador $inscripcionId,
        private readonly TipoDeCalificacion $tipo,
        private readonly ?int $numeroParcial,
        private readonly NumeroDecimal $valor,
        private readonly Identificador $criterioId,
        private readonly DateTimeImmutable $fecha,
        private readonly DateTimeImmutable $actualizadoEn,
    ) {
        $numeroValido = $tipo === TipoDeCalificacion::Final
            ? $numeroParcial === null
            : $numeroParcial >= self::PARCIAL_MINIMO && $numeroParcial <= self::PARCIAL_MAXIMO;

        if (! $numeroValido || ! EscalaDeCalificaciones::contiene($valor)) {
            throw new InvalidArgumentException('calificacion_invalida');
        }
    }

    public function id(): Identificador
    {
        return $this->id;
    }

    public function inscripcionId(): Identificador
    {
        return $this->inscripcionId;
    }

    public function tipo(): TipoDeCalificacion
    {
        return $this->tipo;
    }

    public function numeroParcial(): ?int
    {
        return $this->numeroParcial;
    }

    public function valor(): NumeroDecimal
    {
        return $this->valor;
    }

    public function criterioId(): Identificador
    {
        return $this->criterioId;
    }

    public function fecha(): DateTimeImmutable
    {
        return $this->fecha;
    }

    public function actualizadoEn(): DateTimeImmutable
    {
        return $this->actualizadoEn;
    }
}
