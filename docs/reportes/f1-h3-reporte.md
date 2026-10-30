# Reporte de cierre del hito F1-H3: Competencia

**Fecha:** 29 de octubre de 2026 · **Rama:** `hito/f1-h3` (desde `main`, `dd85aa1`, etiqueta `f1-h2`) · **Especificación:** `docs/hitos/f1-h3.md` v1.0.2
**Estado:** construcción terminada (T01 a T09). Auditoría independiente del Lote C entregada, sin bloqueantes; I-1 y los menores m-1, m-2, m-3, m-5, m-6, m-8 y m-9 están corregidos (sección 9). Falta la revisión del responsable (punto 11), con dos autorizaciones pendientes (sección 4). No hubo push, PR ni etiquetas en esta sesión.

## 1. Resumen

Por la API ya se puede:

- dar de alta, listar y borrar lógicamente lugares, rivales, temporadas y torneos;
- programar, modificar y cancelar entrenamientos, con advertencia de empalme de lugar;
- registrar y corregir la asistencia a entrenamientos y consultar el porcentaje por jugador y por equipo;
- programar y cancelar partidos (la temporada sale de la fecha local), armar la convocatoria, definir la alineación y capturar el resultado.

Un entrenador ve solo sus equipos: fuera de ellos recibe 404, byte a byte igual al de un recurso inexistente. Un jugador en baja no aparece en convocatorias.

| Métrica al cierre (corridas de T09) | Valor |
|---|---|
| Pruebas | **1 224** (8 285 aserciones); H2 cerró con 920 |
| Cobertura por capa | Dominio **100,0 %** (umbral 95) · Aplicación **100,0 %** (85) · Infraestructura y HTTP **99,4 %** (70) |
| Mutación (`make mutacion`, solo `src/*/Dominio/`) | **501** mutantes, 501 eliminados; MSI **100 %**, MSI cubierto **100 %** (mínimos 80 y 85); ninguno omitido ni con tiempo agotado |
| PHPStan | Nivel **7**, sin línea base ni `ignoreErrors` |
| `composer audit` | Sin avisos |
| Duración de `make verificar` | **615 s (10 min 15 s)**, sin Infection (enmienda a DD-53) |
| Duración de `make mutacion` | **112 s (1 min 52 s)** |
| `make humo-h3` | 16 pasos con OK |
| `make humo` | H0, H1 (14 pasos) y H2 (16 pasos) con OK. Desde el FIX `6ae0a0b` (I-1) incluye H3: H0 a H3 pasan tras un solo `make fresh` (sección 9) |

## 2. Corridas de cierre (T09)

Antes de cada corrida se revisaron los procesos de `app`, `worker` y `scheduler` (`/proc`) y de Windows (`tasklist`): no había ningún `pest`, `composer`, `phpstan`, `infection` ni `php -S`. Los cuatro contenedores estaban arriba, `app` y `db` sanos, sin reinicios. Las corridas fueron una tras otra, sin nada más en marcha.

### 2.1 `make verificar` (sobre `2aa6936`)

| Etapa | Llegó al segundo | Duración | Resultado |
|---|---:|---:|---|
| Arranque de `docker compose exec` y Pint | 10 | 10 s | `PASS … 585 files` |
| PHPStan nivel 7 | 24 | 14 s | `[OK] No errors` |
| Pest con cobertura, hasta el resumen | 579 | 555 s (`Duration: 555.12s`) | **1 224 pruebas** (8 285 aserciones) |
| Informe de cobertura y cobertura por capa | 615 | 36 s | 100,0 / 100,0 / 99,4 % (total 99,5 %) |
| `composer audit` | 615 | < 1 s | `No security vulnerability advisories found.` |
| **Total** | | **615 s (10 min 15 s)**; código 0 | por debajo de 13 min |

PHPStan tardó 14 s, cuando en las corridas anteriores tardaba 3 s. Pest tardó 84 s menos que en T08 (639 s), con 6 pruebas más. Las dos diferencias no se investigaron. Mi suposición es que se deben a que fue la primera corrida tras levantar el entorno de cero: el análisis sin caché tarda más y los contenedores recién arrancados corren más rápido.

### 2.2 `make mutacion` (sobre `2aa6936`)

| Etapa | Llegó al segundo | Duración | Resultado |
|---|---:|---:|---|
| Arranque e Infection: pruebas iniciales | 16 | 16 s | PHPUnit 12.5.33, PCOV, 4 hilos |
| Mutantes | 111 | 95 s | 501 generados, 501 eliminados por las pruebas |
| `bin/verificar-mutacion.php` | 112 | < 1 s | «Mutación completa: 501 mutantes evaluados, ninguno omitido ni con tiempo agotado.» |
| **Total** | | **112 s (1 min 52 s)**; código 0 | `Time: 1m 49s` según Infection |

### 2.3 Migración (§10 punto 4)

1. `make fresh`: código 0. Corre las 52 migraciones (16 de Competencia: las ocho tablas y sus ocho historiales) y los cinco seeders.
2. `php artisan migrate:rollback --database=propietario --force`: código 0. Revierte las 52, todas en el lote 1, sin errores. `migrate:status` las muestra todas como `Pending`.
3. `make fresh`: código 0. Deja la base limpia y sembrada.

### 2.4 Humo (§10 punto 6)

`make humo-h3` corrió tras un `make fresh` y pasó los 16 pasos. Después vino otro `make fresh` y `make humo` (H0 a H2), que pasó. El `make fresh` intermedio es necesario porque `humo-h1.sh` exige una base recién sembrada (lo dice su cabecera): en su paso 11 cuenta los jugadores que ve el entrenador 2, y en el 13 los que ve el entrenador 1, y `humo-h3.sh` deja jugadores en equipos de los dos. La salida completa está en la sección 8.

**Corrección tras la auditoría (I-1):** ese razonamiento solo vale si H3 corre antes de H1. El FIX `6ae0a0b` agrega `humo-h3.sh` al final del destino `humo`, y tras un solo `make fresh` pasan H0, H1, H2 y H3 (sección 9 y `f1-h3-fix-humo.md`).

## 3. Por tarea

Todas las corridas de `make verificar` pasaron con código 0, sin nada más corriendo. Hasta T07 incluían Infection; desde el FIX de la compuerta, no.

| Tarea | Commit | Pruebas de la suite | `make verificar` |
|---|---|---:|---|
| T01 Migraciones | `16b4bfb` | 942 | 597 s |
| T02 Catálogos | `6786ab1` | 1 007 | 562 s |
| T03 Entrenamientos y puertos | `f957122` | 1 055 | 898 s (primera tras reiniciar Docker); 700 s al repetirla sin cambios (`163d617`) |
| T04 Asistencia y porcentajes | `b2ba728` | 1 095 | 710 s |
| T05 Partidos | `36acad8` | 1 144 | 729 s |
| T06 Convocatoria | `725a74b` | 1 162 | 719 s |
| FIX constante | `4b112df` | — | sin corrida propia, por indicación del responsable (la incluye la de T07) |
| T07 Alineación y resultado | `bbf708c` | 1 198 | 745 s |
| FIX DA-32 → DA-34 | `478330d` | — | sin corrida propia; la incluye la del FIX de la compuerta |
| FIX compuerta (enmienda a DD-53) | `a15493d`, `4b86c86` | 1 203 | 681 s; `make mutacion` 166 s |
| T08 Seguridad y alcance | `98c70a7` | 1 218 | 696 s |
| T09 Humo y cierre | `2aa6936` | 1 224 | 615 s; `make mutacion` 112 s |

### T01 — Migraciones de Competencia (`16b4bfb`)

- **Archivos:** 16 migraciones en `database/migrations/competencia/` (ocho tablas, grupo 040, y sus historiales, grupo 090); `tests/Integration/Competencia/MigracionesDeCompetenciaTest.php`.
- **Pruebas:** 22 (RNF-03: `CHECK`, UQ parciales, rollback y fresh; RNF-02: solo inserción e historial en la misma transacción).
- **No hecho:** seeders (§5.2 no pide ninguno); las restricciones que §5.1 no nombra las valida la aplicación (T05 y T07).
- **Fuera de alcance:** los catálogos de Competencia no están marcados (H) en la arquitectura, pero tienen historial por §3.4; `HistorialDeTablasTest` lo admite.
- **Desviaciones:** UQ parcial en `asistencias_entrenamiento` (R-2, decisión del responsable); DA-01 a DA-03.

### T02 — Catálogos, HU-28 (`6786ab1`)

- **Archivos:** dominio, aplicación e infraestructura de lugares, rivales, temporadas y torneos en `src/Competencia/`; cuatro controladores, tres solicitudes y tres recursos en `app/Http/`; `FormatoDeError`, `routes/api.php`, `lang/es/errores.php`, `docs/api/openapi.yaml`; `CatalogosTest`, `ContratoDeCompetenciaTest`, `CatalogosDeCompetenciaTest`.
- **Pruebas:** 65 (HU-28, §7.1 filas 1 a 7; DD-07; RNF-02).
- **No hecho:** editar catálogos y borrado físico (prohibidos); filtro de torneos por temporada (§6.1 no lo nombra).
- **Fuera de alcance:** la temporada no lleva `vigente` (§6.2 no lo pide).
- **Desviaciones:** `app/Http/**` y `FormatoDeError` (R-1, autorizadas); DA-04 a DA-06.

### T03 — Entrenamientos, HU-10, y puertos de equipo (`f957122`)

- **Archivos:** entrenamientos en `src/Competencia/`; puertos `EquiposDelEntrenador` y `JugadoresDelEquipo` en `src/Compartido/Aplicacion/`; adaptadores y su enlace en `src/Plantel/Infraestructura/`; `EntrenamientoController`, solicitudes y recurso; `lang/es/advertencias.php` (nuevo); `tests/Ayudantes/MundoDeCompetencia.php` y una línea en `tests/Pest.php`; `EntrenamientosTest`, `ContratoDeEntrenamientosTest`, `EntrenamientoTest`, `PuertosDeEquipoTest`, `CompetenciaSinPlantelTest`.
- **Pruebas:** 48 (HU-10, §7.2 filas 1 a 7; RN-22; RNF-01; F1-H3-T03, arquitectura).
- **No hecho:** asistencia (T04), lesiones, eventos de dominio (§2.3).
- **Desviaciones:** R-1; DA-07 a DA-15 (DA-08, DA-12, DA-13 y DA-14 entraron a la especificación v1.0.1).

### T04 — Asistencia y porcentajes, HU-11 (`b2ba728`)

- **Archivos:** asistencia y conteos en `src/Competencia/` (incluido `VisibilidadDeJugadorSql`); `AsistenciaDeEntrenamientoController`, dos solicitudes y dos recursos; `AsistenciaDeEntrenamientosTest`, `ContratoDeAsistenciaTest`, `AsistenciaDeEntrenamientoTest`.
- **Pruebas:** 40 nuevas en la suite (HU-11, §7.3 filas 1 a 11; RNF-01, §7.6 filas 1, 2 y 8). Seis mutaciones de los filtros de privacidad, todas detectadas.
- **No hecho:** marca de lesionado (H5), asistencia en el riesgo (RN-05) y sanciones (RN-09, H6); borrar asistencias.
- **Fuera de alcance:** el listado no trae nombres (DA-19); un jugador que deja el equipo sigue en el total del equipo (DA-21).
- **Desviaciones:** ninguna ruta fuera de las rutas base; DA-16 a DA-22.

### T05 — Partidos, HU-12 (`36acad8`; plan aprobado con ajustes)

- **Archivos:** partidos en `src/Competencia/` (`Partido`, `ReferenciasDePartido`, `ProgramarPartido`, `ModificarPartido`); `PartidoController`, dos solicitudes y un recurso; `PartidosTest`, `ContratoDePartidosTest`, `PartidoTest`.
- **Pruebas:** 49 nuevas en la suite (HU-12, §7.4 filas 1 a 8, con la 6 parcial hasta T07; RN-22; RNF-01). Cuatro mutaciones detectadas.
- **No hecho:** resultado y alineación (T07), convocatoria (T06), eventos, advertencia por lesionado (H5).
- **Fuera de alcance:** un torneo eliminado después de asignarlo bloquea cambiar el tipo o la fecha del partido hasta quitarlo (DA-24 con DA-25).
- **Desviaciones:** ninguna ruta fuera de las rutas base; DA-23 a DA-26 (DA-27 retirada).

### T06 — Convocatoria, HU-12 y HU-29 c3 (`725a74b`)

- **Archivos:** `Convocatoria`, `ConvocarJugador`, `QuitarDeLaConvocatoria`, `ConsultaDeConvocatoriaSql` y repositorio; `ConvocatoriaController`, solicitud y recurso; `ConvocatoriaTest` (Feature y Unit), `ContratoDeConvocatoriaTest`.
- **Pruebas:** 18 nuevas en la suite (HU-12, §7.5 filas 1, 2, 3 y 10; HU-29, fila 9; RNF-01, §7.6 fila 3). Seis mutaciones detectadas.
- **No hecho:** alineación y resultado (T07), advertencia por lesionado (H5), vista consolidada (H6).
- **Fuera de alcance:** quitar la convocatoria de un jugador ya en baja solo es posible por id, porque la lista ya no lo muestra.
- **Desviaciones:** ninguna ruta fuera de las rutas base; DA-28 y DA-29.

### FIX — constante de `CompetenciaSinPlantelTest` (`4b112df`)

- Renombra `TABLAS_DE_PLANTEL` a `TABLAS_QUE_COMPETENCIA_NO_LEE`, que chocaba con la de una prueba de H1 desde T03. Un archivo; solo se corrieron las pruebas afectadas.

### T07 — Alineación y resultado, HU-13 (`bbf708c`)

- **Archivos:** `DefinirAlineacion`, `CapturarResultado` y cambios en `Partido` y `Convocatoria`; `AlineacionYResultadoController`, `SolicitudDeAlineacion` y `SolicitudDeResultado`; `docs/trazabilidad.md` (pedido por el responsable); `AlineacionYResultadoTest`, `ContratoDeAlineacionYResultadoTest` y unitarias.
- **Pruebas:** 36 nuevas en la suite (HU-13, §7.5 filas 4 a 8; HTTP de §7.4 fila 6, que queda completa; RN-22; RNF-01). Cuatro mutaciones detectadas.
- **No hecho:** corregir o borrar un resultado (§2.3), habilitar rendimiento (H4), eventos.
- **Fuera de alcance:** la posición se valida con la clave foránea (DA-31). Un puerto de posiciones exigiría tocar `src/Compartido` y `src/Plantel`.
- **Desviaciones:** `docs/trazabilidad.md` (reservado a T09, pedido por el responsable); DA-30 a DA-33.

### FIX — DA-32 corregida por DA-34 (`478330d`)

- **Archivos (dentro de las rutas base):** `DefinirAlineacion`, el puerto `VisibilidadDeJugador` de `src/Competencia/Aplicacion` (`incluirBajas`) y `VisibilidadDeJugadorSql`; `docs/api/openapi.yaml`; `AlineacionYResultadoTest` y `tests/Integration/Competencia/AlineacionClaveForaneaTest.php` (revisión de DA-31).
- **Resultado:** un jugador fuera del alcance en la alineación responde 404, igual que uno inexistente. Dos mutaciones detectadas. Reporte: `f1-h3-fix-da32.md`.

### FIX — compuerta de mutación (`a15493d`, `4b86c86`)

- **Archivos:** `backend/composer.json` (`verificar` sin Infection y `mutacion` nuevo), `Makefile` (destino `mutacion`), `.github/workflows/verificar.yml` (paso `Mutacion`), `docs/decisiones/registro-decisiones.md` (enmienda a DD-53), `docs/hitos/f1-h3.md` v1.0.2 y `VerificarMutacionTest`.
- **Resultado:** la primera corrida falló a los 24 s en PHPStan por un `??` en la prueba, que corrigió `4b86c86`. La segunda pasó en 681 s, y `make mutacion` en 166 s. Reporte: `f1-h3-fix-gate.md`.
- **No hecho:** no se cambió `infection.json5` ni ningún umbral. La integración continua no se comprobó porque no hubo push.

### T08 — Seguridad y alcance de Competencia (`98c70a7`)

- **Archivos:** `tests/Feature/Seguridad/CompetenciaSeguridadTest.php` (nuevo) y un caso en `AlineacionYResultadoTest`.
- **Pruebas:** 15 ejecuciones nuevas (RNF-01, §7.6 filas 1 a 8; RN-22, fila 7; DA-20; HU-13, campo extra en la raíz, línea 42 de `SolicitudDeAlineacion`).
- **Resultado:** no se encontró ningún hueco y no se tocó código de producción. La evidencia de regresión son las 12 mutaciones de los filtros del alcance (entrenamientos, asistencias, partidos y convocatorias): todas hacen fallar al menos una prueba. Así lo aceptó el responsable.
- **Desviaciones:**
  - el plan decía 28 rutas de entrenador; son 29;
  - las 12 mutaciones se aplicaron sobre el propio archivo, con un respaldo que después se restauró, y no en una copia temporal como pide §8 (auditoría del Lote C, m-6).

### T09 — Humo de H3 y cierre documental (`2aa6936` y los F1-DOC de cierre)

- **Archivos:** `scripts/humo/humo-h3.sh` (nuevo), `Makefile` (destino `humo-h3`), `backend/tests/Unit/Herramientas/GuionDeHumoTest.php` (6 pruebas nuevas), `docs/trazabilidad.md`, `docs/decisiones/registro-decisiones.md`, este reporte, `docs/reportes/f1-h3-t08.md` (nota de aceptación) y `docs/reportes/f1-h3-progreso.md`.
- **Guion:** los 16 pasos de §9. Crea por la API los datos de partida de §7.0 que usa: E1 entrena A, con J1, J2, J3 y, después del paso 5, J5; E2 entrena B, con J3 y J4. Usa la categoría «Sub-13» y las matrículas `HUMO-H3-00n`. Cada paso comprueba código y cuerpo, y `set -euo pipefail` detiene el guion en el primer paso roto.
  - **Paso 12:** compara byte a byte el 404 del entrenamiento ajeno con el de uno inexistente, mediante la función `humo_igual` del propio guion (`lib.sh` no está entre las rutas permitidas).
  - **Paso 15:** el partido del paso 11 ya está `jugado` y daría 409 (DA-33), así que el rango de goles se prueba sobre un amistoso programado.
  - **Paso 10:** el identificador de la posición se lee de la tabla sembrada, sin modificar datos, porque el catálogo de posiciones no tiene ruta.
- **Pruebas (`F1-H3-T09`):**
  - el guion real se detiene en el paso 1 si la salud responde 500, y en el paso 2 si el cuerpo del inicio de sesión no cumple (con un `curl` falso y sin servidor ni base);
  - `humo_igual` se detiene si el cuerpo difiere o si el código no es 404, y sigue si son iguales;
  - el guion tiene `set -euo pipefail`, carga `lib.sh` y recorre los pasos 1 a 16 en orden.
  - Sin el guion, las 6 fallaban y las 4 de H2 seguían pasando. Con el guion, pasan las 10.
- **Desviaciones:**
  - El Makefile recibe tres líneas (una en blanco, el destino `humo-h3` y su receta) y la entrada `humo-h3` en `.PHONY`, no una sola línea. **Pendiente de autorización del responsable.**
  - En `2aa6936`, `make humo` no cambió. El FIX `6ae0a0b` (I-1) le agrega `humo-h3.sh` al final; esa es la línea que permite §8.
  - Antes de empezar se revirtió un cambio ajeno, sin commit, en `backend/tests/Feature/SaludTest.php`: una línea de cuatro espacios al final, sin salto de línea. El archivo quedó igual que en `main`, y ningún commit del hito lo toca. **Pendiente de autorización del responsable** (auditoría del Lote C, m-5).

## 4. Rutas tocadas que la especificación no permitía

| Ruta | Tarea | Autorización |
|---|---|---|
| `app/Http/**`, `app/Exceptions/FormatoDeError.php` | T02 y T03 | R-1, el responsable (29/10/2026). Desde la v1.0.1 forman parte de las «rutas base» |
| `docs/trazabilidad.md` | T07 | Pedido por el responsable en el prompt de T07 (§8 lo reservaba a T09) |
| `backend/composer.json`, `Makefile`, `.github/workflows/verificar.yml`, `docs/decisiones/registro-decisiones.md`, `docs/hitos/f1-h3.md` | FIX de la compuerta | Opción (a), aprobada por el responsable |
| `tests/Unit/Herramientas/VerificarMutacionTest.php` | FIX de la compuerta | Autorizado por el responsable (la prueba M-10 apunta al script `mutacion`) |
| `Makefile`, tres líneas (una en blanco, el destino y la receta) más la entrada en `.PHONY`, en vez de una | T09 | **Pendiente de autorización del responsable** |
| `backend/tests/Feature/SaludTest.php`, reversión de un cambio ajeno sin commit (el archivo queda igual que en `main`) | T09 | **Pendiente de autorización del responsable** |

No se tocó `frontend/`. Ninguna ruta de §6.1 cambió, salvo las de la especificación, ni se agregó ningún permiso. No hubo push, PR, etiquetas ni fusiones.

## 5. Decisiones del agente (DA-nn)

DA-01 a DA-34 en `docs/reportes/f1-h3-decisiones-del-agente.md`, registradas en `docs/decisiones/registro-decisiones.md` §5. DA-27 se retiró y no se registra. En T08 y T09 no hubo DA nuevas.

| Bloque | DA | Estado |
|---|---|---|
| Lote A (T01 a T03) | DA-01 a DA-15 | Aprobadas; DA-08, DA-12, DA-13 y DA-14 entraron a la especificación v1.0.1 |
| T04 | DA-16 a DA-22 | Vigentes; T04 aceptada |
| T05 | DA-23 a DA-26 | Aprobadas en el plan |
| T06 | DA-28, DA-29 | Vigentes; T06 aceptada |
| T07 | DA-30 a DA-33 | Vigentes. DA-32 la corrigió DA-34, y DA-31 se revisó en ese FIX |
| FIX tras T07 | DA-34 | Indicada por el responsable |

**De permisos o privacidad** (todas restringen): DA-09 (empalme sin revelar el entrenamiento ajeno), DA-12 (jugadores del equipo con la asignación del usuario), DA-13 (qué es «entrenar un equipo»), DA-20 (porcentaje del jugador solo con alcance de entrenador; el docente recibe 404), DA-28 (solo se convoca a jugadores del equipo del partido), DA-32 (un convocado en baja no cuenta) y DA-34 (404 en la alineación para un jugador fuera del alcance).

## 6. Compuerta de H3 (§10), punto por punto

| # | Punto | Estado | Evidencia |
|---|---|---|---|
| 1 | `make verificar` pasa en local y en CI; `make mutacion` pasa al cerrar cada lote y en CI | **Local: cumple. CI: pendiente del push** | `make verificar`: 615 s, código 0. Pint 10 s, PHPStan 14 s, Pest 555 s, cobertura 36 s y `composer audit` en menos de 1 s (sección 2.1); umbrales por capa 100,0 / 100,0 / 99,4 %; PHPStan nivel 7. `make mutacion`: 112 s, código 0; 16 s de pruebas iniciales y 95 s de mutantes; 501 de 501 eliminados, MSI 100 % (sección 2.2). La CI corre los dos pasos (`composer verificar` y `Mutacion`) cuando el responsable haga push |
| 2 | Cada historia con pruebas por identificador; criterios diferidos de §2.2 anotados | Cumple | `docs/trazabilidad.md`: HU-10 a HU-13, HU-28 y HU-29 con pruebas por identificador. Los siete criterios diferidos de §2.2 están en «Estado al cierre de H3». RN-11, RN-14, RN-18 y RNF-04 no tienen prueba con su identificador; los cubren las pruebas de sus HU (como RN-12 en H1 y H2) |
| 3 | `openapi.yaml` incluye las rutas de §6.1 y las respuestas reales coinciden | Cumple | Las 20 rutas de §6.1 (30 operaciones) están en `docs/api/openapi.yaml`. Las validan contra el esquema `ContratoDeCompetenciaTest`, `ContratoDeEntrenamientosTest`, `ContratoDeAsistenciaTest`, `ContratoDePartidosTest`, `ContratoDeConvocatoriaTest` y `ContratoDeAlineacionYResultadoTest` |
| 4 | `make fresh` y `migrate:rollback` sin errores | Cumple | Sección 2.3: fresh (52 migraciones), rollback (52) y fresh, todo con código 0 |
| 5 | Tablas de §7, una prueba por fila | Cumple | §7.1 filas 1 a 7 (`CatalogosTest`), §7.2 filas 1 a 7 (`EntrenamientosTest`), §7.3 filas 1 a 11 (`AsistenciaDeEntrenamientosTest`), §7.4 filas 1 a 8 (`PartidosTest`; la 6 completa con `AlineacionYResultadoTest`), §7.5 filas 1 a 10 (`ConvocatoriaTest` y `AlineacionYResultadoTest`), §7.6 filas 1 a 8 (`CompetenciaSeguridadTest`, más las de T04 a T07). Cada fila tiene su propia prueba, con la fila en el nombre, **salvo §7.3 filas 8 y 9, que comparten una** (`AsistenciaDeEntrenamientosTest.php:185`, `HU-11 (§7.3 filas 8 y 9)`; declarado en T04) |
| 6 | `make humo` sin fallas (16 pasos de §9) | Cumple | Desde el FIX `6ae0a0b` (I-1), `make humo` corre H0, H1, H2 y H3; tras un solo `make fresh` pasan los cuatro, con los 16 pasos de §9 (sección 9 y `f1-h3-fix-humo.md`). Antes del FIX, la evidencia era `make humo-h3` aparte (sección 8) |
| 7 | Criterios propios de H3 | Cumple | Un entrenador ve solo sus equipos: `CompetenciaSeguridadTest`, las 12 mutaciones de T08 y el humo, paso 12. Una asistencia no se registra dos veces: §7.3 fila 3 y la UQ parcial (`MigracionesDeCompetenciaTest`). Un jugador dado de baja no aparece en convocatorias: §7.5 fila 9 y el humo, paso 14. Porcentaje según §3.4: §7.3 filas 6, 7 y 10, `AsistenciaDeEntrenamientoTest` y el humo, paso 8 (100.00 y 33.33) |
| 8 | Este reporte con lo pedido | Cumple | Secciones 3 (por tarea: commit, archivos, pruebas, `make verificar`, no hecho, fuera de alcance y desviaciones), 4 (rutas fuera de lo permitido) y 5 (DA) |
| 9 | DD-07, DD-17 y DD-18 confirmadas; AB-07 y AB-15 por validar | Cumple | `docs/decisiones/registro-decisiones.md`: DD-07, DD-17 y DD-18 «Confirmada por el responsable el 29/10/2026»; AB-07 y AB-15 «Por validar» |
| 10 | Auditoría independiente del Lote C, sin bloqueantes | Entregada, sin bloqueantes | Un importante (I-1) y menores, corregidos en la sección 9. m-4 no exige acción; m-7 (resultado de la CI) queda para el responsable |
| 11 | El responsable revisa, prueba `make humo` y aprueba; etiqueta `f1-h3` y fusión | **Del responsable** | — |

## 7. Pendientes que H3 deja

- **PT-08 (obligatorio antes de H4):** el tiempo de `make verificar`. Medido de T05 a T09: 729, 719 y 745 s con Infection; 681, 696 y 615 s sin ella. Pest se lleva entre el 77 y el 79 % con Infection (573/729, 567/719 y 577/745) y entre el 90 y el 92 % sin ella (629/681, 639/696 y 555/615). Ver el registro de decisiones §3.
- **PT-07 y PT-01:** siguen pendientes de decisión del responsable.
- **§11 de la especificación:**
  - AB-07, antes de H6 si la institución lo exige;
  - AB-15;
  - captura de rendimiento al jugar un partido (H4);
  - lesionado en asistencia y convocatoria (H5);
  - vista consolidada y acumulación de sanciones sobre la asistencia (H6).
- **CI:** comprobar en la integración continua `composer verificar` y el paso `Mutacion` tras el push.

## 8. Salida completa de los guiones de humo

`make fresh` y después `make humo-h3`:

```text
docker compose exec -T app bash /scripts/humo/humo-h3.sh
OK     paso 1: GET /api/v1/salud -> HTTP 200
OK     paso 2: E1 inicia sesión -> HTTP 200
OK     paso 2: E1 crea la categoría Sub-13 -> HTTP 201
OK     paso 2: E1 crea el equipo A -> HTTP 201
OK     paso 2: registra a Jota Uno (HUMO-H3-001) -> HTTP 201
OK     paso 2: registra a Jota Dos (HUMO-H3-002) -> HTTP 201
OK     paso 2: registra a Jota Tres (HUMO-H3-003) -> HTTP 201
OK     paso 2: E2 inicia sesión (otro archivo de cookies) -> HTTP 200
OK     paso 2: E2 crea el equipo B -> HTTP 201
OK     paso 2: registra a Jota Cuatro (HUMO-H3-004) -> HTTP 201
OK     paso 2: E2 asigna a J3 al equipo B -> HTTP 201
OK     paso 3: E1 crea el lugar L2 -> HTTP 201
OK     paso 3: E1 crea la temporada que contiene hoy -> HTTP 201
OK     paso 4: E1 crea un torneo en esa temporada -> HTTP 201
OK     paso 5: E1 crea un entrenamiento de A con J1, J2 y J3 listados -> HTTP 201
OK     paso 6: E1 crea otro entrenamiento en L2 que se cruza: advierte empalme_de_lugar -> HTTP 201
OK     paso 7: E1 marca el primero realizado -> HTTP 200
OK     paso 7: E1 captura la asistencia de J1, J2 y J3 -> HTTP 201
OK     paso 8: E1 consulta el porcentaje de J1: 100.00 -> HTTP 200
OK     paso 8: el porcentaje del equipo A es 33.33 (la falta justificada cuenta en el denominador) -> HTTP 200
OK     paso 9: E1 crea el rival R1 -> HTTP 201
OK     paso 9: E1 crea un partido oficial con el torneo, en la temporada esperada -> HTTP 201
OK     paso 10: registra a Jota Cinco (HUMO-H3-005) -> HTTP 201
OK     paso 10: E1 convoca a J1 -> HTTP 201
OK     paso 10: E1 convoca a J5 -> HTTP 201
OK     paso 10: E1 define la alineación: J1 titular -> HTTP 200
OK     paso 11: E1 captura el resultado 2-1 -> HTTP 200
OK     paso 11: el partido queda jugado -> HTTP 200
OK     paso 12: E2 consulta un entrenamiento inexistente (referencia) -> HTTP 404
OK     paso 12: E2 consulta el entrenamiento de A: 404 igual byte a byte -> HTTP 404
OK     paso 13: el docente inicia sesión -> HTTP 200
OK     paso 13: el docente recibe 403 al crear un lugar -> HTTP 403
OK     paso 14: antes de la baja, J5 está en la convocatoria -> HTTP 200
OK     paso 14: E1 da de baja a J5 -> HTTP 201
OK     paso 14: J5 desaparece de la convocatoria y J1 sigue -> HTTP 200
OK     paso 15: E1 crea un amistoso programado -> HTTP 201
OK     paso 15: 100 goles responde valor_fuera_de_rango -> HTTP 422
OK     paso 16: E1 cierra sesión -> HTTP 204
OK     paso 16: la misma cookie responde 401 -> HTTP 401
HUMO H3 OK: 16 pasos
```

`make fresh` y después `make humo`:

```text
docker compose exec -T app bash -s < scripts/humo/humo-h0.sh
HUMO H0 OK: GET /api/v1/salud -> 200
docker compose exec -T app bash /scripts/humo/humo-h1.sh
OK     paso 1: GET /api/v1/salud -> HTTP 200
OK     paso 2: el entrenador 1 inicia sesión -> HTTP 200
OK     paso 3: crea la categoría Sub-17 -> HTTP 201
OK     paso 4: crea el equipo Tigres Sub-17 -> HTTP 201
OK     paso 4: el equipo aparece en GET /equipos -> HTTP 200
OK     paso 5: registra un jugador en ese equipo -> HTTP 201
OK     paso 6: la misma matrícula responde 409 sin datos del primero -> HTTP 409
OK     paso 7: el entrenador 2 inicia sesión con otro archivo de cookies -> HTTP 200
OK     paso 8: el jugador del entrenador 1 responde 404 al entrenador 2 -> HTTP 404
OK     paso 9: la búsqueda por matrícula devuelve solo nombres, apellidos y matrícula -> HTTP 200
OK     paso 10: el entrenador 2 crea su equipo -> HTTP 201
OK     paso 10: asigna al jugador por matrícula -> HTTP 201
OK     paso 11: GET /jugadores del entrenador 2 devuelve al jugador con solo su equipo -> HTTP 200
OK     paso 12: el docente inicia sesión -> HTTP 200
OK     paso 12: el docente recibe 403 al crear un equipo -> HTTP 403
OK     paso 12: el docente recibe una lista vacía en GET /jugadores -> HTTP 200
OK     paso 13: el entrenador 1 da de baja al jugador -> HTTP 201
OK     paso 13: deja de aparecer en GET /jugadores del entrenador 1 -> HTTP 200
OK     paso 13: deja de aparecer en GET /jugadores del entrenador 2 -> HTTP 200
OK     paso 14: el entrenador 1 cierra sesión -> HTTP 204
OK     paso 14: la misma cookie responde 401 -> HTTP 401
HUMO H1 OK: 14 pasos
docker compose exec -T app bash /scripts/humo/humo-h2.sh
Servidor del guion con QUEUE_CONNECTION=sync
OK     paso 1: GET /api/v1/salud -> HTTP 200
OK     paso 2: el docente 1 inicia sesión -> HTTP 200
OK     paso 3: crea un periodo escolar que contiene hoy y sale vigente -> HTTP 201
OK     paso 4: crea la asignatura MAT-101 -> HTTP 201
OK     paso 5: crea la materia con esa asignatura y ese periodo -> HTTP 201
OK     paso 6: el entrenador 1 inicia sesión (otro archivo de cookies) -> HTTP 200
OK     paso 6: crea su categoría Sub-15 -> HTTP 201
OK     paso 6: crea su equipo -> HTTP 201
OK     paso 6: registra un jugador -> HTTP 201
OK     paso 7: el docente 1 busca la matrícula: solo nombres, apellidos y matrícula -> HTTP 200
OK     paso 7: lo inscribe por matrícula -> HTTP 201
OK     paso 8: captura la calificación parcial 8.50 -> HTTP 201
OK     paso 8: captura la calificación final 8.00 -> HTTP 201
OK     paso 9: captura la asistencia presente del 2026-10-27 -> HTTP 201
OK     paso 9: captura la asistencia presente del 2026-10-28 -> HTTP 201
OK     paso 9: captura la asistencia falta del 2026-10-29 -> HTTP 201
OK     paso 10: situación como docente: 8.00, sin_riesgo, 66.67 y con materias -> HTTP 200
OK     paso 11: define el criterio propio 8.50 -> HTTP 200
OK     paso 11: la situación pasa a 1 reprobada y en_observacion -> HTTP 200
OK     paso 12: el entrenador 1 ve el mismo nivel, sin materias -> HTTP 200
OK     paso 13: el docente 2 inicia sesión -> HTTP 200
OK     paso 13: el docente 2 recibe 404 en la situación -> HTTP 404
OK     paso 13: el docente 2 recibe una lista vacía en GET /jugadores -> HTTP 200
OK     paso 14: el entrenador 1 recibe 403 al crear un periodo -> HTTP 403
OK     paso 14: el docente 1 recibe 403 al crear un equipo -> HTTP 403
OK     paso 15: un valor fuera de rango responde calificacion_fuera_de_rango -> HTTP 422
OK     paso 16: el docente 1 cierra sesión -> HTTP 204
OK     paso 16: la misma cookie responde 401 -> HTTP 401
HUMO H2 OK: 16 pasos
```

## 9. Correcciones de la auditoría del Lote C

| Hallazgo | Corrección | Dónde |
|---|---|---|
| I-1 | `make humo` corre H0, H1, H2 y H3. Tras un solo `make fresh` pasan los cuatro (H3, 16 pasos) en 30 s, sin cambiar los guiones | FIX `6ae0a0b`; `f1-h3-fix-humo.md` (salida completa); §10 punto 6 en la sección 6 |
| m-1 | Pest se lleva entre el 77 y el 79 % con Infection y entre el 90 y el 92 % sin ella | Sección 7 y `registro-decisiones.md`, PT-08 |
| m-2 | El Makefile de T09 son tres líneas más la entrada en `.PHONY`; pendiente de autorización | Secciones 3 (T09) y 4 |
| m-3 | §7.3 filas 8 y 9 comparten una prueba | Sección 6, punto 5 |
| m-5 | Reversión de `SaludTest.php`; pendiente de autorización | Secciones 3 (T09) y 4 |
| m-6 | Las mutaciones de T08 se hicieron sobre el archivo, con respaldo, y no en copia temporal | Sección 3 (T08) y `f1-h3-t08.md` |
| m-8 | DA-09 revela que un lugar está reservado a esa hora, sin decir qué equipo ni quién | `registro-decisiones.md` §5 |
| m-9 | Plan retrospectivo de T09 | `f1-h3-plan-t09.md` |

Sin DA nuevas. m-4 (reglas sin identificador en el nombre) ya estaba anotado en `docs/trazabilidad.md`. m-7 (resultado de la CI tras el push) queda para el responsable.

### 9.1 `make verificar` tras las correcciones (sobre `6ae0a0b`)

Antes se revisaron los procesos de `app`, `worker` y `scheduler` (`/proc`) y de Windows (`tasklist`): no había ningún `pest`, `composer`, `phpstan`, `infection` ni `php -S`. Los cuatro contenedores estaban arriba; `app` y `db`, sanos. Durante la corrida no se lanzó nada más.

| Etapa | Llegó al segundo | Duración | Resultado |
|---|---:|---:|---|
| Arranque de `docker compose exec` y Pint | 10 | 10 s | `PASS … 585 files` |
| PHPStan nivel 7 | 13 | 3 s | `[OK] No errors` |
| Pest con cobertura, hasta el resumen | 571 | 558 s (`Duration: 556.79s`) | **1 224 pruebas** (8 285 aserciones) |
| Informe de cobertura y cobertura por capa | 613 | 42 s | 100,0 / 100,0 / 99,4 % (total 99,5 %) |
| `composer audit` | 613 | < 1 s | `No security vulnerability advisories found.` |
| **Total** | | **613 s (10 min 13 s)**; código 0 | por debajo de 13 min |

Pest se lleva el 91 % de la corrida (558/613), dentro del rango corregido en m-1. `make mutacion` no se repitió: el FIX solo toca el Makefile y nada de `src/*/Dominio/`.
