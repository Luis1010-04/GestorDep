<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Rendimiento\Dominio\CatalogoDeMetricas;
use Src\Rendimiento\Dominio\RendimientoDePartido;
use Src\Rendimiento\Dominio\RepositorioDeRendimientos;

/**
 * Corrige un rendimiento que registró el usuario (HU-14, §3.3). `metricas` reemplaza la lista y se valida contra la
 * posición con la que se capturó. Un rendimiento ajeno da el mismo 404 que uno inexistente (RN-14).
 */
final class CorregirRendimiento
{
    public function __construct(
        private readonly BuscarRendimientoDelRegistrante $buscarRendimiento,
        private readonly CatalogoDeMetricas $catalogo,
        private readonly RepositorioDeRendimientos $rendimientos,
        private readonly Reloj $reloj,
    ) {}

    /**
     * @param  array{minutos?: int, fue_titular?: bool, tarjetas_amarillas?: int, tarjetas_rojas?: int, calificacion_general?: int, observacion?: string|null}  $cambios
     * @param  list<array{metrica_id: string, valor: int}>|null  $metricas  nulo conserva las actuales
     *
     * @throws RecursoNoEncontrado
     */
    public function ejecutar(Identificador $id, array $cambios, ?array $metricas): RendimientoDePartido
    {
        $anterior = $this->buscarRendimiento->ejecutar($id);
        $datos = $anterior->datos()->con($cambios);
        $valores = $metricas === null ? null : $this->catalogo->deLaPosicion($anterior->posicionId())->valores(CapturarRendimiento::entradas($metricas));

        $nuevo = $anterior->corregido($datos, $valores, Instante::truncadoASegundos($this->reloj->ahora()));
        $this->rendimientos->actualizar($anterior, $nuevo);

        return $nuevo;
    }
}
