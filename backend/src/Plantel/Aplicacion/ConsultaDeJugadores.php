<?php

namespace Src\Plantel\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Lecturas de jugadores del usuario actual; toda consulta pasa por `AlcanceDeJugadores` (RNF-01). */
interface ConsultaDeJugadores
{
    /**
     * Excluye a los jugadores en baja (DD-30). `equipoId` se cruza con los equipos del usuario: un equipo ajeno no devuelve nada.
     *
     * @param  'apellidos'|'nombres'  $campoDeOrden  el otro nombre es el segundo criterio; el último es el identificador
     * @return list<JugadorConEquipos>
     */
    public function listar(?Identificador $equipoId, string $campoDeOrden, bool $descendente, int $pagina, int $porPagina): array;

    /** Cuenta con el mismo alcance y filtro que `listar`. */
    public function contar(?Identificador $equipoId): int;

    /** Incluye a los jugadores en baja; nulo si el jugador no existe o no está en el alcance. */
    public function buscar(Identificador $jugadorId): ?JugadorConEquipos;

    /**
     * Como `buscar`, pero con el alcance del entrenador solamente: lo usan las escrituras de plantel (expediente, baja,
     * asignar, cambiar posición). Quien solo ve al jugador porque lo enseña no puede modificarlo (DA-10).
     */
    public function buscarParaEscribir(Identificador $jugadorId): ?JugadorConEquipos;
}
