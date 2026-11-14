# Plan de F1-H5-T03b: fecha de última actualización del estado oficial (RNF-04, DA-56)

**Fecha:** 6 de noviembre de 2026
**Rama:** `hito/f1-h5` · **Especificación:** `docs/hitos/f1-h5.md` v1.0.7, §6.2 y §8 (T03b)
**Estado:** propuesto, **a la espera de aprobación del responsable**. No se escribe código hasta su aprobación. Va antes de T06, y después de la auditoría de T05 y de la medición de DA-57, porque exige `make verificar`.

> **Superado** (nota del 13/11/2026, auditoría de estado de H5): el plan se aprobó y T03b se ejecutó el 7/11/2026 (`f1-h5-t03b.md` y `f1-h5-progreso.md` §16).

## 1. Qué cambia

El recurso del estado oficial agrega un campo con el momento de su última actualización. Solo el momento, nunca el contenido del cambio.

**Nombre:** `actualizado_en`. Es el nombre que usan los 21 esquemas de `docs/api/openapi.yaml` que tienen este dato, con `type: string` y `format: date-time`. Ninguno usa otro nombre.

| Caso | `actualizado_en` |
|---|---|
| Estado confirmado, recién creado | El instante de la confirmación, igual a `vigente_desde` |
| `PATCH` que cambia la fecha estimada | El instante del cambio; `vigente_desde` no cambia |
| `PATCH` que cambia el estado | El instante del cambio, en la fila nueva |
| `PATCH` con valores idénticos (DA-48) | No cambia |
| Alta | El instante del alta, en el `apto` nuevo |
| Corrección del detalle de la lesión | No cambia: es la lesión, no el estado |
| `apto` implícito, sin registro (DA-01) | Nulo |

Lo ven todos los que ven el estado oficial (§5.3), incluido el docente. Es un campo más, sin relación con `lesion_id`, que sigue siendo solo para el registrante (DA-18).

## 2. Archivos

- **`src/Salud/Dominio/EstadoOficial.php`:** propiedad `actualizadoEn`. `conFecha` recibe el instante.
- **`src/Salud/Aplicacion/`:** `CambiarEstado` pasa el instante a `conFecha`; `EstadoVisible` lleva el campo, nulo en `aptoImplicito`.
- **`src/Salud/Infraestructura/RepositorioDeEstadosSql.php`:** lee `actualizado_en` y `guardarCambio` escribe el del estado, para que la respuesta y la base digan lo mismo.
- **`app/Http/Resources/EstadoDeDisponibilidadRecurso.php`:** el campo nuevo.
- **`docs/api/openapi.yaml`:** `actualizado_en` en `EstadoDeDisponibilidad`, obligatorio y `nullable: true`.
- **`docs/hitos/f1-h5.md` §6.2:** el campo en «Estado oficial», con su versión nueva.

Sin migraciones: `estados_disponibilidad` ya tiene `actualizado_en`, y `guardarNuevo` y `guardarCambio` ya lo escriben. Sin cambios en el puerto `EstadoDeDisponibilidadOficial`, que solo devuelve el estado (T04).

## 3. Pruebas primero

| Archivo | Cubre | Falla esperada |
|---|---|---|
| `tests/Unit/Salud/EstadoOficialTest.php` | `conFecha`, `cerrar` y `sucesor` fijan el instante; el resto no lo cambia | Método o argumento inexistente |
| `tests/Feature/Salud/ConfirmacionYEstadoTest.php` | Una prueba por fila de §1, con el identificador `DA-56`; D1 y E1 ven el momento sin `lesion_id`; respuesta del `PATCH` igual a la base | Clave ausente en la respuesta |
| `tests/Feature/Salud/ContratoDeEstadoYLesionTest.php` | El contrato con el campo nuevo | Propiedad no permitida o ausente |

**Cambios en pruebas existentes, sin quitar ninguna aserción:**

- `CAMPOS_DEL_ESTADO` agrega `actualizado_en`, porque DA-18 comprueba la lista exacta de campos. Es un cambio de contrato pedido, y refuerza la prueba.
- Las llamadas a `new EstadoOficial` (6 en `tests/`) agregan el argumento nuevo.

Al terminar, mutación de `conFecha` sin instante y de `guardarCambio` con el reloj en lugar del instante del estado, con control antes y después.

## 4. Cierre

- **Corridas, en serie:** PHPStan, `make verificar`, `make mutacion`, `make fresh` y `make humo`.
- **Logs:** en `docs/reportes/logs/` los que respaldan el reporte; ninguno se borra.
- **Documentos:** `docs/reportes/f1-h5-t03b.md`, con la falla pegada; `f1-h5-decisiones-del-agente.md` y `f1-h5-progreso.md`.
- **Commit:** `F1-H5-T03b: fecha de última actualización del estado oficial`, local, sin push.

## 5. Decisiones que necesito

1. **Nombre:** `actualizado_en`.
2. **`apto` implícito:** nulo, porque no hay registro. Es lo que hace `vigente_desde` (DA-01).
3. **Corrección de la lesión:** no cambia el `actualizado_en` del estado. Mostrar ese momento revelaría a quien no ve la lesión que alguien la corrigió, y DA-56 pide no exponer el contenido.

## 6. Riesgos

- **Contrato ya publicado:** agrega un campo obligatorio a una respuesta de T03. Solo suma; `frontend/` no existe en la Fase 1.
- **Mismo segundo:** las columnas guardan segundos, así que dos cambios en un mismo segundo dan el mismo `actualizado_en`. El de estado ya responde 409 por DA-30; el de fecha no.
