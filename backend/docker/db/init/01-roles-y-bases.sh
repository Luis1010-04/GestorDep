#!/bin/bash
# Se ejecuta una sola vez, al crear el volumen de datos.
# Crea el rol propietario (migraciones), el rol de aplicacion y las bases de desarrollo y de pruebas.
set -euo pipefail

psql -v ON_ERROR_STOP=1 --username postgres --dbname postgres \
    -v propietario="$DB_PROPIETARIO" -v propietario_clave="$DB_PROPIETARIO_CLAVE" \
    -v aplicacion="$DB_USERNAME" -v aplicacion_clave="$DB_PASSWORD" <<'SQL'
CREATE ROLE :"propietario" LOGIN PASSWORD :'propietario_clave';
CREATE ROLE :"aplicacion" LOGIN PASSWORD :'aplicacion_clave';
SQL

for base in "$DB_DATABASE" "$DB_DATABASE_PRUEBAS"; do
    psql -v ON_ERROR_STOP=1 --username postgres --dbname postgres \
        -v base="$base" -v propietario="$DB_PROPIETARIO" -v aplicacion="$DB_USERNAME" <<'SQL'
CREATE DATABASE :"base" OWNER :"propietario";
\connect :"base"
GRANT CONNECT ON DATABASE :"base" TO :"aplicacion";
GRANT USAGE ON SCHEMA public TO :"aplicacion";
ALTER DEFAULT PRIVILEGES FOR ROLE :"propietario" IN SCHEMA public
    GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO :"aplicacion";
ALTER DEFAULT PRIVILEGES FOR ROLE :"propietario" IN SCHEMA public
    GRANT USAGE, SELECT ON SEQUENCES TO :"aplicacion";
SQL
done
