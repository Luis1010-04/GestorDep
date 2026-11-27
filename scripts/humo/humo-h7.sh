#!/usr/bin/env bash
# Guion de humo del hito F1-H7 (docs/hitos/f1-h7.md §9, v0.6). `make humo` lo ejecuta dentro del contenedor desde
# /scripts (montaje de solo lectura), contra una base recien sembrada (`make fresh`) y despues de los humos de H0 a H6.
# Crea por la API todos sus datos, con nombres propios que no chocan con los de H1 a H6: la categoria "Sub-6", la
# matricula HUMO-H7-001, el periodo "Humo H7" y la asignatura HUM-701.
set -euo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

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

# Fechas relativas al dia de negocio (America/Mexico_City), para que el periodo contenga hoy.
fecha() { php -r 'echo (new DateTimeImmutable("now", new DateTimeZone("America/Mexico_City")))->modify($argv[1])->format("Y-m-d");' -- "$1"; }
matricula="HUMO-H7-001"

# Paso 1: E1 inicia sesión, crea su categoría y su equipo A y registra a F.
humo_iniciar_sesion "$e1" "$correo_entrenador1" "$contrasena"
humo_paso 1 "E1 inicia sesión" 200 '$d["datos"]["roles"] === ["entrenador"]'
humo_peticion "$e1" POST /api/v1/categorias '{"nombre":"Sub-6"}'
humo_paso 1 "E1 crea la categoría Sub-6" 201
categoria=$(humo_campo datos.id)
humo_peticion "$e1" POST /api/v1/equipos "{\"nombre\":\"Linces Sub-6\",\"categoria_id\":\"$categoria\"}"
humo_paso 1 "E1 crea el equipo A" 201
equipo_a=$(humo_campo datos.id)
humo_peticion "$e1" POST /api/v1/jugadores "{\"nombres\":\"Efe\",\"apellidos\":\"Prueba Humo Siete\",\"fecha_nacimiento\":\"2020-05-12\",\"matricula\":\"$matricula\",\"equipo_id\":\"$equipo_a\",\"posicion\":\"medio\"}"
humo_paso 1 "E1 registra a F en A" 201
f=$(humo_campo datos.id)

# Paso 2: el docente 1 crea un periodo vigente, una asignatura y una materia, e inscribe a F.
humo_iniciar_sesion "$docente" "$correo_docente" "$contrasena"
humo_paso 2 "el docente 1 inicia sesión" 200 '$d["datos"]["roles"] === ["docente"]'
humo_peticion "$docente" POST /api/v1/periodos-escolares "{\"nombre\":\"Humo H7\",\"fecha_inicio\":\"$(fecha "-30 days")\",\"fecha_fin\":\"$(fecha "+120 days")\"}"
humo_paso 2 "el docente 1 crea un periodo que contiene hoy" 201 '$d["datos"]["vigente"] === true'
periodo=$(humo_campo datos.id)
humo_peticion "$docente" POST /api/v1/asignaturas '{"clave":"HUM-701","nombre":"Historia","grado":1}'
humo_paso 2 "el docente 1 crea la asignatura HUM-701" 201
asignatura=$(humo_campo datos.id)
humo_peticion "$docente" POST /api/v1/materias "{\"asignatura_id\":\"$asignatura\",\"periodo_id\":\"$periodo\"}"
humo_paso 2 "el docente 1 crea la materia" 201
materia=$(humo_campo datos.id)
humo_peticion "$docente" POST "/api/v1/materias/$materia/inscripciones" "{\"matricula\":\"$matricula\"}"
humo_paso 2 "el docente 1 inscribe a F" 201
inscripcion=$(humo_campo datos.id)

# Paso 3: el docente 1 captura una calificación de F y la corrige.
humo_peticion "$docente" POST "/api/v1/inscripciones/$inscripcion/calificaciones" '{"tipo":"parcial","numero_parcial":1,"valor":"7.50"}'
humo_paso 3 "el docente 1 captura una calificación de F" 201
calificacion=$(humo_campo datos.id)
humo_peticion "$docente" PATCH "/api/v1/calificaciones/$calificacion" '{"valor":"8.50"}'
humo_paso 3 "el docente 1 corrige la calificación" 200

# Paso 4: el historial de calificaciones de F, con crear y actualizar, autor y valores anterior y nuevo.
humo_peticion "$docente" GET "/api/v1/jugadores/$f/historial/calificaciones"
humo_paso 4 "el docente 1 consulta el historial de calificaciones de F: crear y actualizar, con autor y valores" 200 \
    'count($d["datos"]) === 2
     && ($e = array_column($d["datos"], null, "operacion")) && isset($e["crear"], $e["actualizar"])
     && $e["crear"]["datos_anteriores"] === null && (float) $e["crear"]["datos_nuevos"]["valor"] === 7.5
     && (float) $e["actualizar"]["datos_anteriores"]["valor"] === 7.5 && (float) $e["actualizar"]["datos_nuevos"]["valor"] === 8.5
     && $e["crear"]["autor"]["nombre"] !== "" && $e["actualizar"]["autor"]["id"] === $e["crear"]["autor"]["id"]
     && $e["crear"]["ocurrido_en"] !== ""'

# Paso 5: E1 registra una medición de F y consulta el historial de mediciones.
humo_peticion "$e1" POST "/api/v1/jugadores/$f/mediciones" "{\"equipo_id\":\"$equipo_a\",\"peso_kg\":\"30.00\",\"estatura_cm\":\"125.0\"}"
humo_paso 5 "E1 registra una medición de F" 201
humo_peticion "$e1" GET "/api/v1/jugadores/$f/historial/mediciones"
humo_paso 5 "E1 consulta el historial de mediciones de F: la entrada crear, sin autor (DA-125)" 200 \
    'count($d["datos"]) === 1 && $d["datos"][0]["operacion"] === "crear" && ! array_key_exists("autor", $d["datos"][0])'

# Paso 6: E2, otro entrenador sin F, no ve el historial de mediciones de F.
humo_iniciar_sesion "$e2" "$correo_entrenador2" "$contrasena"
humo_paso 6 "E2 inicia sesión" 200
humo_peticion "$e2" GET "/api/v1/jugadores/$f/historial/mediciones"
humo_paso 6 "E2 consulta el historial de mediciones de F" 404

# Paso 7: el docente 1 recibe del historial de mediciones lo mismo que de la lectura de mediciones.
humo_peticion "$docente" GET "/api/v1/jugadores/$f/mediciones"
lectura_codigo=$HUMO_CODIGO
lectura_cuerpo=$HUMO_CUERPO
humo_peticion "$docente" GET "/api/v1/jugadores/$f/historial/mediciones"
if [ "$HUMO_CODIGO" != "$lectura_codigo" ] || [ "$HUMO_CUERPO" != "$lectura_cuerpo" ]; then
    printf 'FALLA  paso 7: el historial de mediciones (HTTP %s) no responde como la lectura (HTTP %s)\n' "$HUMO_CODIGO" "$lectura_codigo"
    printf '       historial: %s\n       lectura: %s\n' "$HUMO_CUERPO" "$lectura_cuerpo"
    exit 1
fi
printf 'OK     paso 7: el docente 1 recibe del historial de mediciones lo mismo que de la lectura -> HTTP %s\n' "$HUMO_CODIGO"

# Paso 8: sin sesión, el historial de calificaciones responde 401.
humo_peticion "$sin_sesion" GET "/api/v1/jugadores/$f/historial/calificaciones"
humo_paso 8 "sin sesión, el historial de calificaciones de F" 401

# Paso 9: E1 y el docente 1 cierran sesión.
humo_peticion "$e1" DELETE /api/v1/sesion
humo_paso 9 "E1 cierra sesión" 204
humo_peticion "$docente" DELETE /api/v1/sesion
humo_paso 9 "el docente 1 cierra sesión" 204

echo "HUMO H7 OK: 9 pasos"
