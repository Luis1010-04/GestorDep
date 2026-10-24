<?php

namespace Src\Plantel\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\Plantel\Aplicacion\JugadorParaAsignar;
use Src\Plantel\Aplicacion\MatriculaDuplicada;
use Src\Plantel\Dominio\Jugador;
use Src\Plantel\Dominio\RepositorioDeJugadores;

/** Escribe `jugadores` y `jugador_equipo`, cada uno con su historial, todo en una transacción (docs/arquitectura.md, sección 9.2). */
final class RepositorioDeJugadoresEloquent implements RepositorioDeJugadores
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
        private readonly HoyDeNegocio $hoyDeNegocio,
    ) {}

    /** Compara con `lower()` para usar el índice `uq_jugadores_matricula`. */
    public function existeMatricula(string $matricula): bool
    {
        return $this->conexion->table('jugadores')
            ->whereNull('eliminado_en')
            ->whereRaw('lower(matricula) = lower(?)', [$matricula])
            ->exists();
    }

    /** Compara con `lower()` para usar el índice `uq_jugadores_matricula`. */
    public function buscarParaAsignar(string $matricula): ?JugadorParaAsignar
    {
        $fila = $this->conexion->table('jugadores')
            ->whereNull('eliminado_en')
            ->whereRaw('lower(matricula) = lower(?)', [$matricula])
            ->first(['id', 'estatus_deportivo']);

        return $fila === null ? null : new JugadorParaAsignar(Identificador::desdeTexto($fila->id), $fila->estatus_deportivo === 'baja');
    }

    public function guardarNuevo(Jugador $jugador, Identificador $equipoId, Identificador $posicionId): void
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $momento = $jugador->actualizadoEn();
        $instante = $momento->format('Y-m-d H:i:sP');
        $desde = $this->hoyDeNegocio->hoy()->format('Y-m-d');
        $nacimiento = $jugador->fechaNacimiento()->format('Y-m-d');
        $membresiaId = Identificador::generar();

        try {
            $this->conexion->transaction(function () use ($momento, $jugador, $equipoId, $posicionId, $autor, $instante, $desde, $nacimiento, $membresiaId): void {
                $this->historial->registrar(
                    'jugadores',
                    'jugador_id',
                    $jugador->id(),
                    'crear',
                    null,
                    [
                        'nombres' => $jugador->nombres(),
                        'apellidos' => $jugador->apellidos(),
                        'fecha_nacimiento' => $nacimiento,
                        'matricula' => $jugador->matricula(),
                        'estatus_academico' => $jugador->estatusAcademico(),
                        'estatus_deportivo' => $jugador->estatusDeportivo(),
                    ],
                    fn () => $this->conexion->table('jugadores')->insert([
                        'id' => $jugador->id()->valor(),
                        'nombres' => $jugador->nombres(),
                        'apellidos' => $jugador->apellidos(),
                        'fecha_nacimiento' => $nacimiento,
                        'matricula' => $jugador->matricula(),
                        'estatus_academico' => $jugador->estatusAcademico(),
                        'estatus_deportivo' => $jugador->estatusDeportivo(),
                        'creado_en' => $instante,
                        'actualizado_en' => $instante,
                        'creado_por' => $autor,
                        'actualizado_por' => $autor,
                    ]),
                    instante: $momento,
                );

                $this->historial->registrar(
                    'jugador_equipo',
                    'jugador_equipo_id',
                    $membresiaId,
                    'crear',
                    null,
                    [
                        'jugador_id' => $jugador->id()->valor(),
                        'equipo_id' => $equipoId->valor(),
                        'posicion_id' => $posicionId->valor(),
                        'desde' => $desde,
                        'hasta' => null,
                    ],
                    fn () => $this->conexion->table('jugador_equipo')->insert([
                        'id' => $membresiaId->valor(),
                        'jugador_id' => $jugador->id()->valor(),
                        'equipo_id' => $equipoId->valor(),
                        'posicion_id' => $posicionId->valor(),
                        'desde' => $desde,
                        'creado_en' => $instante,
                        'actualizado_en' => $instante,
                        'creado_por' => $autor,
                        'actualizado_por' => $autor,
                    ]),
                    instante: $momento,
                );
            });
        } catch (UniqueConstraintViolationException $e) {
            throw str_contains($e->getMessage(), 'uq_jugadores_matricula') ? new MatriculaDuplicada : $e;
        }
    }
}
