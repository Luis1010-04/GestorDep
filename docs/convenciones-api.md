# Convenciones de la API (se aplican desde H0)

**Versión:** 1.2.1
**Fecha:** 21 de octubre de 2026
**Origen:** sección 7 del Documento de Arranque v1.0, con las correcciones de la auditoría (500 y 503, advertencias, excepción de la búsqueda por matrícula).

Este documento es una fuente de verdad del agente. Ante una contradicción con otro documento se aplica el orden de precedencia de `docs/README.md`, y el agente se detiene y pregunta.

## 1. Reglas generales

| Tema | Convención |
|---|---|
| Versión y formato | Prefijo `/api/v1`; solo JSON; campos en `snake_case` y en español |
| Recursos | Plural en español: `/api/v1/jugadores`, `/api/v1/partidos`. Las acciones que no son un CRUD se expresan como sub-recurso: `/api/v1/avisos-lesion/{id}/confirmacion` |
| Identificadores | UUID v7 |
| Fechas y horas | ISO 8601 en UTC; las fechas sin hora, `AAAA-MM-DD`; los periodos mensuales, `AAAA-MM` |
| Decimales | Calificaciones, promedios, umbrales, IMC y porcentajes se envían y reciben como cadena con dos decimales (`"7.50"`), para no perder precisión. Los enteros (minutos, tarjetas, calificación de partido) van como número |
| Paginación | `pagina` y `por_pagina` (por defecto 15, máximo 100); la respuesta incluye `datos` y `meta` |
| Filtros y orden | `filtro[campo]=valor` y `orden=campo` o `orden=-campo` |
| Autenticación | Sanctum en modo SPA (cookies de sesión); el detalle está en `docs/hitos/f1-h1.md` |
| Idioma de los mensajes | Archivos `lang/es`; el idioma se resuelve con una función única, lista para leer `Accept-Language` en el futuro |
| Contrato | `docs/api/openapi.yaml`; toda ruta nueva se documenta en la misma tarea |

## 2. Códigos HTTP

| Código | Cuándo |
|---|---|
| 200 | Consulta o acción correcta |
| 201 | Recurso creado |
| 204 | Acción correcta sin cuerpo |
| 401 | Sin sesión |
| 403 | Sin permiso sobre un recurso que el usuario sí conoce (por ejemplo, un docente que intenta confirmar un estado oficial), o llamada a una función exclusiva del otro rol (HU-01) |
| 404 | El recurso no existe **o no es visible para el usuario**. Un recurso fuera de su alcance responde 404 y no 403, para no revelar que existe |
| 409 | Conflicto: matrícula duplicada, valor de catálogo repetido, medida ya registrada en el periodo |
| 422 | Validación de campos |
| 429 | Cuenta bloqueada temporalmente por intentos fallidos (RNF-10), con la cabecera `Retry-After` |
| 500 | Error inesperado; sin trazas en producción |
| 503 | La aplicación no alcanza un servicio que necesita (por ejemplo, la base de datos en la ruta de salud) |

**Excepción única a la regla del 404:** la búsqueda por matrícula (RN-20) devuelve nombres, apellidos y matrícula de un jugador no asignado. Un 409 por matrícula duplicada (HU-03) no incluye datos del expediente existente.

## 3. Formato de error

Un objeto con tres claves:

```json
{
  "codigo": "validacion_fallida",
  "mensaje": "Los datos enviados no son válidos.",
  "errores": [
    { "campo": "valor", "codigo": "calificacion_fuera_de_rango", "mensaje": "La calificación debe estar entre 0 y 10." }
  ]
}
```

- `codigo`: estable, en `snake_case`, legible por máquina. Nunca se traduce.
- `mensaje`: traducido, sale de `lang/es`.
- `errores`: detalle por campo, cada uno con su propio `codigo` y `mensaje`. Es un arreglo vacío si el error no es de campo.

Las violaciones de regla de negocio usan la excepción `ReglaDeNegocioViolada` con un código estable y se responden con 409 o 422 según el caso.

## 4. Advertencias

Las acciones que el sistema permite pero quiere señalar (HU-10 empalme, HU-12 jugador lesionado en la convocatoria, HU-11 jugador lesionado al registrar asistencia) responden con éxito e incluyen un arreglo `advertencias`:

```json
{
  "datos": { "...": "..." },
  "advertencias": [
    { "codigo": "jugador_lesionado", "mensaje": "El jugador tiene un estado oficial de lesionado." }
  ]
}
```

Los datos que el sistema "marca" (HU-21 estado que requiere actualización, HU-24 dato posiblemente desactualizado, HU-09 dato incompleto) se devuelven como campos booleanos o de estado dentro del recurso, con nombre en español (`requiere_actualizacion`, `posiblemente_desactualizado`, `incompleto`). Cada nombre se define en el contrato OpenAPI del hito.

### 4.1 Confirmaciones

Cuando una acción necesita que el usuario confirme algo (HU-17: medida ya registrada en el periodo), el flujo es:

1. El primer intento responde **409** con un código estable, por ejemplo `medicion_ya_registrada`.
2. El cliente repite la petición con `"es_correccion": true` y un `"motivo"` no vacío.
3. La API responde **200**, reemplaza el valor y escribe el historial con `operacion = actualizar` y ese `motivo`.

Un `es_correccion: true` sin `motivo` responde 422. Un `es_correccion: true` cuando no hay nada que corregir responde 422 con el código `nada_que_corregir`.

## 5. Idioma y cambio futuro de idioma

| Elemento | Se traduce por idioma | Cómo se prepara |
|---|---|---|
| Mensajes de error y validación | Sí | Todos viven en `lang/es`. Agregar inglés es agregar `lang/en` y leer `Accept-Language`. No exige tocar código |
| Códigos de error | No | Son identificadores estables |
| Rutas y nombres de campos JSON | No | Forman parte del contrato; cambiarlos equivale a una API nueva (`/api/v2`) |

Regla: **ningún texto visible al usuario se escribe directamente en el código**; siempre pasa por `lang/es`. No se hará un segundo idioma de contrato.

## 6. Seguridad en la respuesta

- Nunca se devuelven trazas, rutas de archivo ni consultas SQL en producción.
- Un login fallido no revela si falló el usuario o la contraseña (HU-01).
- Las respuestas de una consulta incluyen solo los datos que la matriz de visibilidad de `docs/requisitos.md` (sección 7) permite al usuario.
