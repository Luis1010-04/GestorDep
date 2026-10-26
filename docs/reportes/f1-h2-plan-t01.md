# Plan F1-H2-T01: calidad del hito (PHPStan nivel 7 e Infection)

**Enfoque**
1. `phpstan.neon` pasa a nivel 7, sin línea base ni `ignoreErrors`. Los 66 errores que aparecen están fuera de `Dominio/`; el responsable autorizó corregirlos solo con tipos y anotaciones (DA-01).
2. Se instala `infection/infection` (require-dev) y se configura en `backend/infection.json5` solo sobre `src/*/Dominio/` (se excluyen `Aplicacion` e `Infraestructura`), con MSI mínimo 80 % y MSI de código cubierto mínimo 85 %.
3. El script `verificar` ejecuta Infection con `--with-uncovered` (sin esa opción el código sin ninguna prueba no cuenta y una prueba borrada no hace fallar) antes de la corrida larga de Pest, para fallar pronto.
4. Infection no reconoce Pest; hace falta un adaptador mínimo (DA-02).
5. Si el dominio existente no alcanza el umbral, se agregan pruebas unitarias.

**Archivos**: `phpstan.neon`, `composer.json`/`composer.lock`, `infection.json5`, `tests/Ayudantes/AdaptadorPestParaInfection.php`, `tests/Ayudantes/EsquemaDePrueba.php`, `tests/Unit/**`, correcciones de tipos en `src/*/Infraestructura/**` y `app/Exceptions/FormatoDeError.php` (autorizadas).

**Pruebas**: filas 1 y 2 de §7.12 (evidencia en el reporte).

**Dudas**: ninguna abierta. Riesgo: tiempo de `make verificar` (§0.4, 15 min).
