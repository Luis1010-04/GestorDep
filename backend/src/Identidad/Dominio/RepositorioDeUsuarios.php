<?php

namespace Src\Identidad\Dominio;

interface RepositorioDeUsuarios
{
    /** La comparación no distingue mayúsculas. */
    public function existeCorreo(string $correo): bool;

    public function existeRol(string $codigo): bool;

    /** Guarda el usuario, sus roles y su fila de historial en una sola transacción. */
    public function guardarNuevo(Usuario $usuario): void;
}
