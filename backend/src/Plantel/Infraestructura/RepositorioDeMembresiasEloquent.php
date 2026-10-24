<?php

namespace Src\Plantel\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\Plantel\Aplicacion\JugadorNoEncontrado;
use Src\Plantel\Aplicacion\JugadorYaEnEquipo;
use Src\Plantel\Dominio\RepositorioDeMembresias;

/** Escribe `jugador_equipo` con su historial en la misma transacción (docs/arquitectura.md, sección 9.2). */
final class RepositorioDeMembresiasEloquent implements RepositorioDeMembresias
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
        private readonly HoyDeNegocio $hoyDeNegocio,
        private readonly Reloj $reloj,
    ) {}

    public function asignar(Identificador $jugadorId, Identificador $equipoId, Identificador $posicionId): void
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $momento = Instante::truncadoASegundos($this->reloj->ahora());
        $instante = $momento->format('Y-m-d H:i:sP');
        $desde = $this->hoyDeNegocio->hoy()->format('Y-m-d');
        $membresiaId = Identificador::generar();

        try {
            $this->historial->registrar(
                'jugador_equipo',
                'jugador_equipo_id',
                $membresiaId,
                'crear',
                null,
                [
                    'jugador_id' => $jugadorId->valor(),
                    'equipo_id' => $equipoId->valor(),
                    'posicion_id' => $posicionId->valor(),
                    'desde' => $desde,
                    'hasta' => null,
                ],
                fn () => $this->conexion->table('jugador_equipo')->insert([
                    'id' => $membresiaId->valor(),
                    'jugador_id' => $jugadorId->valor(),
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
        } catch (UniqueConstraintViolationException $e) {
            throw str_contains($e->getMessage(), 'uq_jugador_equipo_vigente') ? new JugadorYaEnEquipo : $e;
        }
    }

    public function cambiarPosicion(Identificador $jugadorId, Identificador $equipoId, Identificador $posicionId): void
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $momento = Instante::truncadoASegundos($this->reloj->ahora());

        $this->conexion->transaction(function () use ($jugadorId, $equipoId, $posicionId, $autor, $momento): void {
            $fila = $this->conexion->table('jugador_equipo')
                ->where('jugador_id', $jugadorId->valor())
                ->where('equipo_id', $equipoId->valor())
                ->whereNull('hasta')
                ->whereNull('eliminado_en')
                ->lockForUpdate()
                ->first();
            if ($fila === null) {
                throw new JugadorNoEncontrado;
            }
            if ($fila->posicion_id === $posicionId->valor()) {
                return;
            }

            $anteriores = [
                'jugador_id' => $fila->jugador_id,
                'equipo_id' => $fila->equipo_id,
                'posicion_id' => $fila->posicion_id,
                'desde' => $fila->desde,
                'hasta' => $fila->hasta,
            ];

            $this->historial->registrar(
                'jugador_equipo',
                'jugador_equipo_id',
                Identificador::desdeTexto($fila->id),
                'actualizar',
                $anteriores,
                [...$anteriores, 'posicion_id' => $posicionId->valor()],
                fn () => $this->conexion->table('jugador_equipo')->where('id', $fila->id)->update([
                    'posicion_id' => $posicionId->valor(),
                    'actualizado_en' => $momento->format('Y-m-d H:i:sP'),
                    'actualizado_por' => $autor,
                ]),
                instante: $momento,
            );
        });
    }
}
