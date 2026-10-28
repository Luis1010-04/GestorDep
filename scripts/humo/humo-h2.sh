#!/usr/bin/env bash
# Guion de humo del hito F1-H2 (docs/hitos/f1-h2.md §9). `make humo` lo ejecuta dentro del contenedor desde /scripts
# (montaje de solo lectura), despues de humo-h1.sh y contra una base recien sembrada (`make fresh`). Usa datos propios
# que no chocan con los de H1: la categoria "Sub-15", la matricula HUMO-H2-001 y un periodo que contiene hoy.
#
# El servidor del guion arranca con QUEUE_CONNECTION=sync: el recalculo del resumen academico (escucha en cola) ocurre
# dentro de la misma peticion, de modo que la situacion academica ya esta al dia en el paso siguiente.
set -euo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

export QUEUE_CONNECTION=sync
echo "Servidor del guion con QUEUE_CONNECTION=${QUEUE_CONNECTION}"
humo_servidor

contrasena=$(humo_credencial contrasena)
correo_docente1=$(humo_credencial docente)
correo_docente2=$(php -r 'require "vendor/autoload.php"; echo Database\Seeders\UsuariosDemoSeeder::CORREO_DOCENTE_2;')
correo_entrenador=$(humo_credencial entrenador1)
docente1="${HUMO_TMP}/docente1.cookies"
docente2="${HUMO_TMP}/docente2.cookies"
entrenador="${HUMO_TMP}/entrenador1.cookies"
sin_sesion="${HUMO_TMP}/anonimo.cookies"
: > "$sin_sesion"

# Fechas relativas al dia de negocio (America/Mexico_City), para que el periodo sea el vigente (DD-45).
fecha() { php -r 'echo (new DateTimeImmutable("now", new DateTimeZone("America/Mexico_City")))->modify($argv[1])->format("Y-m-d");' -- "$1"; }
inicio=$(fecha "-30 days")
fin=$(fecha "+120 days")
matricula="HUMO-H2-001"

humo_peticion "$sin_sesion" GET /api/v1/salud
humo_paso 1 "GET /api/v1/salud" 200

humo_iniciar_sesion "$docente1" "$correo_docente1" "$contrasena"
humo_paso 2 "el docente 1 inicia sesión" 200 '$d["datos"]["roles"] === ["docente"]'

humo_peticion "$docente1" POST /api/v1/periodos-escolares "{\"nombre\":\"Humo H2\",\"fecha_inicio\":\"$inicio\",\"fecha_fin\":\"$fin\"}"
humo_paso 3 "crea un periodo escolar que contiene hoy y sale vigente" 201 '$d["datos"]["vigente"] === true'
periodo=$(humo_campo datos.id)

humo_peticion "$docente1" POST /api/v1/asignaturas '{"clave":"MAT-101","nombre":"Matemáticas","grado":1}'
humo_paso 4 "crea la asignatura MAT-101" 201
asignatura=$(humo_campo datos.id)

humo_peticion "$docente1" POST /api/v1/materias "{\"asignatura_id\":\"$asignatura\",\"periodo_id\":\"$periodo\"}"
humo_paso 5 "crea la materia con esa asignatura y ese periodo" 201 '$d["datos"]["criterio_aplicado"] === ["ambito" => "general", "calificacion_minima" => "7.00"]'
materia=$(humo_campo datos.id)

humo_iniciar_sesion "$entrenador" "$correo_entrenador" "$contrasena"
humo_paso 6 "el entrenador 1 inicia sesión (otro archivo de cookies)" 200
humo_peticion "$entrenador" POST /api/v1/categorias '{"nombre":"Sub-15"}'
humo_paso 6 "crea su categoría Sub-15" 201
categoria=$(humo_campo datos.id)
humo_peticion "$entrenador" POST /api/v1/equipos "{\"nombre\":\"Pumas Sub-15\",\"categoria_id\":\"$categoria\"}"
humo_paso 6 "crea su equipo" 201
equipo=$(humo_campo datos.id)
humo_peticion "$entrenador" POST /api/v1/jugadores "{\"nombres\":\"Ana\",\"apellidos\":\"Prueba Humo\",\"fecha_nacimiento\":\"2010-03-14\",\"matricula\":\"$matricula\",\"equipo_id\":\"$equipo\",\"posicion\":\"medio\"}"
humo_paso 6 "registra un jugador" 201
jugador=$(humo_campo datos.id)

humo_peticion "$docente1" GET "/api/v1/jugadores/busqueda?matricula=$matricula"
humo_paso 7 "el docente 1 busca la matrícula: solo nombres, apellidos y matrícula" 200 'array_keys($d["datos"]) === ["nombres", "apellidos", "matricula"]'
humo_peticion "$docente1" POST "/api/v1/materias/$materia/inscripciones" "{\"matricula\":\"$matricula\"}"
humo_paso 7 "lo inscribe por matrícula" 201 '$d["datos"]["jugador"]["id"] === "'"$jugador"'"'
inscripcion=$(humo_campo datos.id)

humo_peticion "$docente1" POST "/api/v1/inscripciones/$inscripcion/calificaciones" '{"tipo":"parcial","numero_parcial":1,"valor":"8.50"}'
humo_paso 8 "captura la calificación parcial 8.50" 201 '$d["datos"]["valor"] === "8.50"'
humo_peticion "$docente1" POST "/api/v1/inscripciones/$inscripcion/calificaciones" '{"tipo":"final","valor":"8.00"}'
humo_paso 8 "captura la calificación final 8.00" 201 '$d["datos"]["valor"] === "8.00"'

for asistencia in "$(fecha "-2 days") presente" "$(fecha "-1 days") presente" "$(fecha "today") falta"; do
    set -- $asistencia
    humo_peticion "$docente1" POST "/api/v1/inscripciones/$inscripcion/asistencias" "{\"fecha\":\"$1\",\"estado\":\"$2\"}"
    humo_paso 9 "captura la asistencia $2 del $1" 201
done

humo_peticion "$docente1" GET "/api/v1/jugadores/$jugador/situacion-academica"
humo_paso 10 "situación como docente: 8.00, sin_riesgo, 66.67 y con materias" 200 \
    '$d["datos"]["promedio"] === "8.00" && $d["datos"]["nivel_riesgo"] === "sin_riesgo" && $d["datos"]["porcentaje_asistencia"] === "66.67" && count($d["datos"]["materias"]) === 1'

humo_peticion "$docente1" PUT "/api/v1/materias/$materia/criterio" '{"calificacion_minima":"8.50"}'
humo_paso 11 "define el criterio propio 8.50" 200 '$d["datos"]["ambito"] === "materia"'
humo_peticion "$docente1" GET "/api/v1/jugadores/$jugador/situacion-academica"
humo_paso 11 "la situación pasa a 1 reprobada y en_observacion" 200 \
    '$d["datos"]["materias_reprobadas"] === 1 && $d["datos"]["nivel_riesgo"] === "en_observacion" && $d["datos"]["materias"][0]["aprobada"] === false'

humo_peticion "$entrenador" GET "/api/v1/jugadores/$jugador/situacion-academica"
humo_paso 12 "el entrenador 1 ve el mismo nivel, sin materias" 200 \
    '$d["datos"]["nivel_riesgo"] === "en_observacion" && ! array_key_exists("materias", $d["datos"]) && strpos(json_encode($d), "calificaciones") === false'

humo_iniciar_sesion "$docente2" "$correo_docente2" "$contrasena"
humo_paso 13 "el docente 2 inicia sesión" 200
humo_peticion "$docente2" GET "/api/v1/jugadores/$jugador/situacion-academica"
humo_paso 13 "el docente 2 recibe 404 en la situación" 404 '$d["codigo"] === "recurso_no_encontrado"'
humo_peticion "$docente2" GET /api/v1/jugadores
humo_paso 13 "el docente 2 recibe una lista vacía en GET /jugadores" 200 'count($d["datos"]) === 0'

humo_peticion "$entrenador" POST /api/v1/periodos-escolares "{\"nombre\":\"Otro\",\"fecha_inicio\":\"$inicio\",\"fecha_fin\":\"$fin\"}"
humo_paso 14 "el entrenador 1 recibe 403 al crear un periodo" 403 '$d["codigo"] === "sin_permiso"'
humo_peticion "$docente1" POST /api/v1/equipos "{\"nombre\":\"Docentes\",\"categoria_id\":\"$categoria\"}"
humo_paso 14 "el docente 1 recibe 403 al crear un equipo" 403 '$d["codigo"] === "sin_permiso"'

humo_peticion "$docente1" POST "/api/v1/inscripciones/$inscripcion/calificaciones" '{"tipo":"parcial","numero_parcial":2,"valor":"10.01"}'
humo_paso 15 "un valor fuera de rango responde calificacion_fuera_de_rango" 422 '$d["codigo"] === "calificacion_fuera_de_rango"'

# Se conserva una copia de la cookie anterior al cierre: es la que el servidor debe dejar de reconocer.
cp "$docente1" "${HUMO_TMP}/docente1.antes"
humo_peticion "$docente1" DELETE /api/v1/sesion
humo_paso 16 "el docente 1 cierra sesión" 204
humo_peticion "${HUMO_TMP}/docente1.antes" GET /api/v1/materias
humo_paso 16 "la misma cookie responde 401" 401

echo "HUMO H2 OK: 16 pasos"
