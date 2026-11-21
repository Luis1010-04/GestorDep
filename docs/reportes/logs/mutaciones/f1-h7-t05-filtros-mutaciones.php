<?php

/*
 * Mutación de los filtros de alcance y de visibilidad de las siete rutas de historial de H7 (T05, docs/hitos/f1-h7.md §8).
 * Corre dentro del contenedor `app`, sobre la copia temporal de `/tmp/mut` que prepara f1-h7-t05-filtros-guion.sh; nunca
 * toca `/var/www/html` (el árbol de trabajo). Mismo esquema que f1-h6-t08-filtros-mutaciones.php, con dos cambios:
 *   - cada mutación nombra las pruebas que corre;
 *   - un sobreviviente declarado de antemano, con su motivo, no detiene el lote. El guion termina con el código 4 si
 *     sobrevive una mutación no declarada, y con el código 6 si muere una declarada como sobreviviente.
 *
 * Uso: php f1-h7-t05-filtros-mutaciones.php <lote 1|2|3> [solo-reemplazos]
 *
 * Por cada mutación: restaura el archivo original desde `/var/www/html`, cuenta las ocurrencias del texto a cambiar ANTES
 * (debe haber exactamente una), lo reemplaza, cuenta DESPUÉS y comprueba que el archivo cambió y que su sintaxis es válida.
 * Si cualquiera de esas comprobaciones falla, el guion termina con el código 3 («NO APLICADA»).
 */
const RAIZ = '/tmp/mut';
const ORIGINAL = '/var/www/html';

const PRUEBAS_DE_HISTORIAL = [
    'tests/Feature/Academico/HistorialAcademicoTest.php',
    'tests/Feature/Salud/HistorialDeSaludTest.php',
    'tests/Feature/CondicionFisica/HistorialDeCondicionFisicaTest.php',
    'tests/Feature/Seguridad/RecorridoDeHistorialH7Test.php',
];
const PRUEBAS_ACADEMICAS = ['tests/Feature/Academico/HistorialAcademicoTest.php'];

$lote = (int) ($argv[1] ?? 0);
$soloReemplazos = ($argv[2] ?? '') === 'solo-reemplazos';

/*
 * Cada mutación: [id, qué rompe, archivo, texto original, texto mutado, pruebas, motivo si se declara sobreviviente o null].
 */
$todas = [
    1 => [
        'nombre' => 'historial académico y AlcanceDeJugadores',
        'mutaciones' => [
            ['1.01', 'el historial académico no se limita a las materias del docente', 'src/Consultas/Infraestructura/ConsultaDeHistorialAcademicoSql.php',
                "\n            ->where('materias.docente_id', \$this->usuarioActual->identificador()?->valor());", ';',
                PRUEBAS_DE_HISTORIAL, null],
            ['1.02', 'el historial académico no pasa por AlcanceDeJugadores', 'src/Consultas/Infraestructura/ConsultaDeHistorialAcademicoSql.php',
                "return \$this->alcance->restringir(\$this->usuarioActual, \$consulta, 'inscripciones.jugador_id', incluirBajas: true);", 'return $consulta;',
                PRUEBAS_DE_HISTORIAL,
                'Equivalente hoy: la consulta externa ya exige materias.docente_id del usuario, y la rama del docente de AlcanceDeJugadoresSql, con la columna inscripciones.jugador_id, se cumple siempre (hallazgo 5 de la auditoría de T02).'],
            ['1.03', 'el historial académico no comprueba que el docente tenga una materia con el jugador', 'app/Http/Controllers/HistorialAcademicoController.php',
                "        if (! \$listar->puedeVer(\$jugadorId)) {\n            throw new NotFoundHttpException;\n        }\n", '',
                PRUEBAS_DE_HISTORIAL, null],
            ['1.04', 'el historial académico excluye a los jugadores en baja', 'src/Consultas/Infraestructura/ConsultaDeHistorialAcademicoSql.php',
                "'inscripciones.jugador_id', incluirBajas: true);", "'inscripciones.jugador_id', incluirBajas: false);",
                PRUEBAS_DE_HISTORIAL, null],
            ['1.05', 'la rama del docente de AlcanceDeJugadoresSql no filtra por el docente (solo pruebas del historial académico)', 'src/Consultas/Infraestructura/AlcanceDeJugadoresSql.php',
                "\n                ->where('materias.docente_id', \$usuarioId),", ',',
                PRUEBAS_ACADEMICAS,
                'Hallazgo 5 de la auditoría de T02: con la columna inscripciones.jugador_id, la subconsulta sin alias compara la columna consigo misma y la rama del docente no restringe nada en el historial académico.'],
            ['1.06', 'la rama del docente de AlcanceDeJugadoresSql no filtra por el docente (todas las pruebas de historial)', 'src/Consultas/Infraestructura/AlcanceDeJugadoresSql.php',
                "\n                ->where('materias.docente_id', \$usuarioId),", ',',
                PRUEBAS_DE_HISTORIAL, null],
            ['1.07', 'la rama del entrenador de AlcanceDeJugadoresSql no filtra por el entrenador', 'src/Consultas/Infraestructura/AlcanceDeJugadoresSql.php',
                "\n                ->where('asignacion.usuario_id', \$usuarioId)", '',
                PRUEBAS_DE_HISTORIAL, null],
        ],
    ],
    2 => [
        'nombre' => 'historial de salud',
        'mutaciones' => [
            ['2.01', 'el historial de avisos no se limita a los avisos del lector', 'src/Salud/Infraestructura/ConsultaDeHistorialDeSaludSql.php',
                "                ->where('e.creado_por', \$lector->valor())\n", '',
                PRUEBAS_DE_HISTORIAL, null],
            ['2.02', 'el historial de avisos no incluye los que confirmó el entrenador', 'src/Salud/Infraestructura/ConsultaDeHistorialDeSaludSql.php',
                "\n                ->when(\$comoEntrenador, fn (ConsultaSql \$confirmado) => \$confirmado->orWhere('e.confirmado_por', \$lector->valor()))", '',
                PRUEBAS_DE_HISTORIAL, null],
            ['2.03', 'el historial de lesiones no se limita a las que registró el lector', 'src/Salud/Infraestructura/ConsultaDeHistorialDeSaludSql.php',
                "\n            ->where('e.registrada_por', \$lector->valor());", ';',
                PRUEBAS_DE_HISTORIAL, null],
            ['2.04', 'el historial de avisos no comprueba el jugador', 'app/Http/Controllers/HistorialDeSaludController.php',
                "        \$comprobar->ejecutar(\$jugadorId);\n\n        return \$this->pagina(\$solicitud, fn (int \$pagina, int \$porPagina) => \$listar->avisos(",
                "        return \$this->pagina(\$solicitud, fn (int \$pagina, int \$porPagina) => \$listar->avisos(",
                PRUEBAS_DE_HISTORIAL, null],
            ['2.05', 'el historial de lesiones no comprueba el jugador', 'app/Http/Controllers/HistorialDeSaludController.php',
                "        \$comprobar->ejecutar(\$jugadorId);\n\n        return \$this->pagina(\$solicitud, fn (int \$pagina, int \$porPagina) => \$listar->lesiones(",
                "        return \$this->pagina(\$solicitud, fn (int \$pagina, int \$porPagina) => \$listar->lesiones(",
                PRUEBAS_DE_HISTORIAL, null],
            ['2.06', 'el historial de estados no comprueba el jugador', 'app/Http/Controllers/HistorialDeSaludController.php',
                "        \$comprobar->ejecutar(\$jugadorId);\n\n        return \$this->pagina(\$solicitud, fn (int \$pagina, int \$porPagina) => \$listar->estados(",
                "        return \$this->pagina(\$solicitud, fn (int \$pagina, int \$porPagina) => \$listar->estados(",
                PRUEBAS_DE_HISTORIAL, null],
            ['2.07', 'el docente cuenta como entrenador en Salud (comoEntrenador y lesiones)', 'src/Salud/Infraestructura/VisibilidadDeJugadorSql.php',
                "incluirBajas: true, soloEntrenador: true)->exists();", 'incluirBajas: true, soloEntrenador: false)->exists();',
                PRUEBAS_DE_HISTORIAL, null],
            ['2.08', 'avisos y estados excluyen a los jugadores en baja', 'src/Salud/Aplicacion/ComprobarJugadorVisible.php',
                'esVisible($jugadorId, incluirBajas: true)', 'esVisible($jugadorId, incluirBajas: false)',
                PRUEBAS_DE_HISTORIAL,
                'RN-23 pendiente de decisión (encargo 47): ninguna prueba de historial de Salud recorre un jugador en baja.'],
            ['2.09', 'el historial de Salud entrega los campos de DA-18', 'src/Salud/Aplicacion/ListarHistorialDeSalud.php',
                "private const CAMPOS_OCULTOS = ['creado_por', 'confirmado_por', 'descartado_por', 'registrada_por'];", 'private const CAMPOS_OCULTOS = [];',
                PRUEBAS_DE_HISTORIAL, null],
            ['2.10', 'el historial de estados entrega lesion_id a quien no registró la lesión', 'src/Salud/Aplicacion/ListarHistorialDeSalud.php',
                "        if (array_key_exists('lesion_id', \$datos) && ! in_array(\$datos['lesion_id'], \$lesionesPropias, true)) {\n            unset(\$datos['lesion_id']);\n        }\n", '',
                PRUEBAS_DE_HISTORIAL, null],
        ],
    ],
    3 => [
        'nombre' => 'historial de condición física',
        'mutaciones' => [
            ['3.01', 'el historial de mediciones no se limita a las que registró el lector', 'src/CondicionFisica/Infraestructura/ConsultaDeHistorialDeCondicionFisicaSql.php',
                "->join('mediciones_corporales as e', 'e.id', '=', 'h.medicion_id')\n            ->where('e.jugador_id', \$jugador->valor())\n            ->where('e.registrado_por', \$lector->valor());",
                "->join('mediciones_corporales as e', 'e.id', '=', 'h.medicion_id')\n            ->where('e.jugador_id', \$jugador->valor());",
                PRUEBAS_DE_HISTORIAL, null],
            ['3.02', 'el historial de pruebas físicas no se limita a las que registró el lector', 'src/CondicionFisica/Infraestructura/ConsultaDeHistorialDeCondicionFisicaSql.php',
                "->join('pruebas_fisicas as e', 'e.id', '=', 'h.prueba_id')\n            ->where('e.jugador_id', \$jugador->valor())\n            ->where('e.registrado_por', \$lector->valor());",
                "->join('pruebas_fisicas as e', 'e.id', '=', 'h.prueba_id')\n            ->where('e.jugador_id', \$jugador->valor());",
                PRUEBAS_DE_HISTORIAL, null],
            ['3.03', 'el historial de mediciones no comprueba el jugador', 'app/Http/Controllers/HistorialDeCondicionFisicaController.php',
                "        \$comprobar->ejecutar(\$jugadorId);\n\n        return \$this->pagina(\$solicitud, fn (int \$pagina, int \$porPagina) => \$listar->mediciones(",
                "        return \$this->pagina(\$solicitud, fn (int \$pagina, int \$porPagina) => \$listar->mediciones(",
                PRUEBAS_DE_HISTORIAL, null],
            ['3.04', 'el historial de pruebas físicas no comprueba el jugador', 'app/Http/Controllers/HistorialDeCondicionFisicaController.php',
                "        \$comprobar->ejecutar(\$jugadorId);\n\n        return \$this->pagina(\$solicitud, fn (int \$pagina, int \$porPagina) => \$listar->pruebas(",
                "        return \$this->pagina(\$solicitud, fn (int \$pagina, int \$porPagina) => \$listar->pruebas(",
                PRUEBAS_DE_HISTORIAL, null],
            ['3.05', 'el docente cuenta como entrenador en Condición física', 'src/CondicionFisica/Infraestructura/VisibilidadDeJugadorSql.php',
                'incluirBajas: $incluirBajas, soloEntrenador: true)', 'incluirBajas: $incluirBajas, soloEntrenador: false)',
                PRUEBAS_DE_HISTORIAL,
                'El mundo de §7.1 no tiene un usuario con los dos roles que dé clase a F sin entrenarlo; a un docente sin el rol entrenador lo detiene antes el middleware rol:entrenador.'],
            ['3.06', 'mediciones y pruebas físicas excluyen a los jugadores en baja', 'src/CondicionFisica/Aplicacion/ComprobarJugadorDelEntrenador.php',
                'esVisibleComoEntrenador($jugadorId, incluirBajas: true)', 'esVisibleComoEntrenador($jugadorId, incluirBajas: false)',
                PRUEBAS_DE_HISTORIAL,
                'RN-23 pendiente de decisión (encargo 47): ninguna prueba de historial de Condición física recorre un jugador en baja.'],
            ['3.07', 'el historial de Condición física entrega registrado_por', 'src/CondicionFisica/Aplicacion/ListarHistorialDeCondicionFisica.php',
                "private const CAMPOS_OCULTOS = ['registrado_por'];", 'private const CAMPOS_OCULTOS = [];',
                PRUEBAS_DE_HISTORIAL, null],
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
    $control = correrPest(PRUEBAS_DE_HISTORIAL);
    echo "=== control antes: {$control['resumen']}\n";
    if ($control['fallan'] !== [] || ! str_contains($control['resumen'], 'passed') || str_contains($control['resumen'], 'failed')) {
        echo "El control inicial no pasa; no tiene sentido mutar.\n";
        exit(5);
    }
}

$matadas = [];
$sobrevivenDeclaradas = [];
$sobrevivenNoDeclaradas = [];
$muerenDeclaradas = [];
foreach ($datos['mutaciones'] as [$id, $que, $archivo, $viejo, $nuevo, $pruebas, $motivo]) {
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
    $sintaxis = [];
    exec('php -l '.escapeshellarg($ruta).' 2>&1', $sintaxis, $codigoSintaxis);

    echo "reemplazos: antes=$antes cambios=$cambios despues=$despuesViejo\n";
    if ($cambios !== 1 || $mutado === $original || $despuesViejo !== $esperado || $codigoSintaxis !== 0) {
        echo 'NO APLICADA: el reemplazo no dejó el archivo como se esperaba (sintaxis '.($codigoSintaxis === 0 ? 'válida' : 'INVÁLIDA').").\n";
        exit(3);
    }

    if ($soloReemplazos) {
        restaurar($archivo);

        continue;
    }

    echo 'pruebas: '.implode(' ', $pruebas)."\n";
    $resultado = correrPest($pruebas);
    restaurar($archivo);
    echo "pruebas con la mutación: {$resultado['resumen']}\n";

    if ($resultado['fallan'] === []) {
        if ($motivo === null) {
            echo "SOBREVIVE (NO DECLARADA): $id ningún test falla con la mutación.\n";
            $sobrevivenNoDeclaradas[] = $id;
        } else {
            echo "SOBREVIVE (declarada): $motivo\n";
            $sobrevivenDeclaradas[] = $id;
        }

        continue;
    }

    echo 'MATADA por '.count($resultado['fallan'])." prueba(s); las primeras:\n";
    foreach (array_slice($resultado['fallan'], 0, 3) as $nombre) {
        echo "  ⨯ $nombre\n";
    }
    if ($motivo !== null) {
        echo "MUERE AUNQUE SE DECLARÓ SOBREVIVIENTE: revisar el motivo.\n";
        $muerenDeclaradas[] = $id;
    }
    $matadas[] = $id;
}

foreach ($datos['mutaciones'] as [, , $archivo]) {
    restaurar($archivo);
}
foreach ($datos['mutaciones'] as [, , $archivo]) {
    if (file_get_contents(ORIGINAL.'/'.$archivo) !== file_get_contents(RAIZ.'/'.$archivo)) {
        echo "La copia de $archivo no quedó igual al original.\n";
        exit(2);
    }
}

if ($soloReemplazos) {
    echo "Lote $lote: ".count($datos['mutaciones'])." mutaciones aplicadas exactamente una vez.\n";
    exit(0);
}

$control = correrPest(PRUEBAS_DE_HISTORIAL);
echo "=== control después: {$control['resumen']}\n";
if ($control['fallan'] !== []) {
    exit(5);
}

echo "Lote $lote: ".count($datos['mutaciones']).' mutaciones; matadas: '.count($matadas).' ('.implode(', ', $matadas).')'
    .'; sobreviven declaradas: '.count($sobrevivenDeclaradas).' ('.implode(', ', $sobrevivenDeclaradas).')'
    .'; sobreviven no declaradas: '.count($sobrevivenNoDeclaradas).' ('.implode(', ', $sobrevivenNoDeclaradas).").\n";

if ($sobrevivenNoDeclaradas !== []) {
    exit(4);
}
if ($muerenDeclaradas !== []) {
    exit(6);
}
