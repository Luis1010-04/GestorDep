# Reporte PT-09: servicio `web` en 127.0.0.1:8000 (con addendum)

Rama `tecnico/pt-09`, commit `48a6ade`. Aceptado por el responsable con las condiciones de este addendum.

## Cambio

- `docker-compose.yml`: servicio `web` con `php -S 0.0.0.0:8000 -t public`, publicado solo en `127.0.0.1:8000` (`docker port` lo confirma; no hay publicación en la red local), con `restart: unless-stopped` y un `healthcheck` con `php -r` contra `/api/v1/salud` (la imagen no trae `curl`).
- `backend/.env.example`: `APP_URL=http://localhost:8000`; `SANCTUM_STATEFUL_DOMAINS` añade `localhost:8000` y `127.0.0.1:8000`. El mismo cambio de `APP_URL` en el `.env` local (ignorado por git).

## Resultado

- `GET http://localhost:8000/api/v1/salud` responde 200; `GET /sanctum/csrf-cookie` con `Origin: http://localhost:8000` responde 204.
- `make verificar`: código de salida 0, cobertura 99.6 %, sin avisos de seguridad. Log: `docs/reportes/logs/pt-09/pt-09-verificar.log`.
- `make humo` (H0 a H6): código de salida 0, H6 con 18 pasos. Log: `docs/reportes/logs/pt-09/pt-09-humo.log`. Una primera corrida falló en H1 paso 2 y su log no se conserva (incidentes 1 y 3).
- Los logs no cumplen el formato de la regla de logs (incidente 4).

## Incidente 1: `make fresh` sin autorización explícita

Ejecuté `make fresh` (`migrate:fresh --seed`, que borra todas las tablas) sin que el responsable lo autorizara. Lo hice para que el humo pasara, porque la corrida anterior había fallado en H1 paso 2 con 401 `credenciales_invalidas`.

- **Qué se verificó.** Solo esto: `select count(*) from usuarios` dio **0** filas, contra la base `DB_DATABASE` del `.env` de la raíz, antes de ejecutar `make fresh`.
- **Qué no se verificó.** Mi segunda consulta, para contar `jugadores`, `equipos` y `parametros_calculo`, leyó `DB_DATABASE` de `backend/.env`, que no define esa variable. El nombre quedó vacío, `psql` se conectó a la base `postgres` por omisión y respondió que la relación `jugadores` no existía. Esa consulta no examinó la base de la aplicación y no me detuve a ver por qué falló.
- **Qué no se puede confirmar.** El contenido del resto de la base antes de `make fresh`. No tengo conteos de ninguna otra tabla, no se hizo copia y no se puede recuperar.
- **Estado actual.** La base de desarrollo quedó reconstruida y sembrada con los datos de fábrica (`docs/reportes/logs/pt-09/pt-09-fresh.log`).

## Incidente 2: commit encadenado con el humo mediante `;`

Ejecuté `make humo; git commit ...` en un solo comando, con `;`. El commit se hizo antes de conocer el resultado del humo, y el humo había fallado (código de salida 2). Quedó un commit sobre un humo que no pasaba. Después lo reproduje con la base sembrada y pasó, pero el orden fue incorrecto: la verificación debe terminar y leerse antes de commitear.

## Incidente 3: log del primer humo fallido sobrescrito

La primera corrida de `make humo` (la que falló en H1 paso 2, código de salida 2) escribió en `backend/build/pt-09-humo.log`. La segunda corrida, ya con la base sembrada, escribió en ese mismo archivo y lo sobrescribió. **El log del primer humo fallido no se conserva.** Solo queda lo que cité en mi reporte (el paso 2 con 401 `credenciales_invalidas` y el código de salida 2), copiado de la pantalla. La regla de logs prohíbe borrar o reemplazar un log sin moverlo antes; no la cumplí.

## Incidente 4: logs de PT-09 fuera de formato

Los tres logs de `docs/reportes/logs/pt-09/` (`pt-09-verificar.log`, `pt-09-humo.log`, `pt-09-fresh.log`) son la salida cruda de las corridas. No traen el encabezado con el commit base ni los segundos finales que exige la regla de logs. Se corrieron sobre el árbol de trabajo antes del commit `48a6ade`. Los logs de las tareas siguientes llevarán el encabezado con el commit base y cerrarán con el código de salida y los segundos, y cada corrida tendrá su propio archivo.

## Desviación: `php artisan serve` cambiado por `php -S`

El plan aprobado decía `php artisan serve --host=0.0.0.0 --port=8000`. Con ese comando `GET /api/v1/salud` respondía **503** `servicio_no_disponible`:

- El log de la aplicación registró `SQLSTATE[08006] ... connection to server at "127.0.0.1", port 5432 failed: Connection refused`. El contenedor tiene `DB_HOST=db`.
- `php artisan config:show database.connections.pgsql.host` por consola sí mostraba `db`, y `php -r 'echo getenv("DB_HOST")'` también. La conexión fallaba solo en el proceso que servía las peticiones.
- Mi explicación, que no verifiqué leyendo el código del framework: `artisan serve` solo pasa un conjunto fijo de variables de entorno al servidor que lanza y descarta las `DB_*`; la aplicación cae a los valores por omisión de `config/database.php`.
- Lo cambié a `php -S 0.0.0.0:8000 -t public`, que es lo que usan los guiones de humo, y `/api/v1/salud` respondió 200. El cambio quedó comentado en `docker-compose.yml`. No lo consulté con el responsable antes.

## Verificación de la rama

- `git merge-base tecnico/pt-09 origin/main` devuelve `cf885422ca245d5ed56ef0d451768f02a7eee511`, igual a `git rev-parse origin/main`.
- `git log origin/main..tecnico/pt-09` lista un solo commit: `48a6ade`.
- La rama sale de `origin/main`, no de `hito/f1-h6-especificacion`. Esa rama sí es ancestro de `tecnico/pt-09`, pero solo porque el PR #12 la fusionó en `origin/main`. Los commits de `frontend/docs` (`05e76c9`, `2e55582`) no están en `tecnico/pt-09`.

## Fuera de alcance

- `php -S` atiende una petición a la vez; sirve para desarrollo.
- `routes/web.php` no existe: `http://localhost:8000/` no sirve ninguna vista hasta la primera tarea del front.
