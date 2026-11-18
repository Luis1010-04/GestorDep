# F1-H6, lote B: prueba de mutación

**Fecha y hora:** 16 de noviembre de 2026, 17:57 a 18:00 (America/Mexico_City) · **Base:** `d956050` (T03, T04 y T05 ya integradas) · **Sin cambios de código.**

## Comando

`make mutacion`, que ejecuta `composer mutacion`: `vendor/bin/infection --no-progress --with-uncovered` sobre `src/` sin `Aplicacion` ni `Infraestructura` (solo los Dominios; `infection.json5`, mínimos MSI 80 y MSI de código cubierto 85) y después `bin/verificar-mutacion.php` (falla si hay mutantes omitidos o con tiempo agotado). Salió con **exit 0**.

## Resultado global

| Mutantes | Matados por las pruebas | Escapados | Sin cubrir | Con tiempo agotado | Omitidos | Errores |
|---|---|---|---|---|---|---|
| 999 | 999 | 0 | 0 | 0 | 0 | 0 |

**MSI 100 %, MSI de código cubierto 100 %, cobertura de código mutado 100 %.** Tiempo: 3 min 27 s, 4 hilos. El lote A (T02) cerró con 997 mutantes y 997 matados; los 2 mutantes nuevos son los de `AcumulacionDeSanciones::conteos()` (DA-117), el único cambio de Dominio del lote B. Consultas no tiene Dominio y T04 y T05 no tocaron ninguno.

Log completo: `docs/reportes/logs/f1-h6-loteB-mutacion-20261116-1757.log`.

## MSI por archivo de Dominio del lote B

`make mutacion` no imprime el desglose por archivo. Para tenerlo, corrí una vez más Infection sin cambiar la configuración, con `--filter=src/Alertas/Dominio --log-verbosity=all` (50 s; 114 mutantes, 114 matados, ninguno escapado). Log: `docs/reportes/logs/f1-h6-loteB-mutacion-alertas-dominio-20261116-1801.log`.

| Archivo (`src/Alertas/Dominio/`) | Mutantes | Matados | MSI |
|---|---|---|---|
| `AcumulacionDeSanciones.php` (tocado por el lote B: `conteos()`) | 21 | 21 | 100 % |
| `TarjetasDePartido.php` | 23 | 23 | 100 % |
| `VentanaDeFaltas.php` | 21 | 21 | 100 % |
| `ParametrosDeSanciones.php` | 18 | 18 | 100 % |
| `TarjetasRegistradas.php` | 12 | 12 | 100 % |
| `RiesgoDeBajaDeportiva.php` | 6 | 6 | 100 % |
| `DecisionDeAlerta.php` | 5 | 5 | 100 % |
| `ResultadoDeEvaluacion.php` | 4 | 4 | 100 % |
| `AlertaAcademica.php` | 2 | 2 | 100 % |
| `InasistenciaAEntrenamiento.php` | 2 | 2 | 100 % |
| **Total** | **114** | **114** | **100 %** |

`AccionSobreAlerta.php` es un `enum` sin lógica y no genera mutantes.

## Notas

- La corrida de desglose sobrescribió `build/infection/resumen.log` (que está fuera del repositorio); el resumen global de la corrida de 999 mutantes está en el log de `make mutacion`.
- El commit de este reporte y de los dos logs es el de mensaje «F1-H6-loteB: make mutacion del lote B (log)»; su hash se da en la entrega.
