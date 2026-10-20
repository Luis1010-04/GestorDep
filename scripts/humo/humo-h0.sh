#!/usr/bin/env bash
# Guion de humo del hito F1-H0. Se ejecuta dentro del contenedor de la aplicacion (make humo).
set -euo pipefail

puerto=8099
php -S "127.0.0.1:$puerto" -t public > /dev/null 2>&1 &
servidor=$!
trap 'kill "$servidor" 2>/dev/null || true' EXIT

for _ in $(seq 1 20); do
    curl -s -o /dev/null "http://127.0.0.1:$puerto/up" && break
    sleep 0.5
done

respuesta=$(curl -s -w '\n%{http_code}' -H 'Accept: application/json' "http://127.0.0.1:$puerto/api/v1/salud")
codigo=$(printf '%s' "$respuesta" | tail -n1)
cuerpo=$(printf '%s' "$respuesta" | head -n1)

[ "$codigo" = "200" ] || { echo "HUMO H0 FALLA: salud respondio $codigo"; exit 1; }
echo "$cuerpo" | grep -q '"aplicacion":"ok"' || { echo "HUMO H0 FALLA: aplicacion no esta en ok"; exit 1; }
echo "$cuerpo" | grep -q '"base_de_datos":"ok"' || { echo "HUMO H0 FALLA: base_de_datos no esta en ok"; exit 1; }
echo "HUMO H0 OK: GET /api/v1/salud -> 200"
