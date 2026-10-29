# Progreso del hito F1-H3: Competencia

**Rama:** `hito/f1-h3`, creada el 29 de octubre de 2026 desde `main`.
**Especificación:** `docs/hitos/f1-h3.md` v1.0.

## 0. Punto de partida

- **Commit de `main`:** `dd85aa1` (`Merge pull request #4 from Luis1010-04/hito/f1-h2`), con la etiqueta `f1-h2` (`git tag --points-at HEAD`).
- **Estado de la verificación:** el último código de H2 es `e02a40b` (FIX M-c). Su `make verificar` pasó sin nada más corriendo: 920 pruebas (5 574 aserciones), Pint y PHPStan nivel 7 sin errores, 362 mutantes eliminados (MSI 100 %), cobertura por capa 100,0 / 100,0 / 99,7 % y `composer audit` sin avisos, en 7 min 47 s (`docs/reportes/f1-h2-reporte.md` §2). Después de `e02a40b` solo hay commits F1-DOC y el merge. No se repitió la corrida al partir: la primera de H3 es la de T01.
- **Entorno:** contenedores `app`, `db`, `worker` y `scheduler` arriba y sanos.
- **Árbol:** limpio salvo `docs/hitos/f1-h3.md`, que estaba sin versionar y se agrega en el primer commit F1-DOC de la rama.

## 1. Decisiones del responsable en esta sesión (29/10/2026)

| # | Pregunta | Decisión |
|---|---|---|
| R-1 | §8 no permite `app/Http/**` ni `app/Exceptions/FormatoDeError.php` en T02 a T07, aunque los controladores, solicitudes y recursos viven ahí (arquitectura §5) y el 409 de una excepción se asigna en `FormatoDeError` | Se autorizan, como omisión de la especificación (igual que las «rutas base» de H2). Cada reporte las lista como ruta clase (b) |
| R-2 | §5.1 dice UQ (`entrenamiento_id`, `jugador_id`) total en `asistencias_entrenamiento`; arquitectura §7.5 y §7.1 dicen UQ parcial `where eliminado_en is null` | Parcial, como la arquitectura (mayor precedencia). La especificación se corrige después |

## 2. Lote A

| Tarea | Estado | Commit | Reporte |
|---|---|---|---|
| T01 Migraciones | en curso | | |
| T02 Catálogos | pendiente | | |
| T03 Entrenamientos y puertos | pendiente | | |
