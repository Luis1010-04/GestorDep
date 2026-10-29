<?php

namespace Src\Compartido\Aplicacion;

/**
 * Publica un evento de dominio (src/Compartido/Dominio/Eventos/) después de confirmar la transacción en curso
 * (arquitectura §11): dentro de una transacción espera su commit y, si se revierte, nunca se publica.
 */
interface PublicadorDeEventos
{
    public function publicar(object $evento): void;
}
