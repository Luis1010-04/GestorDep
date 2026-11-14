<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;
use Tests\Ayudantes\NoNulo;
use Tests\TestCase;

/**
 * Tabla de RN-15 (docs/hitos/f1-h5.md §7.4). Solo la fila 3, con E3 como co-entrenador de A y B, no tenía prueba
 * equivalente; las demás filas están en ConfirmacionYEstadoTest y AvisosTest (ver f1-h5-t08.md §2).
 */
test('§7.4 fila 3 (RN-15): E3, co-entrenador de A y B, recibe 404 al corregir la lesión de E2, igual que una inexistente, y no la cambia', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $lesion = NoNulo::exigir(estadoInsertar($m['f1'], $m['e2'])['lesion'], 'la lesión');
    $sesion = sesionesIniciar($this, 'e3@x.com');

    $ajena = sesionesPeticion($this, 'PATCH', lesionRuta($lesion), ['zona' => 'Rodilla'], $sesion)->assertNotFound();
    $inexistente = sesionesPeticion($this, 'PATCH', lesionRuta(Identificador::generar()->valor()), ['zona' => 'Rodilla'], $sesion)->assertNotFound();

    expect($ajena->getContent())->toBe($inexistente->getContent())
        ->and(DB::table('lesiones')->where('id', $lesion)->value('zona'))->toBe('Tobillo')
        ->and(DB::table('lesiones_historial')->where('lesion_id', $lesion)->count())->toBe(0);
});
