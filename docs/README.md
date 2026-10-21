# Mapa de documentos

**Versión:** 1.2 · 21 de octubre de 2026 (cambios en `docs/CAMBIOS-v1.2.md`)

## 1. Orden de precedencia

Si dos documentos se contradicen, prevalece el que aparece primero en esta lista. Aun así, **ante cualquier contradicción el agente se detiene y pregunta**; la lista solo dice cuál texto es el vigente mientras se resuelve.

1. `requisitos.md`: qué hace el sistema (HU, RN, RNF).
2. `alcance-fases.md`: qué entra en la Fase 1 y qué no.
3. `arquitectura.md`: estructura, datos y decisiones técnicas (ADR).
4. `hitos/f1-hN.md`: especificación del hito en curso.
5. `convenciones-api.md`: forma de la API.
6. `estrategia-pruebas.md` y `criterios-aceptacion-fase-1.md`: cómo se demuestra y se acepta.
7. `plan-fase-1.md`: orden del trabajo, hitos y compuerta.

Cada dato tiene **una sola fuente**; los demás documentos lo citan, no lo copian. Excepción: la especificación de cada hito copia lo que el hito usa, para ser autocontenida (`plan-fase-1.md` §5).

## 2. Archivos

| Archivo | Para qué sirve | Quién lo usa |
|---|---|---|
| `../backend/.ai/guidelines/proyecto.md` | Reglas permanentes del agente. Laravel Boost las copia a `backend/CLAUDE.md` y `backend/AGENTS.md`; esos dos **no se editan a mano** | El agente, siempre |
| `../CLAUDE.md` | Archivo corto de la raíz para Claude Code: importa las reglas de `backend/` y explica cómo trabajar | El agente, siempre |
| `../AGENTS.md` | Puntero corto para Codex hacia las mismas reglas | Codex |
| `requisitos.md` | HU, RN y RNF (v1.2) | Fuente de las reglas de negocio |
| `arquitectura.md` | Contextos, tablas, ADR y migraciones (v1.2) | Fuente de la estructura y los datos |
| `alcance-fases.md` | Qué se completa en la Fase 1 y qué se espera en la Fase 2, según el PDF | Quien decide si algo está dentro o fuera |
| `plan-fase-1.md` | Tecnologías, metodología, hitos, dependencias y compuerta | Orden del trabajo |
| `criterios-aceptacion-fase-1.md` | Cuándo se acepta una tarea, un hito y la fase | Quien revisa |
| `estrategia-pruebas.md` | Herramientas, umbrales, pruebas de consola y tablas de casos | El agente y quien revisa |
| `convenciones-api.md` | Formato de la API, errores, advertencias y confirmaciones | El agente y el frontend |
| `hitos/f1-hN.md` | Especificación autocontenida de cada hito; solo existe H0 | El agente, durante ese hito |
| `decisiones/registro-decisiones.md` | Decisiones por defecto y decisiones abiertas | El responsable |
| `trazabilidad.md` | HU, RN y RNF contra sus pruebas (se crea en T01 y se llena en cada hito) | Quien revisa |
| `api/openapi.yaml` | Contrato de la API (se crea en T09 y crece con cada hito) | Agente y frontend |
| `CAMBIOS-v1.2.md` | Qué cambió en la versión 1.2 y qué falta decidir | El responsable y el agente, una vez |
