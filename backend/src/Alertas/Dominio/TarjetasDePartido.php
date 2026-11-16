<?php

namespace Src\Alertas\Dominio;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Tarjetas de un jugador en un partido, con la temporada del partido (RN-09, DA-91). */
final class TarjetasDePartido
{
    /** @throws ReglaDeNegocioViolada si una cantidad es negativa (`tarjetas_invalidas`) */
    public function __construct(
        private readonly string $temporadaId,
        private readonly int $amarillas,
        private readonly int $rojas,
    ) {
        if ($amarillas < 0 || $rojas < 0) {
            throw new ReglaDeNegocioViolada('tarjetas_invalidas');
        }
    }

    /**
     * DA-103: por partido y tipo de tarjeta cuenta el mayor número registrado por un solo entrenador del equipo. Así,
     * una tarjeta que dos entrenadores registran en el mismo partido cuenta una vez. Si los registros no coinciden,
     * se toma el mayor de cada tipo, sin avisar.
     *
     * @param  list<TarjetasRegistradas>  $registros  de un jugador en un equipo, de cualquier entrenador del equipo
     * @return list<self> una entrada por partido, en el orden en que aparece cada partido
     */
    public static function elMayorPorPartido(array $registros): array
    {
        /** @var array<string, array{temporada: string, amarillas: int, rojas: int}> $porPartido */
        $porPartido = [];

        foreach ($registros as $registro) {
            $anterior = $porPartido[$registro->partidoId()] ?? ['temporada' => $registro->temporadaId(), 'amarillas' => 0, 'rojas' => 0];

            $porPartido[$registro->partidoId()] = [
                'temporada' => $anterior['temporada'],
                'amarillas' => max($anterior['amarillas'], $registro->amarillas()),
                'rojas' => max($anterior['rojas'], $registro->rojas()),
            ];
        }

        return array_values(array_map(
            fn (array $partido): self => new self($partido['temporada'], $partido['amarillas'], $partido['rojas']),
            $porPartido,
        ));
    }

    public function temporadaId(): string
    {
        return $this->temporadaId;
    }

    public function amarillas(): int
    {
        return $this->amarillas;
    }

    public function rojas(): int
    {
        return $this->rojas;
    }
}
