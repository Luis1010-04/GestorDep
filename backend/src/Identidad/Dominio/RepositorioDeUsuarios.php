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

    /** Guarda el usuario, sus roles y su fila de historial en una sola transacción. */
    public function guardarNuevo(Usuario $usuario): void;

    /** Guarda el hash nuevo y una fila `actualizar` de historial sin hash (DD-33), en una sola transacción. */
    public function guardarContrasena(Usuario $usuario): void;
}
