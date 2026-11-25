# Plan de las rebanadas 2 a 9 del frontend

**Rama:** `frontend/blade-niceadmin`, en `ae680a7`, que es `main` fusionada por avance rápido el 24/11/2026. **Orden:** del responsable, 24/11/2026, con las decisiones F1 a F4. **Estado:** aprobado el 24/11/2026 con los ajustes de §0. El plan original no instaló bibliotecas ni escribió vistas; R2 y R3 se construyen en tareas aparte. Cada rebanada tendrá además su propio plan, con la ficha de módulo de `frontend/docs/CRITERIOS_ACEPTACION.md` §8, antes de construirse.

**Qué se leyó:** `frontend/docs/` (los cuatro documentos), `docs/reportes/front-diagnostico.md`, `docs/reportes/pt-12-reporte.md`, `docs/api/openapi.yaml` (77 rutas y los esquemas de `components`), `backend/routes/api.php` y `web.php`, `docs/decisiones/registro-decisiones.md` (DA-105, DA-122 y DA-131), `docs/hitos/f1-h6.md` §6.2 (gráficos), `docs/alcance-fases.md` §3 y §5, `backend/public/NiceAdmin/assets/js/main.js` y las vistas, scripts y pruebas de la rebanada 1. Versiones, licencias, pesos e integridad de las bibliotecas: registro de npm y jsDelivr, consultados el 24/11/2026, solo lectura.

**Qué no se hizo:** no se leyeron los Resource ni los Request del backend uno por uno; lo exige la ficha de cada rebanada. Lo que es inferencia se marca como tal.

---

## 0. Aprobación del responsable (24/11/2026) y ajustes

El responsable aprobó este plan con los ajustes siguientes. Donde el resto del documento diga otra cosa, manda esta sección.

1. **Arquitectura:** se mantiene el monolito (opción A). Laravel sirve la API y las vistas, y front y back se separan por carpetas. El front solo modifica `backend/resources/views/`, `backend/routes/web.php`, `backend/public/NiceAdmin/`, `backend/public/assets/`, el controlador de páginas (`app/Http/Controllers/Web/PaginaController.php`) y sus pruebas web, `backend/lang/es/interfaz.php` y `frontend/docs/`. **Todas las páginas van en `PaginaController`**: los controladores por área que nombra §4 (`CuentaController`, `JugadoresController`, etc.) no se crean. Cualquier otro archivo de `backend/` es del agente de backend y se pide como petición.
2. **Autorizaciones puntuales del responsable** (respuestas del 24/11/2026): se amplía la lista exacta de `tests/Feature/EntornoDePruebasTest.php` con las rutas web de cada rebanada, sin debilitarla; y el front puede modificar `docs/reportes/` (este plan, reportes, capturas y logs) y la sección «Rutas web» de `docs/trazabilidad.md`.
3. **Aprobadas tal como se proponen:** DAF-03 (DataTables 3.1.6 sin jQuery), DAF-04 (ApexCharts 4.4.0), DAF-05 a DAF-08 y DAF-10 a DAF-16. DataTables entra desde R3.
4. **DAF-09 cambia:** cada tabla ofrece «Descargar esta página» y «Descargar todo». La segunda pide todas las páginas a la API, con un tope de 1000 filas y una confirmación de SweetAlert2 que dice cuántas filas se van a descargar (§3.1).
5. **DAF-17. F4 es una excepción del responsable a `docs/alcance-fases.md` §5:** se permite descargar en el navegador las tablas que la pantalla muestra; los reportes formales siguen en la Fase 2. La fila del registro de decisiones la agrega el backend.
6. **PB-01 a PB-05** quedan en cola para el backend, después de la etiqueta `f1-h7`.
7. **Entorno:** `worker` y `scheduler` van apagados en el override local del front (perfil `apagado`).
8. **Autorizadas para implementar:** R2 y R3, con la prueba primero, después la vista y la comprobación en navegador con capturas; commits `FRONT-R2: …` y `FRONT-R3: …`. El PR #38 sigue en borrador y acumula el trabajo del front hasta que cierre H7. No se fusiona nada.
9. **Estructura de vistas y scripts (orden del responsable del 24/11/2026, 14:15).** Toma como referencia de organización `resources/views/` del repositorio `Luis1010-04/sistema_ventas`, adaptada a las reglas de GestorDep. Rige desde R2 y sustituye las rutas de archivo que nombra §4:
   - **Vistas:** `backend/resources/views/modulos/{modulo}/`, con `index`, `crear`, `editar` y `ver` cuando apliquen. `layouts/` y `parciales/` se conservan donde están, igual que las vistas `ingreso` y `panel` de la rebanada 1. Cada vista usa `@yield('titulo')`, `@yield('contenido')` y `@stack('scripts')`; el layout ya tiene `@stack('scripts')`, y también `@stack('estilos')` para las hojas de estilo de las bibliotecas (DAF-07).
   - **Scripts:** los comunes en `backend/public/assets/js/comun/` (`avisos.js`, `formularios.js`, `tabla.js`); los de cada módulo en `backend/public/assets/js/modulos/{modulo}/{vista}.js`. Los de la rebanada 1 (`api.js`, `sesion.js`, `ingreso.js` y `tinymce-vacio.js`) no se mueven.
   - **`comun/avisos.js`** ofrece `exito()`, `error()`, `aviso()` (esquina superior derecha, sin botón, 1,5 s) y `confirmar()`, sobre SweetAlert2. Ninguna vista ni otro script llama a `Swal.fire`, y los errores usan `icon: 'error'`.
   - **`comun/tabla.js`** configura DataTables con los textos en español de `lang/es` y los botones de descarga de DAF-09 ajustada.
   - **Sin CDN, sin jQuery y ningún cambio de datos por GET:** las rutas web solo devuelven vistas, y toda escritura va por `POST`, `PUT`, `PATCH` o `DELETE` de la API.
   - **Correspondencia en R2 y R3:** R2, `modulos/contrasena/editar` con `js/modulos/contrasena/editar.js`. R3, `modulos/jugadores/index` (listado y búsqueda) y `modulos/jugadores/ver` (ficha), con `js/modulos/jugadores/index.js` y `ver.js`.
   - **Pruebas que lo fijan:** FRONT-R2 11 a 14 (`EstructuraDelFrontTest`).

---

## 1. Decisiones F1 a F4 y lo que cambia al verificarlas

| Decisión | Resuelve | Desde | Lo que se encontró al verificarla |
|---|---|---|---|
| **F1.** Gráficas con ApexCharts de la plantilla | D15 | R9 | La copia de `backend/public/NiceAdmin/assets/vendor/apexcharts/` es **ApexCharts 4.4.0, licencia MIT**, con los mismos SHA-256 que el paquete oficial (`apexcharts.min.js` `f4f5a1cf…0990`, `apexcharts.css` `277b0315…8589`). Desde la 5.3.0 la licencia es la «ApexCharts License» (la 8.9.1, la última, dice «SEE LICENSE IN LICENSE.md»). **Se usa la 4.4.0 y no se actualiza.** La plantilla no trae el archivo de licencia: se añade el `LICENSE` del paquete oficial, como se hizo con Bootstrap. |
| **F2.** DataTables con Bootstrap 5, Buttons, JSZip y pdfmake; jQuery solo porque DataTables lo exige | D10 | R3 | **DataTables 3 ya no exige jQuery.** `datatables.net` 2.3.4 declara la dependencia `jquery >=1.7`; la 3.0.0 y la 3.1.6 no declaran ninguna, y su Readme dice «DataTables is dependency free». El código de la 3.1.6 usa jQuery solo si ya está cargado. Buttons 4.1.4 y las dos integraciones de Bootstrap 5 tampoco lo piden. **Propuesta (DAF-03): DataTables 3.1.6 sin jQuery.** |
| **F3.** Avisos y confirmaciones con SweetAlert2 | D13 | R2 | 11.26.26, MIT. La 11.4.9 traía código que se activaba según el idioma del navegador y el dominio (`navigator.language === 'ru' && location.host.match(...)`). En la 11.26.26, el archivo de distribución no contiene `location` en ningún punto. Una prueba lo fijará. |
| **F4.** Descarga de tablas en PDF y Excel en el navegador, sin endpoints | — | R3 | `docs/alcance-fases.md` §5 prohíbe «Reportes y exportación (PDF, Excel, CSV)» en la Fase 1, y §3 (punto 9) deja la exportación para la Fase 2. F4 se limita a descargar lo que la tabla ya muestra y deja los reportes formales en la Fase 2. **Pido anotarla como excepción en §5, igual que DA-122** (no modifico `alcance-fases.md`). |

**Contradicción con `frontend/docs/ACTIVOS_NICEADMIN.md` §4.** Dice «sin biblioteca de tablas hasta la rebanada 4», que era la recomendación D10 del diagnóstico. F2 resuelve D10 con DataTables, y este plan lo usa desde R3 para fijar el patrón de tablas una sola vez. La alternativa es una tabla Bootstrap con paginación propia en R3 y DataTables desde R4. **Decide el responsable**; recomiendo R3.

---

## 2. Bibliotecas: versión, licencia, peso y dónde se instalan

### 2.1 Versiones propuestas

| Biblioteca | Versión exacta | Licencia | Archivos que se copian | Peso (bytes) | Integridad npm (`sha512`) | Entra en |
|---|---|---|---|---|---|---|
| DataTables | 3.1.6 | MIT | `js/dataTables.min.js` | 123 886 | `gHQ2TCiD…GJ1Dw==` | R3 |
| DataTables para Bootstrap 5 | 3.1.6 | MIT | `js/dataTables.bootstrap5.min.js`, `css/dataTables.bootstrap5.min.css` | 1 466 + 21 555 | `1ajIPTG2…NS9uw==` | R3 |
| Buttons | 4.1.4 | MIT | `js/dataTables.buttons.min.js` | 59 317 | `OvJ9q1u3…ug33kA==` | R3 |
| Buttons para Bootstrap 5 | 4.1.4 | MIT | `js/buttons.bootstrap5.min.js`, `css/buttons.bootstrap5.min.css` | 1 722 + 8 234 | `e0LDHUUK…YM9gQ==` | R3 |
| JSZip (Excel) | 3.10.2 | MIT o GPL-3.0-or-later; **se usa bajo MIT** | `dist/jszip.min.js` | 97 781 | `3l+rb15I…kpyTQ==` | R3 |
| pdfmake (PDF) | 0.3.15 | MIT | `build/pdfmake.min.js`, `build/vfs_fonts.js` | 880 700 + 854 773 | `szzvtHyD…uf3njg==` | R3 |
| SweetAlert2 | 11.26.26 | MIT | `dist/sweetalert2.min.js`, `dist/sweetalert2.min.css` | 48 488 + 30 251 | `ihhDJ/0w…N9ST6g==` | R2 |
| ApexCharts | 4.4.0 (ya versionada en la plantilla) | MIT | Ninguno nuevo; se añade su `LICENSE` | 574 008 + 14 458 (ya en el repositorio) | `JGsHeQEK…xzWw==` | R9 |
| jQuery | **No se instala** si se aprueba DAF-03 | MIT | (`dist/jquery.min.js`, 4.1.0) | (78 965) | (`tnN2N83S…EiF2fw==`) | — |

- **Peso nuevo:** 2 128 173 bytes (unos 2 MB), de los que 1 735 473 son de pdfmake y sus fuentes. Sin pdfmake serían 392 700 bytes. La plantilla ya ocupa unos 88 MB en el historial.
- Con cada biblioteca se copia también el archivo de licencia de su paquete (`License.txt` en DataTables y Buttons, `LICENSE.markdown` en JSZip, `LICENSE` en el resto).
- **Si el responsable mantiene jQuery:** DataTables 2.3.4 (integridad `fKuRlrBI…OpKzSw==`) con jQuery 4.1.0, y las versiones de Buttons y de las integraciones que correspondan a la 2.3. Se fijan en esa misma tarea.
- La integridad completa de cada paquete se anota en `ACTIVOS_NICEADMIN.md` al instalarlo. Aquí se abrevia.

### 2.2 Dónde y cómo

1. **Carpeta:** `backend/public/assets/vendor/{datatables,sweetalert2,jszip,pdfmake}/`, fuera de `NiceAdmin/`. Según `ACTIVOS_NICEADMIN.md` §6 (decisión 3), la plantilla no se mezcla con archivos ajenos a ella, y §5 permite servir desde `/assets/`. Tampoco van en `assets/js/`: las pruebas 10 y 17 de PT-12 recorren esa carpeta como código propio, y los comentarios con URL de las bibliotecas harían fallar la 17. ApexCharts se queda donde está.
2. **Obtención:** el tarball de npm se descarga con `curl`, se verifica su `sha512` contra el registro (como Bootstrap en PT-12) y se extraen solo los archivos de la tabla y su licencia. Sin `package.json`, sin `node_modules` y sin Node en el proyecto (`ESPECIFICACION_FUNCIONAL.md` §3).
3. **Registro:** nueva sección «Bibliotecas fuera de la plantilla» en `ACTIVOS_NICEADMIN.md`, con la versión, la licencia, la integridad npm, el SHA-256 de cada archivo y las vistas que lo cargan. ApexCharts pasa de «inactivo» (§4) a «usado en R9».
4. **Carga:** `layouts/app.blade.php` gana `@stack('estilos')` y `@stack('scripts')`, y cada vista empuja solo lo que usa. Orden: Bootstrap, `tinymce-vacio.js`, `main.js`, `api.js`, `sesion.js`, bibliotecas, scripts propios comunes y, al final, el script de la página. pdfmake y sus fuentes (1,7 MB) se cargan solo en las páginas con tablas descargables en PDF.
5. **Prueba `BibliotecasLocalesTest`** (como `BootstrapLocalTest`): por cada archivo, que exista, su SHA-256 y su licencia, y que la versión aparezca en la cabecera. Además, que `sweetalert2.min.js` no contenga `location`.

### 2.3 Compatibilidades que se comprueban al instalar (en navegador)

- **pdfmake 0.3.15 con Buttons 4.1.4.** Buttons llama a `pdfMake.createPdf(doc).download(nombre)`, y el `vfs_fonts.js` de la 0.3.15 se registra solo con `addVirtualFileSystem` cuando `pdfMake` ya está cargado. Por lectura parecen compatibles, pero no lo he ejecutado. Si falla, se usa pdfmake 0.2.x, con la versión fijada en esa tarea.
- **Integración Bootstrap 5 sin jQuery.** El código de DataTables 3.1.6 dice «This is currently for BS5 only. BS3/4 attach to jQuery». Indica que la integración BS5 no necesita jQuery, pero se comprueba.
- **`buttons.html5.min.js`** de Buttons 4 es un envoltorio de 562 bytes sin código: la exportación HTML5 vive en `dataTables.buttons.min.js`. No se copia.
- **Fuente de `vfs_fonts.js`:** incrusta Roboto. La licencia de la fuente se verifica y se anota al instalar.

---

## 3. Patrones comunes (cada uno se construye con su primer consumidor)

| Pieza | Rebanada | Qué hace |
|---|---|---|
| `assets/js/api.js` | Ya existe (R1) | Se reutiliza sin cambios: `fetch` con sesión, CSRF y errores normalizados. |
| `assets/js/avisos.js` | R2 | Envoltorio de SweetAlert2: `confirmar`, `exito`, `advertencias` (las `advertencias[]` de un 200 o 201) y `error`. `error` aplica CA-GEN-003: 401 lleva a `/ingreso?expirada=1`; 403 (`sin_permiso`, `no_es_registrante`) avisa que no hay autorización; 404 muestra «no disponible» sin más detalle; 409 y 422 sin campo muestran el `mensaje` de la API; 429 usa `Retry-After`; 500, 503 y la falta de red muestran el aviso genérico. No se muestra éxito antes de la respuesta del servidor (CA-FOR-003). |
| `assets/js/formularios.js` | R2 | Coloca cada `errores[].campo` de un 422 junto a su control, también los campos anidados (`registros.N.jugador_id`, `metricas.N.valor`). Deshabilita el botón mientras dura la solicitud (CA-GEN-002) y conserva los valores, salvo las contraseñas. `ingreso.js` no se toca. |
| `assets/js/tabla.js` | R3 | Adaptador entre la API y DataTables (§3.1). |
| `assets/js/graficas.js` | R9 | Convierte una `SerieDeGrafico` en una serie de ApexCharts (§4, R9). |
| Menú y acciones por rol | R2 en adelante | Cada ítem del menú, pestaña o botón lleva `data-roles="docente entrenador"` y arranca `hidden`. `sesion.js` los muestra cuando llega `GET /sesion`, con la unión de los roles (CA-LAY-002). Ocultar no autoriza: la API decide (CA-GEN-004). |
| Rutas web | Todas | Solo `auth:web`, y `whereUuid` donde llevan un identificador. Las páginas no comprueban el rol: el HTML no trae datos privados, todo llega por `fetch` y un 403 o 404 de la API se muestra como tal. |
| Textos | Todas | En `backend/lang/es/interfaz.php`, agrupados por área (D5), incluidos los de DataTables, SweetAlert2 y ApexCharts. Los scripts los leen de `data-textos`. No se cargan archivos de idioma remotos. |
| Datos | Todas | Los decimales se muestran como cadena, tal como llegan («8.50»), y nada se recalcula en el front. Las fechas `AAAA-MM-DD` se muestran tal cual. El formato de las fechas con hora se fija en la ficha de R3. |

**Clases prohibidas en las vistas, por `main.js` de la plantilla (que no se modifica):** `.echart`, porque `main.js` llama a `echarts.getInstanceByDom` sin ECharts cargado y fallaría; `.quill-editor-default`, `.quill-editor-bubble` y `.quill-editor-full` (Quill); y `textarea.tinymce-editor`. `.needs-validation` activa la validación de Bootstrap de `main.js`, y se usa solo donde convenga con el manejo del 422. Una prueba revisa que ninguna vista use esas clases.

### 3.1 Tablas (`tabla.js`) y descarga (F4)

- **Rutas paginadas (casi todas):** modo servidor de DataTables. `start` y `length` se traducen a `pagina = start / length + 1` y `por_pagina = length`, con un menú de 15 (el valor por omisión de la API), 25, 50 y 100 (CA-TAB-002). Solo se ordenan las columnas cuyo campo está en el `enum` de `orden` de esa ruta. La búsqueda libre va apagada, porque ninguna ruta la ofrece (CA-TAB-003). Los filtros son controles del formulario que se envían como `filtro[campo]`. `meta.total` alimenta los totales.
- **Rutas sin paginar** (`/metricas`, `/tipos-prueba`, `/jugadores/{id}/alertas`, la vista de un periodo de pruebas físicas y `por_jugador` de la asistencia del equipo): modo cliente.
- **Estados:** cargando (indicador de DataTables), vacío (texto propio) y error (texto en la tabla más el aviso de `avisos.js`) (CA-TAB-001).
- **Descarga:** botones `excelHtml5` y `pdfHtml5`. Se exportan las columnas visibles, salvo las de acciones. El título es el de la pantalla con los filtros aplicados, y `messageTop` lleva página, total de registros (de `meta`) y la fecha en que se generó. El archivo se llama `{pantalla}-{AAAA-MM-DD}`.
- **DAF-09 (ajustada por el responsable el 24/11/2026): cada tabla ofrece «Descargar esta página» y «Descargar todo»**, cada una en Excel y PDF. «Esta página» exporta las filas que la tabla ya muestra. «Todo» pide a la API las páginas una tras otra, con `por_pagina=100` y los mismos filtros y orden, hasta un tope de 1000 filas. Antes pide confirmación con SweetAlert2, que dice cuántas filas se descargarán y, si hay más de 1000, que se descargan las primeras 1000 del total. En las tablas sin paginar, «todo» exporta las filas ya cargadas, con la misma confirmación y el mismo tope.
- **DAF-16: sin descarga en las tablas de salud** (avisos, lesiones, estados y sus historiales) mientras el responsable no lo confirme. Son datos sensibles con visibilidad por registrante (RN-15, DD-58), y un archivo descargado sale de ese control. El resto de las tablas descargables se lista en cada rebanada.

---

## 4. Rebanadas

Nombres de las pruebas: «FRONT-RN k: …». Commits: «FRONT-RN: …». Las rutas web nuevas van a `backend/routes/web.php` y se registran en `docs/trazabilidad.md`, sección «Rutas web». Las enmiendas a pruebas existentes que trae cada rebanada están en §6.

### R2. Cambiar contraseña

| Aspecto | Contenido |
|---|---|
| Perfil | Docente y entrenador |
| Pantallas | `/contrasena`, enlazada desde el menú de usuario de la cabecera |
| Endpoints | `PUT /contrasena` (204; 401; 422 `validacion_fallida`, `contrasena_actual_incorrecta` y `contrasena_muy_corta` en `contrasena_nueva`) |
| DataTables y descarga | Ninguna |
| Gráficas | Ninguna |
| SweetAlert2 | Confirmación antes de enviar: «se cerrarán sus demás sesiones; esta sigue abierta» (HU-27). Aviso de éxito después del 204. |
| Criterios | CA-FOR-001 (solo `contrasena_actual` y `contrasena_nueva`), CA-FOR-002, CA-FOR-003, CA-GEN-002 a 004, CA-LAY-001 y 002 |
| Pruebas Pest | Sin sesión redirige a `/ingreso`; con sesión, 200 con exactamente esos dos campos, de tipo `password`; textos de `lang/es`; la cabecera enlaza a `/contrasena`; archivos, SHA-256, licencia y versión de SweetAlert2, sin `location`; la página carga SweetAlert2 y la del panel no |
| Navegador | Cada 422 debajo de su campo; doble clic, un solo `PUT`; 204 con aviso; una segunda sesión abierta recibe 401 y va a `/ingreso?expirada=1` |
| Archivos | `routes/web.php`; `app/Http/Controllers/Web/CuentaController.php`; `resources/views/cuenta/contrasena.blade.php`, `parciales/cabecera.blade.php`, `layouts/app.blade.php` (pilas); `lang/es/interfaz.php`; `public/assets/js/{avisos,formularios}.js` y `paginas/contrasena.js`; `public/assets/vendor/sweetalert2/`; `tests/Feature/Web/{ContrasenaTest,BibliotecasLocalesTest}.php`; `docs/trazabilidad.md`; `frontend/docs/ACTIVOS_NICEADMIN.md` |

### R3. Jugadores, lectura

| Aspecto | Contenido |
|---|---|
| Perfil | Docente y entrenador; la pestaña de asistencia, solo entrenador |
| Pantallas | `/jugadores`: listado y búsqueda por matrícula. `/jugadores/{jugador}`: ficha con las pestañas Datos, Situación académica y Asistencia a entrenamientos. Cada pestaña pide sus datos al abrirse, por D9 |
| Endpoints | `GET /jugadores` (`orden`: `apellidos`, `-apellidos`, `nombres`, `-nombres`; `filtro[equipo_id]`); `GET /equipos` (solo el entrenador, para el filtro); `GET /jugadores/busqueda?matricula=` (devuelve nombres, apellidos y matrícula, sin identificador: se muestra sin enlace, RN-20); `GET /jugadores/{jugador}`; `GET /jugadores/{jugador}/situacion-academica` (resumen para el entrenador, más `materias` para el docente); `GET /jugadores/{jugador}/asistencia-entrenamientos` (el docente recibe 404, DA-20) |
| DataTables y descarga | **Jugadores:** servidor, columnas apellidos, nombres, matrícula y equipos (nombre, categoría y posición; solo entrenador, porque el docente recibe `equipos` vacío, RN-13), descarga sí. **Materias de la situación académica (docente):** cliente, descarga sí. La ficha y el resumen son listas de definición, sin tabla |
| Gráficas | Ninguna |
| SweetAlert2 | Ninguna confirmación (solo lectura); errores con `avisos.js` |
| Criterios | CA-TAB-001 a 004, CA-GEN-001, 003 (404 de la ficha), 005 y 006, CA-LAY-002 (primer ítem del menú con rol) |
| Pruebas Pest | Rutas y middleware; un identificador que no es UUID responde 404 sin trazas; el ítem «Jugadores» lleva `data-roles`; archivos de DataTables, Buttons, JSZip y pdfmake (SHA-256, licencia, versión); **prueba de contrato de vistas** (§5) sobre `Jugador`, `JugadorBuscado`, `SituacionAcademica` y `PorcentajeDeAsistencia` |
| Navegador | Total y páginas leídos de `meta`; orden; filtro por equipo, y uno ajeno devuelve la lista vacía; docente sin inscripciones, lista vacía; ficha ajena, «no disponible»; descarga Excel y PDF con las filas visibles; pantalla de 375 px |
| Archivos | `routes/web.php`; `Web/JugadoresController.php`; `views/jugadores/{listado,ficha}.blade.php`; `lang/es/interfaz.php`; `public/assets/js/tabla.js`, `paginas/{jugadores,jugador}.js`; `public/assets/vendor/{datatables,jszip,pdfmake}/`; `tests/Feature/Web/{JugadoresTest,ContratoDeVistasTest}.php` y `BibliotecasLocalesTest.php`; `docs/trazabilidad.md`; `ACTIVOS_NICEADMIN.md` |
| Decisión | D11: columnas y filtros de la fila «DataTables y descarga» |

### R4. Estatus, alertas y avisos de lesión (docente y entrenador)

| Aspecto | Contenido |
|---|---|
| Perfil | Docente y entrenador (grupo `rol:entrenador,docente` de `api.php`) |
| Pantallas | `/estatus`. En la ficha: pestañas Alertas, Disponibilidad y Avisos de lesión (listado y alta) |
| Endpoints | `GET /estatus-de-jugadores` (`filtro[equipo_id]` del entrenador, con `GET /equipos`; `filtro[materia_id]` del docente, con `GET /materias`; `filtro[tipo_alerta]`; sin `orden`); `GET /jugadores/{jugador}/alertas` (sin paginar); `GET /jugadores/{jugador}/estado-disponibilidad`; `GET` y `POST /jugadores/{jugador}/avisos-lesion` (`nota`, `fecha`, `reportado_por_jugador`) |
| Forma por rol (D12, DA-105) | Columnas comunes: jugador, estado oficial (estado, fecha estimada de regreso y aviso de «requiere actualización»), aviso pendiente y alertas, como insignias por tipo con su `causa`. El entrenador ve además el resumen académico (nivel de riesgo y promedio); el docente, sus materias. Con los dos roles se ven ambas columnas, y un campo omitido por la API se muestra vacío, no inventado |
| DataTables y descarga | **Estatus:** servidor, sin orden (la ruta no lo ofrece), descarga sí. **Avisos de lesión:** servidor, **sin descarga** (DAF-16). **Alertas:** lista breve, sin DataTables |
| Gráficas | Ninguna |
| SweetAlert2 | El alta de un aviso no es destructiva: aviso de éxito, y el 409 `jugador_en_baja` se muestra como conflicto |
| Criterios | CA-TAB-001 a 004, CA-FOR-001 a 003, CA-GEN-003, CA-LAY-002 |
| Pruebas Pest | Ruta y menú; contrato de vistas sobre `FilaDeEstatus`, `Alerta`, `EstadoDeDisponibilidad` y `AvisoDeLesion`; los campos del formulario de aviso son los del cuerpo de `POST`; textos de los tres tipos de alerta en `lang/es` |
| Navegador | Forma del docente, del entrenador y de ambos; filtro ajeno da lista vacía, no error (P-05); 422 `filtro_invalido`; una `nota` nula (no visible para ese usuario, DD-58) se muestra como «no visible», no como «sin nota» (se confirma en la ficha) |
| Archivos | `routes/web.php`; `Web/EstatusController.php`; `views/estatus/listado.blade.php` y pestañas de `views/jugadores/ficha.blade.php`; `lang/es/interfaz.php`; `public/assets/js/paginas/{estatus,jugador}.js`; `tests/Feature/Web/EstatusTest.php`; `docs/trazabilidad.md` |
| Nota | `ESPECIFICACION_FUNCIONAL.md` §6 dice «no asumir» para salud con el docente. `api.php` le permite crear y ver avisos y ver el estado, y eso es lo que se habilita: es una verificación, no una suposición (REGLAS §3) |

### R5. Académico (docente), en dos partes

**R5a. Periodos, asignaturas y materias**

| Aspecto | Contenido |
|---|---|
| Perfil | Docente |
| Pantallas | `/academico/periodos`, `/academico/asignaturas`, `/academico/materias` y `/academico/materias/{materia}` (datos y criterio) |
| Endpoints | `GET`, `POST /periodos-escolares` y `DELETE /periodos-escolares/{id}`; `GET`, `POST /asignaturas` y `DELETE /asignaturas/{id}`; `GET /materias` (`filtro[periodo_id]`), `POST /materias`, `GET /materias/{materia}` y `PUT /materias/{materia}/criterio` |
| DataTables y descarga | Periodos (servidor, `orden` por `fecha_inicio`, descarga sí); Asignaturas (servidor, `orden` por `clave`, descarga sí); Materias (servidor, filtro de periodo, descarga sí) |
| SweetAlert2 | **Eliminar periodo o asignatura:** borrado lógico, sin ruta para restaurarlo. **Definir criterio:** cierra el criterio vigente y recalcula los resúmenes de los inscritos |
| Criterios | CA-TAB-001 a 004, CA-FOR-001 a 003 (409 de duplicados; 422 `rango_de_fechas_invalido` y `calificacion_fuera_de_rango`) |
| Pruebas | Rutas, menú «Académico» con `data-roles="docente"`, contrato de vistas (`PeriodoEscolar`, `Asignatura`, `Materia`, `Criterio`) |
| Archivos | `routes/web.php`; `Web/AcademicoController.php`; `views/academico/*.blade.php`; `lang/es/interfaz.php`; `public/assets/js/paginas/academico-*.js`; `tests/Feature/Web/AcademicoTest.php`; `docs/trazabilidad.md` |

**R5b. Inscripciones, calificaciones, asistencia de clase e historial**

| Aspecto | Contenido |
|---|---|
| Pantallas | En `/academico/materias/{materia}`: alumnos inscritos e inscripción por matrícula; un panel lateral por inscripción con sus calificaciones y asistencias. No lleva ruta propia: no existe `GET /inscripciones/{id}`. En la ficha del jugador: pestaña Historial académico (docente) |
| Endpoints | `GET`, `POST /materias/{materia}/inscripciones`; `GET`, `POST /inscripciones/{inscripcion}/calificaciones` y `PATCH /calificaciones/{calificacion}`; `GET`, `POST /inscripciones/{inscripcion}/asistencias` y `PATCH /asistencias-clase/{asistencia}`; `GET /jugadores/{jugador}/historial/calificaciones` y `/historial/asistencias-clase` (H7, HU-26) |
| DataTables y descarga | Inscripciones, Calificaciones, Asistencias e Historiales: servidor, descarga sí. En el historial, `datos_anteriores` y `datos_nuevos` se muestran como texto «campo: antes → después» |
| SweetAlert2 | **Corregir calificación** y **corregir asistencia**: muestran el valor anterior y el nuevo, y avisan que la corrección queda en el historial |
| Criterios | CA-TAB-001 a 004, CA-FOR-001 a 003 (409 `calificacion_duplicada`, `asistencia_duplicada`, `jugador_ya_inscrito`, `jugador_en_baja`; 404 `jugador_no_registrado`) |
| Archivos | `views/academico/materia.blade.php`, pestaña de la ficha, `paginas/academico-materia.js`, `lang`, `tests/Feature/Web/AcademicoTest.php` |
| Riesgo | El historial es de H7, que sigue abierto: antes de construir se vuelve a fusionar `main` y se revisa el cambio de `openapi.yaml` |

### R6. Plantel (entrenador)

| Aspecto | Contenido |
|---|---|
| Perfil | Entrenador |
| Pantallas | `/plantel/categorias`, `/plantel/equipos` y `/plantel/equipos/{equipo}` (jugadores del equipo, alta de jugador, asignación por matrícula y cambio de posición). En la ficha: editar datos y registrar la baja |
| Endpoints | `GET`, `POST /categorias` y `DELETE /categorias/{id}`; `GET`, `POST /equipos`; `GET /jugadores?filtro[equipo_id]`; `POST /jugadores` (`posicion`: `portero`, `defensa`, `medio`, `delantero`); `POST /equipos/{equipo}/jugadores`; `PATCH /equipos/{equipo}/jugadores/{jugador}`; `PATCH /jugadores/{jugador}`; `POST /jugadores/{jugador}/baja` |
| DataTables y descarga | Categorías, Equipos y Jugadores del equipo: servidor, `orden` donde la ruta lo admite, descarga sí |
| SweetAlert2 | **Baja del jugador:** confirmación de peligro con el motivo y la fecha a la vista. Es irreversible: no hay ruta para revertirla, y el jugador sale de los listados de todos sus entrenadores (DD-30). **Eliminar categoría:** borrado lógico. El cambio de posición queda en el historial y no lleva confirmación |
| Criterios | CA-FOR-001 a 003 (409 `matricula_duplicada`, `jugador_ya_en_equipo`, `jugador_ya_en_baja`; 422 `motivo_obligatorio`, `posicion_invalida`), CA-TAB-001 a 004 |
| Pruebas | Rutas, menú «Plantel» con `data-roles="entrenador"`, contrato de vistas (`Categoria`, `Equipo`, `Jugador`, `Baja`); el selector de posición usa exactamente el `enum` del contrato |
| Archivos | `routes/web.php`; `Web/PlantelController.php`; `views/plantel/*.blade.php`, modales de la ficha; `paginas/plantel-*.js`; `lang`; `tests/Feature/Web/PlantelTest.php`; `docs/trazabilidad.md` |
| Limitación | No existe `GET /equipos/{id}`: la cabecera de la página del equipo se toma de `GET /equipos` (PB-04) |

### R7. Competencia (entrenador), en tres partes

**R7a. Lugares, rivales, temporadas y torneos**

| Aspecto | Contenido |
|---|---|
| Pantallas | `/competencia/lugares`, `/competencia/rivales`, `/competencia/temporadas` y `/competencia/torneos` |
| Endpoints | `GET`, `POST` y `DELETE` de cada catálogo; el torneo pide `temporada_id`, que sale de `GET /temporadas` |
| DataTables y descarga | Las cuatro: servidor, `orden` (`nombre` o `fecha_inicio`), descarga sí |
| SweetAlert2 | **Eliminar** cada elemento: borrado lógico, sin ruta para restaurarlo |
| Criterios | CA-TAB-001 a 004, CA-FOR-001 a 003 (409 `temporada_solapada` y duplicados; 422 `temporada_inexistente`) |
| Archivos | `routes/web.php`; `Web/CompetenciaController.php`; `views/competencia/*.blade.php`; `paginas/competencia-*.js`; `lang`; `tests/Feature/Web/CompetenciaTest.php` |

**R7b. Entrenamientos y su asistencia**

| Aspecto | Contenido |
|---|---|
| Pantallas | En la página del equipo: pestañas Entrenamientos (filtros desde y hasta, `orden` por `inicia_en`) y Asistencia del equipo. `/entrenamientos/{entrenamiento}`: detalle, modificación, cambio de estado y captura y corrección de la asistencia |
| Endpoints | `GET`, `POST /equipos/{equipo}/entrenamientos`; `GET`, `PATCH /entrenamientos/{entrenamiento}`; `GET`, `POST /entrenamientos/{entrenamiento}/asistencias`; `PATCH /asistencias-entrenamiento/{asistencia}`; `GET /equipos/{equipo}/asistencia-entrenamientos`; `GET /lugares` |
| DataTables y descarga | Entrenamientos y Asistencias del entrenamiento: servidor, descarga sí. Asistencia por jugador: cliente, descarga sí |
| SweetAlert2 | **Marcar realizado o cancelado:** irreversible, solo desde programado. **Corregir asistencia.** Las advertencias `empalme_de_lugar` y `jugador_lesionado` se muestran después de guardar, porque la API guarda igual |
| Limitaciones | El responsable solo puede ser el propio usuario: no hay listado de entrenadores (PB-03). El nombre del jugador sale de `jugadores_del_equipo` del detalle; un jugador ya en baja (DA-19) se muestra «no disponible». Los nombres de lugares eliminados no se pueden resolver (PB-02) |

**R7c. Partidos, convocatoria, alineación y resultado**

| Aspecto | Contenido |
|---|---|
| Pantallas | En la página del equipo: pestañas Partidos (filtros de temporada y de estado, `orden`) y Para convocar. `/partidos/{partido}`: datos, modificación, cancelación, convocatoria, alineación y resultado. En la ficha: pestaña Vista para convocar |
| Endpoints | `GET`, `POST /equipos/{equipo}/partidos`; `GET`, `PATCH /partidos/{partido}`; `GET`, `POST /partidos/{partido}/convocatoria`; `DELETE /convocatorias/{convocatoria}`; `GET /partidos/{partido}/convocatoria/vista`; `PUT /partidos/{partido}/alineacion`; `PUT /partidos/{partido}/resultado`; `GET /equipos/{equipo}/jugadores/para-convocar`; `GET /jugadores/{jugador}/vista-para-convocar`; catálogos de lugares, rivales, torneos y temporadas |
| DataTables y descarga | Partidos, Para convocar y Vista de la convocatoria: servidor, descarga sí. Convocatoria: servidor; los nombres se toman de la vista, porque la convocatoria no trae datos del jugador (DA-29); descarga sí |
| SweetAlert2 | **Cancelar partido** (irreversible). **Capturar resultado** (pasa a jugado y no se corrige). **Quitar de la convocatoria** (su alineación desaparece). **Guardar alineación** (reemplaza la completa). Advertencia `jugador_lesionado` al convocar |
| Bloqueo | **La alineación pide `posicion_id` y ninguna ruta publica el catálogo de posiciones (PB-01).** Sin PB-01, R7c se entrega sin alineación |

### R8. Rendimiento y salud (entrenador), en tres partes

**R8a. Rendimiento, plantillas, umbral y desempeño**

| Aspecto | Contenido |
|---|---|
| Pantallas | `/rendimiento/pendientes` y `/rendimiento/plantillas`. En `/partidos/{partido}`: pestaña Rendimientos (captura y corrección). En la página del equipo, por jugador: umbral y desempeño |
| Endpoints | `GET /partidos/pendientes-de-rendimiento`; `GET`, `POST /partidos/{partido}/rendimientos`; `PATCH /rendimientos/{rendimiento}`; `GET /metricas`; `GET`, `POST /plantillas-evaluacion`; `GET`, `PUT /equipos/{equipo}/jugadores/{jugador}/umbral`; `GET /equipos/{equipo}/jugadores/{jugador}/desempeno` |
| DataTables y descarga | Pendientes, Rendimientos del partido y Plantillas: servidor, descarga sí. Métricas: cliente, sin descarga (es un catálogo de consulta) |
| SweetAlert2 | **Corregir rendimiento.** **Fijar umbral** (cierra el vigente) |
| Bloqueo | **PB-01 también bloquea** crear plantillas (`posicion_id`) y mostrar en la captura solo las métricas de la posición del jugador: `MetricaDelCatalogo.posiciones` trae `posicion_id`, y el jugador solo el código de su posición. Sin PB-01, el formulario tendría que ofrecer todas las métricas y esperar los 422 `metrica_no_aplica_a_posicion`. Recomiendo esperar |

**R8b. Salud**

| Aspecto | Contenido |
|---|---|
| Pantallas | En la ficha: acciones sobre los avisos, sobre el estado, lesiones (listado, detalle y corrección) e historiales |
| Endpoints | `POST /avisos-lesion/{aviso}/descarte`; `POST /avisos-lesion/{aviso}/confirmacion` (con `apto` se prohíben `tipo`, `zona`, `causa`, `fecha_estimada_regreso` y `nota`); `PATCH /jugadores/{jugador}/estado-disponibilidad`; `POST /jugadores/{jugador}/estado-disponibilidad/alta`; `GET /jugadores/{jugador}/lesiones`; `GET`, `PATCH /lesiones/{lesion}`; `GET /jugadores/{jugador}/historial/{avisos-lesion,lesiones,estados-disponibilidad}` (H7) |
| DataTables y descarga | Lesiones e historiales: servidor, **sin descarga** (DAF-16) |
| SweetAlert2 | **Descartar aviso. Confirmar aviso** (cambia el estado oficial que ven todos). **Cambiar estado. Dar de alta. Corregir lesión.** El 403 `no_es_registrante` tiene su propio mensaje |

**R8c. Condición física**

| Aspecto | Contenido |
|---|---|
| Pantallas | En la ficha: mediciones, pruebas físicas (listado y vista de un periodo) e historiales |
| Endpoints | `GET`, `POST /jugadores/{jugador}/mediciones`; `GET /tipos-prueba`; `GET`, `POST /jugadores/{jugador}/pruebas-fisicas` (`filtro[periodo]` `AAAA-MM`, `filtro[tipo_prueba_id]`); `GET /jugadores/{jugador}/historial/{mediciones,pruebas-fisicas}` (H7). El `equipo_id` sale de los equipos del jugador que ve el entrenador |
| DataTables y descarga | Mediciones, Pruebas e Historiales: servidor, descarga sí. Vista del periodo: cliente, descarga sí |
| SweetAlert2 | **Corrección con confirmación** (`docs/convenciones-api.md` 4.1). El primer `POST` va sin `es_correccion`; si responde 409 `medicion_ya_registrada` o `prueba_ya_registrada`, SweetAlert2 ofrece corregir, pide el motivo y reenvía con `es_correccion`. El botón se deshabilita tras el primer clic (DA-64) |

Las tres partes de R8: CA-TAB-001 a 004 y CA-FOR-001 a 003; pruebas Pest de las rutas `/rendimiento/*`, del menú y del contrato de vistas (`Rendimiento`, `MetricaDelCatalogo`, `PlantillaEvaluacion`, `Umbral`, `Desempeno`, `Lesion`, `Medicion`, `PruebaFisica`, `TipoDePrueba` y las tres `EntradaDeHistorial…`). Archivos: `Web/RendimientoController.php`, `views/rendimiento/*.blade.php`, pestañas de la ficha y del partido, `paginas/*.js`, `lang`, `tests/Feature/Web/RendimientoTest.php` y `docs/trazabilidad.md`.

### R9. Gráficos

| Aspecto | Contenido |
|---|---|
| Perfil | Docente y entrenador (jugador); entrenador (equipo) |
| Pantallas | Pestaña Gráficos en la ficha del jugador y en la página del equipo, esta con un selector de jugador (los del equipo, de `GET /jugadores?filtro[equipo_id]`) |
| Endpoints | `GET /jugadores/{jugador}/graficos`; `GET /equipos/{equipo}/graficos` con `filtro[jugador_id]` opcional |
| DataTables y descarga | Ninguna. La barra de descarga de ApexCharts va apagada: F4 habla de tablas |
| SweetAlert2 | Ninguna |

**Gráficas** (semántica de las series según `docs/hitos/f1-h6.md` §6.2):

| N.º | Gráfica | Serie del endpoint | Quién la ve | Tipo y ejes |
|---|---|---|---|---|
| 1 | Rendimiento por partido | `rendimiento_partidos` de `/jugadores/{id}/graficos` | Entrenador | Línea; eje X de fechas (`fecha` del partido); Y = calificación general (entero) |
| 2 | Asistencia a entrenamientos por mes | `asistencia_entrenamientos` | Entrenador | Columnas; X = mes `AAAA-MM`; Y = porcentaje |
| 3 | Peso, estatura e IMC | `mediciones.peso_kg`, `.estatura_cm` y `.imc` | Entrenador | Tres líneas pequeñas, una por unidad; X = mes |
| 4 | Promedio académico por periodo | `resumen_academico` | Entrenador | Columnas; X = nombre del periodo escolar; Y = promedio |
| 5 | Calificaciones por materia | `calificaciones[]` | Docente | Una línea por materia (clave y nombre de la asignatura); X = fecha de captura; Y = calificación |
| 6 | Asistencia a clase por materia | `asistencia_clases[]` | Docente | Una serie por materia; X = mes; Y = porcentaje |
| 7 | Rendimiento del equipo y del jugador | `equipo.rendimiento_partidos` y `jugador.rendimiento_partidos` de `/equipos/{id}/graficos` | Entrenador | Dos líneas en un eje X de fechas: promedio del equipo y, con `filtro[jugador_id]`, el jugador (HU-25 c2) |
| 8 | Asistencia del equipo y del jugador | `equipo.asistencia_entrenamientos` y `jugador.asistencia_entrenamientos` | Entrenador | Columnas agrupadas; X = mes |

- Cada gráfica muestra `actualizado_en` y, si corresponde, la insignia «posiblemente desactualizado» (RNF-04). Una serie vacía muestra un estado vacío con texto, nunca un lienzo vacío ni puntos inventados (CA-PAN-001).
- Un usuario con los dos roles ve las seis del jugador; un campo omitido por la API no se dibuja.
- **Ningún cálculo en el front:** el valor (cadena con decimales) se convierte a número solo para dibujarlo, y la etiqueta muestra la cadena original. Locale español desde `apexcharts/locales/es.json` de la plantilla.
- **Criterios:** CA-GEN-001, CA-GEN-003 (404 del jugador o del equipo), CA-GEN-006 (sin errores de consola) y CA-PAN-001.
- **Pruebas Pest:** `LICENSE` de ApexCharts (MIT); SHA-256 de `apexcharts.min.js` y `apexcharts.css` iguales a los de la 4.4.0; ApexCharts se carga solo en la ficha y en la página del equipo; ninguna vista usa `.echart`; contrato de vistas sobre `GraficosDelJugador` y `GraficosDelEquipo`.
- **Navegador:** docente, entrenador y los dos roles; una serie vacía; `posiblemente_desactualizado`; jugador fuera del equipo, 404.
- **Archivos:** pestañas en `views/jugadores/ficha.blade.php` y `views/plantel/equipo.blade.php`; `public/assets/js/graficas.js`; `public/NiceAdmin/assets/vendor/apexcharts/LICENSE` (nuevo); `lang`; `tests/Feature/Web/GraficosTest.php`; `ACTIVOS_NICEADMIN.md`.

---

## 5. Pruebas y evidencia, comunes a todas las rebanadas

1. **Pest en `tests/Feature/Web/`**, que fallan antes de implementar y pasan después, como en PT-12. Las pruebas 10 (sin `localStorage` ni `sessionStorage`) y 17 (sin recursos externos) de PT-12 ya recorren cualquier vista o script nuevo.
2. **Prueba de contrato de vistas (`ContratoDeVistasTest`, nueva en R3).** Cada columna de tabla (`data-campo`) y cada control de formulario (`name`) declara su endpoint, y la prueba comprueba con `cebe/openapi`, que ya es dependencia de desarrollo, que el campo existe en el esquema de respuesta o en el cuerpo de esa operación de `openapi.yaml`. Automatiza CA-GEN-001, CA-TAB-001 y CA-FOR-001, y falla si el contrato cambia debajo de una vista.
3. **Comprobación en navegador** de cada rebanada con Playwright, que es herramienta del agente y no dependencia del proyecto, contra `http://127.0.0.1:8001` (entorno del front). Se archivan las capturas y el registro de consola y de red en `docs/reportes/capturas/front-rN/`, para no repetir la falta de capturas de PT-12 (D8). El JavaScript no tiene pruebas automáticas, porque no hay Node en el proyecto; cada comportamiento se reporta como «Verificado en navegador» o «Implementado, no verificado» (REGLAS §9).
4. **`make verificar`** en el entorno del front (proyecto `gestordep-front`), con el log en `docs/reportes/logs/front-rN/`.
5. **Datos para el navegador:** se crean por la API o por la propia interfaz en el entorno del front, con los usuarios de `UsuariosDemoSeeder`. Nunca se presentan como reales. `make fresh` (o su equivalente con ese proyecto) en ese entorno, cuando haga falta (autorizado).

## 6. Enmiendas a pruebas existentes que pedirá cada rebanada

Ninguna se debilita: las listas siguen siendo exactas y solo crecen con lo aprobado en este plan.

| Prueba | Cambio |
|---|---|
| `tests/Feature/EntornoDePruebasTest.php` (lista exacta de rutas fuera de `/api/v1`) | Agrega las rutas web de cada rebanada, y solo esas |
| `PaginasWebTest` PT-12 15 (rutas web exactas con su middleware) | Ídem |
| `PanelTest` PT-12 8 («el menú solo contiene Panel») | Desde R3: el menú contiene exactamente los ítems aprobados, cada uno con sus roles |
| `PaginasWebTest` PT-12 19 (orden de los scripts) | Desde R2: el orden de §2.2, con las pilas |

## 7. Orden y partición (D14)

**Orden recomendado:** R2, R3, R6, R4, R5a, R5b, R7a, R7b, R7c, R8a, R8b, R8c y R9.

- R2 es pequeña e introduce SweetAlert2, `avisos.js` y `formularios.js`.
- R3 fija el patrón de tablas y la ficha del jugador.
- R6 va antes que R4 porque completa la ficha (edición y baja) y crea los equipos y jugadores que usan R4, R7 y R8.
- R9 va al final porque dibuja lo que capturan R5, R7 y R8.

**Partición:**

- R5 en dos partes y R7 y R8 en tres, como se ve en §4.
- R6 queda entera; si su ficha pasa de diez operaciones, se parte en categorías y equipos, y alta, edición, baja y asignación.

**Esperan a otra cosa:**

- Los historiales de R5b, R8b y R8c usan rutas de H7, que sigue abierto.
- La alineación de R7c y las plantillas y métricas de R8a esperan PB-01.

## 8. Peticiones para el backend (no se hacen desde el front)

| N.º | Petición | Qué desbloquea | Sin ella |
|---|---|---|---|
| **PB-01** | Catálogo de posiciones (`id`, `codigo`, `nombre`), o `posicion_id` en `Jugador.equipos[]` | Alineación (R7c); plantillas propias y filtrar las métricas por posición en la captura de rendimiento (R8a); nombres de posición en rendimientos, convocatorias, métricas y plantillas | R7c sin alineación; R8a sin plantillas propias y con una captura que depende de los 422 |
| **PB-02** | Nombres de lugar, rival, torneo y responsable dentro de `Partido` y `Entrenamiento` (como `Equipo` incrusta su categoría), o lectura por identificador de esos catálogos | Mostrar partidos y entrenamientos que usan un elemento eliminado, porque los listados excluyen los eliminados | Se resuelven con los catálogos ya cargados, y lo eliminado se muestra «no disponible» |
| **PB-03** | Entrenadores asignados a un equipo | Elegir el `responsable_id` de un entrenamiento | El responsable es siempre el propio usuario |
| **PB-04** | `GET /equipos/{equipo}` | Cabecera de la página del equipo | Se busca en `GET /equipos` (hasta 100 por página) |
| **PB-05** | Documentar el 419 en OpenAPI (deuda H-05 de PT-12) | Contrato completo de los errores de sesión | El front lo trata como error de sesión, igual que en R1 |

Nota: la convocatoria va sin datos del jugador por decisión (DA-29); no se pide cambiarla.

## 9. Decisiones del front (DAF)

Numeración propia del front. Estados al 24/11/2026, tras la aprobación de §0.

| N.º | Decisión | Estado |
|---|---|---|
| DAF-01 | Entorno aparte: carpeta `..\GestorDep-front`, proyecto `gestordep-front` (también en `COMPOSE_PROJECT_NAME` del `.env` local, para que `make` no toque el del backend), imagen `gestordep-front-app` (la del backend, `gestordep-app`, no se vuelve a etiquetar), web en `127.0.0.1:8001` y base sin publicar; `worker` y `scheduler` apagados por orden del responsable. `docker-compose.override.yml` y los `.env` son locales y no se versionan | Tomada |
| DAF-02 | La fusión de `main` en la rama del front fue por avance rápido: la rama no tenía commits propios (0 por delante, 65 por detrás). No se forzó un commit de fusión | Tomada |
| DAF-03 | DataTables 3.1.6 sin jQuery (§1) | Aprobada |
| DAF-04 | ApexCharts 4.4.0 de la plantilla (MIT), sin actualizar a una versión con la «ApexCharts License»; se añade su `LICENSE` | Aprobada |
| DAF-05 | SweetAlert2 11.26.26 en archivos separados (`.min.js` y `.min.css`), no la variante `all`, que inyecta estilos al ejecutarse | Aprobada |
| DAF-06 | pdfmake 0.3.15, con 0.2.x como respaldo si la comprobación de §2.3 falla | Aprobada |
| DAF-07 | Bibliotecas en `backend/public/assets/vendor/`, con su licencia, integridad npm verificada y SHA-256 fijado en prueba, y cargadas solo en las vistas que las usan | Aprobada |
| DAF-08 | Cada biblioteca entra con su primer consumidor: SweetAlert2 en R2; DataTables, Buttons, JSZip y pdfmake en R3; ApexCharts en R9 | Aprobada |
| DAF-09 | Tablas paginadas en modo servidor; cada tabla ofrece «Descargar esta página» y «Descargar todo» (tope de 1000 filas, con confirmación que dice cuántas) | Aprobada con el ajuste de §0.4 |
| DAF-10 | Menú, pestañas y acciones por rol en el cliente (`data-roles` y `hidden`); rutas web solo con `auth:web` | Aprobada |
| DAF-11 | Textos de bibliotecas y gráficas en `lang/es/interfaz.php`, sin archivos de idioma remotos | Aprobada |
| DAF-12 | Clases prohibidas por `main.js` (§3), con una prueba que lo revisa | Aprobada |
| DAF-13 | Decimales y fechas tal como llegan; conversión a número solo para dibujar | Aprobada |
| DAF-14 | Orden y partición de §7 | Aprobada |
| DAF-15 | Evidencia: Pest, prueba de contrato de vistas y navegador con capturas archivadas (§5) | Aprobada |
| DAF-16 | Sin descarga en las tablas de salud hasta que el responsable lo confirme | Aprobada |
| DAF-17 | F4 es una excepción del responsable a `docs/alcance-fases.md` §5; la fila del registro la agrega el backend | Decisión del responsable |
| DAF-18 | Las fechas con hora (por ejemplo, `calculado_en`) se muestran en la zona de presentación de la aplicación (`config('app.zona_presentacion')`, que la vista pasa en `data-zona`), con `Intl.DateTimeFormat('es-MX')`. Las fechas `AAAA-MM-DD` y los decimales siguen tal como llegan (DAF-13) | Tomada en FRONT-R3; a confirmar |
| DAF-19 | Hoja propia `backend/public/assets/css/comun/tablas.css` y clase `text-nowrap` en las tablas de datos: en pantallas angostas solo la tabla se desplaza en horizontal (CA-GEN-006) | Tomada en FRONT-R3; a confirmar |
| DAF-20 | En las tablas sin paginar, «Descargar todo» exporta las filas ya cargadas con la misma confirmación y el mismo tope, sin pedir nada a la API; «Descargar esta página» exporta la página visible de DataTables | Tomada en FRONT-R3 (aplica §0.4) |

## 10. Qué necesita aprobación

1. DAF-03 (sin jQuery) o mantener jQuery con DataTables 2.3.x.
2. DataTables desde R3, contra lo que dice `ACTIVOS_NICEADMIN.md` §4.
3. Anotar F4 como excepción de `docs/alcance-fases.md` §5.
4. DAF-09 (descarga de la página) o la alternativa B (todas las páginas).
5. DAF-16 (sin descarga en salud).
6. Orden y partición (§7) y las enmiendas de §6.
7. Enviar PB-01 a PB-05 al backend; PB-01 bloquea parte de R7c y de R8a.
8. Guardar capturas en el repositorio (§5.3), unas 15 por rebanada.
9. `frontend/docs/` desactualizado: CA-AUT-002 y REGLAS §5 dicen que `GET /sesion` no existe; `ESPECIFICACION_FUNCIONAL.md` §6 habla de «desactivar jugadores» (la API ofrece baja) y §9 dice que sin origen responde 500 (hoy, 403 `origen_no_permitido`). Propongo corregirlos en R2, con su aprobación, porque esos documentos no se tocan sin ella.
10. D9 sigue abierta: `php -S` atiende una solicitud a la vez. Este plan la mitiga con pestañas que piden sus datos al abrirse, sin cambiar el servicio.
