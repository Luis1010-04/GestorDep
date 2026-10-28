<?php

namespace Src\Academico\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Academico\Aplicacion\JugadorYaInscrito;
use Src\Academico\Dominio\Inscripcion;
use Src\Academico\Dominio\RepositorioDeInscripciones;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;

/** Escribe `inscripciones` y `inscripciones_historial` en la misma transacción (RNF-02, arquitectura §9). */
final class RepositorioDeInscripcionesEloquent implements RepositorioDeInscripciones
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function existe(Identificador $materiaId, Identificador $jugadorId): bool
    {
        return $this->conexion->table('inscripciones')
            ->whereNull('eliminado_en')
            ->where('materia_id', $materiaId->valor())
            ->where('jugador_id', $jugadorId->valor())
            ->exists();
    }

    public function guardarNueva(Inscripcion $inscripcion): void
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $instante = $inscripcion->actualizadoEn()->format('Y-m-d H:i:sP');
        $datos = ['materia_id' => $inscripcion->materiaId()->valor(), 'jugador_id' => $inscripcion->jugadorId()->valor()];

        try {
            $this->historial->registrar(
                'inscripciones',
                'inscripcion_id',
                $inscripcion->id(),
                'crear',
                null,
                $datos,
                fn () => $this->conexion->table('inscripciones')->insert([
                    'id' => $inscripcion->id()->valor(),
                    ...$datos,
                    'creado_en' => $instante,
                    'actualizado_en' => $instante,
                    'creado_por' => $autor,
                    'actualizado_por' => $autor,
                ]),
                instante: $inscripcion->actualizadoEn(),
            );
        } catch (UniqueConstraintViolationException) {
            throw new JugadorYaInscrito;
        }
    }
}
