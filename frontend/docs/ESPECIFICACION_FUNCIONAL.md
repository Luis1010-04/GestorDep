# Especificación funcional — GestorDep

**Ubicación prevista en el repositorio:** `frontend/docs/ESPECIFICACION_FUNCIONAL.md`  
**Estado:** Borrador base para implementación del frontend  
**Versión:** 1.0  
**Propósito:** establecer qué debe presentar y permitir el frontend, sin atribuir al backend capacidades que no hayan sido verificadas.

---

## 1. Propósito del sistema

GestorDep es una plataforma web para el seguimiento académico y deportivo de jugadores de una escuela de fútbol. El sistema contempla información de jugadores, equipos, actividades académicas, competencias, rendimiento, condición física, salud y alertas.

El frontend se desarrollará con **Laravel Blade**, utilizando la plantilla **NiceAdmin**, basada en **Bootstrap 5**, y consumirá las operaciones existentes de la API mediante JavaScript `fetch`.

## 2. Arquitectura y límites

- El backend existente está organizado bajo un enfoque DDD y expone una API con prefijo `/api/v1`.
- El frontend se incorporará en el proyecto Laravel existente mediante rutas web y vistas Blade.
- Las vistas Blade sirven la estructura visual; la obtención y envío de datos de negocio se realizará a través de la API.
- No duplicar reglas de negocio, casos de uso ni acceso a datos en controladores web o JavaScript.
- La autorización real corresponde al backend. Ocultar opciones del menú no sustituye la validación de permisos en la API.
- La autenticación existente utiliza sesión/cookies de Laravel Sanctum, no debe asumirse autenticación Bearer.

## 3. Tecnologías acordadas

| Área | Tecnología / decisión |
|---|---|
| Vistas | Laravel Blade |
| Plantilla | NiceAdmin |
| Estilos y componentes | Bootstrap 5.3.3 (versión identificada en los recursos auditados) |
| Comunicación con API | JavaScript `fetch` |
| Autenticación | Laravel Sanctum con sesión/cookies |
| Tablas | DataTables, sujeto a validar la distribución e integración elegidas |
| Notificaciones | SweetAlert2 |
| PDF | DOMPDF, fuera del primer hito y sujeto a aprobación/definición |
| Construcción de assets | Copia estática inicial; no introducir Vite o Node sin decisión expresa |

Las versiones y dependencias deberán comprobarse en el repositorio antes de instalar o actualizar paquetes.

## 4. Roles conocidos

La API contempla los siguientes roles:

- `docente`
- `entrenador`

No crear roles de administrador, coordinador o jugador sin un requisito aprobado y soporte correspondiente en backend.

Un usuario puede tener más de un rol. Cuando ocurra, el menú debe reunir las secciones autorizadas para sus roles.

## 5. Áreas funcionales identificadas

Las siguientes áreas aparecen en la arquitectura y auditoría del backend. Su presencia no significa que todas las pantallas deban implementarse en el primer hito.

- Identidad y sesión.
- Plantel y jugadores.
- Ámbito académico: perfiles académicos, periodos, materias/asignaturas, calificaciones y asistencia, según operaciones disponibles.
- Competencia: equipos, categorías, torneos, temporadas, partidos, convocatorias y alineaciones, según operaciones disponibles.
- Rendimiento.
- Condición física.
- Salud.
- Alertas y estatus de jugadores.
- Consultas de lectura.

**Importante:** el backend contempla torneos y temporadas; no se debe inventar un módulo de ligas. La auditoría no identificó un endpoint de entrenadores ni un listado global de alertas. Las alertas identificadas están asociadas a un jugador (`/jugadores/{id}/alertas`).

## 6. Reglas de visibilidad conocidas

La interfaz puede adaptar el menú a los roles devueltos por la sesión, pero cada operación debe seguir siendo autorizada por la API.

| Sección / acción | Docente | Entrenador |
|---|---:|---:|
| Panel | Sí | Sí |
| Consulta de jugadores dentro de su ámbito | Sí | Sí |
| Crear, editar o desactivar jugadores | No asumir | Sí, según permisos del backend |
| Información académica y asistencia | Sí, dentro de su ámbito | Según operaciones/permisos reales |
| Periodos, materias/asignaturas, calificaciones y asistencia de clase | Sí | No asumir |
| Equipos, categorías, entrenamientos, partidos, convocatorias y alineaciones | No asumir | Sí, según operaciones/permisos reales |
| Rendimiento, condición física y salud | No asumir | Sí, según operaciones/permisos reales |
| Alertas y estatus de jugadores | Consulta según ámbito | Consulta según ámbito |
| Cerrar sesión y cambiar contraseña | Según API | Según API |

Esta tabla es una guía inicial derivada de la auditoría; debe contrastarse con middleware, políticas, controladores, OpenAPI y pruebas antes de habilitar acciones. “No asumir” significa que el frontend no debe habilitar la acción hasta verificarla.

## 7. Hito inicial del frontend

El primer hito debe limitarse a:

1. Integrar los recursos estáticos necesarios de NiceAdmin.
2. Configurar rutas web para servir las vistas Blade.
3. Crear el layout principal y sus parciales (cabecera, menú y pie).
4. Crear la pantalla de ingreso.
5. Integrar el flujo de autenticación Sanctum con sesión.
6. Recuperar y representar la identidad y roles del usuario autenticado.
7. Mostrar un menú adaptado a los roles.
8. Crear una pantalla inicial/panel sin cifras ni gráficas ficticias.
9. Gestionar estados de carga, sesión expirada y errores de API.

No considerar terminado el hito si la pantalla de ingreso solo es visual o si el panel muestra información de demostración.

## 8. Fuera del alcance inicial

Salvo aprobación posterior, no incluir en el primer hito:

- Generación de reportes PDF.
- Instalación de DOMPDF.
- Administración de usuarios/roles desde la interfaz: la auditoría indica que los usuarios y roles se crean mediante consola y existe un requisito que restringe esta administración.
- Nuevos roles.
- Endpoints o funcionalidades que no existan en la API.
- Datos de prueba presentados como datos reales.
- Cambios a la lógica de dominio para resolver necesidades visuales.

## 9. Contrato de integración conocido

La auditoría identificó:

- Prefijo API: `/api/v1`.
- Inicio de sesión: `POST /api/v1/sesion`.
- Cierre de sesión: `DELETE /api/v1/sesion`.
- No se identificó inicialmente `GET /api/v1/sesion`; cualquier incorporación debe estar aprobada, implementada, documentada en OpenAPI y cubierta por pruebas antes de consumirla.
- Protección CSRF: obtener la cookie mediante `GET /sanctum/csrf-cookie`.
- Respuesta de inicio de sesión con identidad y roles: `id`, `nombre`, `correo`, `roles`.
- Errores con forma `{codigo, mensaje, errores}`.
- Listados paginados con forma `{datos, meta: {pagina, por_pagina, total, total_paginas}}`.
- Parámetros de paginación identificados: `pagina`, `por_pagina` (máximo 100).
- Filtros identificados: `filtro[campo]`.
- Ordenamiento identificado: `orden=-campo`.
- Algunos valores decimales se serializan como cadenas.

No inferir campos concretos de cada recurso a partir de esta descripción general. Consultar OpenAPI y el Resource correspondiente para cada pantalla.

## 10. Reglas para datos y contenido

- Los datos visibles deben proceder de respuestas reales de la API.
- No inventar nombres, equipos, categorías, estadísticas, calificaciones, fechas ni estados.
- No rellenar campos ausentes con valores ficticios.
- Si un dato opcional no viene en la respuesta, mostrar un tratamiento neutral (por ejemplo, “No disponible”) solo si resulta apropiado para ese campo.
- Distinguir entre ausencia de datos, error de conexión y falta de autorización.
- Los textos de interfaz pueden ser redactados por el frontend; los datos de negocio no.

## 11. Fuentes de verdad y resolución de contradicciones

Orden recomendado de consulta:

1. Código actual y pruebas del backend para el comportamiento implementado.
2. OpenAPI vigente para contratos HTTP publicados.
3. Requisitos y decisiones aprobados del proyecto.
4. Este documento para el alcance funcional del frontend.
5. Informes de auditoría como evidencia auxiliar, no como sustituto de verificar el estado actual del repositorio.

Si dos fuentes se contradicen, no elegir silenciosamente una. Registrar la discrepancia y solicitar decisión o verificar con el responsable del proyecto.
