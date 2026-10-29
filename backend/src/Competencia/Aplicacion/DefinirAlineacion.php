<?php

namespace Src\Competencia\Aplicacion;

use InvalidArgumentException;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Competencia\Dominio\Convocatoria;
use Src\Competencia\Dominio\PartidoNoProgramado;
use Src\Competencia\Dominio\RepositorioDeConvocatorias;
use Src\Competencia\Dominio\RolDeAlineacion;

/**
 * Reemplaza la alineación completa de un partido programado (HU-13, §3.4). Cada jugador debe estar en la convocatoria
 * visible (DA-32); un jugador fuera del alcance da 404 (DA-34). Quien no está en la lista queda sin posición ni rol.
 * Solo las filas que cambian escriben historial.
 */
final class DefinirAlineacion
{
    public function __construct(
        private readonly RepositorioDeConvocatorias $convocatorias,
        private readonly ConsultaDeConvocatoria $consulta,
        private readonly BuscarPartidoDelEntrenador $buscarPartido,
        private readonly VisibilidadDeJugador $visibilidad,
        private readonly Reloj $reloj,
    ) {}

    /**
     * @param  list<array{jugador_id: string, posicion_id: string, rol_alineacion: RolDeAlineacion}>  $alineacion
     * @return list<Convocatoria> la convocatoria visible completa, ya con la alineación
     *
     * @throws RecursoNoEncontrado|PartidoNoProgramado|JugadorNoConvocado|PosicionInexistente
     */
    public function ejecutar(Identificador $partidoId, array $alineacion): array
    {
        $this->buscarPartido->ejecutar($partidoId)->exigirProgramado();

        $convocados = [];
        foreach ($this->consulta->todasDelPartido($partidoId) as $convocatoria) {
            $convocados[$convocatoria->jugadorId()->valor()] = $convocatoria;
        }

        $alineados = [];
        foreach ($alineacion as $indice => $entrada) {
            $convocatoria = $convocados[strtolower($entrada['jugador_id'])] ?? throw $this->noConvocado($entrada['jugador_id'], $indice);
            $alineados[$convocatoria->id()->valor()] = ['posicion' => $this->posicion($entrada['posicion_id']), 'rol' => $entrada['rol_alineacion']];
        }

        $instante = Instante::truncadoASegundos($this->reloj->ahora());
        $cambios = [];
        foreach ($convocados as $convocatoria) {
            $destino = $alineados[$convocatoria->id()->valor()] ?? ['posicion' => null, 'rol' => null];
            if (! $convocatoria->tieneAlineacion($destino['posicion'], $destino['rol'])) {
                $cambios[] = ['anterior' => $convocatoria, 'nueva' => $convocatoria->conAlineacion($destino['posicion'], $destino['rol'], $instante)];
            }
        }
        $this->convocatorias->actualizarAlineacion($cambios);

        return $this->consulta->todasDelPartido($partidoId);
    }

    /**
     * Un jugador fuera del alcance de entrenador sin bajas (inexistente, de otro equipo, en baja aunque haya estado
     * convocado, o con un UUID que no es v7) da el mismo 404; solo un jugador visible que no está convocado da 422 (DA-34).
     */
    private function noConvocado(string $jugadorId, int $indice): RecursoNoEncontrado|JugadorNoConvocado
    {
        try {
            $jugador = Identificador::desdeTexto($jugadorId);
        } catch (InvalidArgumentException) {
            return new RecursoNoEncontrado;
        }

        return $this->visibilidad->esVisibleParaEntrenador($jugador, incluirBajas: false)
            ? new JugadorNoConvocado("alineacion.{$indice}.jugador_id")
            : new RecursoNoEncontrado;
    }

    /** @throws PosicionInexistente si no es un UUID v7; la existencia la comprueba la clave foránea al escribir (DA-31) */
    private function posicion(string $posicionId): Identificador
    {
        try {
            return Identificador::desdeTexto($posicionId);
        } catch (InvalidArgumentException) {
            throw new PosicionInexistente;
        }
    }
}
