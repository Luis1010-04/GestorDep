# F1-H6: plan de DA-101, Pest en paralelo

**Fecha:** 14 de noviembre de 2026
**Rama y commit de partida:** `hito/f1-h6-especificacion`, `6a10ce0`
**Estado:** plan para aprobación. No se ha tocado código ni configuración.
**Origen:** DA-81 y DA-82 de H5 (`docs/reportes/f1-h5-decisiones-del-agente.md`), cuya tarea se registra en H6 como DA-101 (`docs/hitos/f1-h6.md` §3.1); medición de H5 en `docs/reportes/f1-h5-medicion-verificar.md`.

## 1. Qué se busca

Bajar el tiempo de `make verificar` antes de que H6 le agregue pruebas, sin cambiar qué se verifica: las mismas pruebas, los mismos umbrales de cobertura, el mismo candado.

Punto de partida, medido en H5:

| Dato | Valor |
|---|---|
| Total de `make verificar` | 742 s en la medición por etapas; entre 713 y 868 s en las corridas de cierre |
| Etapa de Pest con cobertura | 718 s, el 97 % del total |
| Núcleos que usa Pest | 1 de 8 disponibles en el contenedor |
| Límites vigentes | aviso a 780 s, alto a 900 s, tope del candado a 1 200 s |

## 2. Qué se paraleliza

**Solo la etapa de Pest de `composer verificar`**, por procesos, con `pest --parallel`. Cada proceso ejecuta archivos de prueba completos.

- No hace falta ninguna dependencia nueva: `brianium/paratest` v7.20.0 ya está en `composer.lock`, como dependencia de Pest 4.7.8.
- No se paralelizan Pint, PHPStan ni `composer audit`: suman 21 s.
- `make mutacion` no cambia: Infection ya corre con 4 hilos y solo ejecuta `tests/Unit`, que no usa la base.
- `make pruebas` (Pest sin cobertura) queda en serie, como hoy. Se puede paralelizar después con el mismo mecanismo si lo pides.

### 2.1 El problema: todas las pruebas comparten una base

Las 109 pruebas de `Feature`, `Integration` y `Arch` usan una sola base, `gestordep_pruebas`, y la limpian entre pruebas con `tests/Ayudantes/EsquemaDePrueba.php` (vaciado de tablas, y `migrate:fresh` cuando una prueba hace DDL). Dos procesos sobre esa base se borrarían los datos entre sí.

### 2.2 La solución: una base por proceso

Cada proceso de paratest recibe la variable `TEST_TOKEN` (1, 2, 3…). Con ella:

- `tests/TestCase.php` apunta las conexiones `pgsql` y `propietario` a `gestordep_pruebas_N`. Sin `TEST_TOKEN` (corrida en serie), se usa `gestordep_pruebas`, igual que hoy.
- `EsquemaDePrueba.php` no cambia: su estado es por proceso, así que cada proceso migra su base una vez y después solo vacía datos.

Las bases `gestordep_pruebas_1` a `gestordep_pruebas_8` las crea el superusuario, con el mismo dueño y los mismos permisos que `gestordep_pruebas`. El rol propietario no tiene permiso de crear bases, y **no se le da**: la separación de roles de H0 queda intacta.

Alternativas descartadas:

| Alternativa | Por qué no |
|---|---|
| Dar `CREATEDB` al rol propietario para que cada proceso cree su base | Amplía los privilegios de un rol que hoy solo migra |
| Un esquema de PostgreSQL por proceso en la misma base | `EsquemaDePrueba.php` y las migraciones dan por hecho el esquema `public`; habría que reescribir la huella de estructura y el vaciado |
| Transacciones que se revierten por prueba | Varias pruebas necesitan commits reales: disparadores, `afterCommit`, concurrencia y dos roles de base |

### 2.3 Número de procesos

Se elige midiendo (§5), entre 2, 4 y 6. El valor queda fijo en `composer.json`, no automático, porque las bases deben existir de antemano. La máquina tiene 4 núcleos físicos y la base de datos compite por ellos, así que espero que el mejor valor sea 4; no lo doy por hecho.

Conexiones: cada prueba abre a lo sumo dos y las cierra al terminar (`TestCase::tearDown`). Con 8 procesos son 16 simultáneas, lejos del límite por defecto de 100 de PostgreSQL.

## 3. Cobertura

Los umbrales por capa (95, 85 y 70 %) y `bin/verificar-cobertura.php` **no se tocan**. Paratest reúne la cobertura de todos los procesos y escribe un solo Clover y un solo HTML en las mismas rutas; el verificador lee y borra ese Clover como hoy (PT-02).

Riesgo concreto que hay que comprobar: hoy pcov recibe `-d pcov.directory=.` en la línea de comandos. Si los procesos hijos no heredan esa opción, pcov usaría su directorio por defecto y podría dejar `app/` fuera del reporte. No sé todavía si paratest la propaga; se comprueba en la primera corrida. Si no lo hace, la opción se fija en la configuración de PHP del contenedor y de CI en lugar de la línea de comandos.

**Criterio de aceptación de la cobertura** (enmendado el 15/11/2026 por decisión del responsable): en el mismo commit, la corrida en serie y la corrida en paralelo deben dar **las mismas pruebas y las mismas líneas de cobertura** (ejecutables y cubiertas, por capa), **con una aserción adicional por proceso por la reconstrucción del esquema**. Cualquier otra diferencia se investiga hasta explicarla; no se acepta «casi igual» ni se ajusta un umbral.

Por qué cambia el número de aserciones: `esquemaReconstruir()` (`tests/Ayudantes/EsquemaDePrueba.php`) ejecuta `migrate:fresh` y comprueba que termina bien, y esa comprobación cuenta como una aserción. En serie ocurre una vez al empezar; en paralelo, una vez en cada proceso. Medido sobre las mismas 33 pruebas de `tests/Feature/Identidad`: 138 aserciones en serie, 139 con 2 procesos y 140 con 3. El total puede variar además en una o dos según cómo se repartan los archivos que hacen DDL, porque una reconstrucción pendiente al final de un proceso no llega a ejecutarse. No se cambió código de prueba para igualar el número.

## 4. Candado de la base

`bin/con-candado.sh` no cambia de mecanismo:

- **Candado:** sigue siendo uno por contenedor. Ahora protege el conjunto `gestordep_pruebas` y `gestordep_pruebas_1` a `_8`. Los procesos de paratest heredan el descriptor del candado, que sigue tomado mientras viva cualquiera de ellos.
- **Vigilante:** termina el grupo de procesos del comando. Hay que comprobar que los procesos de paratest quedan en ese grupo: se repite la prueba de PT-07 (cortar el cliente de `docker compose exec` a media corrida) y se confirma que no sobrevive ningún proceso y que el candado se libera.
- **Tope de 20 minutos:** sin cambio.
- **`make verificar`, `make pruebas` y `make mutacion`** siguen excluyéndose entre sí con el código 75.

Solo se actualiza el comentario del guion, para decir que el candado cubre también las bases por proceso.

## 5. Medición antes y después

Mismo método que la medición de H5, para que los números sean comparables.

| Aspecto | Cómo |
|---|---|
| Commit | El mismo para «antes» y «después»; la única diferencia es el modo paralelo |
| Máquina | La misma laptop, mismo Docker, contenedores sin límite de CPU ni memoria |
| Condiciones | Nada más corriendo; se anota la CPU del anfitrión y la carga de la VM antes y después de cada corrida |
| Antes | 3 corridas completas de `make verificar` en serie |
| Después | 3 corridas completas por cada candidato (2, 4 y 6 procesos) |
| Qué se anota | Tiempo total, tiempo de la etapa de Pest, pruebas, aserciones, cobertura por capa y código de salida |
| Qué se informa | Mediana y rango de cada serie, no la mejor corrida |
| Logs | `docs/reportes/logs/medicion-verificar/`, con fecha y hora en el nombre; la ruta va en el reporte |

Son unas 12 corridas; con las de serie a unos 13 minutos, la medición ocupa cerca de una hora y media de máquina. El rango de las tres corridas en serie sirve además para DA-82 (lentitud variable).

**Propuesta de criterio de éxito, para que la confirmes o la cambies:**

1. Las 3 corridas del valor elegido pasan con código 0.
2. Mismas pruebas y líneas de cobertura que en serie, con una aserción adicional por proceso por la reconstrucción del esquema (§3, enmendado el 15/11/2026).
3. La mediana del total queda por debajo de 450 s. El número es mío: deja margen bajo el aviso de 780 s para lo que agregue H6. Si no se alcanza, se informa el número real y decides.

## 6. Pruebas que pueden fallar solo en paralelo

Se revisan antes de medir y se corrigen de raíz si fallan:

- `tests/Feature/EntornoDePruebasTest.php` afirma que la base se llama exactamente `gestordep_pruebas`. Hay que cambiarla para que acepte `gestordep_pruebas` o `gestordep_pruebas_N` y siga rechazando la base de desarrollo. **Es un cambio a una prueba existente y necesita tu aprobación explícita**; no la debilita: sigue comprobando que nunca se usa otra base.
- Pruebas de `tests/Unit/Herramientas/` que escriben archivos: usan `tempnam`, con nombre único; no deberían chocar.
- Pruebas que hacen DDL o `migrate:fresh` (24 archivos): cada proceso tiene su base, así que no se afectan entre sí.

Encontrado al ejecutar (15/11/2026, aprobado por el responsable como DA-104): dos funciones de ayuda estaban definidas dentro de un archivo de prueba y otros archivos las usaban, `parametrosDePrueba()` y `decimal()`. En paralelo fallaban 27 pruebas con «Call to undefined function». Se trasladaron, sin cambiar su lógica, a `tests/Ayudantes/DominioDePrueba.php`, que `tests/Pest.php` carga siempre.

Una prueba que falle solo en paralelo **no se omite, no se reintenta y no se marca como en serie sin avisarte**: se reporta con su causa.

## 7. Archivos que se tocarían

Esta tarea no está en `docs/hitos/f1-h6.md`, así que estas rutas necesitan tu aprobación:

| Archivo | Cambio |
|---|---|
| `backend/composer.json` | El paso de Pest de `verificar` agrega `--parallel --processes=N` |
| `backend/tests/TestCase.php` | Elegir la base según `TEST_TOKEN` |
| `backend/tests/Feature/EntornoDePruebasTest.php` | Aceptar la base del proceso (§6) |
| `backend/tests/Unit/Herramientas/**` | Prueba que fija la forma del paso de Pest (paralelo, cobertura y Clover), como ya se hace con `mutacion` |
| `backend/tests/Ayudantes/DominioDePrueba.php` (nuevo) | Las funciones de ayuda `parametrosDePrueba()` y `decimal()`, trasladadas sin cambiar su lógica (DA-104) |
| `backend/tests/Pest.php` | Una línea que carga ese archivo (DA-104) |
| `backend/tests/Unit/Academico/ParametrosDeRiesgoTest.php` | Pierde la definición de `parametrosDePrueba()` y los dos `use` que solo ella usaba (DA-104) |
| `backend/tests/Unit/Compartido/NumeroDecimalTest.php` | Pierde la definición de `decimal()` (DA-104) |
| `backend/docker/db/init/01-roles-y-bases.sh` y un guion idempotente junto a él | Crear las bases por proceso |
| `Makefile` | Una línea en `levantar` que ejecuta ese guion (ver decisión 2) |
| `.github/workflows/verificar.yml` | El paso «Roles y base de pruebas» crea también las bases por proceso |
| `backend/bin/con-candado.sh` | Solo el comentario |
| `docs/estrategia-pruebas.md` §2 | Una línea: Pest corre en paralelo, una base por proceso |
| `docs/reportes/f1-h5-decisiones-del-agente.md` y `docs/decisiones/registro-decisiones.md` | Estado de DA-81 (H5) y de DA-101 |
| `docs/reportes/f1-h6-da101.md` y logs | Reporte y medición |

No se tocan `phpunit.xml`, `infection.json5`, `bin/verificar-cobertura.php`, `EsquemaDePrueba.php` ni ningún archivo de `src/` o `app/`.

## 8. Orden de trabajo

1. Medición «antes»: 3 corridas en serie en `6a10ce0`.
2. Pruebas primero: la de `EntornoDePruebasTest` y la de Herramientas, y ver que fallan por la razón correcta.
3. Bases por proceso (guion, `Makefile`, CI) y selección de base en `TestCase`.
4. Primera corrida en paralelo: comparar pruebas, aserciones y cobertura con la de serie. Aquí se resuelve la duda de pcov (§3).
5. Prueba del vigilante del candado (§4).
6. Medición «después» con 2, 4 y 6 procesos; elegir el valor.
7. `make verificar` y `make mutacion` finales, commit, reporte, y alto.

## 9. Cuándo me detengo

- La cobertura en paralelo no coincide con la de serie y no logro explicarlo y corregirlo.
- Una prueba falla solo en paralelo y la única salida sería debilitarla u omitirla.
- El vigilante no termina los procesos de paratest.
- Hace falta una dependencia nueva o tocar un archivo fuera de §7.

## 10. Cómo se revierte

Con un `git revert` del commit: `verificar` vuelve a correr en serie sobre `gestordep_pruebas`. Las bases por proceso quedan vacías y no estorban.

## 11. Decisiones que necesito

1. **Aprobación del plan** y de las rutas de §7, incluido el cambio a `EntornoDePruebasTest`.
2. **Cómo se crean las bases en un entorno que ya existe.** El guion de inicio de PostgreSQL solo corre al crear el volumen. Opciones: (a) un guion idempotente que `make levantar` ejecuta siempre, y que crea solo las bases que falten; (b) borrar el volumen de la base y volver a levantar, lo que elimina los datos locales de desarrollo. Recomiendo (a).
3. **Criterio de éxito** de §5, en particular los 450 s.
4. **Formato del commit.** La tarea no tiene número en la especificación. Propongo `F1-H6-T00: Pest en paralelo (DA-101)`, como las T00 de H4 y H5.
5. **Rama.** Propongo trabajar en `hito/f1-h6-especificacion`, donde ya está la especificación. Si prefieres una rama aparte para DA-101, dime el nombre.
