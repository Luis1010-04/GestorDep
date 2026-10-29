# Reporte F1-H2-FIX: `make verificar` falla si Infection omite o descarta mutantes por tiempo

**Commit:** `F1-H2-FIX: verificar falla si Infection omite mutantes por tiempo` (ver `git log`). Pedido del responsable tras T10.

## Qué pasaba

En una corrida de T08, Infection informó «50 mutants required more time than configured» y `make verificar` **pasó**. Cómo funciona Infection (0.35.8, `vendor/infection/infection/src/Process/Runner/MutationTestingRunner.php`):

- Un mutante se marca **`Skipped`** (el mensaje «required more time than configured») cuando el *tiempo nominal* de las pruebas que lo cubren, medido en la corrida inicial, es mayor o igual al `timeout` configurado (antes 20 s). No se ejecuta.
- Los `Skipped` no cuentan en el MSI, y el MSI se calcula sobre los evaluados: con 273 evaluados y 50 omitidos, el MSI seguía en 100 % y el paso de mutación salía con código 0. Los mutantes que sí agotan el tiempo aparecen como `Timed Out` y tampoco hacen fallar la ejecución.
- Solo falla si **todos** se omiten (el MSI baja a 0 %).

Es decir, `verificar` podía dar por buena una mutación en la que una parte del dominio nunca se evaluó.

## Cambios

- `backend/bin/verificar-mutacion.php` (nuevo): lee el resumen que Infection ya escribe (`build/infection/resumen.log`) y falla si `Timed Out` o `Skipped` es mayor que 0, si no hay mutantes (`Total` = 0), si el resumen no existe o si faltan los contadores. Mensaje de fallo con las cantidades.
- `backend/composer.json`: el script `verificar` ejecuta la herramienta justo después de Infection.
- `backend/infection.json5`: el límite explícito pasa de 20 a **60 s** con un comentario que explica su semántica: las pruebas de dominio tardan décimas de segundo, de modo que 60 s solo se alcanza ante una anomalía, y entonces la herramienta hace fallar `verificar`. No se tocó ningún umbral de MSI.
- `backend/tests/Unit/Herramientas/VerificarMutacionTest.php` (7 pruebas): limpio pasa; un `Timed Out` falla; 50 `Skipped` fallan; ambos se informan; resumen inexistente; contadores ausentes; cero mutantes.

## Antes y después con mutantes forzados

Forzado con una clase temporal y una prueba temporal de 1,5 s (más una clase rápida) y el límite bajado a 1 s en una copia temporal de la configuración; los archivos temporales se borraron y no están en el repositorio.

| | Infection sola (antes) | Infection + `verificar-mutacion.php` (después) |
|---|---|---|
| Mutantes generados | 4 | 4 |
| Evaluados / omitidos | 2 killed / **2 omitidos por tiempo** | igual |
| MSI | 100 % | 100 % |
| **Código de salida** | **0** (el paso pasa) | **1** (`Infection no evaluó todos los mutantes: 2 mutantes fueron omitidos (Skipped)…`) |

Un intento previo con un bucle infinito no produjo `Timed Out` (Infection lo registró como eliminado); por eso la demostración usa el camino `Skipped`, que es el que ocurrió en T08. Si todos los mutantes se omiten, Infection ya falla por sí misma (MSI 0 %), lo que también se comprobó.

## `make verificar`

| Paso | Resultado |
|---|---|
| Pint / PHPStan nivel 7 | pasan |
| Infection | 335 mutantes eliminados; MSI 100 %; la nueva verificación: «335 mutantes evaluados, ninguno omitido ni con tiempo agotado» |
| Pest | **783 pruebas** (3975 aserciones); 273,2 s |
| Cobertura | Dominio 100 %, Aplicación 100 %, Infraestructura y HTTP 99,8 % |
| Auditoría | sin avisos |
| **Duración total** | **381 s (6 min 21 s)** |

## Lo que no se hizo

- No se cambió el MSI mínimo ni se activó `timeoutsAsEscaped` (cambiaría el cálculo del MSI, no el hueco que se cerró).

## Fuera de alcance

- La causa de las ralentizaciones transitorias del entorno sigue sin identificarse; con este cambio una corrida así ya no pasa en silencio, falla y se repite.

## Desviaciones

- Ninguna.
