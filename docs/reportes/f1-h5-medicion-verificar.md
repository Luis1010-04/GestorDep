# F1-H5: medición de tiempos de `make verificar` (DA-57, solo medición)

Rama `hito/f1-h5`, commit `df068a2`, 13/11/2026. Solo se midió: no cambiaron pruebas, código, `Makefile` ni configuración de Docker. Los logs están en `docs/reportes/logs/medicion-verificar/`.

## 1. Entorno

| Dato | Valor |
|---|---|
| Anfitrión | Windows 11, AMD Ryzen 5 7235HS, 4 núcleos físicos y 8 lógicos, 32 GB de RAM (18,7 GB libres antes) |
| VM de Docker | Docker 29.9.2, 8 CPU y 15,5 GB visibles |
| Contenedores | `app`, `db`, `worker` y `scheduler`, **sin límite** de CPU ni de memoria (`NanoCpus=0`, `CpuQuota=0`, `Memory=0`) |
| Contenedor `app` | `nproc` = 8, 14,5 GB disponibles. No tiene `free` ni `uptime`; se usaron `/proc/meminfo` y `/proc/loadavg` |

Logs del entorno: `00-entorno-antes-20261113-0704.log` y `06-entorno-despues-20261113-0717.log`.

## 2. Etapas de `make verificar`

`make verificar` ejecuta `docker compose exec -T app bash bin/con-candado.sh composer verificar`. El script `verificar` de `composer.json` corre, en orden:

1. `pint --test`
2. `phpstan analyse --memory-limit=1G --no-progress`
3. `pest --coverage --coverage-html=build/coverage --coverage-clover=build/coverage/clover.xml`, con pcov y `memory_limit=1G`
4. `bin/verificar-cobertura.php build/coverage/clover.xml`, los umbrales de cobertura
5. `composer audit`

Cada etapa se corrió por separado, en serie y con el mismo candado (`bin/con-candado.sh`), con el guion `backend/build/medir-verificar.sh` (copia versionada: `docs/reportes/logs/medicion-verificar/guion-medir-verificar.sh`). Mientras corría no se ejecutó ningún otro comando.

## 3. Tiempos

| Etapa | Duración | Carga de la VM al empezar → al terminar (1 min, 5 min, 15 min) | Log |
|---|---:|---|---|
| 1. Pint | 13 s | 0,15 0,10 0,21 → 0,34 0,15 0,22 | `01-pint-20261113-0705.log` |
| 2. PHPStan | 5 s | 0,34 0,15 0,22 → 0,39 0,16 0,22 | `02-phpstan-20261113-0705.log` |
| 3. Pest con cobertura | **718 s** (Pest informa 681 s) | 0,39 0,16 0,22 → 1,27 1,16 0,80 | `03-pest-20261113-0705.log` |
| 4. Umbrales de cobertura | 0 s | sin cambio | `04-cobertura-20261113-0705.log` |
| 5. `composer audit` | 3 s | 1,27 1,16 0,80 → 1,17 1,14 0,80 | `05-audit-20261113-0705.log` |
| **Total** | **742 s**, todas con código 0 | | `resumen-20261113-0705.log` |

Pest: 1 971 pruebas y 12 240 aserciones, todas en verde. PHPStan tarda 5 s porque usa su caché persistente.

### Qué domina

- **Pest con cobertura es el 97 % del total** (718 de 742 s). Las otras cuatro etapas suman 21 s.
- **Dentro de la etapa de Pest**, unos 37 s quedan fuera del tiempo que informa Pest (718 − 681). Corresponden al arranque y a escribir los informes HTML y Clover de cobertura.
- **El tiempo no se concentra en pocos archivos.** La suma de los tiempos por prueba da 674 s. Los doce archivos más lentos suman unos 258 s; el primero es `ArquitecturaTest` (42 s) y el segundo `ConfirmacionYEstadoTest` (39 s). El resto se reparte en muchos archivos de 5 a 15 s.
- **Pest usa un solo núcleo.** La carga de la VM sube hasta cerca de 1,2 con 8 CPU disponibles: Pest corre en serie, sin paralelismo, y deja libres unos 7 núcleos. Por eso el tiempo depende sobre todo de la velocidad de un núcleo y de la base de datos, no de cuántos núcleos haya.

## 4. Carga antes y después

| Momento | CPU del anfitrión | Carga de la VM | Contenedores |
|---|---:|---|---|
| Antes (13:04 UTC) | 47 % | 0,02 0,08 0,20 | `app` 0,01 %, `db` 0,72 % |
| Después (13:17 UTC) | 31 % | 0,91 1,08 0,78 | `app` 7,3 %, `db` 0,22 % |

- Antes de empezar, el anfitrión ya tenía un 47 % de CPU ocupado. En la muestra de 2 s, los procesos que más CPU usaban eran VS Code y `PhoneExperienceHost`.
- Durante la corrida no se midió la CPU del anfitrión, para no ejecutar comandos mientras corría.

## 5. Comparación con las corridas anteriores

| Corrida | Pruebas | Pest | Total |
|---|---:|---:|---:|
| Cierre de T06 | 1 927 | 681 s | 733 s |
| Cierre de T07 | 1 946 | 691 s | 745 s |
| Cierre de T08 | 1 958 | 661 s | 713 s |
| C01, primera entrega | 1 966 | 740 s | 794 s |
| C01, contrato del descarte y UUID v7 | 1 967 | 675 s | 755 s |
| C01, DA-76 sustituye a DA-45 | 1 971 | 743 s | 799 s |
| C01, primer cierre de DA-76 | 1 969 | 807 s | 868 s |
| **Esta medición, por etapas** | **1 971** | **681 s** | **742 s** |

- **Las pruebas casi no influyen en el tiempo.** De T06 a hoy la suite creció 44 pruebas (2,3 %). Con el mismo número de pruebas (1 971), Pest tardó 743 s en una corrida y 681 s en esta.
- **Lo que varía es el tiempo de Pest.** Fue de 661 a 807 s (146 s de diferencia) entre corridas casi iguales. Lo que no es Pest se mantiene entre 52 y 80 s.
- **Lo más probable es que la carga de la máquina haya influido.** Pest corre en un solo hilo dentro de una VM que comparte la CPU con Windows, así que cualquier carga del anfitrión lo alarga. La corrida de 868 s empezó justo después de una corrida de mutación en una copia temporal del árbol. La de 755 s fue la primera después de que la computadora se suspendiera y Docker Desktop se reiniciara. Hoy, con un 47 % de CPU del anfitrión ocupado al empezar, Pest tardó 681 s. No se midió la CPU del anfitrión durante las corridas anteriores, así que esto no se puede demostrar; es la explicación compatible con los datos.

## 6. Conclusión para DA-57

- **Dónde está el tiempo:** casi todo es Pest con cobertura, en un solo hilo, con el tiempo repartido entre muchos archivos.
- **Margen:** 742 s quedan por debajo del aviso de 780 s. Por la variación observada (hasta +146 s en Pest), una corrida puede acercarse al alto de 900 s sin que la suite haya crecido.
- **Qué podría ayudar:** con 8 núcleos y sin límites de CPU, correr Pest en paralelo podría reducir el tiempo. Eso es DA-57, una tarea separada para después de cerrar H5. Aquí no se cambió nada.
