<?php

namespace Src\CondicionFisica\Aplicacion;

use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;

/**
 * Historial de mediciones o pruebas físicas de un jugador (HU-26; docs/hitos/f1-h7.md §5.3 y §6.2). Quien llama comprueba
 * antes el jugador, como en la ruta de lectura del recurso. Cada entrenador ve solo lo que registró (RN-14). Aplica DA-18
 * a los datos (DA-125): quita `registrado_por`; el campo se omite, no va en nulo.
 */
final class ListarHistorialDeCondicionFisica
{
    private const CAMPOS_OCULTOS = ['registrado_por'];

    public function __construct(
        private readonly ConsultaDeHistorialDeCondicionFisica $consulta,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    /**
     * @return array{entradas: list<EntradaDeHistorialDeCondicionFisica>, total: int}
     *
     * @throws RecursoNoEncontrado
     */
    public function mediciones(Identificador $jugador, int $pagina, int $porPagina): array
    {
        return self::sinCamposDeDa18($this->consulta->mediciones($jugador, $this->lector(), $pagina, $porPagina));
    }

    /**
     * @return array{entradas: list<EntradaDeHistorialDeCondicionFisica>, total: int}
     *
     * @throws RecursoNoEncontrado
     */
    public function pruebas(Identificador $jugador, int $pagina, int $porPagina): array
    {
        return self::sinCamposDeDa18($this->consulta->pruebas($jugador, $this->lector(), $pagina, $porPagina));
    }

    /** @throws RecursoNoEncontrado */
    private function lector(): Identificador
    {
        return $this->usuarioActual->identificador() ?? throw new RecursoNoEncontrado;
    }

    /**
     * @param  array{entradas: list<EntradaDeHistorialDeCondicionFisica>, total: int}  $listado
     * @return array{entradas: list<EntradaDeHistorialDeCondicionFisica>, total: int}
     */
    private static function sinCamposDeDa18(array $listado): array
    {
        return [
            'entradas' => array_map(
                fn (EntradaDeHistorialDeCondicionFisica $entrada) => $entrada->conDatos(
                    self::filtrar($entrada->datosAnteriores),
                    self::filtrar($entrada->datosNuevos),
                ),
                $listado['entradas'],
            ),
            'total' => $listado['total'],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $datos
     * @return array<string, mixed>|null
     */
    private static function filtrar(?array $datos): ?array
    {
        if ($datos === null) {
            return null;
        }

        foreach (self::CAMPOS_OCULTOS as $campo) {
            unset($datos[$campo]);
        }

        return $datos;
    }
}
