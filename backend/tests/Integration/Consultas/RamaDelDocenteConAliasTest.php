<?php

/*
 * Hallazgo 5 (docs/hitos/f1-h7.md v0.6 §8, T06b): la rama del docente de `AlcanceDeJugadoresSql` y de
 * `VisibilidadDeAlertasSql` usaba `inscripciones` y `materias` sin alias en su subconsulta. Con una columna externa
 * `inscripciones.jugador_id`, la subconsulta comparaba su propia columna consigo misma y la rama se cumplía sola si el
 * docente tenía cualquier inscripción. Mundo de `mundoDeDocentes()`: D1 da M1 (J1, J3 y J5) y D2 da M2 (J2 y J3); el
 * periodo P1 contiene el hoy de negocio. Cada prueba lleva su control: lo que D1 sí ve sigue visible.
 */

use Illuminate\Support\Facades\DB;
use Src\Compartido\Aplicacion\AlcanceDeJugadores;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Identificador;
use Src\Consultas\Infraestructura\VisibilidadDeAlertasSql;

/** @param  list<string>  $roles */
function ramaDelDocenteUsuario(string $id, array $roles): UsuarioActual
{
    return new class($id, $roles) implements UsuarioActual
    {
        /** @param  list<string>  $roles */
        public function __construct(private readonly string $id, private readonly array $roles) {}

        public function identificador(): ?Identificador
        {
            return Identificador::desdeTexto($this->id);
        }

        public function roles(): array
        {
            return $this->roles;
        }
    };
}

function ramaDelDocenteVeLaInscripcion(string $docente, string $inscripcion): bool
{
    return app(AlcanceDeJugadores::class)
        ->restringir(ramaDelDocenteUsuario($docente, ['docente']), DB::table('inscripciones')->where('inscripciones.id', $inscripcion), 'inscripciones.jugador_id')
        ->exists();
}

function ramaDelDocenteVeLaAlerta(string $docente, string $alerta): bool
{
    $visibilidad = new VisibilidadDeAlertasSql(ramaDelDocenteUsuario($docente, ['docente']), app(HoyDeNegocio::class));

    return $visibilidad->restringir(DB::table('alertas as inscripciones')->where('inscripciones.id', $alerta), 'inscripciones')->exists();
}

test('RNF-01 (hallazgo 5): la rama del docente de AlcanceDeJugadores no se cumple sola cuando la columna externa es inscripciones.jugador_id', function () {
    $m = mundoDeDocentes();

    expect(ramaDelDocenteVeLaInscripcion($m['d1'], $m['i2']))->toBeFalse()
        ->and(ramaDelDocenteVeLaInscripcion($m['d1'], $m['i1']))->toBeTrue();
});

test('RNF-01 (hallazgo 5): la rama del docente de VisibilidadDeAlertasSql no se cumple sola con esa columna', function () {
    $m = mundoDeDocentes();
    $deJ2 = alertaInsertar($m['j2'], ALERTA_ACADEMICA);
    $deJ1 = alertaInsertar($m['j1'], ALERTA_ACADEMICA);

    expect(ramaDelDocenteVeLaAlerta($m['d1'], $deJ2))->toBeFalse()
        ->and(ramaDelDocenteVeLaAlerta($m['d1'], $deJ1))->toBeTrue();
});
