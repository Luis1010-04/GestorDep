<?php

namespace Src\Consultas\Aplicacion;

use Src\Compartido\Aplicacion\EquiposDelEntrenador;
use Src\Compartido\Aplicacion\PorcentajeDeAsistenciaAClases;
use Src\Compartido\Aplicacion\PorcentajeDeAsistenciaAEntrenamientos;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Identificador;

/**
 * Gráficos de historial de un jugador (HU-25, DA-97, DA-105). El entrenador que entrena al jugador recibe lo que él registró
 * (RN-14): rendimiento en partidos, asistencia a entrenamientos, mediciones y el resumen académico simplificado por periodo
 * (RN-13). El docente recibe las calificaciones y la asistencia a clases de sus materias, sin mediciones. Quien no tiene al
 * jugador en su alcance, o lo tiene en baja (RN-23), recibe nulo: la API responde 404 (RNF-01).
 */
final class ConsultarGraficosDelJugador
{
    public function __construct(
        private readonly ConsultaDeEstatus $estatus,
        private readonly ConsultaDeGraficos $graficos,
        private readonly PorcentajeDeAsistenciaAEntrenamientos $asistenciaAEntrenamientos,
        private readonly PorcentajeDeAsistenciaAClases $asistenciaAClases,
        private readonly EquiposDelEntrenador $equipos,
        private readonly UsuarioActual $usuarioActual,
        private readonly ParametroDeDesactualizacion $parametro,
        private readonly HoyDeNegocio $hoyDeNegocio,
    ) {}

    public function ejecutar(Identificador $jugador): ?GraficosDelJugador
    {
        $usuario = $this->usuarioActual->identificador();
        if ($usuario === null || ! $this->estatus->jugadorVisible($jugador)) {
            return null;
        }

        $roles = $this->usuarioActual->roles();
        $marca = new MarcaDeDesactualizacion($this->hoyDeNegocio->hoy(), $this->parametro->dias());
        $serie = fn (DatosDeSerie $datos): SerieDeGrafico => SerieDeGrafico::desde($datos, $marca);

        $equipos = in_array('entrenador', $roles, true) ? $this->equipos->equiposDe($usuario) : [];
        $comoEntrenador = $equipos !== [] && $this->graficos->jugadorEnEquipos($jugador, $equipos);
        $comoDocente = in_array('docente', $roles, true);

        $mediciones = null;
        if ($comoEntrenador) {
            $medidas = $this->graficos->medicionesDelJugador($jugador);
            $mediciones = ['peso_kg' => $serie($medidas['peso_kg']), 'estatura_cm' => $serie($medidas['estatura_cm']), 'imc' => $serie($medidas['imc'])];
        }

        $materias = $comoDocente ? $this->graficos->materiasDelDocente($jugador) : [];

        return new GraficosDelJugador(
            $comoEntrenador ? $serie($this->graficos->rendimientoDelJugador($jugador)) : null,
            $comoEntrenador ? $serie(DatosDeSerie::deAsistencia($this->asistenciaAEntrenamientos->delJugadorEnEquipos($jugador, $equipos))) : null,
            $mediciones,
            $comoEntrenador ? $serie($this->graficos->resumenAcademicoDelJugador($jugador)) : null,
            $comoDocente ? array_map(
                fn (MateriaDelGrafico $materia): SerieDeMateria => new SerieDeMateria($materia, $serie($this->graficos->calificacionesDeLaMateria($jugador, $materia->id))),
                $materias,
            ) : null,
            $comoDocente ? array_map(
                fn (MateriaDelGrafico $materia): SerieDeMateria => new SerieDeMateria($materia, $serie(DatosDeSerie::deAsistencia($this->asistenciaAClases->delJugadorEnLaMateria($jugador, $materia->id)))),
                $materias,
            ) : null,
        );
    }
}
