<?php

use Src\Rendimiento\Dominio\CategoriaDeCalificacion;

test('RN-06 (DD-09): cada calificación general de 1 a 10 cae en su categoría de texto', function (int $calificacion, CategoriaDeCalificacion $categoria) {
    expect(CategoriaDeCalificacion::de($calificacion))->toBe($categoria);
})->with([
    [1, CategoriaDeCalificacion::Deficiente],
    [4, CategoriaDeCalificacion::Deficiente],
    [5, CategoriaDeCalificacion::Regular],
    [6, CategoriaDeCalificacion::Regular],
    [7, CategoriaDeCalificacion::Bueno],
    [8, CategoriaDeCalificacion::Bueno],
    [9, CategoriaDeCalificacion::Sobresaliente],
    [10, CategoriaDeCalificacion::Sobresaliente],
]);

test('RN-06: los códigos de las categorías son los que viajan en categoria_calificacion', function () {
    expect(array_map(fn (CategoriaDeCalificacion $categoria) => $categoria->value, CategoriaDeCalificacion::cases()))
        ->toBe(['deficiente', 'regular', 'bueno', 'sobresaliente']);
});

test('RN-06: una calificación fuera de 1 a 10 no tiene categoría', function (int $calificacion) {
    expect(fn () => CategoriaDeCalificacion::de($calificacion))->toThrow(InvalidArgumentException::class);
})->with([0, 11]);
