<?php

const NS = '\\';

$contextos = [
    'Compartido', 'Identidad', 'Plantel', 'Academico', 'Competencia',
    'Rendimiento', 'CondicionFisica', 'Salud', 'Alertas', 'Consultas',
];

test('F1-H0-T05: existen los contextos con sus capas', function () use ($contextos) {
    foreach ($contextos as $contexto) {
        $capas = $contexto === 'Consultas' ? ['Aplicacion', 'Infraestructura'] : ['Dominio', 'Aplicacion', 'Infraestructura'];
        foreach ($capas as $capa) {
            expect(is_dir(base_path("src/$contexto/$capa")))->toBeTrue("Falta src/$contexto/$capa");
        }
    }
    expect(is_dir(base_path('src/Consultas/Dominio')))->toBeFalse();
});

test('F1-H0-T05: cada contexto tiene su proveedor de servicios registrado', function () use ($contextos) {
    $registrados = require base_path('bootstrap/providers.php');
    foreach ($contextos as $contexto) {
        $clase = implode(NS, ['Src', $contexto, 'Infraestructura', $contexto.'ServiceProvider']);
        expect(class_exists($clase))->toBeTrue("Falta $clase")
            ->and($registrados)->toContain($clase);
    }
});

arch('F1-H0-T05: el dominio no depende del framework', function () {
    expect(implode(NS, ['Src', '*', 'Dominio']))
        ->not->toUse(['Illuminate', 'Laravel', 'App', 'Symfony']);
});

foreach ($contextos as $contexto) {
    arch("F1-H0-T05: la aplicacion de $contexto no depende de su infraestructura", function () use ($contexto) {
        expect(implode(NS, ['Src', $contexto, 'Aplicacion']))
            ->not->toUse(implode(NS, ['Src', $contexto, 'Infraestructura']));
    });

    arch("F1-H0-T05: $contexto no importa el dominio de otro contexto", function () use ($contexto, $contextos) {
        $dominiosAjenos = [];
        foreach ($contextos as $otro) {
            if (! in_array($otro, [$contexto, 'Compartido', 'Consultas'], true)) {
                $dominiosAjenos[] = implode(NS, ['Src', $otro, 'Dominio']);
            }
        }

        expect(implode(NS, ['Src', $contexto]))->not->toUse($dominiosAjenos);
    });
}
