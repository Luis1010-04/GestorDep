<?php

namespace Src\Compartido\Aplicacion;

use Illuminate\Database\Eloquent\Builder as ConsultaEloquent;
use Illuminate\Database\Query\Builder as ConsultaSql;

/**
 * Restringe una consulta a los jugadores que el usuario puede ver (RNF-01, docs/arquitectura.md, 10).
 * Toda consulta de jugadores pasa por aqui; la implementacion SQL vive en Consultas.
 */
interface AlcanceDeJugadores
{
    /**
     * @template TConsulta of ConsultaSql|ConsultaEloquent
     *
     * @param  TConsulta  $consulta  consulta que se restringe; se modifica y se devuelve
     * @param  string  $columnaJugadorId  columna con el id del jugador, con su tabla (por ejemplo, `jugadores.id`)
     * @param  bool  $incluirBajas  si es falso, excluye a los jugadores con baja (RN-23)
     * @param  bool  $soloEntrenador  si es verdadero, solo cuenta la rama del entrenador: las escrituras de plantel (expediente,
     *                                baja, asignar, cambiar posicion) no se amplian a quien solo ve al jugador porque lo ensena (DA-10)
     * @return TConsulta la misma consulta, restringida; las condiciones que se agreguen despues con `orWhere` la rompen
     */
    public function restringir(UsuarioActual $usuario, ConsultaSql|ConsultaEloquent $consulta, string $columnaJugadorId, bool $incluirBajas = false, bool $soloEntrenador = false): ConsultaSql|ConsultaEloquent;
}
