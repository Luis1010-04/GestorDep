<?php

namespace Src\Salud\Aplicacion;

use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;

/**
 * Historial de avisos, lesiones o estados de un jugador (HU-26; docs/hitos/f1-h7.md §5.3 y §6.2). Quien llama comprueba
 * antes el jugador, como en la ruta de lectura del recurso. Aplica DA-18 a los datos (DA-125): quita los autores
 * (`creado_por`, `confirmado_por`, `descartado_por`, `registrada_por`) y deja `lesion_id` solo a quien registró la
 * lesión. Los campos se omiten; no van en nulo.
 */
final class ListarHistorialDeSalud
{
    private const CAMPOS_OCULTOS = ['creado_por', 'confirmado_por', 'descartado_por', 'registrada_por'];

    public function __construct(
        private readonly ConsultaDeHistorialDeSalud $consulta,
        private readonly VisibilidadDeJugador $visibilidad,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    /**
     * @return array{entradas: list<EntradaDeHistorialDeSalud>, total: int}
     *
     * @throws RecursoNoEncontrado
     */
    public function avisos(Identificador $jugador, int $pagina, int $porPagina): array
    {
        $lector = $this->lector();
        $comoEntrenador = $this->visibilidad->esVisibleComoEntrenador($jugador);

        return $this->sinCamposDeDa18($jugador, $lector, $this->consulta->avisos($jugador, $lector, $comoEntrenador, $pagina, $porPagina));
    }

    /**
     * @return array{entradas: list<EntradaDeHistorialDeSalud>, total: int}
     *
     * @throws RecursoNoEncontrado
     */
    public function lesiones(Identificador $jugador, int $pagina, int $porPagina): array
    {
        $lector = $this->lector();

        return $this->sinCamposDeDa18($jugador, $lector, $this->consulta->lesiones($jugador, $lector, $pagina, $porPagina));
    }

    /**
     * @return array{entradas: list<EntradaDeHistorialDeSalud>, total: int}
     *
     * @throws RecursoNoEncontrado
     */
    public function estados(Identificador $jugador, int $pagina, int $porPagina): array
    {
        return $this->sinCamposDeDa18($jugador, $this->lector(), $this->consulta->estados($jugador, $pagina, $porPagina));
    }

    /** @throws RecursoNoEncontrado */
    private function lector(): Identificador
    {
        return $this->usuarioActual->identificador() ?? throw new RecursoNoEncontrado;
    }

    /**
     * @param  array{entradas: list<EntradaDeHistorialDeSalud>, total: int}  $listado
     * @return array{entradas: list<EntradaDeHistorialDeSalud>, total: int}
     */
    private function sinCamposDeDa18(Identificador $jugador, Identificador $lector, array $listado): array
    {
        $lesionesPropias = $this->consulta->lesionesRegistradasPor($jugador, $lector);

        return [
            'entradas' => array_map(
                fn (EntradaDeHistorialDeSalud $entrada) => $entrada->conDatos(
                    self::filtrar($entrada->datosAnteriores, $lesionesPropias),
                    self::filtrar($entrada->datosNuevos, $lesionesPropias),
                ),
                $listado['entradas'],
            ),
            'total' => $listado['total'],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $datos
     * @param  list<string>  $lesionesPropias
     * @return array<string, mixed>|null
     */
    private static function filtrar(?array $datos, array $lesionesPropias): ?array
    {
        if ($datos === null) {
            return null;
        }

        foreach (self::CAMPOS_OCULTOS as $campo) {
            unset($datos[$campo]);
        }
        if (array_key_exists('lesion_id', $datos) && ! in_array($datos['lesion_id'], $lesionesPropias, true)) {
            unset($datos['lesion_id']);
        }

        return $datos;
    }
}
