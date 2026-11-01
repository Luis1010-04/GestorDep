<?php

use Illuminate\Support\Facades\DB;

/*
 * PT-08 (F1-H4-T00): las conexiones de prueba confirman sin esperar a que el WAL llegue al disco. Solo cambia la
 * durabilidad ante una caida del servidor; la visibilidad, los candados y los permisos son los mismos.
 */

test('PT-08: las conexiones de prueba confirman sin esperar al disco', function () {
    expect([
        'pgsql' => DB::connection('pgsql')->scalar('show synchronous_commit'),
        'propietario' => DB::connection('propietario')->scalar('show synchronous_commit'),
    ])->toBe(['pgsql' => 'off', 'propietario' => 'off']);
});
