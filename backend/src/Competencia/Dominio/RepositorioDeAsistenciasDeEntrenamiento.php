<?php

namespace Src\Competencia\Dominio;

use Src\Compartido\Dominio\Identificador;

/** Persistencia de la asistencia a entrenamientos. No se borra; cada alta y cada corrección escriben su historial. */
interface RepositorioDeAsistenciasDeEntrenamiento
{
    /** @param  list<Identificador>  $jugadores */
    public function hayAlgunaDe(Identificador $entrenamientoId, array $jugadores): bool;

    /**
     * Guarda todos o ninguno, cada uno con su historial, en una transacción. Si otra captura registró a alguno entre
     * la comprobación y la escritura, lanza AsistenciaYaRegistrada (Aplicacion).
     *
     * @param  list<AsistenciaDeEntrenamiento>  $asistencias
     */
    public function guardarNuevas(array $asistencias): void;

    public function buscar(Identificador $id): ?AsistenciaDeEntrenamiento;

    public function actualizar(AsistenciaDeEntrenamiento $anterior, AsistenciaDeEntrenamiento $nueva): void;

    /** @return list<AsistenciaDeEntrenamiento> */
    public function listarDelEntrenamiento(Identificador $entrenamientoId, int $pagina, int $porPagina): array;

    public function contarDelEntrenamiento(Identificador $entrenamientoId): int;
}
