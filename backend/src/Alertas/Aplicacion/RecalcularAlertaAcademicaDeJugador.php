<?php

namespace Src\Alertas\Aplicacion;

use DateTimeImmutable;
use Src\Alertas\Dominio\AlertaAcademica;
use Src\Compartido\Aplicacion\RiesgoAcademicoDelJugador;
use Src\Compartido\Dominio\Identificador;

/**
 * Recalcula la alerta de riesgo de baja académica de un jugador (RN-21). La usan el escucha de los eventos de Académico
 * (DA-88) y el comando diario. El nivel sale del puerto de P-09, no del resumen guardado.
 */
final class RecalcularAlertaAcademicaDeJugador
{
    public function __construct(
        private readonly RiesgoAcademicoDelJugador $riesgo,
        private readonly RepositorioDeParametrosDeAlertas $parametros,
        private readonly AplicarDecisionDeAlerta $aplicar,
    ) {}

    public function ejecutar(Identificador $jugador, DateTimeImmutable $ahora): void
    {
        $riesgo = $this->riesgo->de($jugador);

        $this->aplicar->ejecutar(
            TipoDeAlerta::RiesgoDeBajaAcademica,
            $jugador,
            null,
            AlertaAcademica::seCumple($riesgo?->nivel, $this->parametros->nivelQueActivaLaAlertaAcademica()),
            ['nivel_riesgo' => $riesgo?->nivel, 'promedio' => $riesgo?->promedio, 'materias_reprobadas' => $riesgo->materiasReprobadas ?? 0],
            $ahora,
        );
    }
}
