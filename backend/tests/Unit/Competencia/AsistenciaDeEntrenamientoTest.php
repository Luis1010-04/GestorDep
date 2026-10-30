<?php

use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;
use Src\Competencia\Dominio\AsistenciaDeEntrenamiento;
use Src\Competencia\Dominio\ConteoDeAsistenciaAEntrenamientos;
use Src\Competencia\Dominio\EstadoDeAsistencia;

test('HU-11 (§3.4): el porcentaje es presentes entre todos los registros por 100, con la falta justificada solo en el denominador', function (int $presentes, int $faltas, int $justificadas, ?string $esperado) {
    expect((new ConteoDeAsistenciaAEntrenamientos($presentes, $faltas, $justificadas))->porcentaje()?->comoCadena())->toBe($esperado);
})->with([
    'fila 6 de §7.3' => [3, 1, 1, '60.00'],
    'fila 10 de §7.3' => [2, 1, 1, '50.00'],
    'solo justificada' => [0, 0, 1, '0.00'],
    'todo presente' => [4, 0, 0, '100.00'],
    'redondeo hacia arriba (2 de 3)' => [2, 1, 0, '66.67'],
    'redondeo hacia abajo (1 de 3)' => [1, 0, 2, '33.33'],
    'medio hacia arriba (1 de 8)' => [1, 7, 0, '12.50'],
    'HALF_UP en el tercer decimal (1 de 6)' => [1, 5, 0, '16.67'],
    'sin registros' => [0, 0, 0, null],
]);

test('HU-11: los conteos se exponen tal cual', function () {
    $conteo = new ConteoDeAsistenciaAEntrenamientos(3, 1, 2);

    expect([$conteo->presentes(), $conteo->faltas(), $conteo->faltasJustificadas()])->toBe([3, 1, 2]);
});

test('HU-11: un conteo negativo viola conteo_de_asistencia_invalido', function (int $presentes, int $faltas, int $justificadas) {
    expect(fn () => new ConteoDeAsistenciaAEntrenamientos($presentes, $faltas, $justificadas))
        ->toThrow(ReglaDeNegocioViolada::class, 'conteo_de_asistencia_invalido');
})->with([[-1, 0, 0], [0, -1, 0], [0, 0, -1]]);

test('HU-11: corregir el estado conserva el registro y fija el instante', function () {
    $utc = new DateTimeZone('UTC');
    $original = new AsistenciaDeEntrenamiento(Identificador::generar(), Identificador::generar(), Identificador::generar(), EstadoDeAsistencia::Falta, new DateTimeImmutable('2026-09-10 00:00:00', $utc));
    $instante = new DateTimeImmutable('2026-09-20 15:00:00', $utc);

    $corregida = $original->conEstado(EstadoDeAsistencia::Presente, $instante);

    expect($corregida->estado())->toBe(EstadoDeAsistencia::Presente)
        ->and($corregida->actualizadoEn())->toBe($instante)
        ->and($corregida->id())->toBe($original->id())
        ->and($corregida->entrenamientoId())->toBe($original->entrenamientoId())
        ->and($corregida->jugadorId())->toBe($original->jugadorId())
        ->and($original->estado())->toBe(EstadoDeAsistencia::Falta);
});
