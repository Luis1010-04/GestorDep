<?php

use Illuminate\Support\Facades\DB;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\CondicionFisica\Aplicacion\RegistrarMedicion;
use Src\CondicionFisica\Dominio\ValorFueraDeRango;

/** @return array<string, string> el mundo de `medicionesMundo()`, con E1 como usuario de la sesión */
function medicionesConSesionDeE1(): array
{
    $m = medicionesMundo();
    app()->instance(UsuarioActual::class, new class($m['e1']) implements UsuarioActual
    {
        public function __construct(private readonly string $id) {}

        public function identificador(): Identificador
        {
            return Identificador::desdeTexto($this->id);
        }

        public function roles(): array
        {
            return ['entrenador'];
        }
    });

    return $m;
}

test('AU-09: con el máximo de estatura en 9999.99, el caso de uso rechaza 9999.95 por la columna, sin llegar a la base', function () {
    $m = medicionesConSesionDeE1();
    DB::connection('propietario')->table('rangos_mediciones_corporales')->where('codigo', 'estatura_cm')->update(['maximo' => '9999.99']);
    $error = null;

    try {
        app(RegistrarMedicion::class)->ejecutar(Identificador::desdeTexto($m['f1']), [
            'equipo_id' => $m['a'], 'peso_kg' => '70.00', 'estatura_cm' => '9999.95',
        ]);
    } catch (ValorFueraDeRango $e) {
        $error = $e;
    }

    expect($error?->campo())->toBe('estatura_cm')
        ->and(DB::table('mediciones_corporales')->where('jugador_id', $m['f1'])->count())->toBe(0);
});
