#!/usr/bin/env bash
# Guion de humo del hito F1-H4 (docs/hitos/f1-h4.md §9, v1.0.7). `make humo` lo ejecuta dentro del contenedor desde
# /scripts (montaje de solo lectura), contra una base recien sembrada (`make fresh`) y despues de los humos de H0 a H3.
# Crea por la API los datos de partida que usa: E1 entrena el equipo A con P1 (portero), D1 (defensa) y F1 (delantero), y
# un partido PJ1 jugado con los tres convocados. Usa datos propios que no chocan con los de H1 a H3: la categoria
# "Sub-11" y las matriculas HUMO-H4-00n. La temporada que contiene hoy la crea H3; si este guion corre solo, la crea el.
set -euo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

# humo_igual <n> <descripcion> <codigo_esperado> <cuerpo_de_referencia>: como humo_paso, pero exige ademas que el cuerpo
# sea identico byte a byte al de referencia (§6.1: un recurso ajeno responde igual que uno inexistente).
humo_igual() {
    local n=$1 descripcion=$2 esperado=$3 referencia=$4
    if [ "$HUMO_CODIGO" != "$esperado" ]; then
        humo_paso "$n" "$descripcion" "$esperado"
    fi
    if [ "$HUMO_CUERPO" != "$referencia" ]; then
        printf 'FALLA  paso %s: %s -> HTTP %s (el cuerpo no es igual byte a byte al de referencia)\n' "$n" "$descripcion" "$HUMO_CODIGO"
        printf '       cuerpo:     %s\n' "$HUMO_CUERPO"
        printf '       referencia: %s\n' "$referencia"
        exit 1
    fi
    printf 'OK     paso %s: %s -> HTTP %s\n' "$n" "$descripcion" "$HUMO_CODIGO"
}

humo_servidor

contrasena=$(humo_credencial contrasena)
correo_entrenador1=$(humo_credencial entrenador1)
correo_entrenador2=$(humo_credencial entrenador2)
correo_docente=$(humo_credencial docente)
e1="${HUMO_TMP}/entrenador1.cookies"
e2="${HUMO_TMP}/entrenador2.cookies"
docente="${HUMO_TMP}/docente.cookies"
sin_sesion="${HUMO_TMP}/anonimo.cookies"
: > "$sin_sesion"

# Fechas e instantes relativos al dia de negocio (America/Mexico_City, RNF-04), para que la temporada contenga hoy.
fecha() { php -r 'echo (new DateTimeImmutable("now", new DateTimeZone("America/Mexico_City")))->modify($argv[1])->format("Y-m-d");' -- "$1"; }
instante() { php -r 'echo (new DateTimeImmutable("today ".$argv[1], new DateTimeZone("America/Mexico_City")))->format(DATE_ATOM);' -- "$1"; }

humo_iniciar_sesion "$e1" "$correo_entrenador1" "$contrasena"
humo_paso 1 "E1 inicia sesión" 200 '$d["datos"]["roles"] === ["entrenador"]'

humo_peticion "$e1" GET /api/v1/metricas
humo_paso 2 "E1 consulta el catálogo: las nueve métricas sembradas" 200 \
    'count($d["datos"]) === 9 && in_array("atajadas", array_column($d["datos"], "codigo"), true)'
catalogo=$HUMO_CUERPO
# metricas <codigo>...: la lista {metrica_id, valor} de esos códigos del catálogo, con valor 1.
metricas() {
    printf '%s' "$catalogo" | php -r '
        $ids = array_column(json_decode(stream_get_contents(STDIN), true)["datos"], "id", "codigo");
        echo json_encode(array_map(fn ($c) => ["metrica_id" => $ids[$c], "valor" => 1], array_slice($argv, 1)));' -- "$@"
}

# Datos de partida por la API de H1 y H3: equipo A con P1, D1 y F1, y el partido PJ1 jugado con los tres convocados.
humo_peticion "$e1" POST /api/v1/categorias '{"nombre":"Sub-11"}'
humo_paso 3 "E1 crea la categoría Sub-11" 201
categoria=$(humo_campo datos.id)
humo_peticion "$e1" POST /api/v1/equipos "{\"nombre\":\"Coyotes Sub-11\",\"categoria_id\":\"$categoria\"}"
humo_paso 3 "E1 crea el equipo A" 201
equipo_a=$(humo_campo datos.id)
# registrar_jugador <nombres> <matricula> <posicion>: alta en A por la API de H1.
registrar_jugador() {
    humo_peticion "$e1" POST /api/v1/jugadores "{\"nombres\":\"$1\",\"apellidos\":\"Prueba Humo\",\"fecha_nacimiento\":\"2015-03-20\",\"matricula\":\"$2\",\"equipo_id\":\"$equipo_a\",\"posicion\":\"$3\"}"
    humo_paso 3 "registra a $1 ($2, $3)" 201
}
registrar_jugador "Pe Uno" HUMO-H4-001 portero
p1=$(humo_campo datos.id)
registrar_jugador "De Uno" HUMO-H4-002 defensa
d1=$(humo_campo datos.id)
registrar_jugador "Efe Uno" HUMO-H4-003 delantero
f1=$(humo_campo datos.id)

humo_peticion "$e1" POST /api/v1/temporadas "{\"nombre\":\"Temporada Humo H4\",\"fecha_inicio\":\"$(fecha "-30 days")\",\"fecha_fin\":\"$(fecha "+120 days")\"}"
if [ "$HUMO_CODIGO" = 409 ]; then
    humo_paso 3 "ya hay una temporada que contiene hoy (la del humo de H3)" 409 '$d["codigo"] === "temporada_solapada"'
else
    humo_paso 3 "E1 crea la temporada que contiene hoy" 201
fi
humo_peticion "$e1" POST /api/v1/lugares '{"nombre":"Cancha Humo H4"}'
humo_paso 3 "E1 crea un lugar" 201
lugar=$(humo_campo datos.id)
humo_peticion "$e1" POST /api/v1/rivales '{"nombre":"Rival Humo H4"}'
humo_paso 3 "E1 crea un rival" 201
rival=$(humo_campo datos.id)
humo_peticion "$e1" POST "/api/v1/equipos/$equipo_a/partidos" \
    "{\"inicia_en\":\"$(instante 15:00)\",\"lugar_id\":\"$lugar\",\"rival_id\":\"$rival\",\"tipo\":\"amistoso\",\"condicion\":\"local\"}"
humo_paso 3 "E1 crea el amistoso PJ1" 201
pj1=$(humo_campo datos.id)
for jugador in "$p1" "$d1" "$f1"; do
    humo_peticion "$e1" POST "/api/v1/partidos/$pj1/convocatoria" "{\"jugador_id\":\"$jugador\"}"
    humo_paso 3 "E1 convoca a un jugador a PJ1" 201
done
humo_peticion "$e1" PUT "/api/v1/partidos/$pj1/resultado" '{"goles_favor":2,"goles_contra":1}'
humo_paso 3 "E1 captura el resultado: PJ1 queda jugado" 200 '$d["datos"]["estado"] === "jugado"'

# captura <jugador> <minutos> <metricas_json>: cuerpo de una captura con calificación 7.
captura() { printf '{"jugador_id":"%s","minutos":%s,"fue_titular":true,"tarjetas_amarillas":0,"tarjetas_rojas":0,"calificacion_general":7,"observacion":"Humo H4","metricas":%s}' "$1" "$2" "$3"; }

humo_peticion "$e1" POST "/api/v1/partidos/$pj1/rendimientos" "$(captura "$p1" 90 "$(metricas goles asistencias atajadas goles_recibidos)")"
humo_paso 3 "E1 captura a P1 en PJ1 con sus métricas" 201 \
    '$d["datos"]["jugador_id"] === "'"$p1"'" && count($d["datos"]["metricas"]) === 4 && $d["datos"]["categoria_calificacion"] === "bueno"'
rendimiento=$(humo_campo datos.id)

humo_peticion "$e1" POST "/api/v1/partidos/$pj1/rendimientos" "$(captura "$d1" 90 "$(metricas goles asistencias faltas_cometidas)")"
humo_paso 4 "E1 captura a D1 sin recuperaciones: metrica_obligatoria_faltante" 422 '$d["codigo"] === "metrica_obligatoria_faltante"'

humo_peticion "$e1" GET /api/v1/partidos/pendientes-de-rendimiento
humo_paso 5 "E1 consulta pendientes: PJ1 aparece porque le faltan D1 y F1" 200 \
    'count(array_filter($d["datos"], fn ($p) => $p["partido_id"] === "'"$pj1"'" && $p["convocados_sin_rendimiento"] === 2)) === 1'

humo_peticion "$e1" GET "/api/v1/equipos/$equipo_a/jugadores/$f1/umbral"
humo_paso 6 "E1 consulta el umbral de F1: la plantilla «Desarrollo» (6.00, ventana 5)" 200 \
    '$d["datos"]["origen"] === "plantilla" && $d["datos"]["calificacion_minima"] === "6.00" && $d["datos"]["ventana_partidos"] === 5'

humo_peticion "$e1" PUT "/api/v1/equipos/$equipo_a/jugadores/$f1/umbral" '{"calificacion_minima":"7.0","ventana_partidos":3}'
humo_paso 7 "E1 cambia el umbral de F1 a 7.0 con ventana 3" 200 \
    '$d["datos"]["origen"] === "umbral" && $d["datos"]["calificacion_minima"] === "7.00" && $d["datos"]["ventana_partidos"] === 3'

humo_peticion "$e1" GET "/api/v1/equipos/$equipo_a/jugadores/$f1/desempeno"
humo_paso 8 "E1 consulta el desempeño de F1: datos_insuficientes con el umbral nuevo" 200 \
    '$d["datos"]["resultado"] === "datos_insuficientes" && $d["datos"]["promedio"] === null && $d["datos"]["ventana_partidos"] === 3 && $d["datos"]["partidos_considerados"] === 0'

inexistente=$(php -r 'require "vendor/autoload.php"; echo Src\Compartido\Dominio\Identificador::generar()->valor();')
humo_iniciar_sesion "$e2" "$correo_entrenador2" "$contrasena"
humo_paso 9 "E2 inicia sesión (otro archivo de cookies)" 200
humo_peticion "$e2" GET "/api/v1/partidos/$inexistente/rendimientos"
humo_paso 9 "E2 lista los rendimientos de un partido inexistente (referencia)" 404 '$d["codigo"] === "recurso_no_encontrado"'
referencia=$HUMO_CUERPO
humo_peticion "$e2" GET "/api/v1/partidos/$pj1/rendimientos"
humo_igual 9 "E2 lista los rendimientos de PJ1: 404 igual byte a byte" 404 "$referencia"

humo_peticion "$e2" PATCH "/api/v1/rendimientos/$rendimiento" '{"calificacion_general":1}'
humo_igual 10 "E2 intenta corregir el rendimiento de E1: 404 igual byte a byte" 404 "$referencia"

humo_iniciar_sesion "$docente" "$correo_docente" "$contrasena"
humo_paso 11 "el docente inicia sesión" 200
humo_peticion "$docente" GET /api/v1/partidos/pendientes-de-rendimiento
humo_paso 11 "el docente consulta pendientes: 403" 403 '$d["codigo"] === "sin_permiso"'

humo_peticion "$sin_sesion" GET "/api/v1/equipos/$equipo_a/jugadores/$f1/desempeno"
humo_paso 12 "sin sesión, el desempeño responde 401" 401 '$d["codigo"] === "no_autenticado"'

humo_peticion "$e1" POST "/api/v1/partidos/$pj1/rendimientos" "$(captura "$f1" 151 "$(metricas goles asistencias tiros_a_porteria)")"
humo_paso 13 "E1 captura a F1 con 151 minutos: valor_fuera_de_rango" 422 \
    '$d["codigo"] === "valor_fuera_de_rango" && $d["errores"][0]["campo"] === "minutos"'

# Se conserva una copia de la cookie anterior al cierre: es la que el servidor debe dejar de reconocer.
cp "$e1" "${HUMO_TMP}/entrenador1.antes"
humo_peticion "$e1" DELETE /api/v1/sesion
humo_paso 14 "E1 cierra sesión" 204
humo_peticion "${HUMO_TMP}/entrenador1.antes" GET /api/v1/metricas
humo_paso 14 "la misma cookie responde 401" 401

echo "HUMO H4 OK: 14 pasos"
