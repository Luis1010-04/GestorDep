<?php

use Src\Compartido\Aplicacion\JugadorLocalizado;
use Src\Compartido\Aplicacion\LocalizadorDeJugadores;

/*
 * El puerto `LocalizadorDeJugadores` es una excepción de visibilidad acotada (RN-20): localiza a un jugador por
 * matrícula sin pasar por AlcanceDeJugadores. Solo lo usa el caso de uso de inscripción de Académico, devuelve
 * únicamente el identificador y si está en baja, y su adaptador no consulta la tabla `jugadores` por su cuenta. Esta
 * prueba no toca la lista cerrada de AlcanceDeJugadores (tests/Arch/AlcanceDeJugadoresTest.php).
 */

const PUERTO_LOCALIZADOR = 'src/Compartido/Aplicacion/LocalizadorDeJugadores.php';

const USOS_AUTORIZADOS_DEL_LOCALIZADOR = [
    PUERTO_LOCALIZADOR => 'La declaración del puerto.',
    'src/Plantel/Infraestructura/LocalizadorDeJugadoresPlantel.php' => 'El adaptador: delega en el repositorio de Plantel ya autorizado.',
    'src/Plantel/Infraestructura/PlantelServiceProvider.php' => 'Enlaza el puerto con su adaptador.',
    'src/Academico/Aplicacion/InscribirJugador.php' => 'El único caso de uso que lo usa: inscribir por matrícula (HU-06, RN-20).',
];

/**
 * Detecta el puerto y su adaptador por nombre de clase, y también una cadena que los nombre (resolverlos con
 * `app('...LocalizadorDeJugadores')` eludiría la detección por nombre). Los comentarios no cuentan.
 *
 * @param  array<string, string>  $archivos  ruta relativa => código
 * @return list<string> rutas que nombran el puerto o su adaptador
 */
function archivosQueNombranElLocalizador(array $archivos): array
{
    $rutas = [];
    foreach ($archivos as $ruta => $codigo) {
        foreach (token_get_all($codigo) as $token) {
            if (! is_array($token)) {
                continue;
            }
            $esNombre = in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)
                && preg_match('/(^|\\\\)LocalizadorDeJugadores(Plantel)?$/', $token[1]) === 1;
            $esCadena = in_array($token[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)
                && preg_match('/(^|[\\\\\'"])LocalizadorDeJugadores(Plantel)?([\'"]|$)/', $token[1]) === 1;
            if ($esNombre || $esCadena) {
                $rutas[] = $ruta;
                break;
            }
        }
    }

    return $rutas;
}

test('RN-20: el detector encuentra los archivos que nombran el localizador o su adaptador y no confunde otros nombres', function () {
    $archivos = [
        'src/Academico/Aplicacion/Otro.php' => "<?php\nuse Src\\Compartido\\Aplicacion\\LocalizadorDeJugadores;",
        'src/Academico/Aplicacion/Adaptador.php' => "<?php\n\$x = new \\Src\\Plantel\\Infraestructura\\LocalizadorDeJugadoresPlantel(\$r);",
        'src/Academico/Aplicacion/Cadena.php' => "<?php\n\$x = app('Src\\\\Compartido\\\\Aplicacion\\\\LocalizadorDeJugadores');",
        'src/Plantel/Aplicacion/Distinto.php' => "<?php\nclass LocalizadorDeJugadoresAntiguo {}",
        'src/Plantel/Aplicacion/Texto.php' => "<?php\n\$x = 'el LocalizadorDeJugadores del manual';",
        'src/Plantel/Aplicacion/Comentario.php' => "<?php\n// LocalizadorDeJugadores en un comentario no cuenta",
    ];

    expect(archivosQueNombranElLocalizador($archivos))->toBe([
        'src/Academico/Aplicacion/Otro.php',
        'src/Academico/Aplicacion/Adaptador.php',
        'src/Academico/Aplicacion/Cadena.php',
    ]);
});

test('RN-20: solo la declaración, el adaptador, su enlace y el caso de uso de inscripción usan LocalizadorDeJugadores', function () {
    $nombran = archivosQueNombranElLocalizador(archivosPhpDeLaAplicacion());
    sort($nombran);
    $autorizados = array_keys(USOS_AUTORIZADOS_DEL_LOCALIZADOR);
    sort($autorizados);

    expect($nombran)->toBe($autorizados);
    foreach (USOS_AUTORIZADOS_DEL_LOCALIZADOR as $ruta => $motivo) {
        expect(is_file(base_path($ruta)))->toBeTrue($ruta)->and($motivo)->not->toBeEmpty();
    }
});

test('RN-20: el puerto devuelve solo el identificador y si el jugador está en baja', function () {
    $propiedades = array_map(fn (ReflectionProperty $p): string => $p->getName(), (new ReflectionClass(JugadorLocalizado::class))->getProperties());
    sort($propiedades);

    expect($propiedades)->toBe(['enBaja', 'id']);
    $metodos = array_map(fn (ReflectionMethod $m): string => $m->getName(), (new ReflectionClass(LocalizadorDeJugadores::class))->getMethods());
    expect($metodos)->toBe(['porMatricula']);
});

test('RN-20: el adaptador del localizador no consulta la tabla de jugadores por su cuenta', function () {
    $codigo = (string) file_get_contents(base_path('src/Plantel/Infraestructura/LocalizadorDeJugadoresPlantel.php'));

    expect($codigo)->not->toMatch('/[\'"]jugadores[\'".\s]/')->and($codigo)->not->toContain('JugadorModelo');
});
