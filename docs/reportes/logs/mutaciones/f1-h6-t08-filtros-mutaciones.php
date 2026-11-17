<?php

/*
 * Mutación de los filtros de alcance y de visibilidad de H6 (T08, docs/hitos/f1-h6.md §8). Corre dentro del contenedor `app`,
 * sobre la copia temporal de `/tmp/mut` que prepara f1-h6-t08-filtros-guion.sh; nunca toca `/var/www/html` (el árbol de trabajo).
 *
 * Uso: php f1-h6-t08-filtros-mutaciones.php <lote 1|2|3> [solo-reemplazos]
 *
 * Por cada mutación: restaura el archivo original desde `/var/www/html`, cuenta las ocurrencias del texto a cambiar ANTES (debe
 * haber exactamente una), lo reemplaza, cuenta DESPUÉS (el texto viejo ya no debe estar, salvo que el nuevo lo contenga) y
 * comprueba que el archivo cambió y que su sintaxis es válida. Si cualquiera de esas comprobaciones falla, el guion termina con
 * el código 3 («NO APLICADA»): una mutación no aplicada no cuenta como sobreviviente ni como matada. Después corre las pruebas
 * del lote; si ninguna falla, la mutación SOBREVIVE y el guion se detiene con el código 4 para reportarla.
 */
const RAIZ = '/tmp/mut';
const ORIGINAL = '/var/www/html';

$lote = (int) ($argv[1] ?? 0);
$soloReemplazos = ($argv[2] ?? '') === 'solo-reemplazos';

$todas = [
    1 => [
        'nombre' => 'alertas y estatus',
        'pruebas' => ['tests/Feature/Alertas/AlertasDelJugadorTest.php', 'tests/Feature/Consultas/EstatusDeJugadoresTest.php', 'tests/Feature/Seguridad/RecorridoDeAlcanceH6Test.php'],
        'mutaciones' => [
            ['1.01', 'las alertas cerradas se ven', 'src/Consultas/Infraestructura/VisibilidadDeAlertasSql.php',
                'return $consulta->whereNull("$alias.cerrada_en")->where(function', 'return $consulta->where(function'],
            ['1.02', 'el entrenador de otro equipo ve la alerta deportiva', 'src/Consultas/Infraestructura/VisibilidadDeAlertasSql.php',
                '"$alias.jugador_id", "$alias.equipo_id")));', '"$alias.jugador_id")));'],
            ['1.03', 'el docente ve alertas de materias que no son suyas', 'src/Consultas/Infraestructura/VisibilidadDeAlertasSql.php',
                "            ->where('materias.docente_id', \$usuario)\n", ''],
            ['1.04', 'cualquier entrenador del equipo, no el del usuario, da visibilidad', 'src/Consultas/Infraestructura/VisibilidadDeAlertasSql.php',
                "            ->where('asignacion.usuario_id', \$usuario)\n", ''],
            ['1.05', 'una membresía vencida da visibilidad a las alertas', 'src/Consultas/Infraestructura/VisibilidadDeAlertasSql.php',
                "            ->whereNull('membresia.hasta')\n", ''],
            ['1.06', 'jugadorVisible no aplica AlcanceDeJugadores (estatus)', 'src/Consultas/Infraestructura/ConsultaDeEstatusSql.php',
                <<<'T'
                        return $this->alcance->restringir(
                            $this->usuarioActual,
                            $this->conexion->table('jugadores')->whereNull('jugadores.eliminado_en')->where('jugadores.id', $jugador->valor()),
                            'jugadores.id',
                        )->exists();
                T,
                <<<'T'
                        return $this->conexion->table('jugadores')->whereNull('jugadores.eliminado_en')->where('jugadores.id', $jugador->valor())->exists();
                T],
            ['1.07', 'el listado de estatus incluye a los jugadores en baja', 'src/Consultas/Infraestructura/ConsultaDeEstatusSql.php',
                <<<'T'
                            $this->conexion->table('jugadores')->whereNull('jugadores.eliminado_en'),
                            'jugadores.id',
                        );

                        if ($filtros->equipoId !== null) {
                T,
                <<<'T'
                            $this->conexion->table('jugadores')->whereNull('jugadores.eliminado_en'),
                            'jugadores.id',
                            incluirBajas: true,
                        );

                        if ($filtros->equipoId !== null) {
                T],
            ['1.08', 'filtro[equipo_id] acepta equipos que el usuario no entrena', 'src/Consultas/Infraestructura/ConsultaDeEstatusSql.php',
                "                ->where('asignacion_filtrada.usuario_id', \$usuario->valor())\n", ''],
            ['1.09', 'filtro[materia_id] acepta materias de otro docente', 'src/Consultas/Infraestructura/ConsultaDeEstatusSql.php',
                "                ->where('materia_filtrada.docente_id', \$usuario->valor()));", "                ->whereNull('materia_filtrada.eliminado_en'));"],
            ['1.10', 'el aviso pendiente es visible para cualquiera', 'src/Consultas/Infraestructura/ConsultaDeEstatusSql.php',
                'aviso.creado_por = ?', 'aviso.creado_por is not null and ? is not null'],
            ['1.11', 'GET /jugadores/{jugador}/alertas no aplica el alcance', 'src/Consultas/Aplicacion/ConsultarAlertasDelJugador.php',
                'return $this->estatus->jugadorVisible($jugador) ? $this->alertas->delJugador($jugador) : null;', 'return $this->alertas->delJugador($jugador);'],
        ],
    ],
    2 => [
        'nombre' => 'vista para convocar y listas',
        'pruebas' => [
            'tests/Feature/Consultas/VistaParaConvocarTest.php', 'tests/Feature/Consultas/OrdenPorDesempenoTest.php',
            'tests/Feature/Consultas/VistaDeLaConvocatoriaTest.php', 'tests/Feature/Consultas/ParidadDeVistaParaConvocarTest.php',
            'tests/Feature/Seguridad/RecorridoDeAlcanceH6Test.php',
        ],
        'mutaciones' => [
            ['2.01', 'la vista deja pasar el alcance de docente (sin soloEntrenador)', 'src/Consultas/Infraestructura/ConsultaDeVistaParaConvocarSql.php',
                "            \$this->conexion->table('jugadores')->whereNull('jugadores.eliminado_en'),\n            'jugadores.id',\n            soloEntrenador: true,",
                "            \$this->conexion->table('jugadores')->whereNull('jugadores.eliminado_en'),\n            'jugadores.id',"],
            ['2.02', 'la vista incluye a los jugadores en baja', 'src/Consultas/Infraestructura/ConsultaDeVistaParaConvocarSql.php',
                "            \$this->conexion->table('jugadores')->whereNull('jugadores.eliminado_en'),\n            'jugadores.id',\n            soloEntrenador: true,",
                "            \$this->conexion->table('jugadores')->whereNull('jugadores.eliminado_en'),\n            'jugadores.id',\n            incluirBajas: true,\n            soloEntrenador: true,"],
            ['2.03', 'la convocatoria de la vista incluye a los jugadores en baja', 'src/Consultas/Infraestructura/ConsultaDeVistaParaConvocarSql.php',
                "            'convocatorias.jugador_id',\n            soloEntrenador: true,", "            'convocatorias.jugador_id',\n            incluirBajas: true,\n            soloEntrenador: true,"],
            ['2.04', 'la convocatoria de la vista deja pasar el alcance de docente', 'src/Consultas/Infraestructura/ConsultaDeVistaParaConvocarSql.php',
                "            'convocatorias.jugador_id',\n            soloEntrenador: true,", "            'convocatorias.jugador_id',"],
            ['2.05', 'un partido eliminado se puede consultar', 'src/Consultas/Infraestructura/ConsultaDeVistaParaConvocarSql.php',
                "->whereNull('eliminado_en')->value('equipo_id')", "->value('equipo_id')"],
            ['2.06', 'una convocatoria eliminada cuenta como convocado', 'src/Consultas/Infraestructura/ConsultaDeVistaParaConvocarSql.php',
                "                ->whereNull('convocatorias.eliminado_en')\n", ''],
            ['2.07', 'cualquier entrenador de un equipo, no el del usuario, lo «entrena»', 'src/Consultas/Infraestructura/ConsultaDeVistaParaConvocarSql.php',
                "            ->where('usuario_id', \$this->usuario()->valor())\n", ''],
            ['2.08', 'el filtro de equipo no exige que el usuario lo entrene', 'src/Consultas/Infraestructura/ConsultaDeVistaParaConvocarSql.php',
                "return \$this->entrena(\$equipo)\n            && \$this->visibles()", 'return $this->visibles()'],
            ['2.09', 'una membresía vencida cuenta como jugador del equipo', 'src/Consultas/Infraestructura/ConsultaDeVistaParaConvocarSql.php',
                "            ->whereNull('membresia.hasta')\n", ''],
            ['2.10', 'la vista usa las evaluaciones de cualquier entrenador', 'src/Consultas/Infraestructura/ConsultaDeVistaParaConvocarSql.php',
                "            ->where('entrenador_id', \$this->usuario()->valor())\n", ''],
            ['2.11', 'la vista usa las mediciones de cualquier entrenador', 'src/Consultas/Infraestructura/ConsultaDeVistaParaConvocarSql.php',
                "            ->where('registrado_por', \$usuario)\n", ''],
            ['2.12', 'la vista usa las pruebas físicas de cualquier entrenador', 'src/Consultas/Infraestructura/ConsultaDeVistaParaConvocarSql.php',
                "            ->where('prueba.registrado_por', \$usuario)\n", ''],
            ['2.13', 'asistencia.equipo cuenta los equipos de todos los entrenadores', 'src/Consultas/Infraestructura/ConsultaDeVistaParaConvocarSql.php',
                "                ->where('asignacion.usuario_id', \$usuario->valor())\n", ''],
            ['2.14', 'asistencia.de_mis_capturas cuenta lo que capturaron otros', 'src/Consultas/Infraestructura/ConsultaDeVistaParaConvocarSql.php',
                "\$this->porcentaje(\$deLosEquipos->where('asistencia.creado_por', \$usuario->valor())),", '$this->porcentaje($deLosEquipos),'],
        ],
    ],
    3 => [
        'nombre' => 'gráficos y roles',
        'pruebas' => [
            'tests/Feature/Consultas/GraficosDelJugadorTest.php', 'tests/Feature/Consultas/GraficosDelEquipoTest.php',
            'tests/Feature/Consultas/ContratoDeGraficosTest.php', 'tests/Feature/Consultas/GuardaDeDocenteEnGraficosTest.php', 'tests/Feature/Seguridad/RecorridoDeAlcanceH6Test.php',
        ],
        'mutaciones' => [
            ['3.01', 'los gráficos usan el rendimiento de cualquier entrenador', 'src/Consultas/Infraestructura/ConsultaDeGraficosSql.php',
                "            ->where('rendimiento.registrado_por', \$this->usuario()->valor())\n", ''],
            ['3.02', 'los gráficos usan las mediciones de cualquier entrenador', 'src/Consultas/Infraestructura/ConsultaDeGraficosSql.php',
                "            ->where('registrado_por', \$this->usuario()->valor())\n", ''],
            ['3.03', 'el docente ve materias de otro docente (lista de materias)', 'src/Consultas/Infraestructura/ConsultaDeGraficosSql.php',
                "            ->where('inscripcion.jugador_id', \$jugador->valor())\n            ->where('materia.docente_id', \$this->usuario()->valor())\n",
                "            ->where('inscripcion.jugador_id', \$jugador->valor())\n"],
            ['3.04', 'el docente ve calificaciones de materias de otro docente', 'src/Consultas/Infraestructura/ConsultaDeGraficosSql.php',
                "            ->where('inscripcion.materia_id', \$materia->valor())\n            ->where('materia.docente_id', \$this->usuario()->valor())\n",
                "            ->where('inscripcion.materia_id', \$materia->valor())\n"],
            ['3.05', 'GET /jugadores/{jugador}/graficos no aplica el alcance', 'src/Consultas/Aplicacion/ConsultarGraficosDelJugador.php',
                'if ($usuario === null || ! $this->estatus->jugadorVisible($jugador)) {', 'if ($usuario === null) {'],
            ['3.06', 'GET /equipos/{equipo}/graficos no exige entrenar el equipo', 'src/Consultas/Aplicacion/ConsultarGraficosDelEquipo.php',
                'if ($usuario === null || ! $this->equipos->entrena($usuario, $equipo)) {', 'if ($usuario === null) {'],
            ['3.07', 'filtro[jugador_id] no comprueba el alcance del jugador', 'src/Consultas/Aplicacion/ConsultarGraficosDelEquipo.php',
                '! ($this->estatus->jugadorVisible($jugador) && $this->graficos->jugadorEnEquipos($jugador, [$equipo]))', '! $this->graficos->jugadorEnEquipos($jugador, [$equipo])'],
            ['3.08', 'filtro[jugador_id] no comprueba que el jugador sea del equipo', 'src/Consultas/Aplicacion/ConsultarGraficosDelEquipo.php',
                '! ($this->estatus->jugadorVisible($jugador) && $this->graficos->jugadorEnEquipos($jugador, [$equipo]))', '! $this->estatus->jugadorVisible($jugador)'],
            ['3.09', 'el docente accede a las rutas de entrenador', 'routes/api.php',
                "Route::middleware('rol:entrenador')->group(function () {", "Route::middleware('rol:entrenador,docente')->group(function () {"],
            ['3.10', 'el docente pierde las rutas compartidas', 'routes/api.php',
                "Route::middleware('rol:entrenador,docente')->group(function () {", "Route::middleware('rol:entrenador')->group(function () {"],
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

/** @return array{resumen: string, fallan: list<string>} */
function correrPest(array $pruebas): array
{
    exec('cd '.RAIZ.' && php -d memory_limit=1G vendor/bin/pest '.implode(' ', $pruebas).' 2>&1', $salida);
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
    $control = correrPest($datos['pruebas']);
    echo "=== control antes: {$control['resumen']}\n";
    if ($control['fallan'] !== [] || ! str_contains($control['resumen'], 'passed') || str_contains($control['resumen'], 'failed')) {
        echo "El control inicial no pasa; no tiene sentido mutar.\n";
        exit(5);
    }
}

$matadas = 0;
foreach ($datos['mutaciones'] as [$id, $que, $archivo, $viejo, $nuevo]) {
    restaurar($archivo);
    $ruta = RAIZ.'/'.$archivo;
    $original = (string) file_get_contents($ruta);
    $antes = substr_count($original, $viejo);
    echo "=== $id $que ($archivo)\n";

    if ($antes !== 1) {
        echo "NO APLICADA: el texto a cambiar aparece $antes veces antes del reemplazo y debe aparecer exactamente una.\n";
        exit(3);
    }

    $mutado = str_replace($viejo, $nuevo, $original, $cambios);
    $despuesViejo = substr_count($mutado, $viejo);
    $esperado = str_contains($nuevo, $viejo) ? substr_count($nuevo, $viejo) : 0;
    file_put_contents($ruta, $mutado);
    exec('php -l '.escapeshellarg($ruta).' 2>&1', $sintaxis, $codigoSintaxis);

    echo "reemplazos: antes=$antes cambios=$cambios despues=$despuesViejo\n";
    if ($cambios !== 1 || $mutado === $original || $despuesViejo !== $esperado || $codigoSintaxis !== 0) {
        echo "NO APLICADA: el reemplazo no dejó el archivo como se esperaba (sintaxis ".($codigoSintaxis === 0 ? 'válida' : 'INVÁLIDA').").\n";
        exit(3);
    }

    if ($soloReemplazos) {
        $matadas++;

        continue;
    }

    $resultado = correrPest($datos['pruebas']);
    echo "pruebas con la mutación: {$resultado['resumen']}\n";
    if ($resultado['fallan'] === []) {
        echo "SOBREVIVE: $id ningún test falla con la mutación.\n";
        restaurar($archivo);
        exit(4);
    }

    echo 'MATADA por '.count($resultado['fallan'])." prueba(s); las primeras:\n";
    foreach (array_slice($resultado['fallan'], 0, 3) as $nombre) {
        echo "  ⨯ $nombre\n";
    }
    $matadas++;
}

foreach ($datos['mutaciones'] as [, , $archivo]) {
    restaurar($archivo);
}

if (! $soloReemplazos) {
    $control = correrPest($datos['pruebas']);
    echo "=== control después: {$control['resumen']}\n";
    if ($control['fallan'] !== []) {
        exit(5);
    }
}

echo "Lote $lote: $matadas de ".count($datos['mutaciones'])." mutaciones ".($soloReemplazos ? 'aplicadas exactamente una vez' : 'matadas').".\n";
