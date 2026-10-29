# Reporte F1-H2-FIX: M-6 (§7.6 fila 9 prueba por sí sola «después del commit»)

**Commit:** `F1-H2-FIX: M-6 la fila 9 de 7.6 comprueba que el evento sale ya confirmado` (ver `git log`). **Plan:** `f1-h2-plan-fix-deuda-c2.md`. Solo pruebas: ningún cambio de código de producción.

## Hallazgo

§7.6 fila 9 (`CriterioPorMateriaTest.php:153`) usaba `Event::fake` y solo afirmaba que el evento se publicaba una vez con el id de la materia. Por sí sola no distinguía «después del commit» de «dentro de la transacción».

## Cambio

`tests/Feature/Academico/CriterioPorMateriaTest.php`: la prueba de la fila 9 registra un oyente real. En el momento exacto del despacho, el oyente anota:

1. el id de la materia del evento;
2. el nivel de transacción de la conexión de la aplicación (`DB::transactionLevel()`);
3. el criterio vigente de la materia leído por **otra conexión** (`propietario`), que solo ve datos confirmados.

Afirma `[[M1, 0, '6.00']]`: una sola publicación, con la materia, fuera de toda transacción y con el criterio nuevo **ya confirmado**. Lo que afirmaba antes (una vez y con el id) sigue incluido en esa comparación exacta.

El número total de aserciones baja en 1 (5365 → 5364) porque dos aserciones de `Event::fake` se reemplazan por una sola comparación exacta que afirma más. No es un debilitamiento.

## Evidencia de que la prueba nueva puede fallar

En una copia temporal, el publicador despacha de inmediato (sin `DB::afterCommit`) y el caso de uso publica **antes** de escribir el criterio:

```
  ⨯ DD-46 (§7.6 fila 9): al confirmarse el PUT se publica CriterioDeMa… 11.06s
  Failed asserting that two arrays are identical.
  -        2 => '6.00',
  +        2 => null,
  Tests:    1 failed (4 assertions)
```

Con la versión anterior (`Event::fake`: una vez y con el id), esa mutación pasaba. Siguen vigentes, y se citarán juntas en la trazabilidad de T15, la fila 10 (`assertNotDispatched` tras un fallo real del historial) y `arquitectura §11: el publicador despacha después del commit y nada si la transacción se revierte` (`RecalculoDelResumenTest.php`).

## `make verificar`

Una corrida completa, sin nada más corriendo:

- Pint: `PASS ... 406 files`.
- PHPStan nivel 7: sin errores.
- Infection: 362 eliminados; MSI 100 %; MSI cubierto 100 %; ninguno omitido.
- Pest: **889 pruebas** (5364 aserciones), `Duration: 401.30s`.
- Cobertura: Dominio 100,0 %, Aplicación 100,0 %, Infraestructura y HTTP 99,7 %.
- Auditoría: sin avisos.
- **Total: 515 s (8 min 35 s).** Pasa.

## Desviaciones

- Ninguna.
