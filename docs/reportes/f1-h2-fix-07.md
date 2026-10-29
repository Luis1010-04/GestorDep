# Reporte F1-H2-FIX: pruebas de privacidad de la auditoría de C2 (M-4, M-5, M-7)

**Commit:** `F1-H2-FIX: pruebas de privacidad de la auditoria de C2` (ver `git log`). Pedido del responsable: los hallazgos menores de la auditoría que tocan privacidad se corrigen de inmediato (`f1-h2-progreso.md`, «Deuda de la auditoría»). **Solo pruebas**: no cambia código de producción.

## Cambios

| Hallazgo | Archivo | Cambio |
|---|---|---|
| M-4 | `tests/Feature/Academico/AlcanceDelDocenteTest.php` | §7.4 fila 9 ya no inscribe a J4 en M3: DE ve a J1 **solo** por M3 y a J4 **solo** por su equipo ED, como dice la fila. El caso «visto por las dos ramas, sale una vez» pasa a una prueba propia |
| M-5 | `tests/Feature/Academico/InscripcionesTest.php` | §7.5 fila 6: el `GET` de inscripciones de M2 (también con `?por_pagina=abc` y `?pagina=-1`) y el `POST` con id que no es v7 se comparan **byte a byte** con los de una materia inexistente |
| M-5 | `tests/Feature/Academico/EscrituraDeUsuarioConDosRolesTest.php` | Prueba nueva DA-10: seis escrituras de plantel de DE sobre J1 (que solo enseña), con cuerpos válidos e inválidos, dan un 404 **byte a byte** idéntico al mismo intento sobre un jugador inexistente; J1 queda intacto |
| M-7 | `tests/Arch/LocalizadorDeJugadoresTest.php` | El detector reconoce también el adaptador `LocalizadorDeJugadoresPlantel` y una cadena que nombre el puerto o el adaptador (`app('…\LocalizadorDeJugadores')`). Los comentarios y textos libres siguen sin contar. La lista de usos autorizados no cambia |

M-8 (el 409 `jugador_en_baja` al inscribir) es comportamiento exigido por DD-52: no lleva cambio y se registra en T15.

## Evidencia de que las pruebas endurecidas pueden fallar

M-4. En una copia temporal (`/tmp`, borrada después), se quitó la rama del entrenador a los usuarios que además son docentes (`AlcanceDeJugadoresSql::restringir`):

```
  ⨯ RNF-01 (§7.4 fila 9): DE con el equipo ED (J4) y la materia M3 (J1)… 3.10s
  ✓ RNF-01: un jugador que DE ve por las dos ramas (equipo ED y materia… 0.27s
  Failed asserting that two arrays are identical.
  ➜  92▕     expect(array_column($lista->json('datos'), 'id'))->toBe([$m['j1'], $m['j4']])
  Tests:    1 failed, 1 passed (8 assertions)
```

La segunda prueba (J4 por las dos ramas) es la forma antigua de la fila 9: pasa con la mutación, que es justamente el hueco que señaló el auditor.

M-7. La prueba del detector incluye casos que antes no se detectaban (adaptador por nombre y puerto por cadena) y casos que no deben detectarse (`LocalizadorDeJugadoresAntiguo`, texto libre, comentario). Con el detector anterior, esta prueba falla en los dos casos nuevos.

M-5. Los 404 de privacidad de DA-10 y del `GET` de inscripciones ya respondían idénticos (lo comprobó el auditor por HTTP); ahora lo afirman las pruebas.

Pruebas filtradas: `Tests: 54 passed (285 assertions)` (Arch de alcance y del localizador, alcance del docente, inscripciones y escritura con dos roles).

## `make verificar`

Corrida única por etapas, que también es la medición pedida antes de T12 (`f1-h2-medicion-verificar.md`): Pint 4 s, PHPStan 15 s, Infection 64 s (335 eliminados, MSI 100 %), verificación de mutación 0 s, Pest 332 s (**785 pruebas**, 4010 aserciones), cobertura (Dominio 100 %, Aplicación 100 %, Infraestructura y HTTP 99,8 %), auditoría 5 s. **Total: 420 s (7 min 0 s).** Pasa.

## Lo que no se hizo

- M-1, M-2, M-3, M-6, M-9 y M-10 no son de privacidad: quedan como deuda para el FIX único antes de T15 (M-1 y M-2 ya están resueltos en documentación; M-9 con la medición).

## Desviaciones

- Ninguna. Ninguna prueba se debilitó: la fila 9 queda más estricta y el caso anterior se conserva como prueba propia.
