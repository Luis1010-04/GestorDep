<?php

namespace Src\Plantel\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;
use Src\Plantel\Aplicacion\Baja;
use Src\Plantel\Aplicacion\JugadorParaAsignar;

/** Persistencia de jugadores y de su membresía en un equipo. Solo ve lo no eliminado. */
interface RepositorioDeJugadores
{
    /** La matrícula no distingue mayúsculas; el llamador la entrega ya recortada. */
    public function existeMatricula(string $matricula): bool;

    /**
     * Guarda el jugador y su membresía vigente en el equipo (`desde` es hoy en la zona de la institución), con sus
     * historiales, en una transacción. Si otra petición ganó la carrera por la matrícula, lanza MatriculaDuplicada (Aplicacion).
     */
    public function guardarNuevo(Jugador $jugador, Identificador $equipoId, Identificador $posicionId): void;

    /**
     * Cambia solo los datos recibidos (nulo = no se toca; textos ya recortados) bajo bloqueo de la fila. Sin diferencia real
     * no escribe nada; con ella actualiza la fila y escribe una fila `actualizar` del historial con la instantánea completa
     * (DD-39), con un único instante, en una transacción. Si el jugador no existe, lanza JugadorNoEncontrado (Aplicacion).
     */
    public function actualizarExpediente(Identificador $jugadorId, ?string $nombres, ?string $apellidos, ?DateTimeImmutable $fechaNacimiento): void;

    /**
     * Bajo bloqueo de la fila del jugador crea la baja (motivo ya recortado), cambia ambos estatus a `baja` y escribe el
     * historial `actualizar` del jugador (instantánea completa, DD-39) y el `crear` de la baja, con un único instante, en
     * una transacción. No toca las membresías (RN-17). Lanza JugadorNoEncontrado si no existe y JugadorYaEnBaja si ya está en baja.
     */
    public function darDeBaja(Identificador $jugadorId, string $motivo, DateTimeImmutable $fecha): Baja;

    /** Solo para asignar (DD-31): el llamador nunca entrega el identificador al usuario. Matrícula ya recortada, sin distinguir mayúsculas. */
    public function buscarParaAsignar(string $matricula): ?JugadorParaAsignar;
}
