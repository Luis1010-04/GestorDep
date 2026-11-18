# Reglas para agentes de IA — GestorDep

**Ubicación prevista:** `frontend/docs/REGLAS_AGENTE_IA.md`  
**Estado:** Instrucciones operativas para agentes que analicen o modifiquen el frontend.

---

## 1. Regla principal: no asumir

No inventes ni completes por intuición:

- Endpoints, métodos HTTP o parámetros.
- Campos de solicitudes o respuestas.
- Relaciones entre entidades.
- Roles, permisos o alcances de acceso.
- Estados, enumeraciones o catálogos.
- Datos de jugadores, equipos, torneos, partidos o estadísticas.
- Reglas de negocio.
- Resultados de pruebas o afirmaciones de que algo funciona.

Si falta información, detente en ese punto, identifica exactamente qué falta y solicita aclaración o inspección adicional.

## 2. Inspección obligatoria antes de implementar

Antes de escribir código para una funcionalidad:

1. Inspecciona el estado actual del repositorio y las convenciones existentes.
2. Consulta el OpenAPI vigente.
3. Localiza la ruta, controlador, Request, Resource y caso de uso relevantes del backend.
4. Revisa las pruebas existentes relacionadas.
5. Comprueba los permisos y middleware aplicables.
6. Contrasta lo encontrado con `ESPECIFICACION_FUNCIONAL.md` y `CRITERIOS_ACEPTACION.md`.
7. Informa cualquier discrepancia antes de resolverla por cuenta propia.

Los informes de auditoría son referencias auxiliares. No sustituyen la comprobación del código actual.

## 3. Fuentes de verdad

Para conocer el comportamiento implementado, prioriza:

1. Código y pruebas actuales del backend.
2. OpenAPI vigente para el contrato HTTP publicado.
3. Requisitos y decisiones aprobados por el responsable del proyecto.
4. Documentos de `frontend/docs/`.
5. Informes de auditoría previos como orientación.

Si el código y OpenAPI no coinciden, reporta la discrepancia. No elijas una versión silenciosamente ni modifiques el contrato para hacer que tu implementación funcione.

## 4. Límites de modificación

- Trabaja dentro del alcance solicitado.
- No modifiques `src/` ni la lógica de dominio/aplicación para resolver necesidades exclusivamente visuales.
- No cambies reglas de negocio, contratos o permisos sin autorización expresa.
- No agregues endpoints por conveniencia.
- No instales paquetes, frameworks, herramientas de build o dependencias sin autorización y justificación.
- No introduzcas Vite, Node, jQuery, DOMPDF u otras dependencias por iniciativa propia.
- No elimines pruebas, documentación, créditos de licencia ni convenciones existentes.
- No sobrescribas archivos del template sin revisar primero su contenido y licencia.
- Conserva cambios previos del usuario y evita revertir trabajo ajeno al alcance.

El responsable del proyecto ha autorizado incorporar el frontend en `frontend/` usando Blade y NiceAdmin. Esta autorización no equivale a permiso general para cambiar el backend o agregar cualquier dependencia.

## 5. Integración frontend/backend

- Utiliza la API existente para datos y operaciones de negocio.
- No dupliques lógica de negocio en controladores web, Blade o JavaScript.
- Utiliza `fetch` y el mecanismo de sesión/cookies de Sanctum documentado en el proyecto.
- No implementes Bearer tokens si el backend utiliza autenticación de sesión.
- Incluye credenciales y CSRF de acuerdo con la configuración real de Sanctum.
- No guardes contraseñas, tokens o secretos en almacenamiento persistente del navegador.
- Trata los errores HTTP de acuerdo con la respuesta real.
- No presupongas que existe un endpoint para obtener la sesión actual: la auditoría inicial no identificó `GET /api/v1/sesion`. Verifica el estado actual antes de utilizarlo o proponerlo.

## 6. Roles y autorización

- Los roles identificados son `docente` y `entrenador`.
- No crees roles nuevos ni asumas que existe un administrador.
- La interfaz puede ocultar opciones según roles confirmados, pero el backend es la autoridad de autorización.
- Respeta el alcance de datos del usuario autenticado.
- Un `404` puede ser intencional para no revelar recursos fuera del ámbito del usuario; no intentes eludirlo.

## 7. Datos y demostraciones

- No uses datos simulados en pantallas presentadas como funcionales.
- No inventes estadísticas para llenar tarjetas o gráficas.
- Si no existe endpoint para una sección, deja la sección fuera del alcance o muestra un estado honesto sin datos de negocio.
- Los mocks solo se permiten si el responsable los solicita expresamente y deben identificarse claramente como datos de demostración.
- No uses `localStorage` o `sessionStorage` para simular persistencia del backend.

## 8. NiceAdmin y dependencias visuales

- Reutiliza los componentes y estilos del NiceAdmin existente.
- No copies indiscriminadamente scripts de demostración que inicialicen plugins no incluidos.
- Mantén únicamente los plugins realmente utilizados.
- Verifica versiones y compatibilidad de DataTables y su integración Bootstrap antes de instalarlos.
- `simple-datatables` no es equivalente a DataTables con procesamiento de servidor.
- Mantén los créditos requeridos por la licencia gratuita del template.
- No cambies la estructura global de CSS/JS sin revisar las dependencias existentes.

## 9. Calidad y pruebas

Antes de declarar terminado un trabajo:

1. Ejecuta las pruebas pertinentes disponibles.
2. Ejecuta validaciones estáticas o de formato aplicables.
3. Comprueba la página en navegador o mediante una prueba HTTP adecuada cuando sea posible.
4. Revisa consola del navegador y solicitudes de red.
5. Verifica estados de carga, vacío, éxito, error y sesión expirada que correspondan.
6. Contrasta la implementación con todos los criterios de aceptación aplicables.

No afirmes que ejecutaste una prueba si no la ejecutaste. Distingue claramente entre:
- **Verificado:** comprobado mediante ejecución.
- **Implementado, no verificado:** existe código, pero no se pudo probar.
- **Pendiente:** no está implementado.
- **Bloqueado:** falta una decisión, dato o dependencia.

## 10. Forma de trabajar

- Implementa un hito pequeño y coherente a la vez.
- Antes de modificar, presenta un resumen del plan y archivos que probablemente cambiarán.
- No amplíes el alcance durante la implementación.
- Si descubres una necesidad adicional, documéntala como propuesta y espera autorización.
- Mantén los cambios enfocados y evita refactorizaciones no relacionadas.
- Actualiza `estado-de-implementacion.md` si existe y el trabajo cambia el estado de un criterio.

## 11. Informe obligatorio al finalizar cada tarea

Entrega un resumen con:

1. **Resultado:** qué quedó implementado.
2. **Archivos:** lista exacta de archivos creados, modificados o eliminados.
3. **Integración:** endpoints y contratos utilizados.
4. **Pruebas:** comandos ejecutados y resultados reales.
5. **Criterios:** identificadores de aceptación cumplidos, pendientes o bloqueados.
6. **Riesgos:** discrepancias, limitaciones y decisiones requeridas.
7. **Siguiente paso:** una recomendación concreta, sin iniciar trabajo adicional automáticamente.

## 12. Condición para detenerse

Detente y pregunta antes de continuar cuando:

- El requisito contradiga el backend o la documentación aprobada.
- El endpoint o campo necesario no exista o no esté confirmado.
- Se requiera cambiar `src/`, permisos, contratos o reglas de negocio.
- Se necesite instalar una dependencia no autorizada.
- No esté claro qué rol puede ejecutar una acción.
- Una decisión pueda afectar datos reales o seguridad.

**Es preferible reportar un bloqueo concreto que entregar una implementación basada en una suposición.**
