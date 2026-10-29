# Reporte F1-H2-FIX: UUID que no es v7 en las rutas de H1 responde 404

**Commit:** `F1-H2-FIX: UUID que no es v7 responde 404 en DELETE /categorias/{id}` (ver `git log`). Decisión 3 del responsable tras el Lote B.

## Revisión de las rutas de H1 con parámetro

Se probó cada ruta de H1 con un parámetro de ruta, con un UUID v4 válido y con un UUID v7 inexistente, comparando estado y cuerpo:

| Ruta | UUID v4 antes del cambio |
|---|---|
| `DELETE /categorias/{id}` | **500** (`InvalidArgumentException` sin capturar) |
| `POST /equipos/{equipo}/jugadores` | 404, cuerpo idéntico al del v7 inexistente |
| `PATCH /equipos/{equipo}/jugadores/{jugador}` (equipo) | 404 idéntico |
| `PATCH /equipos/{equipo}/jugadores/{jugador}` (jugador) | 404 idéntico |
| `GET /jugadores/{jugador}` | 404 idéntico |
| `PATCH /jugadores/{jugador}` | 404 idéntico |
| `POST /jugadores/{jugador}/baja` | 404 idéntico |

Los identificadores que llegan en el cuerpo o en la consulta (`equipo_id`, `categoria_id`, `filtro[equipo_id]`) ya se validan con `uuid:7` y responden 422 `validacion_fallida`; no dan 500.

### Fallo antes del cambio

```
⨯ F1-H2-FIX: un UUID que no es v7 en la ruta responde el mismo 404 ... (DELETE /categorias/{id})
Failed asserting that 500 is identical to 404.
Tests: 1 failed, 6 passed
```

## Cambio

- `app/Http/Controllers/CategoriaController.php`: `eliminar()` usa `IdentificadorDeRuta::desde($id)` (introducido en T05; atrapa `InvalidArgumentException` y lanza el 404 genérico, el mismo patrón de `JugadorController::ver`). Es la única ruta de H1 que daba 500.
- `tests/Feature/Plantel/UuidQueNoEsV7Test.php` (7 pruebas, una por ruta/parámetro): el v4 responde el mismo 404, byte a byte, que un v7 inexistente.

## `make verificar`

| Paso | Resultado |
|---|---|
| Pint | pasa |
| PHPStan nivel 7 | `[OK] No errors` |
| Infection | 323 mutantes eliminados; MSI 100 % |
| Pest | **676 pruebas** (3386 aserciones); 255,5 s |
| Cobertura | Dominio 100 %, Aplicación 100 %, Infraestructura y HTTP 99,9 % |
| Auditoría | sin avisos |
| **Duración total** | **380 s (6 min 20 s)** |

## Lo que no se hizo

- No se cambió el contrato de H1: `openapi.yaml` ya documentaba el 404 de `DELETE /categorias/{id}`.

## Fuera de alcance / Desviaciones

- Ninguno.
