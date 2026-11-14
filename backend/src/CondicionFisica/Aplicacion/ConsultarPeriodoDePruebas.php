<?php

namespace Src\CondicionFisica\Aplicacion;

use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\CondicionFisica\Dominio\CatalogoDeTiposDePrueba;
use Src\CondicionFisica\Dominio\PruebaFisica;
use Src\CondicionFisica\Dominio\RepositorioDePruebasFisicas;
use Src\CondicionFisica\Dominio\TipoDePrueba;

/**
 * La vista de un periodo: un elemento por tipo, por código ascendente, con la prueba que registró el usuario o nula
 * (HU-18 c3, DA-63). Con un tipo, solo ese (DA-62). La solicitud HTTP ya comprobó el alcance.
 */
final class ConsultarPeriodoDePruebas
{
    public function __construct(
        private readonly CatalogoDeTiposDePrueba $catalogo,
        private readonly RepositorioDePruebasFisicas $pruebas,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    /**
     * @return list<array{tipo: TipoDePrueba, prueba: PruebaFisica|null}>
     *
     * @throws RecursoNoEncontrado
     */
    public function ejecutar(Identificador $jugadorId, string $periodo, ?Identificador $tipoPruebaId): array
    {
        $usuario = $this->usuarioActual->identificador() ?? throw new RecursoNoEncontrado;
        $porTipo = [];
        foreach ($this->pruebas->delPeriodo($jugadorId, $usuario, $periodo) as $prueba) {
            $porTipo[$prueba->tipoPruebaId()->valor()] = $prueba;
        }

        $vista = [];
        foreach ($this->catalogo->todos() as $tipo) {
            if ($tipoPruebaId === null || $tipo->id()->igualA($tipoPruebaId)) {
                $vista[] = ['tipo' => $tipo, 'prueba' => $porTipo[$tipo->id()->valor()] ?? null];
            }
        }

        return $vista;
    }
}
