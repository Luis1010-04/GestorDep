# Reporte F1-H2-FIX: M-7 de la auditoría del Lote E (pruebas que se saltaban en ejecuciones parciales)

**Commit:** `632611f` (`F1-H2-FIX: E M-7 las pruebas de alcance ya no se saltan en ejecuciones parciales`); este reporte va aparte, en un commit F1-DOC. **Origen:** M-7 de la auditoría del Lote E y el plan del punto 2 de `f1-h2-progreso.md`. **Solo pruebas:** no cambia código de producción.

## Hallazgo

Dos pruebas de privacidad tenían un `->skip()` condicional, porque usaban funciones y constantes definidas en `tests/Arch/AlcanceDeJugadoresTest.php`, que solo existen si esa carpeta se ejecuta en la misma corrida:

- `AlcanceDelDocenteTest.php:155`, §7.4 fila 14: las lecturas de jugadores pasan por `AlcanceDeJugadores` y la lista cerrada no crece.
- `LocalizadorDeJugadoresTest.php:78`: solo cuatro archivos usan el localizador.

En la suite completa corrían. En cualquier ejecución parcial se saltaban sin fallar. Antes del cambio:

```
$ pest tests/Feature/Academico/AlcanceDelDocenteTest.php --filter='fila 14'
  - RNF-01 (§7.4 fila 14): las lecturas de jugadores siguen pasando por… 0.48s
  Tests:    1 skipped (0 assertions)
$ pest tests/Arch/LocalizadorDeJugadoresTest.php
  Tests:    1 skipped, 3 passed (5 assertions)
```

## Cambio

- **Nuevo `tests/Ayudantes/DeteccionDeConsultasDeJugadores.php`.** Recibe, sin cambios, las líneas 2 a 97 de `tests/Arch/AlcanceDeJugadoresTest.php`:
  - el comentario de la regla;
  - las constantes `IMPLEMENTACION_DEL_ALCANCE` y `DECLARACION_DEL_MODELO_JUGADOR`;
  - la lista cerrada `USOS_AUTORIZADOS_SIN_ALCANCE`, con sus motivos;
  - las funciones `referenciasAJugadoresEn`, `pasaPorElAlcance`, `consultasDeJugadoresSinAlcance` y `archivosPhpDeLaAplicacion`.
- **`tests/Pest.php`:** carga ese ayudante siempre, junto a los demás (`require_once`).
- **`tests/Arch/AlcanceDeJugadoresTest.php`:** conserva sus cinco pruebas sin cambios y un comentario que dice dónde vive la lista y que agregar un uso requiere aprobación.
- **Se quitaron los dos `->skip()`.** `grep -rn "skip(" tests` ya no encuentra ninguno.

La lista cerrada sigue siendo la misma (dos archivos): la afirman la prueba de arquitectura y la fila 14 de §7.4.

## Evidencia

Después del cambio, ejecutadas solas, corren y pasan:

```
  ✓ RNF-01 (§7.4 fila 14): las lecturas de jugadores siguen pasando por… 1.60s
  Tests:    1 passed (2 assertions)
  Tests:    4 passed (14 assertions)    (LocalizadorDeJugadoresTest completo)
```

Y ejecutadas solas detectan una fuga real. En una copia temporal completa (con `vendor`, borrada después) se añadió `src/Consultas/Aplicacion/Fuga.php`, con `DB::table('jugadores')` sin alcance, y `src/Academico/Aplicacion/Atajo.php`, que resuelve directamente `LocalizadorDeJugadoresPlantel`:

```
  ⨯ RNF-01 (§7.4 fila 14): las lecturas de jugadores siguen pasando por… 0.17s
  Failed asserting that two arrays are identical.
  +    0 => 'src/Consultas/Aplicacion/Fuga.php: 'jugadores' en linea 3',
  Tests:    1 failed (2 assertions)
  ⨯ RN-20: solo la declaración, el adaptador, su enlace y el caso de us… 0.13s
  Failed asserting that two arrays are identical.
  +    0 => 'src/Academico/Aplicacion/Atajo.php',
  Tests:    1 failed (1 assertions)
```

Antes del cambio, esas mismas ejecuciones parciales habrían terminado con «skipped». Un primer intento enlazó `vendor` en lugar de copiarlo. Las pruebas fallaron por una razón ajena (`Call to undefined method Illuminate\Container\Container::basePath()`, porque el autoloader cargaba el árbol original), así que se descartó y se repitió con la copia completa.

## `make verificar`

Una corrida completa, sin nada más corriendo. Antes de lanzarla: ningún proceso `pest`, `infection`, `phpstan` ni `composer` vivo en los contenedores ni en el anfitrión, y `docker stats` por debajo del 1 % en los cuatro contenedores.

- Pint: `PASS ... 408 files`. Antes de la corrida, Pint corrigió un detalle de estilo en el ayudante nuevo.
- PHPStan nivel 7: sin errores.
- Infection: 362 eliminados; MSI 100 %; MSI cubierto 100 %; ninguno omitido (`Time: 1m 8s`).
- Pest: **909 pruebas** (5535 aserciones), `Duration: 365.26s`, sin fallos ni saltadas.
- Cobertura: Dominio 100,0 %, Aplicación 100,0 %, Infraestructura y HTTP 99,7 %.
- `composer audit`: sin avisos.
- **Total: 469 s (7 min 49 s).** Pasa.

## Desviaciones

- Ninguna.
