<?php

/*
 * Sin borrado fisico sobre tablas de negocio (proyecto.md, seccion 1): solo borrado logico con historial.
 * Una llamada ->delete( o ->forceDelete( es valida unicamente si la sentencia nombra, con un literal,
 * una tabla del framework. Si la tabla no se puede leer como literal (un modelo, una variable), cuenta
 * como violacion. Nadie agrega excepciones sin aprobacion.
 */

const TABLAS_DEL_FRAMEWORK_PERMITIDAS = ['sessions', 'trabajos', 'trabajos_fallidos', 'cache'];

/** @return list<string> */
function borradosSobreTablasDeNegocioEn(string $codigo): array
{
    $tokens = token_get_all($codigo);
    $hallazgos = [];

    foreach ($tokens as $i => $token) {
        if (! is_array($token) || $token[0] !== T_OBJECT_OPERATOR) {
            continue;
        }

        $nombre = $tokens[$i + 1] ?? null;
        $apertura = $tokens[$i + 2] ?? null;
        if (! is_array($nombre) || ! in_array($nombre[1], ['delete', 'forceDelete'], true) || $apertura !== '(') {
            continue;
        }

        $sentencia = '';
        for ($j = $i - 1; $j >= 0; $j--) {
            if (in_array($tokens[$j], [';', '{', '}'], true)) {
                break;
            }
            $sentencia = (is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j]).$sentencia;
        }

        $encontradas = preg_match_all('/(?:table|from)\(\s*[\'"]([a-z_]+)[\'"]/', $sentencia, $coincidencias);
        $tabla = $encontradas > 0 ? end($coincidencias[1]) : null;

        if ($nombre[1] === 'forceDelete' || $tabla === null || ! in_array($tabla, TABLAS_DEL_FRAMEWORK_PERMITIDAS, true)) {
            $hallazgos[] = '->'.$nombre[1].'() en linea '.$nombre[2].' sobre '.($tabla ?? 'una tabla no legible como literal');
        }
    }

    return $hallazgos;
}

test('F1-H1-T08: la lista de tablas del framework permitidas es exactamente la acordada', function () {
    expect(TABLAS_DEL_FRAMEWORK_PERMITIDAS)->toBe(['sessions', 'trabajos', 'trabajos_fallidos', 'cache']);
});

test('F1-H1-T08: el detector marca borrados de negocio y deja pasar las tablas del framework', function () {
    $violaciones = <<<'PHP'
        <?php
        DB::table('jugadores')->delete();
        $this->conexion->table('equipos')->where('id', 1)->delete();
        $jugador->forceDelete();
        $jugador->delete();
        DB::table('sessions')->forceDelete();
        PHP;

    $permitidos = <<<'PHP'
        <?php
        $this->conexion->table('sessions')
            ->where('user_id', 1)
            ->delete();
        DB::table('trabajos_fallidos')->delete();
        Route::delete('/sesion', [SesionController::class, 'cerrar']);
        // $jugador->delete();
        PHP;

    expect(borradosSobreTablasDeNegocioEn($violaciones))->toHaveCount(5)
        ->and(borradosSobreTablasDeNegocioEn($permitidos))->toBe([]);
});

test('F1-H1-T08: ningun codigo de src ni app borra fisicamente tablas de negocio', function () {
    $hallazgos = [];
    foreach (['src', 'app'] as $carpeta) {
        $archivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($carpeta), FilesystemIterator::SKIP_DOTS));
        foreach ($archivos as $archivo) {
            /** @var SplFileInfo $archivo */
            if ($archivo->getExtension() !== 'php') {
                continue;
            }
            foreach (borradosSobreTablasDeNegocioEn((string) file_get_contents($archivo->getPathname())) as $hallazgo) {
                $hallazgos[] = $archivo->getPathname().': '.$hallazgo;
            }
        }
    }

    expect($hallazgos)->toBe([]);
});
