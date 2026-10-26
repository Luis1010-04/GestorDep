#!/usr/bin/env bash
# Funciones comunes de los guiones de humo. Los guiones la cargan con `source` desde su propio directorio;
# solo declara funciones.

HUMO_PUERTO=8099
HUMO_BASE="http://127.0.0.1:${HUMO_PUERTO}"
# Dominio con estado de Sanctum (SANCTUM_STATEFUL_DOMAINS): sin Origin o Referer de uno la peticion no abre sesion.
# `localhost` esta en la lista por defecto de Sanctum y en la de `.env.example`; no tiene que ser la direccion real.
HUMO_ORIGEN="http://localhost"

HUMO_CODIGO=""
HUMO_CUERPO=""

# Levanta el servidor de desarrollo y espera a que responda. Se detiene y se limpia al salir el guion.
humo_servidor() {
    HUMO_TMP=$(mktemp -d)
    php -S "127.0.0.1:${HUMO_PUERTO}" -t public > /dev/null 2>&1 &
    HUMO_SERVIDOR=$!
    trap 'kill "$HUMO_SERVIDOR" 2>/dev/null || true; rm -rf "$HUMO_TMP"' EXIT
    for _ in $(seq 1 20); do
        curl -s -o /dev/null "${HUMO_BASE}/api/v1/salud" && return 0
        sleep 0.5
    done
}

# Las credenciales de demostracion viven solo en el seeder; HUMO_CONTRASENA sobrescribe la contrasena (opcional).
humo_credencial() {
    case "$1" in
        contrasena) php -r 'require "vendor/autoload.php"; echo Database\Seeders\UsuariosDemoSeeder::contrasena();' ;;
        docente) php -r 'require "vendor/autoload.php"; echo Database\Seeders\UsuariosDemoSeeder::CORREO_DOCENTE;' ;;
        entrenador1) php -r 'require "vendor/autoload.php"; echo Database\Seeders\UsuariosDemoSeeder::CORREO_ENTRENADOR_1;' ;;
        entrenador2) php -r 'require "vendor/autoload.php"; echo Database\Seeders\UsuariosDemoSeeder::CORREO_ENTRENADOR_2;' ;;
    esac
}

# Valor de la cookie XSRF-TOKEN del archivo de cookies, decodificado de URL.
humo_xsrf() {
    local valor
    valor=$(awk '$6 == "XSRF-TOKEN" { print $7 }' "$1" | tail -n1)
    php -r 'echo rawurldecode($argv[1]);' "$valor"
}

# humo_peticion <cookies> <metodo> <ruta> [cuerpo_json]: deja el codigo en HUMO_CODIGO y el cuerpo en HUMO_CUERPO.
humo_peticion() {
    local cookies=$1 metodo=$2 ruta=$3 cuerpo=${4:-} respuesta
    local argumentos=(-s -w '\n%{http_code}' -X "$metodo" -b "$cookies" -c "$cookies"
        -H 'Accept: application/json' -H "Origin: ${HUMO_ORIGEN}" -H "Referer: ${HUMO_ORIGEN}/"
        -H "X-XSRF-TOKEN: $(humo_xsrf "$cookies")")
    if [ -n "$cuerpo" ]; then
        argumentos+=(-H 'Content-Type: application/json' --data "$cuerpo")
    fi
    respuesta=$(curl "${argumentos[@]}" "${HUMO_BASE}${ruta}")
    HUMO_CODIGO=$(printf '%s' "$respuesta" | tail -n1)
    HUMO_CUERPO=$(printf '%s' "$respuesta" | sed '$d')
}

# Inicio de sesion de Sanctum SPA: cookie CSRF y luego POST /api/v1/sesion con la cookie y las cabeceras.
# humo_iniciar_sesion <cookies> <correo> <contrasena>
humo_iniciar_sesion() {
    local cookies=$1
    : > "$cookies"
    curl -s -o /dev/null -c "$cookies" -b "$cookies" -H 'Accept: application/json' \
        -H "Origin: ${HUMO_ORIGEN}" -H "Referer: ${HUMO_ORIGEN}/" "${HUMO_BASE}/sanctum/csrf-cookie"
    humo_peticion "$cookies" POST /api/v1/sesion "{\"correo\":\"$2\",\"contrasena\":\"$3\"}"
}

# Evalua una expresion PHP sobre el cuerpo JSON de la ultima respuesta (variable $d): codigo 0 si es verdadera.
humo_cumple() {
    printf '%s' "$HUMO_CUERPO" | php -r '$d = json_decode(stream_get_contents(STDIN), true); exit(eval("return (".$argv[1].");") ? 0 : 1);' -- "$1" 2> /dev/null
}

# Valor de un campo del cuerpo JSON (ruta con puntos, por ejemplo datos.id).
humo_campo() {
    printf '%s' "$HUMO_CUERPO" | php -r '
        $v = json_decode(stream_get_contents(STDIN), true);
        foreach (explode(".", $argv[1]) as $k) { $v = $v[$k] ?? null; }
        echo is_array($v) ? json_encode($v) : (string) $v;' -- "$1"
}

# humo_paso <n> <descripcion> <codigo_esperado> [expresion_php]: una linea OK o FALLA; sale con 1 en la primera falla.
humo_paso() {
    local n=$1 descripcion=$2 esperado=$3 expresion=${4:-}
    if [ "$HUMO_CODIGO" != "$esperado" ]; then
        printf 'FALLA  paso %s: %s -> HTTP %s (se esperaba %s)\n' "$n" "$descripcion" "$HUMO_CODIGO" "$esperado"
        printf '       cuerpo: %s\n' "$HUMO_CUERPO"
        exit 1
    fi
    if [ -n "$expresion" ] && ! humo_cumple "$expresion"; then
        printf 'FALLA  paso %s: %s -> HTTP %s (el cuerpo no cumple: %s)\n' "$n" "$descripcion" "$HUMO_CODIGO" "$expresion"
        printf '       cuerpo: %s\n' "$HUMO_CUERPO"
        exit 1
    fi
    printf 'OK     paso %s: %s -> HTTP %s\n' "$n" "$descripcion" "$HUMO_CODIGO"
}
