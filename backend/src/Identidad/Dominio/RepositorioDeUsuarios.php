<?php

namespace Src\Identidad\Dominio;

use Src\Compartido\Dominio\Identificador;

interface RepositorioDeUsuarios
{
    /** La comparación no distingue mayúsculas. */
    public function existeCorreo(string $correo): bool;

    public function existeRol(string $codigo): bool;

    /** La comparación no distingue mayúsculas; los usuarios con borrado lógico no se recuperan. */
    public function buscarPorCorreo(string $correo): ?Usuario;

    /** Los usuarios con borrado lógico no se recuperan. */
    public function buscarPorId(Identificador $id): ?Usuario;

    /** Guarda solo `intentos_fallidos` y `bloqueado_hasta`; no escribe historial (DD-33). */
    public function guardarAcceso(Usuario $usuario): void;

    /**
     * Relee al usuario con su fila bloqueada, le aplica `$cambio` y guarda como `guardarAcceso`, todo en una transacción:
     * dos intentos simultáneos no pisan el contador (AB-17). Si `$cambio` lanza, no se guarda nada. Un usuario que ya no
     * existe no se toca.
     *
     * @param  callable(Usuario): void  $cambio
     */
    public function modificarAccesoConCandado(Identificador $id, callable $cambio): void;

    /** Guarda el usuario, sus roles y su fila de historial en una sola transacción. */
    public function guardarNuevo(Usuario $usuario): void;

    /** Guarda el hash nuevo y una fila `actualizar` de historial sin hash (DD-33), en una sola transacción. */
    public function guardarContrasena(Usuario $usuario): void;
}
