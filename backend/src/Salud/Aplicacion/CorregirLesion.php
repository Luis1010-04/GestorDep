<?php

namespace Src\Salud\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Salud\Dominio\Lesion;
use Src\Salud\Dominio\RepositorioDeLesiones;

/**
 * Corrige tipo, zona, causa o nota (RN-16). La fecha estimada no se cambia aquí (DA-02). Sin cambios reales no escribe
 * nada (plan de T03). Se permite con el jugador en baja (DA-12). La solicitud HTTP ya comprobó que el usuario la registró.
 */
final class CorregirLesion
{
    public function __construct(
        private readonly RepositorioDeLesiones $lesiones,
        private readonly Transaccion $transaccion,
        private readonly Reloj $reloj,
    ) {}

    /**
     * @param  array{tipo?: string, zona?: string, causa?: string, nota?: string}  $cambios
     *
     * @throws RecursoNoEncontrado
     */
    public function ejecutar(Identificador $lesionId, array $cambios): Lesion
    {
        return $this->transaccion->ejecutar(function () use ($lesionId, $cambios): Lesion {
            $lesion = $this->lesiones->buscarParaActualizar($lesionId) ?? throw new RecursoNoEncontrado;
            $corregida = $lesion->corregir($cambios, Instante::truncadoASegundos($this->reloj->ahora()));
            if ($lesion->tieneElMismoDetalleQue($corregida)) {
                return $lesion;
            }

            $this->lesiones->guardarCambio($lesion, $corregida);

            return $corregida;
        });
    }
}
