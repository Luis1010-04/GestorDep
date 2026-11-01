# Auditoría del F1-H4-FIX-2

**Fecha:** 31 de octubre de 2026 · **Rama:** `hito/f1-h4` · **Alcance:** el diff `0cccd14..ed6ac4b` (7 archivos). Al revisarlo, HEAD estaba en `aad66fd`, que solo agrega los dos informes de auditoría anteriores (`git diff --stat ed6ac4b aad66fd`), así que el árbol revisado es el de `ed6ac4b`.
**Reglas:** sesión de solo lectura. No se modificó ningún archivo salvo este informe, y no hubo commits ni push. Las corridas fueron en serie. La mutación se hizo en una copia temporal dentro del contenedor (`/tmp/auditor-f2`), que se borró al terminar.

## 1. Corridas

Los logs están en `C:\Users\luis_\AppData\Local\Temp\claude\c--laragon-www-GestorDep\e68a7674-79b5-488a-a36d-1a5b9a3e3533\scratchpad\f2-*.txt`.

| Corrida | Resultado |
|---|---|
| `make levantar` | Código 0 |
| Pest: `ContratoDePlantillasYUmbralesTest`, `PlantillasYUmbralesTest` y `CapturaDeRendimientoTest` | `78 passed (617 assertions)` |
| Pint `--test` sobre los dos archivos PHP del diff | `PASS … 2 files` |
| PHPStan sobre los dos archivos PHP del diff | `[OK] No errors` |
| Mutación: el decorador de `RepositorioDeUmbrales` se construye pero no se registra en el contenedor | `1 failed`: la prueba de contrato falla. El 409 `conflicto` depende de la carrera forzada, no de otra cosa |
| `make bajar` | Código 0 |

No se ejecutaron `make verificar` ni `make mutacion` completos sobre `ed6ac4b`. El FIX-2 no toca `src/*/Dominio/`, así que no cambia el número de mutantes. La suite completa queda para la integración continua, que ya era condición.

## 2. Verificaciones pedidas

### C1. Referencias a la regla vieja de DA-13

| Sitio | En `ed6ac4b` | Estado |
|---|---|---|
| `openapi.yaml:3949-3952`, `GET /partidos/pendientes-de-rendimiento` | «al que el entrenador que consulta no le ha registrado rendimiento; los rendimientos de otro entrenador no cuentan (v1.0.8, DA-36, DD-17)». La única mención de DA-13 en el archivo (línea 2761) es la DA-13 de H3 (entrenar un equipo) | **Cumplido** |
| `ConsultaDePendientesDeRendimientoSql.php:17-22`, comentario de la clase | «a los que el entrenador que consulta no les ha registrado un rendimiento […] Lo que capture otro entrenador no cuenta ni se revela (v1.0.8, DA-36, DD-17)». Ya coincide con el método `pendientes` y con el filtro por `registrado_por` | **Cumplido** |
| `f1-h4-decisiones-del-agente.md:21`, resumen | DA-13 aparece tachada, con «**Sustituida por DA-36**», y su privacidad corregida a «Sí: revelaba que otro entrenador había capturado (m-7)» | **Cumplido** |
| `f1-h4-decisiones-del-agente.md:30`, fila de DA-36 | Existe: «Un rendimiento por partido, jugador y registrante (DD-17) […] Sustituye a DA-13», privacidad «Sí», «Indicada por el responsable […]; DD-17 ampliada el 31/10/2026» | **Cumplido** |

**Búsqueda complementaria:** busqué «cualquier rendimiento», «ningún rendimiento» y «E1 no puede capturarlo» en la especificación, `openapi.yaml`, la trazabilidad, el registro, `backend/src`, `backend/tests` y el reporte de cierre. Solo aparece en `registro-decisiones.md:198` y en el detalle de DA-13 (`f1-h4-decisiones-del-agente.md:122-128`). Los dos están marcados como sustituidos y se conservan como historia, así que no son referencias vigentes.

### C2. DD-17 aprobada y citada en DA-36

- **DD-17** (`registro-decisiones.md:28`) agrega:
  - «Dos entrenadores del mismo equipo pueden registrar, cada uno, su propia calificación de un mismo jugador en un mismo partido. Cada entrenador ve y promedia solo lo que él registró (RN-14). La unicidad de `rendimientos_partido` es por (`partido_id`, `jugador_id`, `registrado_por`). Ver DA-36»;
  - en el estado: «La redacción sobre el rendimiento la aprobó el responsable el 31/10/2026 (H4, DA-36, especificación v1.0.8)».
- **DA-36** (`f1-h4-decisiones-del-agente.md:272`) tiene una línea «Fuente» que cita DD-17 con el mismo texto, palabra por palabra. Lo comparé con el de `registro-decisiones.md:28`.
- **Registro** (`registro-decisiones.md:221`): DA-36 dice «Aplica DD-17 (redacción aprobada el 31/10/2026)».
- **Estado:** **cumplido.** M-2 de la auditoría anterior queda resuelto: la regla de captura por entrenador ya tiene respaldo escrito. El repositorio no tiene otro registro de la aprobación que este texto. Lo da por bueno el responsable que pidió esta revisión.

### Encabezado de `registro-decisiones.md` §6

En la línea 182:
- cita la v1.0.8;
- separa las dos razones: «**DA-28 queda retirada** (F1-H4-FIX del Lote B: no hay fuente en `docs/requisitos.md`). **DA-13 queda sustituida por DA-36** (F1-H4-FIX de la auditoría del Lote C, especificación v1.0.8)».

**Estado:** **cumplido**. Ya no hay atribución cruzada.

### Prueba de contrato del 409 `conflicto`

- **Prueba:** `ContratoDePlantillasYUmbralesTest.php:59-75`.
  - Reemplaza `RepositorioDeUmbrales` por un decorador cuyo `vigenteDe` devuelve `null` y cuyo `reemplazar` delega en el real. La interfaz tiene solo esos dos métodos, así que el decorador está completo.
  - El PUT de E1 sobre F1, que ya tiene un umbral vigente creado antes en la misma prueba, choca con la UQ parcial del umbral vigente.
  - Espera 409 `conflicto` y valida la respuesta contra el esquema de `PUT /equipos/{equipo}/jugadores/{jugador}/umbral`, cuyo 409 documenta `conflicto` (`openapi.yaml:4517`).
- **Corrida:** pasa.
- **Mutación:** sin el decorador, falla (§1).
- **Estado:** **cumplido**. El resto de m-2 de la auditoría anterior queda resuelto.

### Nota del reporte de T05

En `f1-h4-t05.md:7`, la oración «Es la única ruta tocada, más este reporte y el progreso; no hay código de producción» volvió al párrafo de T05, fuera de la cita. La nota de m-4 conserva su contenido sin cambios.

**Estado:** **cumplido**.

## 3. Hallazgos

### Bloqueantes

Ninguno.

### Mayores

Ninguno.

### Menores

**m-1. El reporte de cierre no incluye DA-36 entre las decisiones de privacidad.**
- **Dónde:** `docs/reportes/f1-h4-reporte.md:149-158`. El archivo está en el diff de `ed6ac4b`.
- **Evidencia:**
  - La línea 147 ya dice «DA-01 a DA-36» y que DA-13 está sustituida.
  - La lista «Las que tocan permisos o privacidad son:» sigue terminando en DA-29, sin DA-36.
  - DA-36 está marcada «Sí» en privacidad en el resumen (`f1-h4-decisiones-del-agente.md:30`) y en el registro (`registro-decisiones.md:221`).
- **Impacto:** documental; no afecta el comportamiento.

**Fuera del alcance de este diff:**
- La diferencia entre `docs/arquitectura.md` §7.6 (UQ por `partido_id` y `jugador_id`) y el código sigue abierta. El FIX-2 no la toca y está pendiente del F1-DOC que debe confirmar el responsable.
- La corrida de la integración continua tras el push sigue pendiente.

## 4. Veredicto

**Aprobado.** Las cinco verificaciones pedidas se cumplen:

- C1: no quedan referencias vigentes a la regla vieja de DA-13, y DA-36 tiene su fila;
- C2: DD-17 está ampliada, con fecha de aprobación, y DA-36 la cita palabra por palabra;
- el encabezado del registro está corregido;
- el 409 `conflicto` está bajo contrato, con una prueba que depende de verdad de la carrera;
- la nota de T05 está ordenada.

El menor m-1 puede corregirse junto con el F1-DOC de la arquitectura, a criterio del responsable. Siguen pendientes, como antes de este FIX, la corrida de la integración continua y la decisión sobre `docs/arquitectura.md` §7.6.
