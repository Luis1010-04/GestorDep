# Activos de NiceAdmin

Registro de la plantilla NiceAdmin y de los activos que el frontend puede usar. Decisiones del responsable del 18/11/2026. Este documento no cambia el código: describe qué se versiona, bajo qué licencia y qué se usa en cada rebanada.

## 1. Plantilla, versión y licencia

| Dato | Valor |
|---|---|
| Plantilla | NiceAdmin, de BootstrapMade.com (`https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/`) |
| Versión | Actualizada el 20/04/2024 con Bootstrap v5.3.3 (encabezado de `main.js` y de `style.css`) |
| Licencia | Gratuita de BootstrapMade (`https://bootstrapmade.com/license/`). El texto vinculante es el de esa dirección; lo que sigue es un resumen. |
| Bootstrap | v5.3.3, licencia MIT. Los archivos `bootstrap.min.css` y `bootstrap.bundle.min.js` de la plantilla tienen los mismos SHA-256 que los del paquete oficial de npm `bootstrap@5.3.3` (ver `docs/reportes/pt-12-reporte.md`). |
| Bootstrap Icons | v1.11.3, licencia MIT. |

**Obligaciones de la licencia gratuita que aplican a este proyecto**
- Se conserva el crédito «Designed by BootstrapMade», con su enlace a `https://bootstrapmade.com/`, en el pie de cada página que use la plantilla.
- Se conservan el archivo de licencia y los encabezados de cada archivo de la plantilla (`Template Name`, `Template URL`, `Updated`, `Author`, `License`).
- No se usa el formulario de contacto PHP/AJAX, que pertenece a la versión Pro (`php-email-form/validate.js` queda sin uso).
- No se usan los archivos SCSS (`assets/scss/`).
- El enlace del crédito es la única URL externa permitida en las vistas, porque es un hipervínculo y no un recurso que se carga.

## 2. Carpeta de destino

La plantilla completa se versiona en `backend/public/NiceAdmin/`, con la misma estructura que `frontend/NiceAdmin/` (`assets/css`, `assets/js`, `assets/img`, `assets/scss`, `assets/vendor` y las páginas de ejemplo). Se conservan sin cambios los encabezados de licencia de cada archivo. `main.js` se incluye sin modificar.

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

## 5. Regla de no usar CDN

- Las vistas y los scripts propios no cargan nada desde una red externa: ni CDN, ni Google Fonts, ni imágenes remotas. Todo recurso se sirve desde `/NiceAdmin/` o desde `/assets/`.
- Una prueba o una búsqueda con `grep` sobre `backend/resources/views/` y los scripts propios no debe encontrar `http://` ni `https://`, salvo el enlace del crédito de BootstrapMade.
- Las páginas de ejemplo de `backend/public/NiceAdmin/*.html` sí contienen enlaces a Google Fonts y a otros sitios, porque son de la plantilla. No forman parte de la aplicación y no se enlazan desde ella.

## 6. Puntos pendientes de decisión

1. **`main.js` falla sin TinyMCE.** En la línea 190, `tinymce.init({...})` se ejecuta sin comprobar que TinyMCE esté cargado. Sin TinyMCE, `main.js` lanza `ReferenceError: tinymce is not defined` en cada página. El código anterior a esa línea ya se ejecutó (menú lateral, búsqueda y tooltips), pero el posterior no: la validación de formularios `.needs-validation` y el redimensionado de ECharts. Por decisión del responsable `main.js` no se modifica. Opciones: cargar `tinymce.min.js` (8,8 MB por página, contradice «inactivo»); definir antes de `main.js` un objeto `tinymce` vacío con un `init` que no hace nada, en un script propio; o no usar `main.js`.
2. **Tipografías.** `style.css` pide «Open Sans», «Nunito» y «Poppins», y la plantilla las carga desde Google Fonts. Con la regla de no usar CDN, la interfaz usa la tipografía de reserva del sistema (`sans-serif`) hasta que se decida alojar esas fuentes en local.
3. **Tamaño.** La plantilla completa pesa unos 88 MB (46 MB solo de ECharts, 11 MB de Remix Icon, 8,8 MB de TinyMCE) y entraría al historial de git.
4. **Archivos de Bootstrap duplicados.** Los de `backend/public/assets/vendor/bootstrap/` (PT-12) quedarían duplicados con los de `NiceAdmin/assets/vendor/bootstrap/`, que son idénticos. La enmienda a PT-12 propone eliminar los primeros.
