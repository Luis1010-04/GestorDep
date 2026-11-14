<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Aplicacion\EstadoDeDisponibilidadOficial;
use Src\Compartido\Dominio\Identificador;

/**
 * Advertencia `jugador_lesionado` de la asistencia y la convocatoria (HU-11 c2, HU-12 c3, DA-15). Solo `lesionado`
 * advierte; `con_molestia` no. El estado es el vigente al pedirlo, con una sola consulta al puerto de Salud (T04). El
 * mensaje lo pone quien responde, desde `lang/es`; aquí van el código y el campo de la petición (convenciones §4 v1.3).
 * Quien llama ya validó a los jugadores y su alcance, como exige el puerto.
 */
final class AdvertenciasDeLesion
{
    private const CODIGO = 'jugador_lesionado';

    public function __construct(private readonly EstadoDeDisponibilidadOficial $estados) {}

    /**
     * Una advertencia por cada lesionado de los registros, con su posición en la petición desde 0.
     *
     * @param  list<Identificador>  $jugadores  en el orden de `registros`
     * @return list<array{codigo: string, campo: string}>
     */
    public function deAsistencia(array $jugadores): array
    {
        if ($jugadores === []) {
            return [];
        }

        $estados = $this->estados->de($jugadores);
        $advertencias = [];
        foreach ($jugadores as $posicion => $jugador) {
            if (($estados[$jugador->valor()] ?? null) === 'lesionado') {
                $advertencias[] = ['codigo' => self::CODIGO, 'campo' => "registros.{$posicion}.jugador_id"];
            }
        }

        return $advertencias;
    }

    /** @return list<array{codigo: string, campo: string}> */
    public function deConvocatoria(Identificador $jugador): array
    {
        $estados = $this->estados->de([$jugador]);

        return ($estados[$jugador->valor()] ?? null) === 'lesionado' ? [['codigo' => self::CODIGO, 'campo' => 'jugador_id']] : [];
    }
}
