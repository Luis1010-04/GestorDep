<?php

namespace Src\Compartido\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Estado oficial vigente de jugadores, para contextos que no pueden leer las tablas de Salud (docs/hitos/f1-h5.md §8, T04,
 * DA-08). Competencia lo usa para la advertencia de jugador lesionado. El adaptador vive en Salud.
 *
 * No comprueba el alcance: quien llama ya debe haberlo hecho. Solo expone el estado, nunca el detalle de la lesión (RN-15).
 */
interface EstadoDeDisponibilidadOficial
{
    /**
     * Devuelve cada identificador pedido: sin registro, `apto` (DA-01, DA-36). Eso incluye un identificador sin jugador:
     * Salud no puede leer Plantel para distinguirlo, así que quien llama debe haber validado al jugador.
     *
     * @param  list<Identificador>  $jugadores
     * @return array<string, string> `apto`, `con_molestia` o `lesionado`, por identificador del jugador
     */
    public function de(array $jugadores): array;
}
