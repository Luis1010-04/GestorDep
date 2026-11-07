<?php

namespace Src\CondicionFisica\Aplicacion;

use InvalidArgumentException;
use Src\Compartido\Aplicacion\EquiposDelEntrenador;
use Src\Compartido\Aplicacion\PosicionEnEquipo;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Dominio\Reloj;
use Src\CondicionFisica\Dominio\CatalogoDeRangos;
use Src\CondicionFisica\Dominio\EquipoInexistente;
use Src\CondicionFisica\Dominio\MedicionCorporal;
use Src\CondicionFisica\Dominio\MedicionYaRegistrada;
use Src\CondicionFisica\Dominio\NadaQueCorregir;
use Src\CondicionFisica\Dominio\RepositorioDeMediciones;
use Src\CondicionFisica\Dominio\ValorFueraDeRango;

/**
 * Registra una medición o la corrige con confirmación (HU-17, docs/convenciones-api.md §4.1). Tras el 404 y el 422 de la
 * solicitud, el orden es el de DA-38: baja, equipo, rango, nada que corregir, ya registrada y conflicto.
 */
final class RegistrarMedicion
{
    public function __construct(
        private readonly RepositorioDeMediciones $mediciones,
        private readonly CatalogoDeRangos $rangos,
        private readonly VisibilidadDeJugador $visibilidad,
        private readonly EquiposDelEntrenador $equiposDelEntrenador,
        private readonly PosicionEnEquipo $posicionEnEquipo,
        private readonly Transaccion $transaccion,
        private readonly UsuarioActual $usuarioActual,
        private readonly HoyDeNegocio $hoyDeNegocio,
        private readonly Reloj $reloj,
    ) {}

    /**
     * @param  array{equipo_id: string, peso_kg: string, estatura_cm: string, fecha?: string, es_correccion?: bool, motivo?: string}  $datos
     * @return array{medicion: MedicionCorporal, es_nueva: bool}
     *
     * @throws RecursoNoEncontrado|JugadorEnBajaSinMedicion|EquipoInexistente|ValorFueraDeRango|NadaQueCorregir|MedicionYaRegistrada|MedicionConcurrente
     */
    public function ejecutar(Identificador $jugadorId, array $datos): array
    {
        $autor = $this->usuarioActual->identificador() ?? throw new RecursoNoEncontrado;

        return $this->transaccion->ejecutar(function () use ($jugadorId, $datos, $autor): array {
            if (! $this->visibilidad->esVisibleComoEntrenador($jugadorId, incluirBajas: false)) {
                throw new JugadorEnBajaSinMedicion;
            }

            $equipoId = $this->equipoDe($datos['equipo_id'], $jugadorId, $autor);
            $peso = NumeroDecimal::desdeTexto($datos['peso_kg']);
            $estatura = NumeroDecimal::desdeTexto($datos['estatura_cm']);
            // El límite efectivo es el menor entre el máximo del rango y lo que cabe en la columna (AU-09).
            $this->rangos->de('peso_kg')->acotadoA($this->rangos->maximoDeColumna('peso_kg'))->exigir($peso, 'peso_kg');
            $this->rangos->de('estatura_cm')->acotadoA($this->rangos->maximoDeColumna('estatura_cm'))->exigir($estatura, 'estatura_cm');

            $instante = Instante::truncadoASegundos($this->reloj->ahora());
            $fecha = $datos['fecha'] ?? $this->hoyDeNegocio->hoy()->format('Y-m-d');
            $anterior = $this->mediciones->delPeriodoParaActualizar($jugadorId, $autor, substr($fecha, 0, 7));

            if ($datos['es_correccion'] ?? false) {
                if ($anterior === null) {
                    throw new NadaQueCorregir;
                }

                $corregida = $anterior->corregir($equipoId, $fecha, $peso, $estatura, $instante);
                if ($corregida->tieneLosMismosValoresQue($anterior)) {
                    return ['medicion' => $anterior, 'es_nueva' => false];
                }

                $this->mediciones->guardarCorreccion($anterior, $corregida, (string) ($datos['motivo'] ?? ''));

                return ['medicion' => $corregida, 'es_nueva' => false];
            }

            if ($anterior !== null) {
                throw new MedicionYaRegistrada;
            }

            $medicion = MedicionCorporal::registrar(Identificador::generar(), $jugadorId, $equipoId, $autor, $fecha, $peso, $estatura, $instante);
            $this->mediciones->guardarNueva($medicion);

            return ['medicion' => $medicion, 'es_nueva' => true];
        });
    }

    /**
     * Un equipo que el entrenador entrena y donde el jugador tiene membresía vigente (DA-13). Un UUID que no es v7 no
     * identifica a ningún equipo: responde lo mismo.
     *
     * @throws EquipoInexistente
     */
    private function equipoDe(string $texto, Identificador $jugadorId, Identificador $autor): Identificador
    {
        try {
            $equipoId = Identificador::desdeTexto($texto);
        } catch (InvalidArgumentException) {
            throw new EquipoInexistente;
        }

        if (! $this->equiposDelEntrenador->entrena($autor, $equipoId) || $this->posicionEnEquipo->de($jugadorId, $equipoId) === null) {
            throw new EquipoInexistente;
        }

        return $equipoId;
    }
}
