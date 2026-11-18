# Criterios de aceptación — GestorDep

**Ubicación prevista:** `frontend/docs/CRITERIOS_ACEPTACION.md`  
**Estado:** Base inicial; los criterios específicos de cada módulo deben ampliarse después de inspeccionar sus contratos reales.

---

## 1. Cómo utilizar este documento

Una funcionalidad se acepta únicamente cuando:

1. Cumple todos los criterios aplicables.
2. Los datos y operaciones se integran con la API real.
3. No introduce cambios no autorizados en el backend.
4. Se ejecutan y documentan las pruebas pertinentes.
5. No quedan errores conocidos ocultos bajo datos simulados.

Los criterios marcados como **pendientes de contrato** no autorizan a inventar campos ni endpoints. Primero debe consultarse OpenAPI y el código real.

## 2. Criterios globales del frontend

**CA-GEN-001 — Integración con backend**
- La pantalla obtiene datos desde los endpoints existentes.
- No utiliza arreglos, JSON local ni registros ficticios como sustituto de respuestas reales.
- Los nombres de campos, parámetros y estructuras coinciden con el contrato vigente.

**CA-GEN-002 — Manejo de carga**
- Las operaciones asíncronas muestran un estado de carga comprensible.
- Se evita enviar repetidamente la misma operación por clics sucesivos cuando pueda causar duplicados.
- La interfaz recupera su estado al finalizar la operación, tanto si tiene éxito como si falla.

**CA-GEN-003 — Manejo de errores**
- `401`: se informa que la sesión no está autenticada y se dirige al ingreso cuando corresponda.
- `403`: se informa que el usuario no tiene autorización.
- `404`: se presenta un estado de recurso no encontrado o no disponible, sin revelar información restringida.
- `409`: se muestra el conflicto de negocio devuelto por la API de forma comprensible.
- `422`: se muestran errores de validación junto a los campos correspondientes cuando sea posible.
- `429`: se informa del límite de solicitudes y se respeta `Retry-After` si está presente.
- `500`/`503`: se muestra un mensaje genérico; no se exponen trazas ni detalles internos.
- Los códigos adicionales deben tratarse conforme al comportamiento real del backend.

**CA-GEN-004 — Seguridad**
- Las solicitudes autenticadas usan las cookies de sesión correspondientes.
- Las solicitudes que modifican datos envían el token CSRF de acuerdo con Sanctum.
- No se guardan contraseñas ni tokens sensibles en `localStorage` o `sessionStorage`.
- La visibilidad del menú no se considera control de autorización.
- No se muestran datos de otro ámbito si la API los restringe.

**CA-GEN-005 — Navegación**
- El menú indica la sección activa.
- Los enlaces no implementados no deben mostrarse como funcionales.
- Las rutas protegidas no deben presentar contenido privado a usuarios sin sesión.
- Los estados de error, vacío y carga tienen una presentación consistente.

**CA-GEN-006 — Calidad**
- No hay errores JavaScript que impidan operar la página.
- No hay enlaces rotos en las pantallas entregadas.
- La interfaz funciona en los tamaños de pantalla contemplados por el diseño responsivo de Bootstrap.
- No se elimina el crédito requerido por la licencia gratuita de NiceAdmin/BootstrapMade.

## 3. Autenticación y sesión

**CA-AUT-001 — Inicio de sesión**
- La pantalla solicita únicamente los datos definidos por el contrato real.
- Se obtiene primero la cookie CSRF mediante `/sanctum/csrf-cookie`.
- Se envía el inicio de sesión a `POST /api/v1/sesion`.
- La solicitud utiliza el mecanismo de sesión/cookies configurado; no se inventa un flujo Bearer.
- Si las credenciales son válidas, la interfaz continúa a una ruta protegida.
- Si son inválidas, muestra el error devuelto sin revelar detalles innecesarios.
- Los campos muestran validaciones pertinentes.

**CA-AUT-002 — Usuario autenticado**
- La identidad y roles se obtienen de una fuente implementada y verificada.
- No asumir que existe `GET /api/v1/sesion`: la auditoría inicial no lo identificó.
- Si se aprueba y desarrolla ese endpoint, debe estar en OpenAPI y tener pruebas.
- Al recargar la página, la interfaz recupera el estado de sesión mediante el mecanismo aprobado, sin depender únicamente de datos volátiles del navegador.

**CA-AUT-003 — Cierre de sesión**
- Se invoca `DELETE /api/v1/sesion`.
- Tras el cierre correcto, se redirige al ingreso.
- Una sesión cerrada no permite continuar consultando pantallas protegidas.

## 4. Layout y navegación

**CA-LAY-001 — Estructura**
- Se utiliza un layout Blade compartido.
- Cabecera, menú lateral y pie se mantienen en parciales reutilizables.
- Los recursos CSS/JS se cargan desde rutas locales del proyecto.
- No se copia sin revisión el `main.js` de demostración de NiceAdmin si contiene inicializaciones de plugins no incluidos.

**CA-LAY-002 — Menú por roles**
- El menú se construye con los roles reales del usuario.
- Si tiene ambos roles, se presenta la unión de las opciones verificadas.
- Las opciones no verificadas no se habilitan por inferencia.
- La API sigue validando cada operación.

## 5. Panel inicial

**CA-PAN-001 — Panel sin datos ficticios**
- El panel no presenta indicadores, conteos o gráficas inventados.
- Si aún no existe endpoint de dashboard, se muestra una bienvenida o contenido estático no engañoso.
- Cualquier indicador se incorpora únicamente cuando exista una fuente API identificada y validada.

## 6. Listados y tablas

**CA-TAB-001 — Listado conectado**
- Los registros provienen del endpoint real del módulo.
- Las columnas corresponden a campos presentes en el contrato.
- Se muestran estados de carga, vacío y error.
- No se agregan columnas cuyo dato no esté disponible.

**CA-TAB-002 — Paginación**
- La paginación de la interfaz se traduce al contrato del backend: `pagina` y `por_pagina`.
- Se respeta el máximo de 100 registros por página identificado en la auditoría.
- El total y páginas se calculan a partir de `meta`, no contando únicamente los elementos de la página actual.

**CA-TAB-003 — Filtros y ordenamiento**
- Solo se ofrecen filtros realmente soportados por el endpoint.
- Los filtros se envían con la estructura `filtro[campo]` cuando así lo defina el contrato.
- El ordenamiento usa `orden` y los nombres de campo permitidos por el endpoint.
- No se promete búsqueda u ordenamiento del lado servidor si el backend no lo soporta.

**CA-TAB-004 — DataTables**
- La integración transforma el contrato de API al formato que requiere la versión instalada de DataTables.
- Se comprueba la compatibilidad real de los paquetes y adaptadores elegidos.
- No se confunde `simple-datatables` con DataTables.
- No se instala jQuery ni otra dependencia sin verificar necesidad, versión y autorización del proyecto.

## 7. Formularios de alta, edición y acciones

**CA-FOR-001 — Contrato**
- Cada campo del formulario corresponde a un campo aceptado por el Request/API del módulo.
- Reglas, valores permitidos y obligatoriedad proceden del backend/OpenAPI.
- No se inventan catálogos ni valores de enumeraciones.

**CA-FOR-002 — Validación**
- Se muestran los errores `422` junto al campo correcto cuando el backend identifica el campo.
- Los valores ingresados se conservan cuando sea seguro y apropiado.
- La validación del frontend mejora la experiencia, pero no sustituye la del backend.

**CA-FOR-003 — Confirmación**
- Las acciones destructivas o irreversibles solicitan confirmación.
- La interfaz muestra el resultado real de la operación.
- No se muestra éxito antes de recibir confirmación del servidor.

## 8. Criterios por módulos futuros

Para cada módulo antes de implementarlo, completar una ficha con:

- Identificador y nombre del módulo.
- Roles autorizados según backend.
- Ruta web y endpoint(s) API existentes.
- Método HTTP y parámetros.
- Esquema de respuesta y campos confirmados.
- Acciones permitidas.
- Reglas de validación.
- Estados de carga, vacío, éxito y error.
- Criterios de aceptación específicos.
- Casos de prueba ejecutados.

No se acepta un módulo cuyo endpoint o contrato no haya sido verificado.

## 9. Evidencia mínima para aceptar un cambio

- Lista de archivos creados/modificados.
- Endpoints consultados y métodos utilizados.
- Pruebas ejecutadas y resultado.
- Capturas o evidencia visual de la pantalla, cuando aplique.
- Limitaciones conocidas y criterios aún pendientes.
- Confirmación de que no se añadieron datos ficticios ni funcionalidades no solicitadas.
