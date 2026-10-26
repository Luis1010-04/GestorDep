<?php

namespace Src\Plantel\Infraestructura;

use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\Plantel\Aplicacion\Baja;
use Src\Plantel\Aplicacion\JugadorNoEncontrado;
use Src\Plantel\Aplicacion\JugadorParaAsignar;
use Src\Plantel\Aplicacion\JugadorYaEnBaja;
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
        private readonly Reloj $reloj,
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

    public function actualizarExpediente(Identificador $jugadorId, ?string $nombres, ?string $apellidos, ?DateTimeImmutable $fechaNacimiento): void
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $momento = Instante::truncadoASegundos($this->reloj->ahora());

        $this->conexion->transaction(function () use ($jugadorId, $nombres, $apellidos, $fechaNacimiento, $autor, $momento): void {
            $fila = $this->conexion->table('jugadores')
                ->where('id', $jugadorId->valor())
                ->whereNull('eliminado_en')
                ->lockForUpdate()
                ->first();
            if ($fila === null) {
                throw new JugadorNoEncontrado;
            }

            $anteriores = [
                'nombres' => $fila->nombres,
                'apellidos' => $fila->apellidos,
                'fecha_nacimiento' => $fila->fecha_nacimiento,
                'matricula' => $fila->matricula,
                'estatus_academico' => $fila->estatus_academico,
                'estatus_deportivo' => $fila->estatus_deportivo,
            ];
            $nuevos = [
                ...$anteriores,
                'nombres' => $nombres ?? $fila->nombres,
                'apellidos' => $apellidos ?? $fila->apellidos,
                'fecha_nacimiento' => $fechaNacimiento?->format('Y-m-d') ?? $fila->fecha_nacimiento,
            ];
            if ($nuevos === $anteriores) {
                return;
            }

            $this->historial->registrar(
                'jugadores',
                'jugador_id',
                $jugadorId,
                'actualizar',
                $anteriores,
                $nuevos,
                fn () => $this->conexion->table('jugadores')->where('id', $jugadorId->valor())->update([
                    'nombres' => $nuevos['nombres'],
                    'apellidos' => $nuevos['apellidos'],
                    'fecha_nacimiento' => $nuevos['fecha_nacimiento'],
                    'actualizado_en' => $momento->format('Y-m-d H:i:sP'),
                    'actualizado_por' => $autor,
                ]),
                instante: $momento,
            );
        });
    }

    public function darDeBaja(Identificador $jugadorId, string $motivo, DateTimeImmutable $fecha): Baja
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $momento = Instante::truncadoASegundos($this->reloj->ahora());
        $instante = $momento->format('Y-m-d H:i:sP');
        $bajaId = Identificador::generar();
        $fechaTexto = $fecha->format('Y-m-d');

        $this->conexion->transaction(function () use ($jugadorId, $motivo, $fechaTexto, $autor, $momento, $instante, $bajaId): void {
            $fila = $this->conexion->table('jugadores')
                ->where('id', $jugadorId->valor())
                ->whereNull('eliminado_en')
                ->lockForUpdate()
                ->first();
            if ($fila === null) {
                throw new JugadorNoEncontrado;
            }
            if ($fila->estatus_academico === 'baja' || $fila->estatus_deportivo === 'baja') {
                throw new JugadorYaEnBaja;
            }

            $anteriores = [
                'nombres' => $fila->nombres,
                'apellidos' => $fila->apellidos,
                'fecha_nacimiento' => $fila->fecha_nacimiento,
                'matricula' => $fila->matricula,
                'estatus_academico' => $fila->estatus_academico,
                'estatus_deportivo' => $fila->estatus_deportivo,
            ];

            $this->historial->registrar(
                'jugadores',
                'jugador_id',
                $jugadorId,
                'actualizar',
                $anteriores,
                [...$anteriores, 'estatus_academico' => 'baja', 'estatus_deportivo' => 'baja'],
                fn () => $this->conexion->table('jugadores')->where('id', $jugadorId->valor())->update([
                    'estatus_academico' => 'baja',
                    'estatus_deportivo' => 'baja',
                    'actualizado_en' => $instante,
                    'actualizado_por' => $autor,
                ]),
                instante: $momento,
            );

            $this->historial->registrar(
                'bajas_jugador',
                'baja_jugador_id',
                $bajaId,
                'crear',
                null,
                ['jugador_id' => $jugadorId->valor(), 'motivo' => $motivo, 'fecha' => $fechaTexto, 'registrada_por' => $autor],
                fn () => $this->conexion->table('bajas_jugador')->insert([
                    'id' => $bajaId->valor(),
                    'jugador_id' => $jugadorId->valor(),
                    'motivo' => $motivo,
                    'fecha' => $fechaTexto,
                    'registrada_por' => $autor,
                    'creado_en' => $instante,
                    'actualizado_en' => $instante,
                    'creado_por' => $autor,
                    'actualizado_por' => $autor,
                ]),
                instante: $momento,
            );
        });

        return new Baja($jugadorId, $motivo, new DateTimeImmutable($fechaTexto), $momento);
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
