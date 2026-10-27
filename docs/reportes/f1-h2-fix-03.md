# Reporte F1-H2-FIX (c): `parametros_institucionales` a `database/migrations/academico/`

**Commit:** `F1-H2-FIX: parametros_institucionales en la carpeta academico`. **Decisión:** DA-05 corregida.

- **Archivos:** `git mv` de `2026_10_26_035100_crear_tabla_parametros_institucionales.php` y `2026_10_26_090800_crear_tabla_parametros_institucionales_historial.php` de `database/migrations/alertas/` a `database/migrations/academico/` (la carpeta `alertas/` queda vacía y desaparece). Los nombres de archivo no cambian: el orden de ejecución y la tabla `migrations` de las bases ya migradas siguen válidos. `AppServiceProvider` registra las subcarpetas con `glob`: sin cambios de código.
- **Pruebas:** ninguna nueva; `MigracionesDeAcademicoTest` (fresh, rollback, seeders) cubre el movimiento.
- **`make verificar`:** ver el resultado al final de este archivo.
- **Nota:** `f1-h2-plan-t03.md` y `f1-h2-t03.md` conservan el texto histórico (`alertas/`); vale la corrección de DA-05.

## Resultado de `make verificar`

Pasa: Pint, PHPStan nivel 7 sin errores, Infection MSI 100 %, 530 pruebas (2 792 aserciones; Pest 619,4 s), cobertura Dominio/Aplicación/Infraestructura 100 %, `composer audit` sin avisos. **Tiempo total 12 min 55 s**, cerca del límite de 15 min de §0.4 (la misma suite tardó 8 min 45 s en T03 sin cambios de código: la variación es del entorno, no del movimiento). Conviene decidir qué hacer antes de que T04 en adelante lo supere.
