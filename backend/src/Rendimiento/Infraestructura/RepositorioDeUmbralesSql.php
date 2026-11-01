<?php

namespace Src\Rendimiento\Infraestructura;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\Rendimiento\Aplicacion\UmbralConcurrente;
use Src\Rendimiento\Dominio\CriterioDeEvaluacion;
use Src\Rendimiento\Dominio\RepositorioDeUmbrales;
use Src\Rendimiento\Dominio\UmbralDeRendimiento;

/**
 * Escribe `umbrales_rendimiento`: el cierre del vigente y el alta del nuevo van en una transacción, cada uno con su
 * historial (HU-15, criterio 3). El historial del cierre guarda la fila completa, así que conserva el valor anterior.
 */
final class RepositorioDeUmbralesSql implements RepositorioDeUmbrales
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function vigenteDe(Identificador $jugador, Identificador $equipo, Identificador $entrenador): ?UmbralDeRendimiento
    {
        $fila = $this->conexion->table('umbrales_rendimiento')
            ->whereNull('eliminado_en')
            ->whereNull('vigente_hasta')
            ->where('jugador_id', $jugador->valor())
            ->where('equipo_id', $equipo->valor())
            ->where('entrenador_id', $entrenador->valor())
            ->first(['id', 'plantilla_id', 'calificacion_minima', 'ventana_partidos', 'vigente_desde']);

        return $fila === null ? null : new UmbralDeRendimiento(
            Identificador::desdeTexto($fila->id),
            $jugador,
            $equipo,
            $entrenador,
            $fila->plantilla_id === null ? null : Identificador::desdeTexto($fila->plantilla_id),
            new CriterioDeEvaluacion(NumeroDecimal::desdeTexto($fila->calificacion_minima), $fila->ventana_partidos),
            (new DateTimeImmutable($fila->vigente_desde))->setTimezone(new DateTimeZone('UTC')),
        );
    }

    public function reemplazar(?UmbralDeRendimiento $anterior, UmbralDeRendimiento $nuevo): void
    {
        $instante = $nuevo->vigenteDesde();
        $momento = $instante->format('Y-m-d H:i:sP');
        $autor = $this->usuarioActual->identificador()?->valor();

        try {
            $this->conexion->transaction(function () use ($anterior, $nuevo, $instante, $momento, $autor): void {
                if ($anterior !== null) {
                    $this->historial->registrar(
                        'umbrales_rendimiento', 'umbral_id', $anterior->id(), 'actualizar', self::instantanea($anterior, null), self::instantanea($anterior, $instante),
                        fn () => $this->conexion->table('umbrales_rendimiento')->where('id', $anterior->id()->valor())->update([
                            'vigente_hasta' => $momento, 'actualizado_en' => $momento, 'actualizado_por' => $autor,
                        ]),
                        instante: $instante,
                    );
                }

                $this->historial->registrar(
                    'umbrales_rendimiento', 'umbral_id', $nuevo->id(), 'crear', null, self::instantanea($nuevo, null),
                    fn () => $this->conexion->table('umbrales_rendimiento')->insert([
                        'id' => $nuevo->id()->valor(), ...self::instantanea($nuevo, null),
                        'creado_en' => $momento, 'actualizado_en' => $momento, 'creado_por' => $autor, 'actualizado_por' => $autor,
                    ]),
                    instante: $instante,
                );
            });
        } catch (UniqueConstraintViolationException) {
            throw new UmbralConcurrente;
        }
    }

    /** @return array{jugador_id: string, equipo_id: string, entrenador_id: string, plantilla_id: string|null, calificacion_minima: string, ventana_partidos: int, vigente_desde: string, vigente_hasta: string|null} */
    private static function instantanea(UmbralDeRendimiento $umbral, ?DateTimeImmutable $vigenteHasta): array
    {
        return [
            'jugador_id' => $umbral->jugadorId()->valor(),
            'equipo_id' => $umbral->equipoId()->valor(),
            'entrenador_id' => $umbral->entrenadorId()->valor(),
            'plantilla_id' => $umbral->plantillaId()?->valor(),
            'calificacion_minima' => $umbral->criterio()->calificacionMinima()->comoCadena(),
            'ventana_partidos' => $umbral->criterio()->ventanaPartidos(),
            'vigente_desde' => $umbral->vigenteDesde()->format(DateTimeInterface::ATOM),
            'vigente_hasta' => $vigenteHasta?->format(DateTimeInterface::ATOM),
        ];
    }
}
