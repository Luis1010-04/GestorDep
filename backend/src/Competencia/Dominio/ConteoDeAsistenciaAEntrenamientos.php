<?php

namespace Src\Competencia\Dominio;

use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/**
 * Registros de asistencia a entrenamientos realizados (HU-11, §3.4). La falta justificada cuenta en el denominador
 * y no como presencia: presentes ÷ (presentes + faltas + justificadas) × 100, HALF_UP a dos decimales (DD-51).
 */
final class ConteoDeAsistenciaAEntrenamientos
{
    private const ESCALA = 2;

    private const PORCENTAJE_TOTAL = '100';

    /** @throws ReglaDeNegocioViolada si algún conteo es negativo */
    public function __construct(
        private readonly int $presentes,
        private readonly int $faltas,
        private readonly int $faltasJustificadas,
    ) {
        if ($presentes < 0 || $faltas < 0 || $faltasJustificadas < 0) {
            throw new ReglaDeNegocioViolada('conteo_de_asistencia_invalido');
        }
    }

    public function presentes(): int
    {
        return $this->presentes;
    }

    public function faltas(): int
    {
        return $this->faltas;
    }

    public function faltasJustificadas(): int
    {
        return $this->faltasJustificadas;
    }

    /** `null` sin registros. */
    public function porcentaje(): ?NumeroDecimal
    {
        $registros = $this->presentes + $this->faltas + $this->faltasJustificadas;
        if ($registros === 0) {
            return null;
        }

        return NumeroDecimal::desdeTexto((string) $this->presentes)
            ->multiplicar(NumeroDecimal::desdeTexto(self::PORCENTAJE_TOTAL))
            ->dividir(NumeroDecimal::desdeTexto((string) $registros), self::ESCALA);
    }
}
