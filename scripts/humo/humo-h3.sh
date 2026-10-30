#!/usr/bin/env bash
# Guion de humo del hito F1-H3 (docs/hitos/f1-h3.md §9). `make humo-h3` lo ejecuta dentro del contenedor desde /scripts
# (montaje de solo lectura), contra una base recien sembrada (`make fresh`). Crea por la API los datos de partida de §7.0
# que usa: E1 entrena el equipo A (J1, J2, J3 y, mas tarde, J5); E2 entrena el equipo B (J3 y J4). Usa datos propios que no
# chocan con los de H1 y H2: la categoria "Sub-13", las matriculas HUMO-H3-00n y una temporada que contiene hoy.
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
hoy=$(fecha "today")

humo_peticion "$sin_sesion" GET /api/v1/salud
humo_paso 1 "GET /api/v1/salud" 200

humo_iniciar_sesion "$e1" "$correo_entrenador1" "$contrasena"
humo_paso 2 "E1 inicia sesión" 200 '$d["datos"]["roles"] === ["entrenador"]'
entrenador1=$(humo_campo datos.id)

# Datos de partida de §7.0 por la API de H1: categoría, equipo A y sus jugadores J1, J2 y J3.
humo_peticion "$e1" POST /api/v1/categorias '{"nombre":"Sub-13"}'
humo_paso 2 "E1 crea la categoría Sub-13" 201
categoria=$(humo_campo datos.id)
humo_peticion "$e1" POST /api/v1/equipos "{\"nombre\":\"Halcones Sub-13\",\"categoria_id\":\"$categoria\"}"
humo_paso 2 "E1 crea el equipo A" 201
equipo_a=$(humo_campo datos.id)
# registrar_jugador <paso> <cookies> <nombres> <matricula> <equipo>: alta de un jugador por la API de H1.
registrar_jugador() {
    humo_peticion "$2" POST /api/v1/jugadores "{\"nombres\":\"$3\",\"apellidos\":\"Prueba Humo\",\"fecha_nacimiento\":\"2013-04-10\",\"matricula\":\"$4\",\"equipo_id\":\"$5\",\"posicion\":\"medio\"}"
    humo_paso "$1" "registra a $3 ($4)" 201
}
registrar_jugador 2 "$e1" "Jota Uno" HUMO-H3-001 "$equipo_a"
j1=$(humo_campo datos.id)
registrar_jugador 2 "$e1" "Jota Dos" HUMO-H3-002 "$equipo_a"
j2=$(humo_campo datos.id)
registrar_jugador 2 "$e1" "Jota Tres" HUMO-H3-003 "$equipo_a"
j3=$(humo_campo datos.id)

# E2 entrena el equipo B, con J4 y con J3 asignado por matrícula (J3 está en A y en B).
humo_iniciar_sesion "$e2" "$correo_entrenador2" "$contrasena"
humo_paso 2 "E2 inicia sesión (otro archivo de cookies)" 200
humo_peticion "$e2" POST /api/v1/equipos "{\"nombre\":\"Linces Sub-13\",\"categoria_id\":\"$categoria\"}"
humo_paso 2 "E2 crea el equipo B" 201
equipo_b=$(humo_campo datos.id)
registrar_jugador 2 "$e2" "Jota Cuatro" HUMO-H3-004 "$equipo_b"
humo_peticion "$e2" POST "/api/v1/equipos/$equipo_b/jugadores" '{"matricula":"HUMO-H3-003","posicion":"defensa"}'
humo_paso 2 "E2 asigna a J3 al equipo B" 201

humo_peticion "$e1" POST /api/v1/lugares '{"nombre":"Cancha Humo H3"}'
humo_paso 3 "E1 crea el lugar L2" 201
lugar=$(humo_campo datos.id)
humo_peticion "$e1" POST /api/v1/temporadas "{\"nombre\":\"Temporada Humo H3\",\"fecha_inicio\":\"$(fecha "-30 days")\",\"fecha_fin\":\"$(fecha "+120 days")\"}"
humo_paso 3 "E1 crea la temporada que contiene hoy" 201
temporada=$(humo_campo datos.id)

humo_peticion "$e1" POST /api/v1/torneos "{\"nombre\":\"Torneo Humo H3\",\"temporada_id\":\"$temporada\"}"
humo_paso 4 "E1 crea un torneo en esa temporada" 201 '$d["datos"]["temporada_id"] === "'"$temporada"'"'
torneo=$(humo_campo datos.id)

humo_peticion "$e1" POST "/api/v1/equipos/$equipo_a/entrenamientos" \
    "{\"inicia_en\":\"$(instante 08:00)\",\"termina_en\":\"$(instante 10:00)\",\"lugar_id\":\"$lugar\",\"responsable_id\":\"$entrenador1\"}"
humo_paso 5 "E1 crea un entrenamiento de A con J1, J2 y J3 listados" 201 \
    'count($d["datos"]["jugadores_del_equipo"]) === 3 && array_diff(["'"$j1"'", "'"$j2"'", "'"$j3"'"], array_column($d["datos"]["jugadores_del_equipo"], "id")) === [] && ! isset($d["advertencias"])'
entrenamiento=$(humo_campo datos.id)

humo_peticion "$e1" POST "/api/v1/equipos/$equipo_a/entrenamientos" \
    "{\"inicia_en\":\"$(instante 09:00)\",\"termina_en\":\"$(instante 11:00)\",\"lugar_id\":\"$lugar\",\"responsable_id\":\"$entrenador1\"}"
humo_paso 6 "E1 crea otro entrenamiento en L2 que se cruza: advierte empalme_de_lugar" 201 \
    'array_column($d["advertencias"], "codigo") === ["empalme_de_lugar"]'

humo_peticion "$e1" PATCH "/api/v1/entrenamientos/$entrenamiento" '{"estado":"realizado"}'
humo_paso 7 "E1 marca el primero realizado" 200 '$d["datos"]["estado"] === "realizado"'
humo_peticion "$e1" POST "/api/v1/entrenamientos/$entrenamiento/asistencias" \
    "{\"registros\":[{\"jugador_id\":\"$j1\",\"estado\":\"presente\"},{\"jugador_id\":\"$j2\",\"estado\":\"falta\"},{\"jugador_id\":\"$j3\",\"estado\":\"falta_justificada\"}]}"
humo_paso 7 "E1 captura la asistencia de J1, J2 y J3" 201 'count($d["datos"]) === 3'

# Fórmula de §3.4: presentes / (presentes + faltas + faltas_justificadas) × 100, dos decimales HALF_UP.
humo_peticion "$e1" GET "/api/v1/jugadores/$j1/asistencia-entrenamientos"
humo_paso 8 "E1 consulta el porcentaje de J1: 100.00" 200 \
    '$d["datos"]["porcentaje"] === "100.00" && $d["datos"]["presentes"] === 1 && $d["datos"]["faltas"] === 0'
humo_peticion "$e1" GET "/api/v1/equipos/$equipo_a/asistencia-entrenamientos"
humo_paso 8 "el porcentaje del equipo A es 33.33 (la falta justificada cuenta en el denominador)" 200 \
    '$d["datos"]["porcentaje"] === "33.33" && $d["datos"]["faltas_justificadas"] === 1'

humo_peticion "$e1" POST /api/v1/rivales '{"nombre":"Rival Humo H3"}'
humo_paso 9 "E1 crea el rival R1" 201
rival=$(humo_campo datos.id)
humo_peticion "$e1" POST "/api/v1/equipos/$equipo_a/partidos" \
    "{\"inicia_en\":\"$(instante 12:00)\",\"lugar_id\":\"$lugar\",\"rival_id\":\"$rival\",\"torneo_id\":\"$torneo\",\"tipo\":\"oficial\",\"condicion\":\"local\"}"
humo_paso 9 "E1 crea un partido oficial con el torneo, en la temporada esperada" 201 \
    '$d["datos"]["temporada_id"] === "'"$temporada"'" && $d["datos"]["estado"] === "programado"'
partido=$(humo_campo datos.id)

# J5 se registra en A después del paso 5 (que espera solo J1, J2 y J3) y se convoca para comprobar su baja en el paso 14.
registrar_jugador 10 "$e1" "Jota Cinco" HUMO-H3-005 "$equipo_a"
j5=$(humo_campo datos.id)
humo_peticion "$e1" POST "/api/v1/partidos/$partido/convocatoria" "{\"jugador_id\":\"$j1\"}"
humo_paso 10 "E1 convoca a J1" 201 '$d["datos"]["jugador_id"] === "'"$j1"'"'
humo_peticion "$e1" POST "/api/v1/partidos/$partido/convocatoria" "{\"jugador_id\":\"$j5\"}"
humo_paso 10 "E1 convoca a J5" 201
# El catálogo de posiciones no tiene ruta (H1 §5.6): se lee su identificador sembrado, sin modificar datos.
posicion=$(php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo Illuminate\Support\Facades\DB::table("posiciones")->where("codigo", "medio")->value("id");')
humo_peticion "$e1" PUT "/api/v1/partidos/$partido/alineacion" \
    "{\"alineacion\":[{\"jugador_id\":\"$j1\",\"posicion_id\":\"$posicion\",\"rol_alineacion\":\"titular\"}]}"
humo_paso 10 "E1 define la alineación: J1 titular" 200 \
    'count(array_filter($d["datos"], fn ($c) => $c["jugador_id"] === "'"$j1"'" && $c["rol_alineacion"] === "titular" && $c["posicion_id"] === "'"$posicion"'")) === 1'

humo_peticion "$e1" PUT "/api/v1/partidos/$partido/resultado" '{"goles_favor":2,"goles_contra":1}'
humo_paso 11 "E1 captura el resultado 2-1" 200 '$d["datos"]["goles_favor"] === 2 && $d["datos"]["goles_contra"] === 1'
humo_peticion "$e1" GET "/api/v1/partidos/$partido"
humo_paso 11 "el partido queda jugado" 200 '$d["datos"]["estado"] === "jugado"'

inexistente=$(php -r 'require "vendor/autoload.php"; echo Src\Compartido\Dominio\Identificador::generar()->valor();')
humo_peticion "$e2" GET "/api/v1/entrenamientos/$inexistente"
humo_paso 12 "E2 consulta un entrenamiento inexistente (referencia)" 404 '$d["codigo"] === "recurso_no_encontrado"'
referencia=$HUMO_CUERPO
humo_peticion "$e2" GET "/api/v1/entrenamientos/$entrenamiento"
humo_igual 12 "E2 consulta el entrenamiento de A: 404 igual byte a byte" 404 "$referencia"

humo_iniciar_sesion "$docente" "$correo_docente" "$contrasena"
humo_paso 13 "el docente inicia sesión" 200
humo_peticion "$docente" POST /api/v1/lugares '{"nombre":"Lugar del docente"}'
humo_paso 13 "el docente recibe 403 al crear un lugar" 403 '$d["codigo"] === "sin_permiso"'

humo_peticion "$e1" GET "/api/v1/partidos/$partido/convocatoria"
humo_paso 14 "antes de la baja, J5 está en la convocatoria" 200 'in_array("'"$j5"'", array_column($d["datos"], "jugador_id"), true)'
humo_peticion "$e1" POST "/api/v1/jugadores/$j5/baja" "{\"motivo\":\"Baja de demostración\",\"fecha\":\"$hoy\"}"
humo_paso 14 "E1 da de baja a J5" 201
humo_peticion "$e1" GET "/api/v1/partidos/$partido/convocatoria"
humo_paso 14 "J5 desaparece de la convocatoria y J1 sigue" 200 \
    '! in_array("'"$j5"'", array_column($d["datos"], "jugador_id"), true) && in_array("'"$j1"'", array_column($d["datos"], "jugador_id"), true)'

# El partido del paso 11 ya está jugado (409): el rango se prueba sobre un amistoso programado, sin torneo.
humo_peticion "$e1" POST "/api/v1/equipos/$equipo_a/partidos" \
    "{\"inicia_en\":\"$(instante 18:00)\",\"lugar_id\":\"$lugar\",\"rival_id\":\"$rival\",\"tipo\":\"amistoso\",\"condicion\":\"visitante\"}"
humo_paso 15 "E1 crea un amistoso programado" 201
amistoso=$(humo_campo datos.id)
humo_peticion "$e1" PUT "/api/v1/partidos/$amistoso/resultado" '{"goles_favor":100,"goles_contra":0}'
humo_paso 15 "100 goles responde valor_fuera_de_rango" 422 '$d["codigo"] === "valor_fuera_de_rango"'

# Se conserva una copia de la cookie anterior al cierre: es la que el servidor debe dejar de reconocer.
cp "$e1" "${HUMO_TMP}/entrenador1.antes"
humo_peticion "$e1" DELETE /api/v1/sesion
humo_paso 16 "E1 cierra sesión" 204
humo_peticion "${HUMO_TMP}/entrenador1.antes" GET "/api/v1/partidos/$partido"
humo_paso 16 "la misma cookie responde 401" 401

echo "HUMO H3 OK: 16 pasos"
