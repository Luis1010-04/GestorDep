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
use Src\CondicionFisica\Dominio\CatalogoDeTiposDePrueba;
use Src\CondicionFisica\Dominio\EquipoInexistente;
use Src\CondicionFisica\Dominio\NadaQueCorregir;
use Src\CondicionFisica\Dominio\PruebaFisica;
use Src\CondicionFisica\Dominio\PruebaYaRegistrada;
use Src\CondicionFisica\Dominio\RepositorioDePruebasFisicas;
use Src\CondicionFisica\Dominio\TipoDePrueba;
use Src\CondicionFisica\Dominio\TipoPruebaInexistente;
use Src\CondicionFisica\Dominio\ValorFueraDeRango;

/**
 * Registra una prueba física o la corrige con confirmación (HU-18, docs/convenciones-api.md §4.1). Tras el 404 y el 422
 * de la solicitud, el orden es el de DA-58: baja, equipo, tipo, rango, nada que corregir, ya registrada y conflicto.
 * El doble envío del mismo entrenador lo detiene la unicidad de la base (DA-64).
 */
final class RegistrarPrueba
{
    public function __construct(
        private readonly RepositorioDePruebasFisicas $pruebas,
        private readonly CatalogoDeTiposDePrueba $catalogo,
        private readonly VisibilidadDeJugador $visibilidad,
        private readonly EquiposDelEntrenador $equiposDelEntrenador,
        private readonly PosicionEnEquipo $posicionEnEquipo,
        private readonly Transaccion $transaccion,
        private readonly UsuarioActual $usuarioActual,
        private readonly HoyDeNegocio $hoyDeNegocio,
        private readonly Reloj $reloj,
    ) {}

    /**
     * @param  array{equipo_id: string, tipo_prueba_id: string, resultado: string, fecha?: string, es_correccion?: bool, motivo?: string}  $datos
     * @return array{prueba: PruebaFisica, es_nueva: bool}
     *
     * @throws RecursoNoEncontrado|JugadorEnBajaSinPrueba|EquipoInexistente|TipoPruebaInexistente|ValorFueraDeRango|NadaQueCorregir|PruebaYaRegistrada|PruebaConcurrente
     */
    public function ejecutar(Identificador $jugadorId, array $datos): array
    {
        $autor = $this->usuarioActual->identificador() ?? throw new RecursoNoEncontrado;

        return $this->transaccion->ejecutar(function () use ($jugadorId, $datos, $autor): array {
            if (! $this->visibilidad->esVisibleComoEntrenador($jugadorId, incluirBajas: false)) {
                throw new JugadorEnBajaSinPrueba;
            }

            $equipoId = $this->equipoDe($datos['equipo_id'], $jugadorId, $autor);
            $tipo = $this->tipoDe($datos['tipo_prueba_id']);
            $resultado = NumeroDecimal::desdeTexto($datos['resultado']);
            $tipo->exigirResultado($resultado);

            $instante = Instante::truncadoASegundos($this->reloj->ahora());
            $fecha = $datos['fecha'] ?? $this->hoyDeNegocio->hoy()->format('Y-m-d');
            $anterior = $this->pruebas->delPeriodoParaActualizar($jugadorId, $tipo->id(), $autor, substr($fecha, 0, 7));

            if ($datos['es_correccion'] ?? false) {
                if ($anterior === null) {
                    throw new NadaQueCorregir;
                }

                $corregida = $anterior->corregir($equipoId, $fecha, $resultado, $instante);
                if ($corregida->tieneLosMismosValoresQue($anterior)) {
                    return ['prueba' => $anterior, 'es_nueva' => false];
                }

                $this->pruebas->guardarCorreccion($anterior, $corregida, (string) ($datos['motivo'] ?? ''));

                return ['prueba' => $corregida, 'es_nueva' => false];
            }

            if ($anterior !== null) {
                throw new PruebaYaRegistrada;
            }

            $prueba = PruebaFisica::registrar(Identificador::generar(), $jugadorId, $equipoId, $autor, $tipo->id(), $fecha, $resultado, $instante);
            $this->pruebas->guardarNueva($prueba);

            return ['prueba' => $prueba, 'es_nueva' => true];
        });
    }

    /**
     * Un equipo que el entrenador entrena y donde el jugador tiene membresía vigente (DA-13), como en las mediciones.
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

    /**
     * Un UUID que no es v7, un tipo eliminado y uno inexistente responden lo mismo (DA-61).
     *
     * @throws TipoPruebaInexistente
     */
    private function tipoDe(string $texto): TipoDePrueba
    {
        try {
            $tipoId = Identificador::desdeTexto($texto);
        } catch (InvalidArgumentException) {
            throw new TipoPruebaInexistente;
        }

        return $this->catalogo->buscar($tipoId) ?? throw new TipoPruebaInexistente;
    }
}
