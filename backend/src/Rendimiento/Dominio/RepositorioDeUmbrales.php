<?php

namespace Src\Rendimiento\Dominio;

use Src\Compartido\Dominio\Identificador;

interface RepositorioDeUmbrales
{
    public function vigenteDe(Identificador $jugador, Identificador $equipo, Identificador $entrenador): ?UmbralDeRendimiento;

    /**
     * En una transacción, cierra el anterior (si lo hay) en el instante en que empieza el nuevo e inserta el nuevo, cada
     * fila con su historial. Si choca con el índice del umbral vigente lanza UmbralConcurrente (Aplicacion).
     */
    public function reemplazar(?UmbralDeRendimiento $anterior, UmbralDeRendimiento $nuevo): void;
}
