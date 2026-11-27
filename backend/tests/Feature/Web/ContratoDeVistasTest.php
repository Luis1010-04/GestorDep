<?php

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Schema;
use Src\Compartido\Dominio\Identificador;
use Src\Identidad\Infraestructura\Modelos\UsuarioModelo;
use Tests\TestCase;

/**
 * Páginas del front que declaran endpoints con data-endpoint (CA-GEN-001, CA-TAB-001, CA-FOR-001).
 *
 * @return list<string>
 */
function paginasConContrato(): array
{
    return [
        '/contrasena',
        '/jugadores',
        '/jugadores/'.Identificador::generar()->valor(),
        '/plantel/categorias',
        '/plantel/equipos',
        '/plantel/equipos/'.Identificador::generar()->valor(),
        '/jugadores/crear',
        '/jugadores/'.Identificador::generar()->valor().'/editar',
        '/estatus',
    ];
}

/**
 * Valores del enum de un parámetro de consulta de la operación (los filtros de un GET); vacío si no tiene enum.
 *
 * @return list<string>
 */
function enumDelParametro(Operation $operacion, string $nombre): array
{
    foreach ($operacion->parameters as $parametro) {
        if ($parametro instanceof Parameter && $parametro->name === $nombre && $parametro->schema instanceof Schema) {
            return array_values(array_map(fn ($valor) => (string) $valor, $parametro->schema->enum ?? []));
        }
    }

    return [];
}

/**
 * Valores del enum de un campo del cuerpo de la operación; vacío si el campo no tiene enum.
 *
 * @return list<string>
 */
function enumDelCuerpo(Operation $operacion, string $campo): array
{
    $cuerpo = $operacion->requestBody;
    $esquema = $cuerpo instanceof RequestBody ? ($cuerpo->content['application/json']->schema ?? null) : null;
    $propiedad = $esquema instanceof Schema ? ($esquema->properties[$campo] ?? null) : null;

    return $propiedad instanceof Schema ? array_values(array_map(fn ($valor) => (string) $valor, $propiedad->enum ?? [])) : [];
}

function contratoDeVistas(): OpenApi
{
    /** @var OpenApi $contrato */
    $contrato = Reader::readFromYamlFile(base_path('../docs/api/openapi.yaml'));

    return $contrato;
}

/** La operación de "METODO /ruta" (ruta como en openapi.yaml, sin /api/v1). */
function operacionDelContrato(OpenApi $contrato, string $endpoint): Operation
{
    [$metodo, $ruta] = explode(' ', $endpoint, 2);
    $elemento = $contrato->paths[$ruta] ?? null;
    expect($elemento)->toBeInstanceOf(PathItem::class, 'No existe la ruta '.$ruta);
    /** @var PathItem $elemento */
    $operacion = $elemento->getOperations()[strtolower($metodo)] ?? null;
    expect($operacion)->toBeInstanceOf(Operation::class, 'No existe la operación '.$endpoint);

    /** @var Operation $operacion */
    return $operacion;
}

/** El esquema de datos (o de cada elemento de datos, si es una lista) de la respuesta 200. */
function esquemaDeDatos(Operation $operacion): Schema
{
    $respuesta = $operacion->responses['200'] ?? null;
    expect($respuesta)->toBeInstanceOf(Response::class);
    /** @var Response $respuesta */
    $esquema = $respuesta->content['application/json']->schema ?? null;
    expect($esquema)->toBeInstanceOf(Schema::class);
    /** @var Schema $esquema */
    $datos = $esquema->properties['datos'] ?? null;
    expect($datos)->toBeInstanceOf(Schema::class);
    /** @var Schema $datos */

    return $datos->type === 'array' && $datos->items instanceof Schema ? $datos->items : $datos;
}

/** Recorre un campo con puntos ("equipos.categoria.nombre"); entra en los elementos de las listas. */
function campoDelEsquema(Schema $esquema, string $campo): ?Schema
{
    $actual = $esquema;
    foreach (explode('.', $campo) as $parte) {
        if ($actual->type === 'array' && $actual->items instanceof Schema) {
            $actual = $actual->items;
        }
        $siguiente = $actual->properties[$parte] ?? null;
        if (! $siguiente instanceof Schema) {
            return null;
        }
        $actual = $siguiente;
    }

    return $actual;
}

/** @return list<string> nombres de los parámetros de consulta de la operación */
function parametrosDeConsulta(Operation $operacion): array
{
    return array_values(array_map(
        fn (Parameter $parametro) => $parametro->name,
        array_filter($operacion->parameters, fn ($parametro) => $parametro instanceof Parameter && $parametro->in === 'query'),
    ));
}

/** @return list<string> valores admitidos por el parámetro orden */
function valoresDeOrden(Operation $operacion): array
{
    foreach ($operacion->parameters as $parametro) {
        if ($parametro instanceof Parameter && $parametro->name === 'orden' && $parametro->schema instanceof Schema) {
            return array_values(array_map(fn ($valor) => (string) $valor, $parametro->schema->enum ?? []));
        }
    }

    return [];
}

/** @return list<string> propiedades del cuerpo JSON de la operación */
function camposDelCuerpo(Operation $operacion): array
{
    $cuerpo = $operacion->requestBody;
    if (! $cuerpo instanceof RequestBody) {
        return [];
    }
    $esquema = $cuerpo->content['application/json']->schema ?? null;

    return $esquema instanceof Schema ? array_map('strval', array_keys($esquema->properties)) : [];
}

function paginaComoDocumento(string $html): DOMXPath
{
    $documento = new DOMDocument;
    libxml_use_internal_errors(true);
    $documento->loadHTML('<?xml encoding="utf-8"?>'.$html);
    libxml_clear_errors();

    return new DOMXPath($documento);
}

function endpointDe(DOMElement $nodo): string
{
    $actual = $nodo;
    while ($actual instanceof DOMElement) {
        if ($actual->hasAttribute('data-endpoint')) {
            return $actual->getAttribute('data-endpoint');
        }
        $actual = $actual->parentNode;
    }

    return '';
}

function usuarioDelContrato(TestCase $prueba): void
{
    $usuario = new UsuarioModelo;
    $usuario->id = Identificador::generar()->valor();
    $prueba->actingAs($usuario, 'web');
}

test('FRONT-R3 9: cada columna y dato de las vistas (data-campo) existe en la respuesta 200 de su endpoint en openapi.yaml (CA-GEN-001, CA-TAB-001)', function () {
    /** @var TestCase $this */
    usuarioDelContrato($this);
    $contrato = contratoDeVistas();

    foreach (paginasConContrato() as $pagina) {
        $xpath = paginaComoDocumento((string) $this->get($pagina)->assertOk()->getContent());
        $declaraciones = $xpath->query('//*[@data-endpoint]');
        expect($declaraciones === false ? 0 : $declaraciones->length)->toBeGreaterThan(0, $pagina);

        foreach ($xpath->query('//*[@data-campo]') ?: [] as $nodo) {
            /** @var DOMElement $nodo */
            $endpoint = endpointDe($nodo);
            expect($endpoint)->not->toBe('', $pagina.': data-campo sin data-endpoint');
            $esquema = esquemaDeDatos(operacionDelContrato($contrato, $endpoint));

            foreach (preg_split('/\s+/', trim($nodo->getAttribute('data-campo'))) ?: [] as $campo) {
                expect(campoDelEsquema($esquema, $campo))->not->toBeNull($pagina.': '.$endpoint.' no tiene '.$campo);
            }
        }
    }
});

test('FRONT-R3 10: cada control de formulario existe en el contrato (parámetro de consulta en GET, campo del cuerpo en las escrituras) (CA-FOR-001, CA-TAB-003)', function () {
    /** @var TestCase $this */
    usuarioDelContrato($this);
    $contrato = contratoDeVistas();
    $revisados = 0;

    foreach (paginasConContrato() as $pagina) {
        $xpath = paginaComoDocumento((string) $this->get($pagina)->getContent());

        foreach ($xpath->query('//form[@data-endpoint]//*[@name]') ?: [] as $control) {
            /** @var DOMElement $control */
            $endpoint = endpointDe($control);
            $operacion = operacionDelContrato($contrato, $endpoint);
            $admitidos = str_starts_with($endpoint, 'GET ') ? parametrosDeConsulta($operacion) : camposDelCuerpo($operacion);

            expect($admitidos)->toContain($control->getAttribute('name'));
            $revisados++;
        }
    }

    expect($revisados)->toBeGreaterThan(0);
});

test('FRONT-R3 11: cada columna ordenable (data-orden) usa un valor del parámetro orden de su endpoint (CA-TAB-003)', function () {
    /** @var TestCase $this */
    usuarioDelContrato($this);
    $contrato = contratoDeVistas();
    $revisadas = 0;

    foreach (paginasConContrato() as $pagina) {
        $xpath = paginaComoDocumento((string) $this->get($pagina)->getContent());

        foreach ($xpath->query('//*[@data-orden]') ?: [] as $columna) {
            /** @var DOMElement $columna */
            $valores = valoresDeOrden(operacionDelContrato($contrato, endpointDe($columna)));
            $campo = $columna->getAttribute('data-orden');

            expect($valores)->toContain($campo)->toContain('-'.$campo);
            $revisadas++;
        }
    }

    expect($revisadas)->toBeGreaterThan(0);
});

test('FRONT-R6 10: cada lista fija de opciones de un formulario usa exactamente los valores del enum de su campo en el contrato, en el cuerpo de las escrituras y en los filtros de los GET (enmienda FRONT-R4) (CA-FOR-001: no se inventan catálogos)', function () {
    /** @var TestCase $this */
    usuarioDelContrato($this);
    $contrato = contratoDeVistas();
    $revisadas = 0;

    foreach (paginasConContrato() as $pagina) {
        $xpath = paginaComoDocumento((string) $this->get($pagina)->getContent());

        foreach ($xpath->query('//form[@data-endpoint]//select[@name]') ?: [] as $lista) {
            /** @var DOMElement $lista */
            $valores = [];
            foreach ($xpath->query('.//option[@value!=""]', $lista) ?: [] as $opcion) {
                /** @var DOMElement $opcion */
                $valores[] = $opcion->getAttribute('value');
            }
            if ($valores === []) {
                continue; // Lista que se llena con datos de la API (equipos, categorías): no tiene valores fijos.
            }

            $endpoint = endpointDe($lista);
            $operacion = operacionDelContrato($contrato, $endpoint);
            $enum = str_starts_with($endpoint, 'GET ')
                ? enumDelParametro($operacion, $lista->getAttribute('name'))
                : enumDelCuerpo($operacion, $lista->getAttribute('name'));
            sort($valores);
            sort($enum);

            expect($valores)->toBe($enum, $pagina.': '.$lista->getAttribute('name'));
            $revisadas++;
        }
    }

    expect($revisadas)->toBeGreaterThan(0);
});
