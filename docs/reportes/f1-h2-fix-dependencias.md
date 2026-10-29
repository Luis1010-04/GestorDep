# Reporte F1-H2-FIX: actualiza symfony/mime por CVE-2026-45070

**Commit:** `9d931b6` (`F1-H2-FIX: actualiza symfony/mime por CVE-2026-45070`). Autorizado por el responsable tras la falla de `composer audit` en la integración continua sobre `cc66784`.

## Aviso

`composer audit` dentro del contenedor, antes del cambio:

```
Found 1 security vulnerability advisory affecting 1 package:
| Package           | symfony/mime                                                                     |
| Severity          | medium                                                                           |
| Advisory ID       | PKSA-wtxr-p26d-nn42                                                              |
| CVE               | CVE-2026-45070                                                                   |
| Title             | CVE-2026-45070: Email Header Injection via Non-Token Characters in Mime          |
|                   | Parameter Names                                                                  |
| Affected versions | … |>=8.0.0,<8.1.0|>=8.1.0,<8.1.10                                                  |
| Reported at       | 2026-10-28T18:08:54+00:00                                                        |
```

`composer show symfony/mime`: `versions : * v8.1.7` (afectada).

## Cambio

`composer update symfony/mime -W` dentro del contenedor. Paquetes que cambiaron de versión en `composer.lock`:

| Paquete | Antes | Después | Motivo |
|---|---|---|---|
| `symfony/mime` | v8.1.7 | **v8.1.10** | Versión corregida de la rama 8 (CVE-2026-45070) |
| `symfony/polyfill-mbstring` | v1.43.0 | v1.43.3 | Dependencia de `symfony/mime` arrastrada por `-W` |

Ningún otro paquete cambió (`git diff --stat backend/composer.lock`: 12 líneas cambiadas y 12 quitadas, solo esos dos bloques).

- **No** cambiaron Laravel, PHP ni ninguna restricción: `composer.json` quedó idéntico (comparado byte a byte con la copia previa). No hubo condición de parada (§0.4).
- **No** se añadieron ignorados a la configuración de `composer audit` ni se quitó el paso del script `verificar`.

## `make verificar`

Una corrida completa, sin nada más corriendo:

| Etapa | Hasta | Resultado |
|---|---:|---|
| Pint | 13 s | `PASS ... 406 files` |
| PHPStan nivel 7 | 22 s | `[OK] No errors` |
| Infection | 112 s | 362 eliminados; MSI 100 %; MSI cubierto 100 % |
| Verificación de mutación | 113 s | `362 mutantes evaluados, ninguno omitido ni con tiempo agotado` |
| Pest con cobertura | 550 s | **889 pruebas** (5358 aserciones), `Duration: 437.07s` |
| Cobertura por capa | 579 s | Dominio 100,0 %, Aplicación 100,0 %, Infraestructura y HTTP 99,7 % |
| `composer audit` | 579 s | **`No security vulnerability advisories found.`** |
| **Total** | **579 s (9 min 39 s)** | **pasa** |

La corrida fue más lenta que las del Lote D (469 a 511 s) con el mismo número de pruebas: Pest tardó 437 s frente a 357 a 379 s. Es la variabilidad del entorno ya documentada (`f1-h2-medicion-verificar.md`). Sigue 5 min 21 s por debajo del límite de 15 min.

## Lo que no se hizo

- No se actualizó ningún otro paquete, ni se hizo push, PR o etiqueta.

## Desviaciones

- Ninguna.
