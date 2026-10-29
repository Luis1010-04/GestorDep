<?php

/*
 * RNF-01: ninguna consulta de jugadores se escribe sin pasar por AlcanceDeJugadores. Todo archivo de src/ o app/
 * que nombre el modelo JugadorModelo o la tabla `jugadores` debe nombrar AlcanceDeJugadores y llamar a
 * ->restringir(, salvo la implementacion SQL del alcance, la declaracion del modelo y la lista cerrada de abajo.
 * Cada uso de la lista lleva su motivo. Agregar un uso nuevo requiere aprobacion: no se agrega sin preguntar.
 *
 * Es una deteccion estatica por nombre: no sigue consultas armadas con nombres dinamicos.
 */

const IMPLEMENTACION_DEL_ALCANCE = 'src/Consultas/Infraestructura/AlcanceDeJugadoresSql.php';

const DECLARACION_DEL_MODELO_JUGADOR = 'src/Plantel/Infraestructura/Modelos/JugadorModelo.php';

/** Los nombres de archivo son los previstos para T12 y T13; si la tarea los llama distinto, se corrige aqui con aprobacion. */
const USOS_AUTORIZADOS_SIN_ALCANCE = [
    'src/Plantel/Infraestructura/RepositorioDeJugadoresEloquent.php' => 'Inserción del alta de un jugador (T12): escribe, no consulta lo que el usuario ve.',
    'src/Plantel/Infraestructura/BusquedaDeJugadorPorMatricula.php' => 'Búsqueda por matrícula (RN-20, T13): única excepción de visibilidad, devuelve solo nombres, apellidos y matrícula.',
];

/** @return list<string> */
function referenciasAJugadoresEn(string $codigo): array
{
    $referencias = [];

    foreach (token_get_all($codigo) as $token) {
        if (! is_array($token)) {
            continue;
        }
        [$tipo, $texto, $linea] = $token;

        $nombraElModelo = in_array($tipo, [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)
            && preg_match('/(^|\\\\)JugadorModelo$/', $texto) === 1;
        $nombraLaTabla = $tipo === T_CONSTANT_ENCAPSED_STRING
            && preg_match('/^[\'"]jugadores([.\s\'"])/', $texto) === 1;

        if ($nombraElModelo || $nombraLaTabla) {
            $referencias[] = "$texto en linea $linea";
        }
    }

    return $referencias;
}

function pasaPorElAlcance(string $codigo): bool
{
    $nombres = [];
    foreach (token_get_all($codigo) as $token) {
        if (is_array($token) && in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
            $nombres[] = $token[1];
        }
    }

    return preg_grep('/(^|\\\\)AlcanceDeJugadores$/', $nombres) !== []
        && preg_match('/->\s*restringir\s*\(/', $codigo) === 1;
}

/**
 * @param  array<string, string>  $archivos  ruta relativa => codigo
 * @param  list<string>  $exentos  rutas relativas que no se revisan
 * @return list<string>
 */
function consultasDeJugadoresSinAlcance(array $archivos, array $exentos): array
{
    $hallazgos = [];
    foreach ($archivos as $ruta => $codigo) {
        if (in_array($ruta, $exentos, true)) {
            continue;
        }
        $referencias = referenciasAJugadoresEn($codigo);
        if ($referencias !== [] && ! pasaPorElAlcance($codigo)) {
            $hallazgos[] = "$ruta: ".implode(', ', $referencias);
        }
    }

    return $hallazgos;
}

/** @return array<string, string> */
function archivosPhpDeLaAplicacion(): array
{
    $archivos = [];
    foreach (['src', 'app'] as $carpeta) {
        $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($carpeta), FilesystemIterator::SKIP_DOTS));
        foreach ($iterador as $archivo) {
            /** @var SplFileInfo $archivo */
            if ($archivo->getExtension() === 'php') {
                $ruta = str_replace('\\', '/', substr($archivo->getPathname(), strlen(base_path()) + 1));
                $archivos[$ruta] = (string) file_get_contents($archivo->getPathname());
            }
        }
    }

    return $archivos;
}
