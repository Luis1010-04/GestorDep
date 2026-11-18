# Diagnóstico del frontend (sin código)

Rama `frontend/blade-niceadmin`, 17/11/2026. Solo lectura: no se escribió código, no se tocó `frontend/NiceAdmin/` y no hay commit de este archivo.

**Qué se leyó:** `frontend/docs/` (los tres documentos), `docs/api/openapi.yaml` (99 operaciones en 70 rutas), `backend/routes/api.php` (grupos de rol), `backend/bootstrap/app.php`, `backend/app/Exceptions/FormatoDeError.php`, `docs/convenciones-api.md` §1, §3 y §4, `docs/alcance-fases.md` §5 y §7, `docs/decisiones/registro-decisiones.md` (DA-122) y `docker-compose.yml`.

**Qué no se hizo:** no se ejecutó nada contra la API ni se abrió un navegador. Los roles salen de los grupos de `routes/api.php` y coinciden con la presencia o ausencia de 403 en OpenAPI. No leí cada Resource ni cada Request: eso lo exige la ficha de cada módulo (`CRITERIOS_ACEPTACION.md` §8) antes de construirlo. Lo que es inferencia lo marco como tal.

---

## 1. Qué dice `alcance-fases.md` sobre el frontend

- **§7, punto 6:** el frontend es precondición de la Fase 2 y se desarrolla en `frontend/blade-niceadmin`, creada desde `main` tras fusionar H6 (DA-122, aprobada el 17/11/2026).
- **Ese punto no enumera pantallas.** Por eso no puedo confirmar desde `alcance-fases.md` qué pantallas son "de Fase 1 del front". Lo que confirma es la rama y el momento.
- **Las pantallas del primer hito vienen de `frontend/docs/ESPECIFICACION_FUNCIONAL.md` §7:** ingreso, layout con menú por rol y panel sin cifras ficticias.
- **Contradicción a resolver.** `alcance-fases.md` §5 sigue diciendo que "cualquier cosa dentro de `frontend/`: pantallas, componentes, estilos" está prohibido en la Fase 1. DA-122 y §7.6 lo autorizan en la rama aparte, pero §5 no se actualizó. Propongo que el responsable lo anote como excepción de DA-122. No lo modifico.
- **Reportes, PDF, administración de usuarios y alertas distintas de las tres del MVP** están excluidos por §5 y por `ESPECIFICACION_FUNCIONAL.md` §8.

## 2. Endpoints disponibles, por rol

Fuente: grupos de middleware de `backend/routes/api.php`. Todos los errores usan `{codigo, mensaje, errores[{campo, codigo, mensaje}]}`. Las advertencias van en 200/201 como `advertencias[]` (convenciones §4).

| Quién | Operaciones (método y ruta) | Errores documentados |
|---|---|---|
| **Público** | `GET /salud`; `GET /sanctum/csrf-cookie` (fuera de `/api/v1`); `POST /sesion` | `/salud`: 503. `POST /sesion`: 401 `credenciales_invalidas`, 422, 429 `cuenta_bloqueada` con `Retry-After` |
| **Cualquier usuario con sesión** | `DELETE /sesion`; `PUT /contrasena`; `GET /jugadores`; `GET /jugadores/busqueda`; `GET /jugadores/{id}`; `GET /jugadores/{id}/situacion-academica`; `GET /jugadores/{id}/asistencia-entrenamientos` | 401 y 404; 422 en `PUT /contrasena`, en el listado y en la búsqueda. Estas rutas no tienen 403 |
| **Docente o entrenador** | `GET` y `POST /jugadores/{id}/avisos-lesion`; `GET /jugadores/{id}/estado-disponibilidad`; `GET /jugadores/{id}/alertas`; `GET /estatus-de-jugadores`; `GET /jugadores/{id}/graficos` | 401, 403, 404; 422 en el listado de estatus |
| **Solo docente** | Periodos escolares, asignaturas y materias (alta, listado, borrado lógico y ficha); `PUT /materias/{id}/criterio`; inscripciones; calificaciones y asistencias de clase (alta, listado, corrección) | 401, 403, 404, 409, 422 |
| **Solo entrenador** | Categorías, equipos, alta, edición y baja de jugadores, asignación a equipo y posición; lugares, rivales, temporadas y torneos; entrenamientos y su asistencia; partidos, convocatoria, alineación y resultado; métricas, rendimiento, plantillas, umbrales y desempeño; descarte y confirmación de avisos, estado de disponibilidad y alta, lesiones, mediciones, tipos de prueba y pruebas físicas; vistas para convocar; gráficos del equipo | 401, 403, 404, 409, 422 |

- **Falta `GET /api/v1/sesion`.** El plan de PT-10 está presentado y espera mis respuestas a sus tres decisiones.
- **419 (token CSRF vencido o ausente) no está en OpenAPI.** `FormatoDeError` convertiría ese caso en una `HttpException` con código `solicitud_no_valida`. Es una inferencia por lectura, sin ejecutar.
- **El 429 solo está documentado en `POST /sesion`.**
- **11 operaciones declaran `orden`.** El resto no se puede ordenar.

## 3. Pantallas que pide la especificación y su endpoint

| Pantalla (`ESPECIFICACION_FUNCIONAL.md` §7) | ¿Tiene endpoint? |
|---|---|
| Ingreso | Sí: `GET /sanctum/csrf-cookie` y `POST /sesion` |
| Recuperar identidad y roles al cargar o recargar | **No** hasta PT-10 (`GET /sesion`) |
| Cerrar sesión | Sí: `DELETE /sesion` |
| Menú por rol | Depende de la identidad anterior |
| Panel inicial sin cifras | No necesita endpoint; no existe uno de panel, y no debe inventarse |
| Estados de carga, sesión expirada y errores de API | Transversal; la sesión expirada se detecta por el 401 |
| Cambiar contraseña (tabla de §6, "según API") | Sí: `PUT /contrasena` |

Las demás áreas de §5 (plantel, académico, competencia, rendimiento, condición física, salud y alertas) tienen endpoints, pero la especificación no define pantallas para ellas. Cada una necesita su ficha de módulo antes de construirse.

## 4. Huecos

### 4.1 Endpoints faltantes o límites de la API
1. `GET /api/v1/sesion`: bloquea la identidad y los roles (CA-AUT-002).
2. No hay endpoint de panel ni de indicadores. El panel debe ser estático (CA-PAN-001).
3. No hay `GET /equipos/{id}`, ni `GET /categorias/{id}`, ni listado de entrenadores, ni listado global de alertas. `estatus-de-jugadores` es el listado con alertas.
4. Entrenamientos y partidos se listan solo por equipo. Los jugadores de un equipo no tienen listado propio salvo `GET /jugadores` con `filtro[equipo_id]`, que debo confirmar en la ficha.
5. Un docente no puede listar equipos (`GET /equipos` es de entrenador). Los filtros por equipo de `estatus-de-jugadores` solo aciertan con equipos que el usuario entrena.
6. **No hay desactivar jugadores**: la API ofrece baja con historial (`POST /jugadores/{id}/baja`). La tabla de §6 de la especificación dice "desactivar".

### 4.2 Estructura física: dónde vive el frontend
Este es el hueco mayor, y no lo puedo resolver solo.
- `backend/` no tiene `resources/views/`, ni `routes/web.php`; `bootstrap/app.php` solo registra `api` y `commands`; `backend/public/` solo tiene `index.php`, `favicon.ico` y `robots.txt`.
- `docker-compose.yml` monta `./backend`, `./docs` y `./scripts`, pero **no `./frontend`**. El contenedor no ve `frontend/NiceAdmin/`.
- La especificación dice que las vistas Blade se incorporan al proyecto Laravel existente. Eso exige tocar `backend/` (rutas web, vistas, activos), y las reglas del backend piden tarea aprobada para cada ruta. También entra en juego `make verificar` (Pint, PHPStan y cobertura por capa, con 70 % en Infraestructura y HTTP) sobre cualquier PHP nuevo.

### 4.3 Criterios sin cubrir o sin definir
- **CA-AUT-002** y **REGLAS §5** dicen que `GET /sesion` no existe: quedarán desactualizados cuando se integre PT-10.
- **CA-TAB-004:** DataTables, SweetAlert2 y jQuery no están en el repo. `assets/vendor` trae `simple-datatables`, que es otra cosa y no hace paginación de servidor. La especificación los llama "acordados", mientras que REGLAS §4 prohíbe instalar dependencias sin autorización.
- **Gráficos:** `GET .../graficos` devuelve series ya calculadas, y "el dibujo es del frontend". La librería está diferida por decisión suya.
- **No hay estrategia de pruebas del frontend.** `make verificar` no cubre Blade ni JavaScript, Node está vetado (§3 de la especificación) y REGLAS §9 pide revisar navegador y red. Sin definir qué se acepta como prueba, no se puede declarar un criterio cumplido.
- **Textos de la interfaz:** la regla del backend manda los textos en `lang/es`. La especificación (§10) dice que el frontend puede redactar los suyos. No dice dónde.
- **Fichas por módulo** (`CRITERIOS_ACEPTACION.md` §8) y `estado-de-implementacion.md`: no existen.

### 4.4 Decisiones pendientes que arrastro
- **Licencia de NiceAdmin** (gratuita o Pro): sin ella no se versiona la plantilla y no se pueden copiar los activos.
- **PT-09, PT-10 y PT-11** están en ramas aparte, sin fusionar. El front depende de las tres.
- `php -S` del servicio `web` atiende una petición a la vez: un front que dispare varios `fetch` a la vez se serializa. No lo he medido.

## 5. Propuesta de rebanadas

Cada rebanada se acepta con los criterios de `CRITERIOS_ACEPTACION.md` §9: archivos, endpoints, pruebas, capturas y limitaciones.

| # | Rebanada | Endpoints | Criterios | Riesgos |
|---|---|---|---|---|
| **0** | **Prerrequisitos (no son del front):** PT-09 fusionada, PT-10, PT-11 y la decisión de estructura física (§4.2) | — | — | Bloquean la rebanada 1 |
| **1** | **Ingreso, layout, menú por rol, panel vacío y salida** | `GET /sanctum/csrf-cookie`; `POST`, `GET` y `DELETE /sesion` | CA-AUT-001 a 003, CA-LAY-001 y 002, CA-PAN-001, CA-GEN-002 a 006 (en lo que aplique) | Depende de PT-10 y PT-11; 419; licencia; `main.js` de la plantilla |
| **2** | **Cambiar contraseña** | `PUT /contrasena` | CA-FOR-001 a 003, CA-GEN-003 (422) | Cierra las demás sesiones: avisarlo en la interfaz |
| **3** | **Jugadores, lectura:** listado paginado, búsqueda por matrícula, ficha y situación académica | `GET /jugadores`, `/busqueda`, `/{id}`, `/{id}/situacion-academica` | CA-TAB-001 a 003, CA-GEN-003 (404) | Establece el patrón de tablas; define si hay biblioteca |
| **4** | **Alertas y estatus (docente y entrenador)** | `GET /estatus-de-jugadores`; `GET /jugadores/{id}/alertas` | CA-TAB-001 a 003 | Forma distinta por rol (DA-105): no asumir campos |
| **5** | **Académico (docente)**, en dos partes: 5a catálogos (periodos, asignaturas y materias); 5b inscripciones, calificaciones y asistencia de clase | Rutas del grupo `rol:docente` | CA-FOR-001 a 003, CA-TAB-001 y 002 | Escrituras con 409 y 422; correcciones con historial |
| **6** | **Plantel (entrenador):** categorías, equipos, alta, edición y baja de jugador, asignación y posición | Grupo `rol:entrenador` de plantel | CA-FOR-001 a 003 | La baja es irreversible: exige confirmación |
| **7** | **Competencia (entrenador):** lugares, rivales, temporadas y torneos; entrenamientos y su asistencia; partidos, convocatoria, alineación y resultado; vistas para convocar | Grupo `rol:entrenador` de competencia | CA-FOR-001 a 003 | Advertencias en 200/201 (empalme de lugar, jugador lesionado) |
| **8** | **Rendimiento y salud (entrenador):** rendimiento, plantillas, umbrales, desempeño, avisos, estado, lesiones, mediciones y pruebas físicas | Grupo `rol:entrenador` de rendimiento y salud | CA-FOR-003 (confirmación `es_correccion`) | Reglas RN-15 y RN-16: visibilidad por registrante; el doble envío de una prueba física responde 409 `prueba_ya_registrada` (deshabilitar el botón tras el primer clic) |
| **9** | **Gráficos** | `GET /jugadores/{id}/graficos`; `GET /equipos/{id}/graficos` | — | **Bloqueada** hasta decidir librería |

Las rebanadas 5 a 8 son listas de alcance, no planes: cada una necesita su ficha de módulo antes de planearse.

## 6. Sugerencias de diseño para la rebanada 1

### Ingreso
- **Flujo:** `GET /sanctum/csrf-cookie` y luego `POST /api/v1/sesion` con `fetch`, `credentials: 'same-origin'`, `Accept: application/json` y la cabecera `X-XSRF-TOKEN` leída de la cookie `XSRF-TOKEN` (decodificada). Es el flujo de Sanctum SPA que las pruebas del backend ejercitan (`IniciarSesionTest`). No hay Bearer.
- **Origen:** como front y API comparten origen (`http://localhost:8000`), no hace falta CORS y la cookie de sesión funciona con `SameSite=lax`.
- **Errores:** 401 `credenciales_invalidas` muestra el `mensaje` de la API tal cual. La API responde lo mismo para usuario inexistente, desactivado o contraseña incorrecta (RNF-10), y la interfaz no debe distinguirlos. 422 enseña `errores[].campo`. 429 muestra el bloqueo y el `Retry-After` (15 minutos según OpenAPI). 403 `origen_no_permitido` (PT-11) y 419 se tratan como error genérico de sesión.
- **Duplicados:** deshabilitar el botón durante la solicitud (CA-GEN-002).
- **Almacenamiento:** no se guarda nada en `localStorage` ni `sessionStorage` (CA-GEN-004).

### Layout
- Un layout `app` y otro `autenticacion`, con parciales de cabecera, menú y pie (CA-LAY-001).
- **Activos:** solo los que se usan: Bootstrap 5.3.3 y Bootstrap Icons, locales. No se copia el `main.js` de la plantilla (CA-LAY-001); se escribe un script propio mínimo.
- **Pie:** conserva `Designed by BootstrapMade` (licencia gratuita, CA-GEN-006).
- **Un cliente de API pequeño** (`fetch` con CSRF, cabeceras y normalización de error `{status, codigo, mensaje, errores, retryAfter}`). Un 401 en cualquier llamada lleva al ingreso.

### Menú por rol
- **Fuente de la identidad:** `GET /api/v1/sesion` (PT-10), no `localStorage` (CA-AUT-002 y especificación §7 puntos 6 y 7).
- **Cómo:** los ítems llevan un atributo declarativo con los roles que los ven y arrancan ocultos; el script los muestra cuando llega la sesión. Con ambos roles se muestra la unión (CA-LAY-002).
- **En la rebanada 1:** el menú muestra **solo "Panel"** y la salida en el menú de usuario. Los enlaces a pantallas no construidas no se muestran (CA-GEN-005). Cada rebanada posterior añade su ítem con el rol que `routes/api.php` ya establece.
- **Seguridad:** el menú solo oculta; la autorización la decide la API (CA-GEN-004).

### Protección de las páginas
- Las rutas web protegidas con `auth:web` redirigen al ingreso en el servidor, antes de enviar HTML. Así una página privada no se muestra a quien no tiene sesión, sin parpadeo de contenido (CA-GEN-005).
- Eso requiere `routes/web.php` y una configuración de redirección en `bootstrap/app.php`, es decir, tocar `backend/` (ver §4.2).

### Panel vacío
- Bienvenida estática con el nombre y los roles que devuelve `GET /sesion`. Sin tarjetas, conteos ni gráficas (CA-PAN-001).
- Si el sistema aún no tiene secciones habilitadas, un texto neutral y no engañoso.

### Estados y sesión expirada
- La sesión dura 120 minutos (`SESSION_LIFETIME`). Un 401 en cualquier `fetch` redirige al ingreso con un aviso de sesión expirada.
- En un 419 se pediría la cookie CSRF de nuevo y se reintentaría una sola vez, **solo para solicitudes seguras**. No lo reintento solo: es una decisión suya (D7).

## 7. Qué necesita su aprobación, por rebanada

### Rebanada 0 (previa)
- **D1.** Estructura física: ¿vistas en `backend/resources/views`, rutas en `backend/routes/web.php` y activos en `backend/public`, con `frontend/` como origen del template y la documentación? ¿O vistas en `frontend/` con un montaje nuevo en `docker-compose.yml` y configuración de rutas de vistas? Recomiendo la primera por sencillez, con una tarea de backend que liste exactamente los archivos permitidos. Es una novedad respecto a "backend/ no se toca desde la rama del front".
- **D2.** Licencia de NiceAdmin (gratuita o Pro) y versionar `frontend/NiceAdmin/`.
- **D3.** Respuestas a las tres decisiones del plan de PT-10 (la clase `VerSesion`, qué responder con borrado lógico y si habrá paso de humo) y aprobación de fusionar PT-09, PT-10 y PT-11 en `main` antes de rebasar la rama del front.
- **D4.** Anotar en `alcance-fases.md` §5 la excepción de DA-122 (no lo modifiqué).

### Rebanada 1
- **D5.** Dónde viven los textos de la interfaz: en `lang/es` (consistente con la regla del backend) o en las vistas. Recomiendo `lang/es`.
- **D6.** Guardia de rutas del lado del servidor con `auth:web` (recomendada) o solo del lado del cliente.
- **D7.** Reintento ante 419: no reintentar, o una vez para solicitudes seguras.
- **D8.** Cómo se acepta un criterio del front, dado que `make verificar` no lo cubre: pruebas Pest de las rutas web (renderizan, redirigen sin sesión, conservan el crédito), más una lista de comprobación manual en navegador con capturas. No introduzco herramientas nuevas sin su aprobación.
- **D9.** Si el servicio `web` debe correr con más de un worker de PHP (`PHP_CLI_SERVER_WORKERS`) o se acepta el límite actual.

### Rebanada 2
- Ninguna decisión propia, salvo reutilizar el patrón de formularios y confirmar el aviso de "cierra las demás sesiones".

### Rebanada 3
- **D10.** Biblioteca de tablas: recomiendo **ninguna** hasta la rebanada 4. Una tabla Bootstrap con paginación propia leyendo `meta` y `orden` cumple CA-TAB-001 a 003 sin jQuery ni adaptador de DataTables, que exigiría traducir `draw/start/length` a `pagina/por_pagina`.
- **D11.** Qué columnas y filtros (`filtro[equipo_id]`, `orden`) salen de la ficha, que haré al inicio de la tarea.

### Rebanada 4
- **D12.** Cómo presentar la forma distinta por rol de `FilaDeEstatus` (DA-105).

### Rebanadas 5 a 8
- **D13.** Confirmación de acciones destructivas: Bootstrap modal en lugar de SweetAlert2, que no está instalado.
- **D14.** Orden de las rebanadas 5 a 8 y si 5 y 7 se parten en más tareas.

### Rebanada 9
- **D15.** Librería de gráficos (diferida por usted el 17/11/2026).

## 8. Siguiente paso recomendado

1. Resolver D1 a D4 (estructura física, licencia, PT-10 y la excepción del alcance).
2. Con eso, aprobar un plan para la rebanada 1 (archivos, rutas permitidas, pruebas por criterio). No construyo nada hasta su aprobación.
