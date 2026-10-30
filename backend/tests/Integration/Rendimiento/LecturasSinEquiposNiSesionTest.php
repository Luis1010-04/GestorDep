<?php

use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Consultas\Aplicacion\ConsultaDePendientesDeRendimiento;
use Src\Rendimiento\Aplicacion\ConsultaDePlantillas;

/**
 * Ramas de las consultas de Rendimiento que la API no alcanza con un entrenador asignado, probadas sobre la clase
 * (F1-H4-FIX del Lote B): un entrenador sin equipos y la ausencia de sesión, que `UsuarioActual` admite por contrato
 * (por ejemplo, un comando de consola).
 */
beforeEach(function () {
    sesionesPrepararBase();
});

function lecturasComoUsuario(?string $usuarioId): void
{
    app()->instance(UsuarioActual::class, new class($usuarioId) implements UsuarioActual
    {
        public function __construct(private readonly ?string $id) {}

        public function identificador(): ?Identificador
        {
            return $this->id === null ? null : Identificador::desdeTexto($this->id);
        }

        public function roles(): array
        {
            return $this->id === null ? [] : ['entrenador'];
        }
    });
}

test('HU-14: un entrenador sin equipos no tiene partidos pendientes de captura', function () {
    umbralMundo();
    lecturasComoUsuario(sesionesCrearUsuario('sin-equipos@x.com', ['entrenador']));

    expect(app(ConsultaDePendientesDeRendimiento::class)->delUsuario(1, 15))->toBe(['elementos' => [], 'total' => 0]);
});

test('HU-15: sin usuario en la sesión no se lista ninguna plantilla, ni siquiera las de fábrica', function () {
    umbralMundo();
    lecturasComoUsuario(null);

    expect(app(ConsultaDePlantillas::class)->visiblesDelUsuario(null, 1, 15))->toBe(['elementos' => [], 'total' => 0]);
});
