# GestorDep

API backend de un sistema de seguimiento académico y deportivo de futbolistas (Fase 1, MVP).
Stack: Laravel 13, PHP 8.4, PostgreSQL 16, Docker Compose. Solo backend; `frontend/` no se toca.

## Estructura

| Carpeta | Contenido |
|---|---|
| `backend/` | Aplicación Laravel |
| `frontend/` | Reservada; vacía |
| `docs/` | Requisitos, arquitectura, plan y especificación de cada hito (mapa en `docs/README.md`) |
| `scripts/humo/` | Guiones de humo por hito |

## Prerrequisitos

- Git.
- Docker Desktop con WSL2 (el motor debe estar iniciado).
- `make`. En Windows: `winget install --id ezwinports.make -e --scope user` y abrir una terminal nueva.

## Puertos

PostgreSQL de Windows y Laragon pueden ocupar puertos del equipo (por ejemplo 5432, 80 y 3306). PostgreSQL del entorno de Docker se publica en `127.0.0.1:5433`; se cambia con `DB_PUERTO_ANFITRION` en `.env`.

## Arranque en una máquina limpia

```bash
git clone https://github.com/Luis1010-04/GestorDep.git
cd GestorDep
cp .env.example .env
cp backend/.env.example backend/.env
make levantar
docker compose exec -T app php artisan key:generate
make fresh
make verificar
make humo
```

Las claves de `.env.example` son solo de prueba para desarrollo local. `vendor`, `bootstrap/cache` y las vistas compiladas viven en volúmenes de Docker (leerlos desde el montaje de Windows es muy lento). `make levantar` instala las dependencias solo si `vendor` está vacío; después de cambiar `composer.json` o `composer.lock` hay que ejecutar `make dependencias`, porque el volumen no se actualiza solo. Los registros siguen en `backend/storage/logs`. `scripts/` se monta de solo lectura en `/scripts` para `make humo`: si el contenedor se creó antes de ese montaje, `make levantar` lo recrea y lo incorpora.

## Comandos

Todo se ejecuta con `make`, dentro de Docker:

| Comando | Qué hace |
|---|---|
| `make levantar` / `make bajar` | Inicia y detiene el entorno: `app`, `db`, `worker` (cola `database`) y `scheduler` (planificador) |
| `make consola` | Terminal dentro del contenedor de la aplicación |
| `make dependencias` | `composer install` dentro del contenedor; obligatorio tras cambiar `composer.lock` |
| `make vendor-local` | Opcional: copia `vendor` del contenedor a `backend/vendor` solo para el autocompletado del editor; esa copia no se ejecuta |
| `make migrar` / `make fresh` | Migra / reconstruye la base con datos de fábrica |
| `make pruebas` | Ejecuta solo las pruebas |
| `make verificar` | Estilo, análisis estático, pruebas con cobertura y auditoría de dependencias |
| `make humo` | Ejecuta los guiones de humo de `scripts/humo/` contra la aplicación levantada |

## Reglas del proyecto

Las reglas del agente están en `backend/.ai/guidelines/proyecto.md`; Laravel Boost las copia a `backend/CLAUDE.md` y `backend/AGENTS.md`. El `CLAUDE.md` y el `AGENTS.md` de la raíz son punteros. La especificación del hito en curso está en `docs/hitos/`.
