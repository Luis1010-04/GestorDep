# GestorDep

API backend de un sistema de seguimiento académico y deportivo de futbolistas (Fase 1, MVP).
Stack: Laravel 13, PHP 8.4, PostgreSQL 16, Docker Compose. Solo backend; `frontend/` no se toca.

## Estructura

| Carpeta | Contenido |
|---|---|
| `backend/` | Aplicación Laravel (llega en F1-H0-T03) |
| `frontend/` | Reservada; vacía |
| `docs/` | Requisitos, arquitectura, plan y especificación de cada hito (mapa en `docs/README.md`) |
| `scripts/humo/` | Guiones de humo por hito |

## Prerrequisitos

- Git.
- Docker Desktop con WSL2 (el motor debe estar iniciado).
- `make`. En Windows: `winget install --id ezwinports.make -e --scope user` y abrir una terminal nueva.

## Puertos

PostgreSQL de Windows y Laragon pueden ocupar puertos del equipo (por ejemplo 5432, 80 y 3306). El puerto del anfitrión que usa el entorno de Docker se define en F1-H0-T02.

## Comandos

Todo se ejecuta con `make`, dentro de Docker. Los objetivos se agregan por tarea:

| Comando | Qué hace | Disponible desde |
|---|---|---|
| `make levantar` / `make bajar` | Inicia y detiene el entorno: `app`, `db`, `worker` (cola `database`) y `scheduler` (planificador) | F1-H0-T02 |
| `make consola` | Terminal dentro del contenedor de la aplicación | F1-H0-T02 |
| `make migrar` / `make fresh` | Migra / reconstruye la base | F1-H0-T04 |
| `make pruebas` | Ejecuta solo las pruebas | F1-H0-T04 |
| `make verificar` | Estilo, análisis estático, pruebas y auditoría de dependencias | F1-H0-T04 |
| `make humo` | Guion de humo del hito | F1-H0-T04 |

## Reglas del proyecto

Las reglas del agente están en `AGENTS.md`; la especificación del hito en curso, en `docs/hitos/`.
