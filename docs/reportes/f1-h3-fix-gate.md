# Reporte del FIX de la compuerta: `make verificar` sin Infection; mutación en `make mutacion`

**Commits:**

- `a15493d` (`F1-H3-FIX: make verificar sin Infection; mutación en make mutacion`);
- `4b86c86` (`F1-H3-FIX: quita un ?? que PHPStan marca en la prueba de mutación`): corrige un error que la primera corrida de verificación detectó (ver abajo).

**Aprobación:** opción (a) del responsable, 29/10/2026. Enmienda a DD-53.

## Qué cambia

| Archivo | Cambio |
|---|---|
| `backend/composer.json` | `verificar` ya no corre `vendor/bin/infection` ni `bin/verificar-mutacion.php`. Script nuevo `mutacion`: `Composer\Config::disableProcessTimeout`, `vendor/bin/infection --no-progress --with-uncovered` y `bin/verificar-mutacion.php build/infection/resumen.log`. Infection sigue en `require-dev` |
| `Makefile` | Objetivo `mutacion` (`docker compose exec -T app composer mutacion`), en `.PHONY` y con un comentario |
| `.github/workflows/verificar.yml` | Paso nuevo `Mutacion` (`composer mutacion`) después de `Verificar`; el comentario de la cabecera lo nombra |
| `docs/decisiones/registro-decisiones.md` | Enmienda a DD-53 debajo de la tabla de §1. El texto de DD-53 no se tocó |
| `docs/hitos/f1-h3.md` | Versión 1.0.2: §0 (la mutación corre al cerrar cada lote) y §10, punto 1. Fila nueva en el control de cambios |
| `backend/tests/Unit/Herramientas/VerificarMutacionTest.php` | **Fuera de lo descrito; autorizado por el responsable.** La prueba M-10 a de H2 ahora lee `scripts.mutacion` en lugar de `scripts.verificar`, con la misma exigencia: Infection seguido del verificador con la ruta del resumen. Prueba nueva: `verificar` ya no corre Infection ni el verificador, y existe `mutacion` |

**Sin cambios:** `infection.json5` y los umbrales (MSI ≥ 80 %, MSI cubierto ≥ 85 %, que viven en `infection.json5`; ninguno omitido ni con tiempo agotado, que comprueba `bin/verificar-mutacion.php`), PHPStan nivel 7 y la cobertura por capa.

## Comprobaciones antes del commit

- **Sintaxis del workflow:** se leyó con `Symfony\Component\Yaml\Yaml::parse` dentro del contenedor. Resultado: `YAML valido; pasos: Roles y base de pruebas | Dependencias | Configuracion de la aplicacion | Migraciones (fresh y rollback) | Verificar | Mutacion`.
- **`composer validate`:** `./composer.json is valid`.
- **`make mutacion` corre localmente:** es la corrida de la tabla siguiente. El código no cambió después (`4b86c86` solo toca una prueba de herramientas), así que es también la única corrida del paso 3.
- **Pruebas:** las de `Unit/Herramientas` pasaron (30). La versión nueva de M-10 a y la prueba nueva fallaron primero (`2 failed, 9 passed`), porque aún no existía el script `mutacion`.

## `make mutacion`

Una corrida, sin nada más corriendo; se revisaron procesos en `app`, `worker`, `scheduler` y Windows.

| Etapa | Llegó al segundo | Resultado |
|---|---:|---|
| Infection (Pest de base, generación y ejecución de mutantes) | 166 | `501 mutants were killed by Test Framework`; MSI 100 %; Mutation Code Coverage 100 %; Covered Code MSI 100 %; `Time: 2m 37s` |
| `bin/verificar-mutacion.php` | 166 | `Mutación completa: 501 mutantes evaluados, ninguno omitido ni con tiempo agotado.` |
| **Total** | **166 s (2 min 46 s)** | código 0 |

Infection tardó más sola (157 s) que dentro de `make verificar` en T07 (114 s), aunque el código no cambió. Puede deberse a que entonces Pint y PHPStan ya habían calentado el contenedor.

## `make verificar`

**Primera corrida (falló en 24 s, por un error mío):** PHPStan marcó `nullCoalesce.offset` en la prueba que cambié: `$composer['scripts']['mutacion'] ?? []` sobra, porque el tipo declarado dice que la clave existe. No corrí PHPStan después de editar la prueba. Se corrigió en `4b86c86` quitando el `?? []`; no cambia lo que la prueba exige. Antes de relanzar pasaron Pint (`--test`), PHPStan y la prueba.

**Segunda corrida (pasa).** Sin nada más corriendo; se revisaron procesos antes. Cada línea de la salida lleva el segundo en que llegó.

| Etapa | Llegó al segundo | Duración | Resultado |
|---|---:|---:|---|
| Arranque de `docker compose exec` y Pint | 8 | 8 s | `PASS … 584 files` |
| PHPStan nivel 7 | 11 | 3 s | `[OK] No errors` |
| Pest con cobertura, hasta el resumen | 640 | 629 s (`Duration: 627.77s`) | **1 203 pruebas** (7 767 aserciones) |
| Escritura del informe de cobertura y cobertura por capa | 681 | 41 s | Dominio 100,0 % · Aplicación 100,0 % · Infraestructura y HTTP 99,4 % |
| `composer audit` | 681 | < 1 s | sin avisos |
| **Total** | | **681 s (11 min 21 s)** | código 0 |

**Frente a lo esperado (~630 s):** el total quedó 51 s por encima. Infection ya no corre (en T07 eran 114 s), pero Pest tardó 629 s, contra 577 s en T07, con solo 5 pruebas más (1 203 contra 1 198). Las pruebas nuevas del FIX son pocas y rápidas, así que la diferencia parece variación del entorno: el contenedor llevaba más de 5 horas arriba y venía de una corrida de Infection. H2 documentó variaciones de hasta 1,8 veces con el mismo código. Holgura frente a 15 min: 3 min 39 s.

## Lo que no se hizo

- No se cambió `infection.json5` ni ningún umbral.
- La integración continua no se comprobó: no hay push. El paso nuevo se validó solo por su sintaxis.
