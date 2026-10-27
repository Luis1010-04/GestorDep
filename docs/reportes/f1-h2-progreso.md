# Progreso de H2

| Tarea | Estado | Commit | `make verificar` |
|---|---|---|---|
| T01 Calidad (PHPStan 7 e Infection) | hecha | `70ceea1` | pasa; 447 pruebas; 6 min 11 s |
| T02 `NumeroDecimal` | hecha | `2aca9ed` | pasa; 499 pruebas; 5 min 41 s |
| T03 Migraciones y seeders | hecha | `c84f4c5` | pasa; 530 pruebas; 8 min 45 s |
| T04 Dominio del riesgo | hecha | `5db7870` | pasa; 596 pruebas; 9 min 17 s sin carga ajena (la corrida con carga paralela tardó 31 min 04 s; ver `f1-h2-medicion-verificar.md`) |
| T05 Periodos escolares | hecha | `3a82618` | pasa; 623 pruebas; 11 min 49 s |
| T06 Asignaturas | hecha | `f58b835` | pasa; 643 pruebas; 12 min 50 s |
| T07 Materias | hecha | `d4c9bcf` | pasa; 663 pruebas; 13 min 57 s |

## Resumen del lote B (T04 a T07)

- **Commits:** T04 `5db7870`, T05 `3a82618`, T06 `f58b835`, T07 `d4c9bcf`; además `dccd059` (medición de `verificar`, `F1-H2-FIX`).
- **`make verificar` por tarea** (sin otros procesos, salvo la primera corrida de T04, contaminada): 9 min 17 s (596 pruebas), 11 min 49 s (623), 12 min 50 s (643), 13 min 57 s (663). Todas pasaron: Pint, PHPStan nivel 7 sin línea base, Infection, Pest, cobertura por capa y auditoría.
- **Cobertura por capa (T07):** Dominio 100,0 % (umbral 95), Aplicación 100,0 % (85), Infraestructura y HTTP 99,9 % (70).
- **Mutación (Infection, solo `Dominio/`):** 323 mutantes eliminados, MSI 100 %; en T06 hubo 5 mutantes sin cobertura (MSI 98 %), resueltos en T07.
- **Rutas nuevas** (todas documentadas en `openapi.yaml` y validadas contra respuestas reales): `GET/POST/DELETE /periodos-escolares`, `GET/POST/DELETE /asignaturas`, `GET/POST /materias`, `GET /materias/{materia}`.
- **Decisiones del agente nuevas:** DA-08 (respuestas del plan de T04, aceptada por el responsable) y DA-09 (orden del listado de materias). Ninguna cambia privacidad ni permisos.
- **Rutas fuera de la especificación:** ninguna en el lote B. `tests/Pest.php` no se tocó.
- **Puntos de atención**
  1. **Tiempo de `verificar`:** 13 min 57 s en T07, a 63 s del límite de 15 min de §0.4; sube 1 a 2 min por tarea. T08 lo superará casi con seguridad. Hace falta decidir antes de T08 (por ejemplo, correr Infection solo por lote y en CI, o acelerar el reinicio de la base en las pruebas de ruta).
  2. **Prueba intermitente de H1** (`ConsultaDeJugadoresTest.php:313`, lotería de sesiones) sigue pendiente de la decisión sobre cómo fijar `session.lottery`.
  3. `DELETE /categorias/{id}` (H1) responde 500 con un UUID válido que no sea v7; las rutas nuevas usan `IdentificadorDeRuta` (404). No se tocó H1.
  4. En T05 a T07 las clases internas se escribieron antes de ejecutar las pruebas; la evidencia del fallo se obtuvo quitando las rutas (ver cada reporte).

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
