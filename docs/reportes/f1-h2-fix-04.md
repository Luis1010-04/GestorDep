# Reporte F1-H2-FIX: reinicio de las pruebas por `DELETE` en lugar de `TRUNCATE`

**Commit:** `F1-H2-FIX: reinicio de pruebas por DELETE en lugar de TRUNCATE` (ver `git log`). Decisión 1 del responsable tras el Lote B.

## Qué se encontró

El mecanismo de `tests/Ayudantes/EsquemaDePrueba.php` **ya vaciaba tablas** en lugar de reconstruir el esquema (`migrate:fresh` solo ocurre una vez por ejecución o tras DDL). El costo no estaba en rehacer el esquema sino en cómo se vaciaba: `TRUNCATE ... CASCADE` tarda ~0,5 s por tabla con filas en el volumen de Docker (crea archivos nuevos y los sincroniza).

Medición con una prueba temporal (no commiteada), en la misma base:

| Paso de `esquemaReiniciar()` | Antes | Después |
|---|---:|---:|
| Vaciar 4 tablas con filas (usuarios, roles, periodos…) | 1 590 a 2 170 ms (`TRUNCATE`) | 1 ms (`DELETE`) |
| `esquemaReiniciar()` con el mundo de jugadores y una fila de historial | ~1 600 ms | 22 a 25 ms |
| Contratación, hash y petición | 1,3 ms de hash (rondas = 4), ~10 a 20 ms por petición | sin cambio |

## Qué se cambió

Solo `tests/Ayudantes/EsquemaDePrueba.php` (más una prueba nueva):

- `esquemaVaciarDatos()` llama a `esquemaBorrarFilas()`: `DELETE` de las tablas con filas en orden de claves foráneas (hijas antes que padres, calculado con `esquemaOrdenDeBorrado()` a partir de `pg_constraint`), con los disparadores de solo inserción (`DISABLE TRIGGER USER`) de esas tablas desactivados y reactivados en **la misma transacción**. El rol propietario no es superusuario, así que `session_replication_role` no está disponible.
- Si las claves foráneas formaran un ciclo, vuelve a `TRUNCATE` (no ocurre con el esquema actual; está probado).
- El reinicio de secuencias, el reconocimiento de DDL sucio y la huella de estructura no cambian.
- **No se cambió ninguna aserción.** Las 4 pruebas de `ReinicioDeEsquemaTest` (el reinicio rápido deja la misma base que `migrate:fresh`: filas, índices, disparadores, funciones, restricciones, permisos y secuencias; el historial sigue siendo de solo inserción) pasan sin modificación.
- Nuevas: `tests/Integration/Compartido/OrdenDeBorradoTest.php` (4 pruebas: orden hijas-primero, clave hacia sí misma o hacia tabla sin filas, ciclo → nulo, y el orden calculable sobre las claves foráneas reales).

## Medición por etapa

Una corrida por etapa, sin otros procesos. «Antes» es la corrida de T07 (663 pruebas; el detalle por etapa de las demás está en `f1-h2-medicion-verificar.md`); «después» tiene 667 pruebas.

| Etapa | Antes (T07) | Después |
|---|---:|---:|
| Pint | 6 s | 5 s |
| PHPStan nivel 7 | 6 s | 2 a 6 s |
| Infection | 56 s | 59 a 65 s |
| Pest con cobertura | **725 s** | **275 a 295 s** (una corrida aislada tardó 495 s) |
| Verificación de cobertura | 1 s | 1 s |
| Auditoría | 3 s | 4 a 6 s |
| **`make verificar` completo** | **837 s (13 min 57 s)** | **425 s (7 min 5 s)** |

- Reducción: **412 s (6 min 52 s)**, un 49 %. Supera el mínimo de 2 min exigido y la meta de 10 min se cumple con holgura (2 min 55 s por debajo).
- Pruebas: 667 pasan (3348 aserciones). Cobertura: Dominio 100 %, Aplicación 100 %, Infraestructura y HTTP 99,9 %. Infection: 323 mutantes eliminados, MSI 100 %. PHPStan nivel 7 y auditoría limpios.
- **Variabilidad:** la etapa de Pest dio 264/275/280/295 s en cuatro corridas y 495 s en una (la tercera medición por etapas, sin causa conocida: no hubo procesos míos en paralelo; puede ser carga del equipo anfitrión). La compuerta `make verificar` de este commit, la que cuenta, tardó 425 s con Pest en 294,8 s. Conviene volver a medir en el cierre de C2, como se acordó.
- No se tocó Infection ni el límite de 15 minutos.

## Lo que no se hizo

- No se aceleró la primera petición de cada proceso (~1,1 s de arranque en frío) ni los ~20 ms por petición: no son el cuello de botella.

## Fuera de alcance

- `docker-compose` con `fsync=off` en la base de pruebas habría dado un efecto similar, pero toca infraestructura fuera de las rutas permitidas.

## Desviaciones

- La instrucción decía «vaciando tablas en lugar de rehacer el esquema, con el mismo mecanismo»; el mecanismo ya hacía eso, de modo que el cambio fue de `TRUNCATE` a `DELETE`. Se deja constancia por si la premisa era otra.
