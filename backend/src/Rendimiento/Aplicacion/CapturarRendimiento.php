<?php

namespace Src\Rendimiento\Aplicacion;

use InvalidArgumentException;
use Src\Compartido\Aplicacion\PosicionEnEquipo;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Dominio\Reloj;
use Src\Rendimiento\Dominio\CatalogoDeMetricas;
use Src\Rendimiento\Dominio\DatosDeJuego;
use Src\Rendimiento\Dominio\MetricaNoAplicaAPosicion;
use Src\Rendimiento\Dominio\RendimientoDePartido;
use Src\Rendimiento\Dominio\RepositorioDeRendimientos;

/**
 * Captura el rendimiento de un jugador convocado a un partido jugado (HU-14, §3.3). Comprueba en este orden (DA-11):
 * partido del entrenador (404), partido jugado (409), jugador en su alcance y sin baja (404), convocado (422), sin
 * captura previa (409), datos de juego y métricas de la posición del jugador en el equipo (422).
 */
final class CapturarRendimiento
{
    public function __construct(
        private readonly BuscarPartidoDelEntrenador $buscarPartido,
        private readonly VisibilidadDeJugador $visibilidad,
        private readonly PosicionEnEquipo $posiciones,
        private readonly CatalogoDeMetricas $catalogo,
        private readonly RepositorioDeRendimientos $rendimientos,
        private readonly UsuarioActual $usuarioActual,
        private readonly Reloj $reloj,
    ) {}

    /**
     * @param  array{jugador_id: string, minutos: int, fue_titular: bool, tarjetas_amarillas: int, tarjetas_rojas: int, calificacion_general: int, observacion: string|null}  $datos
     * @param  list<array{metrica_id: string, valor: int}>  $metricas
     *
     * @throws RecursoNoEncontrado|PartidoNoJugado|JugadorNoConvocado|RendimientoDuplicado
     */
    public function ejecutar(Identificador $partidoId, array $datos, array $metricas): RendimientoDePartido
    {
        $partido = $this->buscarPartido->ejecutar($partidoId);
        if (! $partido->jugado) {
            throw new PartidoNoJugado;
        }

        $jugador = $this->jugadorVisible($datos['jugador_id']);
        if (! $partido->convoca($jugador)) {
            throw new JugadorNoConvocado;
        }
        if ($this->rendimientos->existeDe($partidoId, $jugador)) {
            throw new RendimientoDuplicado;
        }
        $posicion = $this->posiciones->de($jugador, $partido->equipoId) ?? throw new RecursoNoEncontrado;
        $registrante = $this->usuarioActual->identificador() ?? throw new RecursoNoEncontrado;

        $juego = new DatosDeJuego(
            $datos['minutos'], $datos['fue_titular'], $datos['tarjetas_amarillas'], $datos['tarjetas_rojas'],
            $datos['calificacion_general'], $datos['observacion'],
        );
        $valores = $this->catalogo->deLaPosicion($posicion)->valores(self::entradas($metricas));

        $rendimiento = new RendimientoDePartido(
            Identificador::generar(), $partidoId, $jugador, $partido->equipoId, $registrante, $posicion, $juego, $valores,
            Instante::truncadoASegundos($this->reloj->ahora()),
        );
        $this->rendimientos->guardarNuevo($rendimiento);

        return $rendimiento;
    }

    /**
     * Una métrica con identificador que no es UUID v7 no puede ser de la posición (§3.3).
     *
     * @param  list<array{metrica_id: string, valor: int}>  $metricas
     * @return list<array{metrica: Identificador, valor: NumeroDecimal}>
     *
     * @throws MetricaNoAplicaAPosicion
     */
    public static function entradas(array $metricas): array
    {
        $entradas = [];
        foreach ($metricas as $indice => $metrica) {
            try {
                $id = Identificador::desdeTexto($metrica['metrica_id']);
            } catch (InvalidArgumentException) {
                throw new MetricaNoAplicaAPosicion("metricas.$indice.metrica_id");
            }
            $entradas[] = ['metrica' => $id, 'valor' => NumeroDecimal::desdeTexto((string) $metrica['valor'])];
        }

        return $entradas;
    }

    /** @throws RecursoNoEncontrado */
    private function jugadorVisible(string $jugadorId): Identificador
    {
        try {
            $jugador = Identificador::desdeTexto($jugadorId);
        } catch (InvalidArgumentException) {
            throw new RecursoNoEncontrado;
        }

        if (! $this->visibilidad->esVisibleParaEntrenador($jugador, incluirBajas: false)) {
            throw new RecursoNoEncontrado;
        }

        return $jugador;
    }
}
