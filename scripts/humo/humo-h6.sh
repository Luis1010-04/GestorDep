#!/usr/bin/env bash
# Guion de humo del hito F1-H6 (docs/hitos/f1-h6.md §9, v1.15). `make humo` lo ejecuta dentro del contenedor desde
# /scripts (montaje de solo lectura), contra una base recien sembrada (`make fresh`) y despues de los humos de H0 a H5.
# Crea por la API todos sus datos, con nombres propios que no chocan con los de H1 a H5: la categoria "Sub-7", la
# matricula HUMO-H6-001, el periodo "Humo H6" y la asignatura HUM-601. La temporada que contiene hoy la crea H3; si este
# guion corre solo, la crea el.
#
# El servidor del guion arranca con QUEUE_CONNECTION=sync, como el de H2: la alerta academica (escucha en cola, DA-88) se
# abre o se cierra dentro de la misma peticion que captura o corrige la calificacion, de modo que el paso siguiente ya
# la ve. `alertas:recalcular` (las alertas deportivas y de sanciones, DA-88) se ejecuta con `php /var/www/html/artisan`.
# El listado de estatus se recorre pagina por pagina hasta encontrar a F o llegar a la ultima (`humo_estatus`), para no
# depender de cuantos jugadores dejaron los humos anteriores.
set -euo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

export QUEUE_CONNECTION=sync
echo "Servidor del guion con QUEUE_CONNECTION=${QUEUE_CONNECTION}"
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

# Fechas e instantes relativos al dia de negocio (America/Mexico_City), para que el periodo y la temporada contengan hoy.
fecha() { php -r 'echo (new DateTimeImmutable("now", new DateTimeZone("America/Mexico_City")))->modify($argv[1])->format("Y-m-d");' -- "$1"; }
instante() { php -r 'echo (new DateTimeImmutable("today ".$argv[1], new DateTimeZone("America/Mexico_City")))->format(DATE_ATOM);' -- "$1"; }
hoy=$(fecha "+0 days")
matricula="HUMO-H6-001"

# Expresion PHP que selecciona, en `$d["datos"]` del listado de estatus, la fila del jugador $1.
fila_de() { printf 'array_values(array_filter($d["datos"], fn ($x) => $x["jugador"]["id"] === "%s"))' "$1"; }

# humo_estatus <cookies> <consulta> <jugador>: pide el listado de estatus pagina por pagina (`pagina=1`, `2`...) hasta
# encontrar al jugador o llegar a la ultima pagina. <consulta> es vacia o termina en `&`, por ejemplo
# `filtro%5Btipo_alerta%5D=otra&`. Deja HUMO_CUERPO como {"datos": [las filas recorridas]} y HUMO_CODIGO como el de la
# primera pagina que no respondio 200 (si la hay); sin ella, 200.
humo_estatus() {
    local cookies=$1 consulta=$2 jugador=$3 pagina=1 filas='[]' estado fin
    while :; do
        humo_peticion "$cookies" GET "/api/v1/estatus-de-jugadores?${consulta}pagina=${pagina}"
        [ "$HUMO_CODIGO" = 200 ] || return 0
        estado=$(printf '%s' "$HUMO_CUERPO" | php -r '
            $c = json_decode(stream_get_contents(STDIN), true);
            $filas = array_merge(json_decode($argv[1], true), $c["datos"]);
            $hallado = in_array($argv[2], array_map(fn ($x) => $x["jugador"]["id"], $filas), true);
            echo json_encode(["filas" => $filas, "fin" => $hallado || $c["meta"]["pagina"] >= $c["meta"]["total_paginas"]]);' -- "$filas" "$jugador")
        filas=$(printf '%s' "$estado" | php -r 'echo json_encode(json_decode(stream_get_contents(STDIN), true)["filas"]);')
        fin=$(printf '%s' "$estado" | php -r 'echo json_decode(stream_get_contents(STDIN), true)["fin"] ? "si" : "no";')
        [ "$fin" = si ] && break
        pagina=$((pagina + 1))
    done
    HUMO_CUERPO=$(printf '{"datos":%s}' "$filas")
}

# Paso 1: E1 inicia sesión; crea su categoría, su equipo A, un lugar y un rival, y registra a F.
humo_iniciar_sesion "$e1" "$correo_entrenador1" "$contrasena"
humo_paso 1 "E1 inicia sesión" 200 '$d["datos"]["roles"] === ["entrenador"]'
humo_peticion "$e1" POST /api/v1/categorias '{"nombre":"Sub-7"}'
humo_paso 1 "E1 crea la categoría Sub-7" 201
categoria=$(humo_campo datos.id)
humo_peticion "$e1" POST /api/v1/equipos "{\"nombre\":\"Gatos Sub-7\",\"categoria_id\":\"$categoria\"}"
humo_paso 1 "E1 crea el equipo A" 201
equipo_a=$(humo_campo datos.id)
humo_peticion "$e1" POST /api/v1/temporadas "{\"nombre\":\"Temporada Humo H6\",\"fecha_inicio\":\"$(fecha "-30 days")\",\"fecha_fin\":\"$(fecha "+120 days")\"}"
if [ "$HUMO_CODIGO" = 409 ]; then
    humo_paso 1 "ya hay una temporada que contiene hoy (la del humo de H3)" 409 '$d["codigo"] === "temporada_solapada"'
else
    humo_paso 1 "E1 crea la temporada que contiene hoy" 201
fi
humo_peticion "$e1" POST /api/v1/lugares '{"nombre":"Cancha Humo H6"}'
humo_paso 1 "E1 crea un lugar" 201
lugar=$(humo_campo datos.id)
humo_peticion "$e1" POST /api/v1/rivales '{"nombre":"Rival Humo H6"}'
humo_paso 1 "E1 crea un rival" 201
rival=$(humo_campo datos.id)
humo_peticion "$e1" POST /api/v1/jugadores "{\"nombres\":\"Efe\",\"apellidos\":\"Prueba Humo Seis\",\"fecha_nacimiento\":\"2019-05-12\",\"matricula\":\"$matricula\",\"equipo_id\":\"$equipo_a\",\"posicion\":\"delantero\"}"
humo_paso 1 "E1 registra a F en A" 201
f=$(humo_campo datos.id)

# Paso 2: el docente 1 crea un periodo vigente, una asignatura y una materia, e inscribe a F.
humo_iniciar_sesion "$docente" "$correo_docente" "$contrasena"
humo_paso 2 "el docente 1 inicia sesión" 200 '$d["datos"]["roles"] === ["docente"]'
humo_peticion "$docente" POST /api/v1/periodos-escolares "{\"nombre\":\"Humo H6\",\"fecha_inicio\":\"$(fecha "-30 days")\",\"fecha_fin\":\"$(fecha "+120 days")\"}"
humo_paso 2 "el docente 1 crea un periodo que contiene hoy" 201 '$d["datos"]["vigente"] === true'
periodo=$(humo_campo datos.id)
humo_peticion "$docente" POST /api/v1/asignaturas '{"clave":"HUM-601","nombre":"Lenguaje","grado":1}'
humo_paso 2 "el docente 1 crea la asignatura HUM-601" 201
asignatura=$(humo_campo datos.id)
humo_peticion "$docente" POST /api/v1/materias "{\"asignatura_id\":\"$asignatura\",\"periodo_id\":\"$periodo\"}"
humo_paso 2 "el docente 1 crea la materia" 201
materia=$(humo_campo datos.id)
humo_peticion "$docente" POST "/api/v1/materias/$materia/inscripciones" "{\"matricula\":\"$matricula\"}"
humo_paso 2 "el docente 1 inscribe a F" 201
inscripcion=$(humo_campo datos.id)

# Paso 3: cinco partidos de A con F convocado, el resultado y el rendimiento de F con calificación general 4.
humo_peticion "$e1" GET /api/v1/metricas
humo_paso 3 "E1 consulta el catálogo de métricas" 200
catalogo=$HUMO_CUERPO
# metricas <codigo>...: la lista {metrica_id, valor} de esos códigos del catálogo, con valor 1.
metricas() {
    printf '%s' "$catalogo" | php -r '
        $ids = array_column(json_decode(stream_get_contents(STDIN), true)["datos"], "id", "codigo");
        echo json_encode(array_map(fn ($c) => ["metrica_id" => $ids[$c], "valor" => 1], array_slice($argv, 1)));' -- "$@"
}
captura_de_f() { printf '{"jugador_id":"%s","minutos":90,"fue_titular":true,"tarjetas_amarillas":0,"tarjetas_rojas":0,"calificacion_general":4,"observacion":"Humo H6","metricas":%s}' "$f" "$(metricas goles asistencias)"; }
for hora in 08:00 10:00 12:00 14:00 16:00; do
    humo_peticion "$e1" POST "/api/v1/equipos/$equipo_a/partidos" \
        "{\"inicia_en\":\"$(instante "$hora")\",\"lugar_id\":\"$lugar\",\"rival_id\":\"$rival\",\"tipo\":\"amistoso\",\"condicion\":\"local\"}"
    humo_paso 3 "E1 crea el partido de las $hora" 201
    partido=$(humo_campo datos.id)
    humo_peticion "$e1" POST "/api/v1/partidos/$partido/convocatoria" "{\"jugador_id\":\"$f\"}"
    humo_paso 3 "E1 convoca a F" 201
    humo_peticion "$e1" PUT "/api/v1/partidos/$partido/resultado" '{"goles_favor":0,"goles_contra":1}'
    humo_paso 3 "E1 captura el resultado: el partido queda jugado" 200 '$d["datos"]["estado"] === "jugado"'
    humo_peticion "$e1" POST "/api/v1/partidos/$partido/rendimientos" "$(captura_de_f)"
    humo_paso 3 "E1 registra el rendimiento de F con calificación general 4" 201
done
humo_peticion "$e1" GET "/api/v1/equipos/$equipo_a/jugadores/$f/desempeno"
humo_paso 3 "la evaluación de F queda en no_pasa (umbral 6.00, ventana de 5)" 200 \
    '$d["datos"]["resultado"] === "no_pasa" && $d["datos"]["partidos_considerados"] === 5'

# Paso 4: alertas:recalcular (código 0) y la alerta deportiva de F.
php /var/www/html/artisan alertas:recalcular > "${HUMO_TMP}/recalcular.salida" 2>&1 || {
    printf 'FALLA  paso 4: alertas:recalcular terminó con un código distinto de 0\n'
    cat "${HUMO_TMP}/recalcular.salida"
    exit 1
}
printf 'OK     paso 4: alertas:recalcular -> código 0\n'
humo_peticion "$e1" GET "/api/v1/jugadores/$f/alertas"
humo_paso 4 "E1 consulta las alertas de F: aparece riesgo_baja_deportiva" 200 \
    'in_array("riesgo_baja_deportiva", array_column($d["datos"], "tipo"), true)'

# Paso 5: caso académico, activo.
humo_peticion "$docente" POST "/api/v1/inscripciones/$inscripcion/calificaciones" '{"tipo":"final","valor":"4.00"}'
humo_paso 5 "el docente 1 captura la calificación final 4.00 de F: queda en_riesgo" 201 '$d["datos"]["valor"] === "4.00"'
calificacion=$(humo_campo datos.id)
humo_peticion "$docente" GET "/api/v1/jugadores/$f/alertas"
humo_paso 5 "el docente 1 consulta las alertas de F: con riesgo_baja_academica y sin la deportiva" 200 \
    'in_array("riesgo_baja_academica", array_column($d["datos"], "tipo"), true) && ! in_array("riesgo_baja_deportiva", array_column($d["datos"], "tipo"), true)'

# Paso 6: caso académico, cerrado.
humo_peticion "$docente" PATCH "/api/v1/calificaciones/$calificacion" '{"valor":"9.00"}'
humo_paso 6 "el docente 1 corrige la calificación a 9.00: F queda sin_riesgo" 200 '$d["datos"]["valor"] === "9.00"'
humo_peticion "$docente" GET "/api/v1/jugadores/$f/alertas"
humo_paso 6 "el docente 1 consulta las alertas de F: sin riesgo_baja_academica" 200 \
    '! in_array("riesgo_baja_academica", array_column($d["datos"], "tipo"), true)'

# Paso 7: aviso de lesión del docente.
humo_peticion "$docente" POST "/api/v1/jugadores/$f/avisos-lesion" "{\"nota\":\"Dice que le duele el tobillo\",\"fecha\":\"$hoy\",\"reportado_por_jugador\":true}"
humo_paso 7 "el docente 1 crea un aviso de lesión de F" 201 '$d["datos"]["estado"] === "pendiente"'

# Paso 8: listado de estatus de E1.
humo_estatus "$e1" "" "$f"
humo_paso 8 "E1 consulta el listado de estatus: F con aviso_pendiente y la alerta deportiva" 200 \
    "count($(fila_de "$f")) === 1 && $(fila_de "$f")[0][\"aviso_pendiente\"] === true && in_array(\"riesgo_baja_deportiva\", array_column($(fila_de "$f")[0][\"alertas\"], \"tipo\"), true)"

# Paso 9: filtro por tipo de alerta.
humo_estatus "$e1" "filtro%5Btipo_alerta%5D=riesgo_baja_deportiva&" "$f"
humo_paso 9 "filtro[tipo_alerta]=riesgo_baja_deportiva: con F" 200 "count($(fila_de "$f")) === 1"
humo_estatus "$e1" "filtro%5Btipo_alerta%5D=riesgo_baja_academica&" "$f"
humo_paso 9 "filtro[tipo_alerta]=riesgo_baja_academica: sin F" 200 "count($(fila_de "$f")) === 0"
humo_peticion "$e1" GET "/api/v1/estatus-de-jugadores?filtro%5Btipo_alerta%5D=otra"
humo_paso 9 "filtro[tipo_alerta]=otra: 422 validacion_fallida" 422 '$d["codigo"] === "validacion_fallida"'

# Paso 10: vista para convocar de E1.
humo_peticion "$e1" GET "/api/v1/jugadores/$f/vista-para-convocar"
humo_paso 10 "E1 consulta la vista para convocar de F: las cinco dimensiones y ninguna posiblemente desactualizada" 200 \
    'array_diff(["rendimiento_reciente", "situacion_academica", "asistencia", "estado_fisico", "progreso"], array_keys($d["datos"])) === [] && strpos(json_encode($d), "\"posiblemente_desactualizado\":true") === false'

# Paso 11: el docente no ve la vista para convocar.
humo_peticion "$docente" GET "/api/v1/jugadores/$f/vista-para-convocar"
humo_paso 11 "el docente 1 consulta la vista para convocar de F: 403" 403

# Paso 12: gráficos del entrenador.
humo_peticion "$e1" GET "/api/v1/jugadores/$f/graficos"
humo_paso 12 "E1 pide los gráficos de F: cinco puntos en rendimiento_partidos" 200 \
    'count($d["datos"]["rendimiento_partidos"]["serie"]) === 5'

# Paso 13: gráficos del docente.
humo_peticion "$docente" GET "/api/v1/jugadores/$f/graficos"
humo_paso 13 "el docente 1 pide los gráficos de F: con calificaciones y sin mediciones" 200 \
    'array_key_exists("calificaciones", $d["datos"]) && count($d["datos"]["calificaciones"]) >= 1 && ! array_key_exists("mediciones", $d["datos"])'

# Paso 14: E2 (otro entrenador, equipo B) pide las alertas de F.
humo_iniciar_sesion "$e2" "$correo_entrenador2" "$contrasena"
humo_paso 14 "E2 inicia sesión" 200 '$d["datos"]["roles"] === ["entrenador"]'
humo_peticion "$e2" POST /api/v1/equipos "{\"nombre\":\"Osos Sub-7\",\"categoria_id\":\"$categoria\"}"
humo_paso 14 "E2 crea el equipo B" 201
humo_peticion "$e2" GET "/api/v1/jugadores/$f/alertas"
humo_paso 14 "E2 pide las alertas de F: 404" 404

# Paso 15: alertas:recalcular otra vez, sin duplicar la alerta deportiva.
php /var/www/html/artisan alertas:recalcular > "${HUMO_TMP}/recalcular.salida" 2>&1 || {
    printf 'FALLA  paso 15: alertas:recalcular terminó con un código distinto de 0\n'
    cat "${HUMO_TMP}/recalcular.salida"
    exit 1
}
printf 'OK     paso 15: alertas:recalcular otra vez -> código 0\n'
humo_peticion "$e1" GET "/api/v1/jugadores/$f/alertas"
humo_paso 15 "E1 consulta las alertas de F: una sola riesgo_baja_deportiva" 200 \
    'count(array_filter($d["datos"], fn ($a) => $a["tipo"] === "riesgo_baja_deportiva")) === 1'

# Paso 16: la baja de F.
humo_peticion "$e1" POST "/api/v1/jugadores/$f/baja" "{\"motivo\":\"Baja de demostración\",\"fecha\":\"$hoy\"}"
humo_paso 16 "E1 da de baja a F" 201
humo_estatus "$e1" "" "$f"
humo_paso 16 "F ya no aparece en el listado de estatus" 200 "count($(fila_de "$f")) === 0"
humo_peticion "$e1" GET "/api/v1/jugadores/$f/alertas"
humo_paso 16 "las alertas de F responden 404" 404

# Paso 17: sin sesión.
humo_peticion "$sin_sesion" GET "/api/v1/jugadores/$f/alertas"
humo_paso 17 "sin sesión, las alertas de F responden 401" 401

# Paso 18: cierre de sesión. Se conserva una copia de la cookie anterior al cierre: es la que el servidor debe dejar de
# reconocer.
cp "$e1" "${HUMO_TMP}/entrenador1.antes"
humo_peticion "$e1" DELETE /api/v1/sesion
humo_paso 18 "E1 cierra sesión" 204
humo_peticion "${HUMO_TMP}/entrenador1.antes" GET "/api/v1/estatus-de-jugadores"
humo_paso 18 "la misma cookie responde 401" 401

echo "HUMO H6 OK: 18 pasos"
