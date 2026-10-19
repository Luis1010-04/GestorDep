.PHONY: levantar bajar consola

levantar:
	docker compose up -d --build --wait

bajar:
	docker compose down

consola:
	docker compose exec app bash
