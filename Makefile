.PHONY: levantar bajar consola dependencias vendor-local verificar pruebas migrar fresh humo

# `vendor` vive en un volumen nombrado: si esta vacio (primer arranque o tras `docker compose down -v`) se instala solo.
# El worker y el scheduler arrancan despues, cuando ya hay dependencias.
levantar:
	docker compose up -d --build --wait db app
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

verificar:
	docker compose exec -T app composer verificar

pruebas:
	docker compose exec -T app php vendor/bin/pest

migrar:
	docker compose exec -T app php artisan migrate --database=propietario --force

fresh:
	docker compose exec -T app php artisan migrate:fresh --database=propietario --seed --force

humo:
	docker compose exec -T app bash -s < scripts/humo/humo-h0.sh
	docker compose exec -T app bash /scripts/humo/humo-h1.sh
	docker compose exec -T app bash /scripts/humo/humo-h2.sh
