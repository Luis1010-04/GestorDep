# Progreso de H2

| Tarea | Estado | Commit | `make verificar` |
|---|---|---|---|
| T01 Calidad (PHPStan 7 e Infection) | hecha | `70ceea1` | pasa; 447 pruebas; 6 min 11 s |
| T02 `NumeroDecimal` | hecha | `2aca9ed` | pasa; 499 pruebas; 5 min 41 s |
| T03 Migraciones y seeders | hecha | `c84f4c5` | pasa; 530 pruebas; 8 min 45 s |
| T04 Dominio del riesgo | **plan escrito, espera aprobación** | (solo el plan) | no se implementó |

## Resumen del lote A

- **Cobertura por capa** (T03, último estado): Dominio 100,0 % (umbral 95), Aplicación 100,0 % (85), Infraestructura y HTTP 100,0 % (70).
- **Mutación (Infection, solo `Dominio/`, suite `Unit`, con `--with-uncovered`):** 203 mutantes, 203 eliminados; MSI 100 %; MSI cubierto 100 % (mínimos 80 % y 85 %). Evidencia de que falla ante una prueba debilitada: `f1-h2-t01.md`.
- **PHPStan:** nivel 7 sin línea base ni `ignoreErrors`.
- **Decisiones del agente:** DA-01 (rutas de T01 autorizadas por el responsable), DA-02 (adaptador de Pest para Infection y `allow-plugins`), DA-03 (`--with-uncovered` y suite `Unit`), DA-04 (fila 7 de §7.12 en dos niveles), DA-05 (carpeta `alertas/` y columnas de historial), DA-06 (`Identificador` sin `floor`). Ninguna cambia privacidad ni permisos.
- **Rutas fuera de la especificación:** las de DA-01 (autorizadas), `composer.json` `allow-plugins` y `tests/Ayudantes/AdaptadorPestParaInfection.php` (DA-02).

## Pendientes que necesitan una respuesta del responsable

1. **Plan de T04** (`f1-h2-plan-t04.md`): aprobar o corregir, en especial las preguntas 1 a 6.
2. **CI:** `.github/workflows/verificar.yml` debe agregar `bcmath` a `extensions` (composer exige `ext-bcmath`); la ruta no estaba permitida en T02.
3. **Prueba intermitente de H1** (`ConsultaDeJugadoresTest.php:313`, lotería de sesiones): proponer fijar `session.lottery` en `phpunit.xml`.
4. **Tiempo de `verificar`:** 8 min 45 s y creciendo; vigilar el límite de 15 min de §0.4.
5. Reconstruir la imagen de Docker (`make levantar`) para tener `bcmath`.
