.PHONY: levantar bajar consola verificar pruebas migrar fresh humo

levantar:
	docker compose up -d --build --wait

bajar:
	docker compose down

consola:
	docker compose exec app bash

verificar:
	docker compose exec -T app composer verificar

pruebas:
	docker compose exec -T app php vendor/bin/pest

migrar:
	docker compose exec -T app php artisan migrate --force

fresh:
	docker compose exec -T app php artisan migrate:fresh --seed --force

humo:
	@set -e; guiones=$$(ls scripts/humo/humo-h*.sh 2>/dev/null || true); \
	if [ -z "$$guiones" ]; then echo "No hay guiones de humo en scripts/humo/"; exit 1; fi; \
	for g in $$guiones; do echo "== $$g"; docker compose exec -T app bash -s < "$$g"; done
