<?php

/** PT-05: ninguna prueba depende de que la lotería de barrido de sesiones acierte o falle al azar. */
test('PT-05: en las pruebas la lotería de barrido de sesiones está en 0 de 100', function () {
    expect(config('session.lottery'))->toBe([0, 100]);
});

test('PT-05: la configuración de producción de la lotería no se toca', function () {
    expect(include base_path('config/session.php'))->toHaveKey('lottery')
        ->and((include base_path('config/session.php'))['lottery'])->toBe([2, 100]);
});
