<?php

namespace Src\Academico\Dominio;

use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Cantidades de registros de asistencia de un jugador (RN-05, DD-44). */
final class ConteoDeAsistencia
{
    private const ESCALA = 2;

    private const PORCENTAJE_TOTAL = '100';

    /** @throws ReglaDeNegocioViolada si algún conteo es negativo */
    public function __construct(
        private readonly int $presentes,
        private readonly int $faltasJustificadas,
        private readonly int $faltas,
    ) {
        if ($presentes < 0 || $faltasJustificadas < 0 || $faltas < 0) {
            throw new ReglaDeNegocioViolada('conteo_de_asistencia_invalido');
        }
    }

    public function registros(): int
    {
        return $this->presentes + $this->faltasJustificadas + $this->faltas;
    }

    /** (presentes + justificadas) / registros * 100, con dos decimales; `null` sin registros. */
    public function porcentaje(): ?NumeroDecimal
    {
        if ($this->registros() === 0) {
            return null;
        }

        return NumeroDecimal::desdeTexto((string) ($this->presentes + $this->faltasJustificadas))
            ->multiplicar(NumeroDecimal::desdeTexto(self::PORCENTAJE_TOTAL))
            ->dividir(NumeroDecimal::desdeTexto((string) $this->registros()), self::ESCALA);
    }
}
