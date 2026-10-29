<?php

namespace Src\Compartido\Infraestructura;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Aplicacion\PublicadorDeEventos;

final class PublicadorDeEventosLaravel implements PublicadorDeEventos
{
    public function __construct(private readonly Dispatcher $eventos) {}

    public function publicar(object $evento): void
    {
        DB::afterCommit(fn () => $this->eventos->dispatch($evento));
    }
}
