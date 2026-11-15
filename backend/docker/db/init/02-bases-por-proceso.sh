#!/bin/bash
# Bases de pruebas por proceso (DA-101): `make verificar` corre Pest en paralelo y cada proceso usa la suya,
# `<base de pruebas>_1` a `<base de pruebas>_8`, con el mismo dueño y los mismos permisos que la base de pruebas.
#
# Es idempotente: crea solo las bases que falten. Se ejecuta al crear el volumen de datos, despues de
# 01-roles-y-bases.sh, y otra vez en cada `make levantar`, porque en un volumen que ya existe los guiones de inicio
# no vuelven a correr. Las crea el superusuario: el rol propietario no tiene permiso de crear bases.
set -euo pipefail

for proceso in 1 2 3 4 5 6 7 8; do
    base="${DB_DATABASE_PRUEBAS}_${proceso}"
    existe=$(psql -v ON_ERROR_STOP=1 --username postgres --dbname postgres -tA -v base="$base" <<'SQL'
SELECT 1 FROM pg_database WHERE datname = :'base';
SQL
    )
    if [ "$existe" = "1" ]; then
        continue
    fi

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
