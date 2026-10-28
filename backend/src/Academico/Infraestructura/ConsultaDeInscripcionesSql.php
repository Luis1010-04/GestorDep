<?php

namespace Src\Academico\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Src\Academico\Aplicacion\ConsultaDeInscripciones;
use Src\Academico\Aplicacion\InscripcionConJugador;
use Src\Compartido\Aplicacion\AlcanceDeJugadores;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use stdClass;

/** Lecturas de inscripciones con su jugador: `AlcanceDeJugadores::restringir()` va al final de las condiciones (RNF-01). */
final class ConsultaDeInscripcionesSql implements ConsultaDeInscripciones
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly AlcanceDeJugadores $alcance,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function buscar(Identificador $inscripcionId): ?InscripcionConJugador
    {
        $fila = $this->visibles()->where('inscripciones.id', $inscripcionId->valor())->first($this->columnas());

        return $fila === null ? null : $this->aInscripcion($fila);
    }

    public function listarDeLaMateria(Identificador $materiaId, int $pagina, int $porPagina): array
    {
        $filas = $this->visibles()
            ->where('inscripciones.materia_id', $materiaId->valor())
            ->orderBy('jugadores.apellidos')
            ->orderBy('jugadores.nombres')
            ->orderBy('inscripciones.id')
            ->offset(($pagina - 1) * $porPagina)
            ->limit($porPagina)
            ->get($this->columnas())
            ->map(fn (stdClass $fila): InscripcionConJugador => $this->aInscripcion($fila))
            ->all();

        return array_values($filas);
    }

    public function contarDeLaMateria(Identificador $materiaId): int
    {
        return $this->visibles()->where('inscripciones.materia_id', $materiaId->valor())->count();
    }

    private function visibles(): Builder
    {
        $consulta = $this->conexion->table('inscripciones')
            ->join('jugadores', 'jugadores.id', '=', 'inscripciones.jugador_id')
            ->whereNull('inscripciones.eliminado_en')
            ->whereNull('jugadores.eliminado_en');

        /** @var Builder */
        return $this->alcance->restringir($this->usuarioActual, $consulta, 'jugadores.id');
    }

    /** @return list<string> */
    private function columnas(): array
    {
        return [
            'inscripciones.id', 'inscripciones.materia_id', 'inscripciones.actualizado_en',
            'jugadores.id as jugador_id', 'jugadores.nombres', 'jugadores.apellidos', 'jugadores.matricula',
        ];
    }

    private function aInscripcion(stdClass $fila): InscripcionConJugador
    {
        return new InscripcionConJugador(
            Identificador::desdeTexto($fila->id),
            Identificador::desdeTexto($fila->materia_id),
            Identificador::desdeTexto($fila->jugador_id),
            $fila->nombres,
            $fila->apellidos,
            $fila->matricula,
            (new DateTimeImmutable($fila->actualizado_en))->setTimezone(new DateTimeZone('UTC')),
        );
    }
}
