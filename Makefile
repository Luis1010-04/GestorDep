.PHONY: levantar bajar consola dependencias vendor-local verificar mutacion pruebas migrar fresh humo humo-h3

# Docker Compose v5 construye con bake. En Windows, si la terminal hereda el directorio con la unidad en minuscula
# (c:\...), Compose pasa a bake el contexto como c:\... y el Dockerfile como C:\..., bake cree que el Dockerfile esta
# fuera del contexto, pide `--allow=fs.read=...` y `--build` falla. Compose no tiene opcion para conceder ese permiso,
# asi que se desactiva esa comprobacion de bake, solo para las construcciones que lanzan estas recetas (F1-H7-T06c, D13).
export BUILDX_BAKE_ENTITLEMENTS_FS := 0

# `vendor` vive en un volumen nombrado: si esta vacio (primer arranque o tras `docker compose down -v`) se instala solo.
# El worker y el scheduler arrancan despues, cuando ya hay dependencias.
# Las bases de pruebas por proceso (DA-101) se crean aqui si faltan: el guion es idempotente y un volumen de datos que
# ya existe no vuelve a correr los guiones de inicio de PostgreSQL.
levantar:
	docker compose up -d --build --wait db app
	docker compose exec -T db bash /docker-entrypoint-initdb.d/02-bases-por-proceso.sh
	docker compose exec -T app sh -c 'test -f vendor/autoload.php || composer install --no-interaction --no-progress'
	docker compose up -d --wait

bajar:
	docker compose down

consola:
	docker compose exec app bash

# Tras cambiar composer.json o composer.lock hay que ejecutarlo: el volumen de `vendor` no se actualiza solo.
dependencias:
	docker compose exec -T app composer install --no-interaction --no-progress

# Solo para el autocompletado del editor: copia `vendor` del contenedor a backend/vendor. Esa copia NO se ejecuta
# (el contenedor usa su propio volumen) y queda desactualizada tras cambiar dependencias: se vuelve a copiar.
vendor-local:
	docker compose cp app:/var/www/html/vendor ./backend/

# verificar, pruebas y mutacion comparten la base de pruebas: pasan por bin/con-candado.sh, que toma un candado
# exclusivo y termina la corrida si muere el cliente de `docker compose exec` (PT-07). Cada receta es un solo comando,
# sin sintaxis de shell: make la ejecuta con cmd.exe en PowerShell y con sh en Git Bash.
verificar:
	docker compose exec -T app bash bin/con-candado.sh composer verificar

# Prueba de mutación (Infection sobre src/*/Dominio/), fuera de `verificar` por tiempo: se corre al cerrar cada lote
# y en CI (enmienda a DD-53).
mutacion:
	docker compose exec -T app bash bin/con-candado.sh composer mutacion

pruebas:
	docker compose exec -T app bash bin/con-candado.sh php -d memory_limit=1G vendor/bin/pest

migrar:
	docker compose exec -T app php artisan migrate --database=propietario --force

fresh:
	docker compose exec -T app php artisan migrate:fresh --database=propietario --seed --force

humo:
	docker compose exec -T app bash -s < scripts/humo/humo-h0.sh
	docker compose exec -T app bash /scripts/humo/humo-h1.sh
	docker compose exec -T app bash /scripts/humo/humo-h2.sh
	docker compose exec -T app bash /scripts/humo/humo-h3.sh
	docker compose exec -T app bash /scripts/humo/humo-h4.sh
	docker compose exec -T app bash /scripts/humo/humo-h5.sh
	docker compose exec -T app bash /scripts/humo/humo-h6.sh
	docker compose exec -T app bash /scripts/humo/humo-h7.sh

humo-h3:
	docker compose exec -T app bash /scripts/humo/humo-h3.sh
