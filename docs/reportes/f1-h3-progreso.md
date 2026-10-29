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
| T01 Migraciones | hecha | `16b4bfb` | `f1-h3-t01.md` |
| T02 Catálogos | hecha | `6786ab1` | `f1-h3-t02.md` |
| T03 Entrenamientos y puertos | hecha | `f957122` | `f1-h3-t03.md` |

### 2.1 Resumen del Lote A

**Commits de la rama**, en orden:

| # | Hash | Prefijo | Mensaje |
|---|---|---|---|
| 1 | `205f66f` | F1-DOC | especificación de H3, punto de partida y plan de T01 |
| 2 | `16b4bfb` | F1-H3-T01 | migraciones de Competencia |
| 3 | `18bff3d` | F1-DOC | reporte de T01 y plan de T02 |
| 4 | `6786ab1` | F1-H3-T02 | catálogos de lugares, rivales, temporadas y torneos |
| 5 | `3f5f980` | F1-DOC | reporte de T02, plan de T03 y decisiones DA-04 a DA-13 |
| 6 | `f957122` | F1-H3-T03 | entrenamientos y puertos de equipo |
| 7 | ver `git log -1` | F1-DOC | reporte de T03, DA-14 y DA-15, y resumen del Lote A |

**Pruebas por identificador** (detalle en cada reporte):

| Tarea | Pruebas nuevas | Identificadores |
|---|---:|---|
| T01 | 22 | RNF-03 (restricciones, UQ parciales, rollback y fresh), RNF-02 (solo inserción, historial en la misma transacción) |
| T02 | 65 | HU-28 (§7.1 filas 1 a 7 y resto), DD-07, RNF-02 |
| T03 | 48 | HU-10 (§7.2 filas 1 a 7 y resto), RN-22, RNF-01, F1-H3-T03 (arquitectura) |

**`make verificar`** (una corrida completa por tarea, sin nada más corriendo; todas pasan):

| Tarea | Pruebas | Mutantes (MSI) | Cobertura D / A / I | Duración |
|---|---:|---|---|---|
| T01 | 942 | 362 (100 %) | 100,0 / 100,0 / 99,7 % | 597 s (9 min 57 s) |
| T02 | 1 007 | 380 (100 %) | 100,0 / 100,0 / 99,4 % | 562 s (9 min 22 s) |
| T03 | 1 055 | 411 (100 %) | 100,0 / 100,0 / 99,4 % | 898 s (14 min 58 s) |

**T03 quedó a 2 s del límite de 15 min de §0.4.** Fue la primera corrida tras reiniciar Docker Desktop (la máquina virtual de WSL no se reanudó después de la suspensión de la computadora). Todas las etapas tardaron más que en T01 y T02, no solo las pruebas nuevas; Pest tardó 528 s. Si una corrida del Lote B pasa de 15 min, me detengo.

**Decisiones del agente:** DA-01 a DA-15 en `f1-h3-decisiones-del-agente.md`. Para aprobar:

- **DA-08:** código nuevo `entrenamiento_no_programado` (409): un entrenamiento cancelado o realizado no cambia de hora, lugar ni responsable.
- **DA-12:** `JugadoresDelEquipo` exige, además del alcance, la asignación al equipo; restringe la privacidad.
- **DA-13:** «entrenar un equipo» incluye el rol `entrenador` y el usuario no eliminado.

**Rutas fuera de §8:** solo `app/Http/**` y `app/Exceptions/FormatoDeError.php`, autorizadas en R-1. No se tocó `frontend/`. No hubo push, PR, etiquetas ni fusiones.

**Pendiente antes de T04:** la confirmación de §3.2 (asistencia y convocatorias visibles para todos los entrenadores del equipo, o regla estricta). Sin respuesta, T04 sigue la decisión por defecto.
