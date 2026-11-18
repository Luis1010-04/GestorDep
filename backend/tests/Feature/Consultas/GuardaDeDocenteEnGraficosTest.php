<?php

use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Consultas\Aplicacion\ConsultaDeGraficos;
use Tests\TestCase;

/**
 * Guarda de defensa en profundidad sobre el adaptador SQL de los gráficos (T08, mutación 3.04), no un criterio de aceptación de la
 * API: `ConsultarGraficosDelJugador` solo le pasa a `calificacionesDeLaMateria` materias que acaba de leer `materiasDelDocente()`,
 * que ya filtra por el docente, así que por la ruta `GET /jugadores/{jugador}/graficos` el filtro de `calificacionesDeLaMateria`
 * nunca excluye nada. Esta prueba lo ejerce llamando al adaptador directamente. Se cita RNF-01 («Cada usuario accede únicamente a
 * la información de sus jugadores asignados y al nivel de detalle que su perfil permite», `docs/requisitos.md`) porque es el
 * requisito de confidencialidad que la guarda protege, no porque el texto describa este método.
 */
function guardaDeDocenteComo(string $usuarioId): void
{
    app()->instance(UsuarioActual::class, new class($usuarioId) implements UsuarioActual
    {
        public function __construct(private readonly string $id) {}

        public function identificador(): Identificador
        {
            return Identificador::desdeTexto($this->id);
        }

        public function roles(): array
        {
            return ['docente'];
        }
    });
}

test('RNF-01 (guarda de defensa en profundidad): calificacionesDeLaMateria devuelve una serie vacía para la materia de otro docente y sus calificaciones para la propia', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    mundoCalificar($m['i3a'], 'final', '8.00');
    mundoCalificar($m['i3b'], 'final', '9.00');
    guardaDeDocenteComo($m['d1']);
    $graficos = app(ConsultaDeGraficos::class);
    $jugador = Identificador::desdeTexto($m['j3']);

    $propia = $graficos->calificacionesDeLaMateria($jugador, Identificador::desdeTexto($m['m1']));
    $deOtroDocente = $graficos->calificacionesDeLaMateria($jugador, Identificador::desdeTexto($m['m2']));

    expect($propia->puntos)->toHaveCount(1)
        ->and($propia->puntos[0]->valor)->toBe('8.00')
        ->and($deOtroDocente->puntos)->toBe([])
        ->and($deOtroDocente->actualizadoEn)->toBeNull();
});
