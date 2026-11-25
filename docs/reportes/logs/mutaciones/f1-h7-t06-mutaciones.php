<?php

/*
 * Mutaciones de T06 (docs/reportes/f1-h7-plan-t06.md §7). Corre dentro del contenedor `app`, sobre la copia temporal de
 * `/tmp/mut` que prepara f1-h7-t06-guion.sh; nunca toca `/var/www/html` (el árbol de trabajo). Mismo esquema que
 * f1-h7-t05-filtros-mutaciones.php:
 *   - cada mutación nombra el archivo de pruebas y el filtro que corre;
 *   - por cada mutación: restaura el archivo original, comprueba que el texto a cambiar aparece exactamente una vez,
 *     lo reemplaza, comprueba que el archivo cambió y que su sintaxis es válida, y corre las pruebas;
 *   - termina con 3 si una mutación no se aplica, con 4 si sobrevive alguna y con 5 si el control inicial no pasa.
 *
 * Uso: php f1-h7-t06-mutaciones.php <lote 1|2|3> [solo-reemplazos]
 */
const RAIZ = '/tmp/mut';
const ORIGINAL = '/var/www/html';
const ACCESO = 'tests/Feature/Seguridad/AccesoConcurrenteTest.php';
const RELOJ = 'tests/Feature/Identidad/RelojDeUsuariosTest.php';
const BAJA = 'tests/Feature/Seguridad/BajaConcurrenteTest.php';

$lote = (int) ($argv[1] ?? 0);
$soloReemplazos = ($argv[2] ?? '') === 'solo-reemplazos';

/** Una llamada al puerto que se reemplaza por la escritura directa (o se borra, si era una guarda). */
function puerto(string $sangria, string $jugadores, string $excepcion, string $escritura, string $comentario = ''): string
{
    $linea = $comentario === '' ? '' : "{$sangria}// {$comentario}\n";

    return $linea."{$sangria}\$this->candado->conJugadoresActivos(\n"
        ."{$sangria}    {$jugadores},\n"
        ."{$sangria}    {$excepcion},\n"
        ."{$sangria}    {$escritura},\n"
        ."{$sangria});\n";
}

$guardaSalud = 'Bloquea la fila del jugador hasta el final de la transacción: una baja simultánea se serializa (AB-29).';

/*
 * Cada mutación: [id, qué rompe, archivo, texto original, texto mutado, archivo de pruebas, filtro].
 */
$todas = [
    1 => [
        'nombre' => 'AB-17 y AB-21 (Identidad)',
        'control' => [ACCESO, RELOJ],
        'mutaciones' => [
            ['1.01', 'el fallo se cuenta sin bloquear la fila del usuario', 'src/Identidad/Infraestructura/RepositorioDeUsuariosEloquent.php',
                "->whereNull('eliminado_en')->lockForUpdate()->first(),", "->whereNull('eliminado_en')->first(),", ACCESO, 'AB-17'],
            ['1.02', 'sin la regla de DA-126: un fallo con la cuenta recién bloqueada se cuenta', 'src/Identidad/Aplicacion/IniciarSesion.php',
                "                \$hasta = \$actual->bloqueadoHasta();\n                if (\$hasta !== null && \$actual->estaBloqueado(\$ahora)) {\n                    throw new CuentaBloqueada(\$hasta->getTimestamp() - \$ahora->getTimestamp());\n                }\n",
                '', ACCESO, 'AB-17'],
            ['1.03', 'bloqueado_hasta con fracción de segundo', 'src/Identidad/Aplicacion/IniciarSesion.php',
                '$ahora = Instante::truncadoASegundos($this->reloj->ahora());', '$ahora = $this->reloj->ahora();', RELOJ, 'AB-21'],
            ['1.04', 'actualizado_en de la contraseña desde el reloj de la base', 'src/Identidad/Infraestructura/RepositorioDeUsuariosEloquent.php',
                "'actualizado_en' => \$momento->format('Y-m-d H:i:sP'),", "'actualizado_en' => new \\Illuminate\\Database\\Query\\Expression('now()'),", RELOJ, 'AB-21'],
            ['1.05', 'creado_en de usuarios:crear desde el reloj de la base', 'src/Identidad/Infraestructura/RepositorioDeUsuariosEloquent.php',
                "                    'creado_en' => \$instante,\n", '', RELOJ, 'AB-21'],
        ],
    ],
    2 => [
        'nombre' => 'el puerto CandadoDeJugadores',
        'control' => [BAJA],
        'mutaciones' => [
            ['2.01', 'sin FOR SHARE', 'src/Plantel/Infraestructura/RepositorioDeJugadoresEloquent.php',
                "            ->sharedLock()\n", '', BAJA, 'AB-'],
            ['2.02', 'el predicado no excluye la baja', 'src/Plantel/Infraestructura/RepositorioDeJugadoresEloquent.php',
                "->where('estatus_deportivo', '<>', 'baja')", "->where('estatus_deportivo', '<>', 'otro')", BAJA, 'AB-'],
            ['2.03', 'el puerto no lanza con jugadores inactivos', 'src/Plantel/Infraestructura/CandadoDeJugadoresPlantel.php',
                "            if (\$inactivos !== []) {\n                throw \$siHayInactivos(\$inactivos);\n            }\n", '', BAJA, 'AB-'],
        ],
    ],
    3 => [
        'nombre' => 'cada caso de uso sin el puerto',
        'control' => [BAJA],
        'mutaciones' => [
            ['3.01', 'AsignarJugadorAEquipo', 'src/Plantel/Aplicacion/AsignarJugadorAEquipo.php',
                puerto('        ', '[$jugador->id]', 'fn (): JugadorEnBaja => new JugadorEnBaja', 'fn () => $this->membresias->asignar($jugador->id, $equipoId, $posicionId)'),
                "        \$this->membresias->asignar(\$jugador->id, \$equipoId, \$posicionId);\n", BAJA, 'AB-20'],
            ['3.02', 'InscribirJugador', 'src/Academico/Aplicacion/InscribirJugador.php',
                puerto('        ', '[$jugador->id]', 'fn (): JugadorEnBajaNoInscribible => new JugadorEnBajaNoInscribible', 'fn () => $this->inscripciones->guardarNueva($inscripcion)', 'Con la fila del jugador bloqueada: una baja simultánea se serializa con la inscripción (AB-26).'),
                "        \$this->inscripciones->guardarNueva(\$inscripcion);\n", BAJA, 'AB-26: una inscripción'],
            ['3.03', 'RegistrarCalificacion', 'src/Academico/Aplicacion/RegistrarCalificacion.php',
                puerto('        ', '[$inscripcion->jugadorId]', 'fn (): JugadorEnBajaSinCaptura => new JugadorEnBajaSinCaptura', 'fn () => $this->calificaciones->guardarNueva($calificacion)', 'Con la fila del jugador bloqueada: una baja simultánea se serializa con la captura (AB-26).'),
                "        \$this->calificaciones->guardarNueva(\$calificacion);\n", BAJA, 'AB-26: una calificación'],
            ['3.04', 'CorregirCalificacion', 'src/Academico/Aplicacion/CorregirCalificacion.php',
                "        \$cambio = \$this->candado->conJugadoresActivos(\n            [\$inscripcion->jugadorId],\n            fn (): JugadorEnBajaSinCaptura => new JugadorEnBajaSinCaptura,\n            fn (): bool => \$this->calificaciones->corregirValor(\$calificacionId, \$valor, Instante::truncadoASegundos(\$this->reloj->ahora())),\n        );\n",
                "        \$cambio = \$this->calificaciones->corregirValor(\$calificacionId, \$valor, Instante::truncadoASegundos(\$this->reloj->ahora()));\n", BAJA, 'AB-26: la corrección de una calificación'],
            ['3.05', 'RegistrarAsistencia (clase)', 'src/Academico/Aplicacion/RegistrarAsistencia.php',
                puerto('        ', '[$inscripcion->jugadorId]', 'fn (): JugadorEnBajaSinCaptura => new JugadorEnBajaSinCaptura', 'fn () => $this->asistencias->guardarNueva($asistencia)', 'Con la fila del jugador bloqueada: una baja simultánea se serializa con la captura (AB-26).'),
                "        \$this->asistencias->guardarNueva(\$asistencia);\n", BAJA, 'AB-26: una asistencia a clase'],
            ['3.06', 'CorregirAsistencia (clase)', 'src/Academico/Aplicacion/CorregirAsistencia.php',
                "        \$cambio = \$this->candado->conJugadoresActivos(\n            [\$inscripcion->jugadorId],\n            fn (): JugadorEnBajaSinCaptura => new JugadorEnBajaSinCaptura,\n            fn (): bool => \$this->asistencias->corregirEstado(\$asistenciaId, \$estado, Instante::truncadoASegundos(\$this->reloj->ahora())),\n        );\n",
                "        \$cambio = \$this->asistencias->corregirEstado(\$asistenciaId, \$estado, Instante::truncadoASegundos(\$this->reloj->ahora()));\n", BAJA, 'AB-26: la corrección de una asistencia'],
            ['3.07', 'CrearAviso', 'src/Salud/Aplicacion/CrearAviso.php',
                puerto('        ', '[$jugadorId]', 'fn (): JugadorEnBajaSinAviso => new JugadorEnBajaSinAviso', 'fn () => $this->avisos->guardarNuevo($aviso)', 'Con la fila del jugador bloqueada: una baja simultánea se serializa con el aviso (AB-29).'),
                "        \$this->avisos->guardarNuevo(\$aviso);\n", BAJA, 'AB-29: un aviso'],
            ['3.08', 'ConfirmarAviso', 'src/Salud/Aplicacion/ConfirmarAviso.php',
                puerto('            ', '[$aviso->jugadorId()]', 'fn (): JugadorEnBajaSinCambioDeEstado => new JugadorEnBajaSinCambioDeEstado', 'fn (): null => null', $guardaSalud),
                '', BAJA, 'AB-29: la confirmación'],
            ['3.09', 'CambiarEstado', 'src/Salud/Aplicacion/CambiarEstado.php',
                puerto('            ', '[$jugadorId]', 'fn (): JugadorEnBajaSinCambioDeEstado => new JugadorEnBajaSinCambioDeEstado', 'fn (): null => null', $guardaSalud),
                '', BAJA, 'AB-29: un cambio de estado'],
            ['3.10', 'RegistrarMedicion', 'src/CondicionFisica/Aplicacion/RegistrarMedicion.php',
                puerto('            ', '[$jugadorId]', 'fn (): JugadorEnBajaSinMedicion => new JugadorEnBajaSinMedicion', 'fn (): null => null', $guardaSalud),
                '', BAJA, 'AB-29: una medición'],
            ['3.11', 'RegistrarPrueba', 'src/CondicionFisica/Aplicacion/RegistrarPrueba.php',
                puerto('            ', '[$jugadorId]', 'fn (): JugadorEnBajaSinPrueba => new JugadorEnBajaSinPrueba', 'fn (): null => null', $guardaSalud),
                '', BAJA, 'AB-29: una prueba física'],
            ['3.12', 'ConvocarJugador', 'src/Competencia/Aplicacion/ConvocarJugador.php',
                puerto('        ', '[$jugador]', 'fn (): RecursoNoEncontrado => new RecursoNoEncontrado', 'fn () => $this->convocatorias->guardarNueva($convocatoria)', 'Con la fila del jugador bloqueada: una baja simultánea se serializa con la convocatoria (AB-31).'),
                "        \$this->convocatorias->guardarNueva(\$convocatoria);\n", BAJA, 'AB-31: una convocatoria'],
            ['3.13', 'DefinirAlineacion', 'src/Competencia/Aplicacion/DefinirAlineacion.php',
                puerto('        ', '$jugadoresAlineados', 'fn (): RecursoNoEncontrado => new RecursoNoEncontrado', 'fn () => $this->convocatorias->actualizarAlineacion($cambios)'),
                "        \$this->convocatorias->actualizarAlineacion(\$cambios);\n", BAJA, 'AB-31: una alineación'],
            ['3.14', 'RegistrarAsistencias (entrenamiento)', 'src/Competencia/Aplicacion/RegistrarAsistencias.php',
                "        \$this->candado->conJugadoresActivos(\n            \$jugadores,\n            fn (array \$inactivos): JugadorNoPerteneceAlEquipo => new JugadorNoPerteneceAlEquipo(\n                'registros.'.(int) array_search(\$inactivos[0]->valor(), \$valores, true).'.jugador_id',\n            ),\n            fn () => \$this->asistencias->guardarNuevas(\$nuevas),\n        );\n",
                "        \$this->asistencias->guardarNuevas(\$nuevas);\n", BAJA, 'AB-31: una asistencia de entrenamiento'],
            ['3.15', 'CapturarRendimiento', 'src/Rendimiento/Aplicacion/CapturarRendimiento.php',
                "            \$this->candado->conJugadoresActivos([\$jugador], fn (): RecursoNoEncontrado => new RecursoNoEncontrado, fn (): null => null);\n",
                '', BAJA, 'AB-31: un rendimiento'],
        ],
    ],
];

if (! isset($todas[$lote])) {
    fwrite(STDERR, "Lote inválido: $lote\n");
    exit(2);
}

$datos = $todas[$lote];

function limpio(string $texto): string
{
    return (string) preg_replace('/\e\[[0-9;]*m/', '', $texto);
}

/**
 * @param  list<string>  $archivos
 * @return array{resumen: string, fallan: list<string>}
 */
function correrPest(array $archivos, ?string $filtro = null): array
{
    $argumentos = implode(' ', array_map('escapeshellarg', $archivos)).($filtro === null ? '' : ' --filter='.escapeshellarg($filtro));
    exec('cd '.RAIZ.' && php -d memory_limit=1G vendor/bin/pest '.$argumentos.' 2>&1', $salida);
    $texto = limpio(implode("\n", $salida));
    preg_match('/Tests:\s+(.*)/', $texto, $resumen);
    preg_match_all('/^\s*⨯ (.+?)\s+[\d.]+s?\s*$/mu', $texto, $fallan);

    return ['resumen' => trim($resumen[1] ?? 'sin resumen de pruebas'), 'fallan' => $fallan[1]];
}

function restaurar(string $archivo): void
{
    if (! copy(ORIGINAL.'/'.$archivo, RAIZ.'/'.$archivo)) {
        fwrite(STDERR, "No se pudo restaurar $archivo\n");
        exit(2);
    }
}

echo "Lote $lote: {$datos['nombre']} ".($soloReemplazos ? '(solo se comprueban los reemplazos)' : '')."\n";

if (! $soloReemplazos) {
    $control = correrPest($datos['control']);
    echo "=== control antes: {$control['resumen']}\n";
    if ($control['fallan'] !== [] || ! str_contains($control['resumen'], 'passed') || str_contains($control['resumen'], 'failed')) {
        echo "El control inicial no pasa; no tiene sentido mutar.\n";
        exit(5);
    }
}

$matadas = [];
$sobreviven = [];
foreach ($datos['mutaciones'] as [$id, $que, $archivo, $viejo, $nuevo, $pruebas, $filtro]) {
    restaurar($archivo);
    $ruta = RAIZ.'/'.$archivo;
    $original = (string) file_get_contents($ruta);
    $antes = substr_count($original, $viejo);
    echo "=== $id $que ($archivo)\n";

    if ($antes !== 1) {
        echo "NO APLICADA: el texto a cambiar aparece $antes veces y debe aparecer exactamente una.\n";
        exit(3);
    }

    $mutado = str_replace($viejo, $nuevo, $original);
    file_put_contents($ruta, $mutado);
    $sintaxis = [];
    exec('php -l '.escapeshellarg($ruta).' 2>&1', $sintaxis, $codigoSintaxis);
    if ($mutado === $original || $codigoSintaxis !== 0) {
        echo 'NO APLICADA: '.($mutado === $original ? 'el archivo no cambió' : 'sintaxis inválida: '.implode(' ', $sintaxis))."\n";
        restaurar($archivo);
        exit(3);
    }

    if ($soloReemplazos) {
        echo "reemplazo correcto\n";
        restaurar($archivo);

        continue;
    }

    $resultado = correrPest([$pruebas], $filtro);
    $murio = $resultado['fallan'] !== [] || str_contains($resultado['resumen'], 'failed');
    echo "pruebas ($pruebas --filter=\"$filtro\"): {$resultado['resumen']}\n";
    foreach ($resultado['fallan'] as $prueba) {
        echo "  falla: $prueba\n";
    }
    echo $murio ? "MUERE\n" : "SOBREVIVE\n";
    $murio ? $matadas[] = $id : $sobreviven[] = $id;

    restaurar($archivo);
    if ((string) file_get_contents($ruta) !== (string) file_get_contents(ORIGINAL.'/'.$archivo)) {
        echo "La restauración de $archivo no coincide con el original.\n";
        exit(2);
    }
    echo "restaurado: $archivo idéntico al original\n";
}

if (! $soloReemplazos) {
    $control = correrPest($datos['control']);
    echo "=== control después: {$control['resumen']}\n";
}

echo '=== resultado: mueren '.count($matadas).' ('.implode(', ', $matadas).'); sobreviven '.count($sobreviven).' ('.implode(', ', $sobreviven).")\n";
exit($sobreviven === [] ? 0 : 4);
