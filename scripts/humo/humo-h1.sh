#!/usr/bin/env bash
# Guion de humo del hito F1-H1 (docs/hitos/f1-h1.md §9). `make humo` lo ejecuta dentro del
# contenedor desde /scripts (montaje de solo lectura), contra una base recien sembrada (`make fresh`): crea la categoria "Sub-17" y una matricula fija.
set -euo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

humo_servidor
contrasena=$(humo_credencial contrasena)
correo1=$(humo_credencial entrenador1)
correo2=$(humo_credencial entrenador2)
correo_docente=$(humo_credencial docente)
cookies1="${HUMO_TMP}/entrenador1.cookies"
cookies2="${HUMO_TMP}/entrenador2.cookies"
cookies_docente="${HUMO_TMP}/docente.cookies"
sin_sesion="${HUMO_TMP}/anonimo.cookies"
: > "$sin_sesion"

humo_peticion "$sin_sesion" GET /api/v1/salud
humo_paso 1 "GET /api/v1/salud" 200

humo_iniciar_sesion "$cookies1" "$correo1" "$contrasena"
humo_paso 2 "el entrenador 1 inicia sesión" 200 '$d["datos"]["roles"] === ["entrenador"]'

humo_peticion "$cookies1" POST /api/v1/categorias '{"nombre":"Sub-17"}'
humo_paso 3 "crea la categoría Sub-17" 201
categoria=$(humo_campo datos.id)

humo_peticion "$cookies1" POST /api/v1/equipos "{\"nombre\":\"Tigres Sub-17\",\"categoria_id\":\"$categoria\"}"
humo_paso 4 "crea el equipo Tigres Sub-17" 201
equipo1=$(humo_campo datos.id)
humo_peticion "$cookies1" GET /api/v1/equipos
humo_paso 4 "el equipo aparece en GET /equipos" 200 'in_array("Tigres Sub-17", array_column($d["datos"], "nombre"), true)'

matricula="HUMO-001"
jugador="{\"nombres\":\"Luis\",\"apellidos\":\"Prueba Humo\",\"fecha_nacimiento\":\"2009-05-14\",\"matricula\":\"$matricula\",\"equipo_id\":\"$equipo1\",\"posicion\":\"medio\"}"
humo_peticion "$cookies1" POST /api/v1/jugadores "$jugador"
humo_paso 5 "registra un jugador en ese equipo" 201
jugador_id=$(humo_campo datos.id)

humo_peticion "$cookies1" POST /api/v1/jugadores "$jugador"
humo_paso 6 "la misma matrícula responde 409 sin datos del primero" 409 'strpos(json_encode($d), "Prueba Humo") === false && strpos(json_encode($d), "Luis") === false'

humo_iniciar_sesion "$cookies2" "$correo2" "$contrasena"
humo_paso 7 "el entrenador 2 inicia sesión con otro archivo de cookies" 200

humo_peticion "$cookies2" GET "/api/v1/jugadores/$jugador_id"
humo_paso 8 "el jugador del entrenador 1 responde 404 al entrenador 2" 404

humo_peticion "$cookies2" GET "/api/v1/jugadores/busqueda?matricula=$matricula"
humo_paso 9 "la búsqueda por matrícula devuelve solo nombres, apellidos y matrícula" 200 'array_keys($d["datos"]) === ["nombres", "apellidos", "matricula"]'

# Las categorias son un catalogo comun: el entrenador 2 usa la que creo el 1.
humo_peticion "$cookies2" POST /api/v1/equipos "{\"nombre\":\"Leones Sub-17\",\"categoria_id\":\"$categoria\"}"
humo_paso 10 "el entrenador 2 crea su equipo" 201
equipo2=$(humo_campo datos.id)
humo_peticion "$cookies2" POST "/api/v1/equipos/$equipo2/jugadores" "{\"matricula\":\"$matricula\",\"posicion\":\"defensa\"}"
humo_paso 10 "asigna al jugador por matrícula" 201

humo_peticion "$cookies2" GET /api/v1/jugadores
humo_paso 11 "GET /jugadores del entrenador 2 devuelve al jugador con solo su equipo" 200 \
    'count($d["datos"]) === 1 && $d["datos"][0]["id"] === "'"$jugador_id"'" && count($d["datos"][0]["equipos"]) === 1 && $d["datos"][0]["equipos"][0]["nombre"] === "Leones Sub-17"'

humo_iniciar_sesion "$cookies_docente" "$correo_docente" "$contrasena"
humo_paso 12 "el docente inicia sesión" 200
humo_peticion "$cookies_docente" POST /api/v1/equipos "{\"nombre\":\"Docentes\",\"categoria_id\":\"$categoria\"}"
humo_paso 12 "el docente recibe 403 al crear un equipo" 403
humo_peticion "$cookies_docente" GET /api/v1/jugadores
humo_paso 12 "el docente recibe una lista vacía en GET /jugadores" 200 'count($d["datos"]) === 0'

humo_peticion "$cookies1" POST "/api/v1/jugadores/$jugador_id/baja" '{"motivo":"Baja de demostración","fecha":"2026-10-26"}'
humo_paso 13 "el entrenador 1 da de baja al jugador" 201
humo_peticion "$cookies1" GET /api/v1/jugadores
humo_paso 13 "deja de aparecer en GET /jugadores del entrenador 1" 200 'count($d["datos"]) === 0'
humo_peticion "$cookies2" GET /api/v1/jugadores
humo_paso 13 "deja de aparecer en GET /jugadores del entrenador 2" 200 'count($d["datos"]) === 0'

# Se conserva una copia de la cookie anterior al cierre: es la que el servidor debe dejar de reconocer.
cp "$cookies1" "${HUMO_TMP}/entrenador1.antes"
humo_peticion "$cookies1" DELETE /api/v1/sesion
humo_paso 14 "el entrenador 1 cierra sesión" 204
humo_peticion "${HUMO_TMP}/entrenador1.antes" GET /api/v1/jugadores
humo_paso 14 "la misma cookie responde 401" 401

echo "HUMO H1 OK: 14 pasos"
