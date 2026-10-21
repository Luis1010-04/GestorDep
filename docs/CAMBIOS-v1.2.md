# Cambios de la versión 1.2 de la documentación

**Fecha:** 21 de octubre de 2026
**Motivo:** revisión de la documentación para trabajar con Claude Code y Laravel Boost: corregir inconsistencias que el agente interpretaría mal, eliminar duplicados y reducir el contexto que se carga en cada tarea.

## 1. Cambios por archivo

| Archivo | Versión | Cambios |
|---|---|---|
| `README.md` | 1.2 | Orden de precedencia entre documentos; regla de una sola fuente por dato; nuevas ubicaciones de las reglas del agente |
| `requisitos.md` | 1.2 | RN-04: redondeo al medio hacia arriba. RN-09: ventana móvil de 30 días y vencimiento por tiempo **[Decisión por defecto]**. HU-17: la corrección se confirma con un motivo |
| `arquitectura.md` | 1.2 | §5: todo bajo `backend/`, plantilla de contexto separada, Consultas sin Dominio, Eloquent fuera de `app/Models`. §7.1: unicidad parcial en todas las tablas con borrado lógico, `citext` en nombres de catálogo, decimales exactos. `numeric(4,2)` en plantillas, umbrales y evaluaciones. §7.9: única modificación permitida en `alertas`. §11: cola `database` con `worker`, eventos después del commit, recálculo diario a las 02:00, reloj inyectable. §14: control de cambios |
| `estrategia-pruebas.md` | 1.2 | Nivel de Larastan por hito. Nota sobre decimales en RN-04. Definición de la ventana de RN-09 y dos filas nuevas de cierre por tiempo. `alertas:recalcular` programado. Flujo de Sanctum en los guiones de humo |
| `convenciones-api.md` | 1.2 | Precedencia vía `README.md`. Decimales como cadena. 403 por función de otro rol. §4.1: flujo de confirmación (HU-17) |
| `plan-fase-1.md` | 1.2 | Colas y planificador. Reglas del agente en `.ai/guidelines` con Boost. Especificaciones de hito autocontenidas. Una tarea por sesión |
| `alcance-fases.md` | 1.1 | La cola `database` y el planificador están permitidos. §8 remite a `plan-fase-1.md` §2 en lugar de duplicar la tabla |
| `criterios-aceptacion-fase-1.md` | 1.1 | H0: servicios `worker` y `scheduler`. H6: recálculo diario y cierre por tiempo. El hito de cada historia proviene del plan |
| `trazabilidad.md` | Sin cambios | |

## 2. Archivos nuevos

| Archivo | Para qué |
|---|---|
| `backend/.ai/guidelines/proyecto.md` | Reglas del agente. Boost las combina con sus guías y genera `backend/CLAUDE.md` y `backend/AGENTS.md`. El contenido del `AGENTS.md` escrito a mano se integra aquí, y el `AGENTS.md` de la raíz queda como un puntero corto para Codex |
| `CLAUDE.md` (raíz) | Archivo corto que Claude Code carga al abrir el repositorio; importa `backend/CLAUDE.md` |
| `docs/CAMBIOS-v1.2.md` | Este documento |

## 3. Decisiones nuevas que se registran en `decisiones/registro-decisiones.md`

Todas son **[Decisión por defecto]** salvo que se indique otra cosa.

1. RN-09: la ventana de 30 días es móvil y termina en la fecha de cálculo, inclusive. *Validar con la coordinación deportiva.*
2. RN-04: el redondeo del promedio es al medio hacia arriba. *Validar con la coordinación académica.*
3. Técnica: cola `database` con servicio `worker`, y `alertas:recalcular` programado a diario a las 02:00 America/Mexico_City.
4. Técnica: decimales como cadena en el JSON de la API.
5. Técnica: las reglas del agente viven en `backend/.ai/guidelines/proyecto.md`; `CLAUDE.md` y `AGENTS.md` de `backend/` los genera Boost.

## 4. Pendientes del responsable (no los resuelve el agente)

- [ ] **Aceptación del cliente:** que la institución confirme por escrito que la Fase 1 es solo API, sin pantallas, reportes ni perfil de administrador. El PDF del cliente empieza justo por ahí, así que conviene dejarlo firmado.
- [ ] **Demostraciones:** decidir si se agrega un visor del contrato OpenAPI (por ejemplo, Swagger UI o Scramble) solo en desarrollo, para mostrar avances al cliente. Hoy `alcance-fases.md` §5 no lo permite sin aprobación.
- [ ] **Instalar Laravel Boost** como tarea aprobada de H0 (o como tarea extra si H0 ya cerró). Como PHP corre en Docker, el servidor MCP se registra en el `.mcp.json` de la raíz con `docker compose exec -T <servicio> php artisan boost:mcp`.
- [ ] Confirmar las decisiones 1 y 2 de la sección 3 con la institución.
