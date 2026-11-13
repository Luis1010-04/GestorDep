#!/usr/bin/env bash
# Guion de humo del hito F1-H5 (docs/hitos/f1-h5.md §9, v1.0.9). `make humo` lo ejecuta dentro del contenedor desde
# /scripts (montaje de solo lectura), contra una base recien sembrada (`make fresh`) y despues de los humos de H0 a H4.
# Crea por la API los datos de partida que usa: E1 entrena el equipo A con F, E2 el equipo B con F, y el docente 1 tiene
# a F inscrito en una materia de un periodo vigente. Usa datos propios que no chocan con los de H1 a H4: la categoria
# "Sub-9", la matricula HUMO-H5-001, el periodo "Humo H5" y la asignatura HUM-501. La temporada que contiene hoy la
# crea H3; si este guion corre solo, la crea el.
set -euo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

# humo_igual <n> <descripcion> <codigo_esperado> <cuerpo_de_referencia>: como humo_paso, pero exige ademas que el cuerpo
# sea identico byte a byte al de referencia (RN-15: una lesion ajena responde igual que una inexistente).
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

# Fechas e instantes relativos al dia de negocio (America/Mexico_City, DD-36), para que el periodo y la temporada
# contengan hoy y las fechas del aviso y de la prueba no sean futuras.
fecha() { php -r 'echo (new DateTimeImmutable("now", new DateTimeZone("America/Mexico_City")))->modify($argv[1])->format("Y-m-d");' -- "$1"; }
instante() { php -r 'echo (new DateTimeImmutable("today ".$argv[1], new DateTimeZone("America/Mexico_City")))->format(DATE_ATOM);' -- "$1"; }
hoy=$(fecha "+0 days")
regreso=$(fecha "+14 days")
periodo_del_mes=${hoy:0:7}
matricula="HUMO-H5-001"

humo_iniciar_sesion "$e1" "$correo_entrenador1" "$contrasena"
humo_paso 1 "E1 inicia sesión" 200 '$d["datos"]["roles"] === ["entrenador"]'
humo_peticion "$e1" POST /api/v1/categorias '{"nombre":"Sub-9"}'
humo_paso 1 "E1 crea la categoría Sub-9" 201
categoria=$(humo_campo datos.id)
humo_peticion "$e1" POST /api/v1/equipos "{\"nombre\":\"Zorros Sub-9\",\"categoria_id\":\"$categoria\"}"
humo_paso 1 "E1 crea el equipo A" 201
equipo_a=$(humo_campo datos.id)
humo_peticion "$e1" POST /api/v1/jugadores "{\"nombres\":\"Efe\",\"apellidos\":\"Prueba Humo\",\"fecha_nacimiento\":\"2017-05-12\",\"matricula\":\"$matricula\",\"equipo_id\":\"$equipo_a\",\"posicion\":\"delantero\"}"
humo_paso 1 "E1 registra a F en A" 201
f=$(humo_campo datos.id)

humo_iniciar_sesion "$e2" "$correo_entrenador2" "$contrasena"
humo_paso 2 "E2 inicia sesión" 200 '$d["datos"]["roles"] === ["entrenador"]'
humo_peticion "$e2" POST /api/v1/equipos "{\"nombre\":\"Osos Sub-9\",\"categoria_id\":\"$categoria\"}"
humo_paso 2 "E2 crea el equipo B" 201
equipo_b=$(humo_campo datos.id)
humo_peticion "$e2" POST "/api/v1/equipos/$equipo_b/jugadores" "{\"matricula\":\"$matricula\",\"posicion\":\"delantero\"}"
humo_paso 2 "E2 asigna a F a B por matrícula" 201

humo_iniciar_sesion "$docente" "$correo_docente" "$contrasena"
humo_paso 3 "el docente 1 inicia sesión" 200 '$d["datos"]["roles"] === ["docente"]'
humo_peticion "$docente" POST /api/v1/periodos-escolares "{\"nombre\":\"Humo H5\",\"fecha_inicio\":\"$(fecha "-30 days")\",\"fecha_fin\":\"$(fecha "+120 days")\"}"
humo_paso 3 "el docente 1 crea un periodo que contiene hoy" 201 '$d["datos"]["vigente"] === true'
periodo=$(humo_campo datos.id)
humo_peticion "$docente" POST /api/v1/asignaturas '{"clave":"HUM-501","nombre":"Ciencias","grado":1}'
humo_paso 3 "el docente 1 crea la asignatura HUM-501" 201
asignatura=$(humo_campo datos.id)
humo_peticion "$docente" POST /api/v1/materias "{\"asignatura_id\":\"$asignatura\",\"periodo_id\":\"$periodo\"}"
humo_paso 3 "el docente 1 crea la materia" 201
materia=$(humo_campo datos.id)
humo_peticion "$docente" POST "/api/v1/materias/$materia/inscripciones" "{\"matricula\":\"$matricula\"}"
humo_paso 3 "el docente 1 inscribe a F" 201

humo_peticion "$docente" POST "/api/v1/jugadores/$f/avisos-lesion" "{\"nota\":\"Dice que le duele el tobillo\",\"fecha\":\"$hoy\",\"reportado_por_jugador\":true}"
humo_paso 4 "el docente 1 crea un aviso de F reportado por el jugador" 201 \
    '$d["datos"]["estado"] === "pendiente" && $d["datos"]["reportado_por_jugador"] === true'
aviso=$(humo_campo datos.id)

humo_peticion "$e1" GET "/api/v1/jugadores/$f/estado-disponibilidad"
humo_paso 5 "E1 consulta el estado de F: apto implícito, el aviso no lo cambia" 200 \
    '$d["datos"]["estado"] === "apto" && $d["datos"]["es_implicito"] === true'

humo_peticion "$docente" POST "/api/v1/avisos-lesion/$aviso/confirmacion" '{"estado":"apto"}'
humo_paso 6 "el docente 1 intenta confirmar el aviso: 403" 403 '$d["codigo"] === "sin_permiso"'

humo_peticion "$e2" POST "/api/v1/avisos-lesion/$aviso/confirmacion" \
    "{\"estado\":\"lesionado\",\"tipo\":\"Esguince\",\"zona\":\"Tobillo\",\"causa\":\"partido\",\"fecha_estimada_regreso\":\"$regreso\"}"
humo_paso 7 "E2 confirma el aviso como lesionado con fecha estimada" 200 '$d["datos"]["estado"] === "confirmado"'
humo_peticion "$e2" GET "/api/v1/jugadores/$f/estado-disponibilidad"
humo_paso 7 "E2 ve el estado que registró, con su lesión" 200 \
    '$d["datos"]["estado"] === "lesionado" && $d["datos"]["fecha_estimada_regreso"] === "'"$regreso"'" && $d["datos"]["lesion_id"] !== null'
lesion=$(humo_campo datos.lesion_id)

humo_peticion "$e1" GET "/api/v1/jugadores/$f/estado-disponibilidad"
humo_paso 8 "E1 consulta el estado de F: lesionado, la misma fecha que ve E2 y sin lesion_id" 200 \
    '$d["datos"]["estado"] === "lesionado" && $d["datos"]["fecha_estimada_regreso"] === "'"$regreso"'" && $d["datos"]["lesion_id"] === null'

inexistente=$(php -r 'require "vendor/autoload.php"; echo Src\Compartido\Dominio\Identificador::generar()->valor();')
humo_peticion "$e1" GET "/api/v1/lesiones/$inexistente"
humo_paso 9 "E1 pide una lesión inexistente (referencia)" 404 '$d["codigo"] === "recurso_no_encontrado"'
referencia=$HUMO_CUERPO
humo_peticion "$e1" GET "/api/v1/lesiones/$lesion"
humo_igual 9 "E1 pide el detalle de la lesión de E2: 404 igual byte a byte" 404 "$referencia"

humo_peticion "$e1" PATCH "/api/v1/jugadores/$f/estado-disponibilidad" "{\"fecha_estimada_regreso\":\"$(fecha "+21 days")\"}"
humo_paso 10 "E1 intenta cambiar la fecha estimada: 403" 403 '$d["codigo"] === "no_es_registrante"'

humo_peticion "$e1" POST /api/v1/temporadas "{\"nombre\":\"Temporada Humo H5\",\"fecha_inicio\":\"$(fecha "-30 days")\",\"fecha_fin\":\"$(fecha "+120 days")\"}"
if [ "$HUMO_CODIGO" = 409 ]; then
    humo_paso 11 "ya hay una temporada que contiene hoy (la del humo de H3)" 409 '$d["codigo"] === "temporada_solapada"'
else
    humo_paso 11 "E1 crea la temporada que contiene hoy" 201
fi
humo_peticion "$e1" POST /api/v1/lugares '{"nombre":"Cancha Humo H5"}'
humo_paso 11 "E1 crea un lugar" 201
lugar=$(humo_campo datos.id)
humo_peticion "$e1" POST /api/v1/rivales '{"nombre":"Rival Humo H5"}'
humo_paso 11 "E1 crea un rival" 201
rival=$(humo_campo datos.id)
humo_peticion "$e1" POST "/api/v1/equipos/$equipo_a/partidos" \
    "{\"inicia_en\":\"$(instante 15:00)\",\"lugar_id\":\"$lugar\",\"rival_id\":\"$rival\",\"tipo\":\"amistoso\",\"condicion\":\"local\"}"
humo_paso 11 "E1 crea un partido de A" 201
partido=$(humo_campo datos.id)
humo_peticion "$e1" POST "/api/v1/partidos/$partido/convocatoria" "{\"jugador_id\":\"$f\"}"
humo_paso 11 "E1 convoca a F: 201 con la advertencia jugador_lesionado" 201 \
    'count(array_filter($d["advertencias"] ?? [], fn ($a) => $a["codigo"] === "jugador_lesionado" && $a["campo"] === "jugador_id")) === 1'

humo_peticion "$e1" POST "/api/v1/jugadores/$f/mediciones" "{\"equipo_id\":\"$equipo_a\",\"peso_kg\":\"70.00\",\"estatura_cm\":\"175.0\"}"
humo_paso 12 "E1 registra una medición de F (70.00 kg, 175.0 cm): IMC calculado" 201 '$d["datos"]["imc"] === "22.86"'

humo_peticion "$e1" POST "/api/v1/jugadores/$f/mediciones" "{\"equipo_id\":\"$equipo_a\",\"peso_kg\":\"71.00\",\"estatura_cm\":\"175.0\"}"
humo_paso 13 "E1 registra otra en el mismo periodo: 409" 409 '$d["codigo"] === "medicion_ya_registrada"'
humo_peticion "$e1" POST "/api/v1/jugadores/$f/mediciones" "{\"equipo_id\":\"$equipo_a\",\"peso_kg\":\"71.00\",\"estatura_cm\":\"175.0\",\"es_correccion\":true,\"motivo\":\"Error de captura\"}"
humo_paso 13 "E1 la corrige con es_correccion y motivo" 200 '$d["datos"]["peso_kg"] === "71.00"'

humo_peticion "$e2" GET "/api/v1/jugadores/$f/mediciones"
humo_paso 14 "E2 lista las mediciones de F: sin las de E1" 200 '$d["datos"] === []'

humo_peticion "$e1" GET /api/v1/tipos-prueba
humo_paso 15 "E1 consulta los tipos de prueba sembrados" 200 'count($d["datos"]) === 3'
velocidad=$(printf '%s' "$HUMO_CUERPO" | php -r 'echo array_column(json_decode(stream_get_contents(STDIN), true)["datos"], "id", "codigo")["velocidad"];')
humo_peticion "$e1" POST "/api/v1/jugadores/$f/pruebas-fisicas" "{\"equipo_id\":\"$equipo_a\",\"tipo_prueba_id\":\"$velocidad\",\"resultado\":\"4.35\",\"fecha\":\"$hoy\"}"
humo_paso 15 "E1 registra una prueba de velocidad" 201
humo_peticion "$e1" GET "/api/v1/jugadores/$f/pruebas-fisicas?filtro%5Bperiodo%5D=$periodo_del_mes"
humo_paso 15 "E1 consulta el periodo: tres tipos, dos con prueba nula" 200 \
    'count($d["datos"]) === 3 && count(array_filter($d["datos"], fn ($t) => $t["prueba"] === null)) === 2'

humo_peticion "$e2" POST "/api/v1/jugadores/$f/estado-disponibilidad/alta" '{}'
humo_paso 16 "E2 da de alta a F: apto" 200 '$d["datos"]["estado"] === "apto"'

humo_peticion "$sin_sesion" GET "/api/v1/jugadores/$f/estado-disponibilidad"
humo_paso 17 "sin sesión, el estado de F responde 401" 401 '$d["codigo"] === "no_autenticado"'

# Se conserva una copia de la cookie anterior al cierre: es la que el servidor debe dejar de reconocer.
cp "$e1" "${HUMO_TMP}/entrenador1.antes"
humo_peticion "$e1" DELETE /api/v1/sesion
humo_paso 18 "E1 cierra sesión" 204
humo_peticion "${HUMO_TMP}/entrenador1.antes" GET "/api/v1/jugadores/$f/estado-disponibilidad"
humo_paso 18 "la misma cookie responde 401" 401

echo "HUMO H5 OK: 18 pasos"
