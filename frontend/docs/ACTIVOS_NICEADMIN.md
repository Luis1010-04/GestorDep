# Activos de NiceAdmin

Registro de la plantilla NiceAdmin y de los activos que el frontend puede usar. Decisiones del responsable del 18/11/2026. Este documento no cambia el código: describe qué se versiona, bajo qué licencia y qué se usa en cada rebanada.

## 1. Plantilla, versión y licencia

| Dato | Valor |
|---|---|
| Plantilla | NiceAdmin, de BootstrapMade.com (`https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/`) |
| Versión | Actualizada el 20/04/2024 con Bootstrap v5.3.3 (encabezado de `main.js` y de `style.css`) |
| Licencia | Gratuita de BootstrapMade (`https://bootstrapmade.com/license/`). El texto vinculante es el de esa dirección. Uso en este proyecto: académico, no comercial. |
| Bootstrap | v5.3.3, licencia MIT. Los archivos `bootstrap.min.css` y `bootstrap.bundle.min.js` de la plantilla tienen los mismos SHA-256 que los del paquete oficial de npm `bootstrap@5.3.3` (ver `docs/reportes/pt-12-reporte.md`). |
| Bootstrap Icons | v1.11.3, licencia MIT. |

**Obligaciones de la licencia gratuita que aplican a este proyecto**
- Se conserva el crédito «Designed by BootstrapMade», con su enlace a `https://bootstrapmade.com/`, en el pie de cada página que use la plantilla.
- Se conservan el archivo de licencia y los encabezados de cada archivo de la plantilla (`Template Name`, `Template URL`, `Updated`, `Author`, `License`).
- No se usa el formulario de contacto PHP/AJAX, que pertenece a la versión Pro (`php-email-form/validate.js` queda sin uso).
- No se usan los archivos SCSS (`assets/scss/`).
- El enlace del crédito es la única URL externa permitida en las vistas, porque es un hipervínculo y no un recurso que se carga.

## 2. Carpeta de destino

La plantilla completa se versiona en `backend/public/NiceAdmin/`, con la misma estructura que `frontend/NiceAdmin/`, salvo la carpeta `forms/` que no se copia (`assets/css`, `assets/js`, `assets/img`, `assets/scss`, `assets/vendor` y las páginas de ejemplo). Se conservan sin cambios los encabezados de licencia de cada archivo. `main.js` se incluye sin modificar.

`frontend/NiceAdmin/` es la copia de origen y no se versiona. Las vistas Blade apuntan a `/NiceAdmin/...`, servido desde `backend/public/`.

## 3. Activos usados en la rebanada 1 (ingreso, layout, panel y salida)

| Activo | Ruta dentro de `NiceAdmin/` | Uso |
|---|---|---|
| Hoja de estilos de la plantilla | `assets/css/style.css` | Estructura de cabecera, menú lateral, panel y pie |
| Script de la plantilla | `assets/js/main.js` | Botón del menú lateral, barra de búsqueda y tooltips |
| Bootstrap CSS | `assets/vendor/bootstrap/css/bootstrap.min.css` | Base de estilos |
| Bootstrap JS | `assets/vendor/bootstrap/js/bootstrap.bundle.min.js` | Componentes (menús desplegables, tooltips) |
| Bootstrap Icons | `assets/vendor/bootstrap-icons/bootstrap-icons.css` y `fonts/bootstrap-icons.woff2` y `.woff` | Íconos de la cabecera y del menú |
| Logo y favicon | `assets/img/logo.png`, `favicon.png`, `apple-touch-icon.png` | Marca de la cabecera y de la pestaña |

La lista exacta de archivos por vista se fija en la enmienda a PT-12 y puede crecer en rebanadas posteriores, cada vez con tarea aprobada.

## 4. Activos inactivos

Se versionan por decisión del responsable, pero **ninguna vista los carga ni los referencia**:

- **Quill** (`assets/vendor/quill/`): editor de texto.
- **TinyMCE** (`assets/vendor/tinymce/`): editor de texto.
- **ECharts** (`assets/vendor/echarts/`), **ApexCharts** (`assets/vendor/apexcharts/`) y **Chart.js** (`assets/vendor/chart.js/`): gráficas. La librería de gráficas sigue diferida hasta la rebanada 9.
- Sin uso por ahora, y no pedidos como inactivos por el responsable: **simple-datatables** (sin biblioteca de tablas hasta la rebanada 4), **Boxicons**, **Remix Icon** y **php-email-form** (Pro, prohibido por la licencia gratuita).

## 4 bis. Bibliotecas fuera de la plantilla

Decisiones F2 y F3 del responsable (24/11/2026) y DAF-05, DAF-07 y DAF-08 del plan del front (`docs/reportes/front-plan-rebanadas.md`). Cada biblioteca se copia sin cambios de su paquete npm, después de verificar la integridad `sha512` del tarball contra el registro, en `backend/public/assets/vendor/{biblioteca}/`, junto con su licencia. Se carga solo en las vistas que la usan, con `@push('estilos')` y `@push('scripts')`. Sin CDN, sin jQuery y sin Node en el proyecto. La prueba `BibliotecasLocalesTest` fija el SHA-256 de cada archivo.

| Biblioteca | Versión | Licencia | Integridad npm | Archivos (SHA-256) | Entra en | Vistas que la cargan |
|---|---|---|---|---|---|---|
| SweetAlert2 | 11.26.26 | MIT | `sha512-ihhDJ/0wA+jpuakxnD5CTBzhos96NVSnt7iHoIS6IaqHFwkQNQ2Q4BJwXeHARfsCvhVH4o1GMm80LZRWN9ST6g==` | `sweetalert2.min.js` (`f4bfbd11…ccd3`), `sweetalert2.min.css` (`a1173e56…1743`), `LICENSE` (`879aa33f…c4b4`) | FRONT-R2 | `modulos/contrasena/editar` |

- **SweetAlert2:** se usan los archivos separados `.min.js` y `.min.css`, no la variante `all`, que inyecta estilos al ejecutarse (DAF-05). La 11.4.9 tenía código que dependía del idioma del navegador y del dominio; la 11.26.26 no contiene `location` (prueba FRONT-R2 9). Solo `backend/public/assets/js/comun/avisos.js` llama a `Swal` (prueba FRONT-R2 13).

## 5. Regla de no usar CDN

- Las vistas y los scripts propios no cargan nada desde una red externa: ni CDN, ni Google Fonts, ni imágenes remotas. Todo recurso se sirve desde `/NiceAdmin/` o desde `/assets/`.
- Una prueba o una búsqueda con `grep` sobre `backend/resources/views/` y los scripts propios no debe encontrar `http://` ni `https://`, salvo el enlace del crédito de BootstrapMade.
- Las páginas de ejemplo de `backend/public/NiceAdmin/*.html` sí contienen enlaces a Google Fonts y a otros sitios, porque son de la plantilla. No forman parte de la aplicación y no se enlazan desde ella.

## 6. Decisiones aplicadas y puntos abiertos

**Decisiones del responsable (18/11/2026)**
1. **`main.js` se incluye sin modificar y se evita su fallo con un script propio.** En la línea 190, `main.js` llama a `tinymce.init({...})` sin comprobar que TinyMCE esté cargado; sin TinyMCE lanza `ReferenceError: tinymce is not defined` en cada página y no ejecuta lo que sigue (validación `.needs-validation` y redimensionado de ECharts). El script propio `backend/public/assets/js/tinymce-vacio.js` define `window.tinymce = { init: function () {} }` y se carga antes de `main.js`. Una prueba (`PT-12 19`) fija ese orden. Si algún día se activa TinyMCE, este script se retira.
2. **La carpeta `forms/` no se copia.** Contiene `contact.php`, el formulario PHP de la versión Pro, que el servidor ejecutaría desde `public/`. Una prueba (`PT-12 18`) exige que no exista `forms/` ni ningún archivo `.php` en `public/NiceAdmin/`.
3. **Estructura.** La plantilla vive en `backend/public/NiceAdmin/`; los scripts propios, en `backend/public/assets/js/`. No se mezcla código propio con archivos de la plantilla. Desde FRONT-R2 (orden del responsable del 24/11/2026, 14:15), los scripts comunes van en `assets/js/comun/` y los de cada módulo en `assets/js/modulos/{modulo}/`; las bibliotecas ajenas a la plantilla, en `assets/vendor/` (§4 bis).
4. **`backend/public/NiceAdmin/LICENCIA.md`** enlaza la licencia oficial (`https://bootstrapmade.com/license/`), recuerda la obligación del pie y declara el uso: **académico, no comercial**.
5. **Bootstrap.** Se usan los archivos de la plantilla, idénticos a los del paquete oficial de npm. Se añadió `assets/vendor/bootstrap/LICENSE` (MIT) del paquete oficial, que la plantilla no trae.

**Puntos abiertos**
1. **Tipografías.** `style.css` pide «Open Sans», «Nunito» y «Poppins», y la plantilla las carga desde Google Fonts. Con la regla de no usar CDN, la interfaz usa la tipografía de reserva del sistema (`sans-serif`) hasta que se decida alojar esas fuentes en local.
2. **Páginas de ejemplo retiradas.** Las 34 páginas `*.html` de la plantilla no se versionan en `backend/public/NiceAdmin/`: con el servidor `php -S` de desarrollo eran públicas, y una dirección inexistente bajo `/NiceAdmin/` respondía con `index.html`. Se conservan `Readme.txt`, `LICENCIA.md` y `assets/`. Una prueba (`PT-12 22`) falla si existe cualquier `.html` o `.htm` dentro de `public/NiceAdmin/`.
3. **Tamaño.** La plantilla pesa unos 88 MB (46 MB solo de ECharts, 11 MB de Remix Icon, 8,8 MB de TinyMCE) y entra al historial de git.
