<?php

namespace Src\Rendimiento\Aplicacion;

use InvalidArgumentException;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Dominio\Reloj;
use Src\Rendimiento\Dominio\CriterioDeEvaluacion;
use Src\Rendimiento\Dominio\PlantillaDeEvaluacion;
use Src\Rendimiento\Dominio\RepositorioDePlantillas;
use Src\Rendimiento\Dominio\RepositorioDeUmbrales;
use Src\Rendimiento\Dominio\UmbralDeRendimiento;
use Src\Rendimiento\Dominio\ValorFueraDeRango;

/**
 * Fija el umbral de un jugador en un equipo para el entrenador de la sesión (HU-15, criterios 2 y 3): con una plantilla
 * visible de su posición, con valores propios o, sin nada, con la plantilla de fábrica de la posición. Cierra el vigente
 * y abre uno nuevo; el primero no cierra nada (v1.0.4 §6.2). En la misma transacción inserta la evaluación con el
 * umbral nuevo, solo para el entrenador que lo cambió (§3.3, §7.3 fila 7).
 */
final class FijarUmbral
{
    public function __construct(
        private readonly ComprobarJugadorDelEquipo $comprobar,
        private readonly RepositorioDeUmbrales $umbrales,
        private readonly RepositorioDePlantillas $plantillas,
        private readonly Reloj $reloj,
        private readonly Transaccion $transaccion,
        private readonly RecalcularEvaluacion $recalcular,
    ) {}

    /**
     * @param  array{plantilla_id?: string, calificacion_minima?: string, ventana_partidos?: int}  $datos  la calificación y la ventana llegan juntas
     *
     * @throws RecursoNoEncontrado|UmbralAmbiguo|PlantillaInexistente|PlantillaDeOtraPosicion|ValorFueraDeRango|UmbralConcurrente|SinUmbralAplicable
     */
    public function ejecutar(Identificador $equipo, Identificador $jugador, array $datos): UmbralAplicable
    {
        ['entrenador' => $entrenador, 'posicion' => $posicion] = $this->comprobar->ejecutar($equipo, $jugador);

        if (isset($datos['plantilla_id'], $datos['calificacion_minima'])) {
            throw new UmbralAmbiguo;
        }

        if (isset($datos['plantilla_id'])) {
            $plantilla = $this->plantillaVisible($datos['plantilla_id'], $entrenador);
            if (! $plantilla->posicionId()->igualA($posicion)) {
                throw new PlantillaDeOtraPosicion;
            }
            [$plantillaId, $criterio] = [$plantilla->id(), $plantilla->criterio()];
        } elseif (isset($datos['calificacion_minima'], $datos['ventana_partidos'])) {
            [$plantillaId, $criterio] = [null, new CriterioDeEvaluacion(NumeroDecimal::desdeTexto($datos['calificacion_minima']), $datos['ventana_partidos'])];
        } else {
            $plantilla = $this->plantillas->deFabricaDe($posicion) ?? throw new SinUmbralAplicable;
            [$plantillaId, $criterio] = [$plantilla->id(), $plantilla->criterio()];
        }

        $nuevo = new UmbralDeRendimiento(
            Identificador::generar(), $jugador, $equipo, $entrenador, $plantillaId, $criterio, Instante::truncadoASegundos($this->reloj->ahora()),
        );
        $this->transaccion->ejecutar(function () use ($jugador, $equipo, $entrenador, $nuevo): void {
            $this->umbrales->reemplazar($this->umbrales->vigenteDe($jugador, $equipo, $entrenador), $nuevo);
            $this->recalcular->ejecutar($jugador, $equipo, $entrenador);
        });

        return UmbralAplicable::desdeUmbral($nuevo);
    }

    /** @throws PlantillaInexistente si no existe, es de otro entrenador o su identificador no es UUID v7 */
    private function plantillaVisible(string $texto, Identificador $entrenador): PlantillaDeEvaluacion
    {
        try {
            $id = Identificador::desdeTexto($texto);
        } catch (InvalidArgumentException) {
            throw new PlantillaInexistente;
        }

        return $this->plantillas->buscarVisible($id, $entrenador) ?? throw new PlantillaInexistente;
    }
}
