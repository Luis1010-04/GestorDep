<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\Compartido\Infraestructura\RelojFijo;

const TABLAS_DE_COMPETENCIA = [
    'lugares', 'rivales', 'temporadas', 'torneos', 'entrenamientos', 'asistencias_entrenamiento', 'partidos', 'convocatorias',
];

const HISTORIALES_DE_COMPETENCIA = [
    'lugares_historial' => 'lugar_id',
    'rivales_historial' => 'rival_id',
    'temporadas_historial' => 'temporada_id',
    'torneos_historial' => 'torneo_id',
    'entrenamientos_historial' => 'entrenamiento_id',
    'asistencias_entrenamiento_historial' => 'asistencia_entrenamiento_id',
    'partidos_historial' => 'partido_id',
    'convocatorias_historial' => 'convocatoria_id',
];

beforeEach(function () {
    esquemaReiniciar();
});

/** @param  array<string, mixed>  $atributos */
function competenciaInsertar(string $tabla, array $atributos): string
{
    $id = Identificador::generar()->valor();
    DB::connection('propietario')->table($tabla)->insert(['id' => $id, ...$atributos]);

    return $id;
}

function competenciaRechazo(callable $operacion): ?string
{
    try {
        $operacion();
    } catch (QueryException $e) {
        return (string) $e->getCode();
    }

    return null;
}

function competenciaEliminar(string $tabla, string $id): void
{
    DB::connection('propietario')->table($tabla)->where('id', $id)->update(['eliminado_en' => '2026-09-15 10:30:00+00']);
}

function competenciaUnico(): string
{
    return substr(Identificador::generar()->valor(), -12);
}

function competenciaEntrenador(): string
{
    return competenciaInsertar('usuarios', [
        'nombre' => 'Entrenador de prueba', 'correo' => competenciaUnico().'@example.test', 'contrasena_hash' => 'hash-de-prueba',
    ]);
}

function competenciaEquipo(): string
{
    return competenciaInsertar('equipos', [
        'nombre' => 'Equipo '.competenciaUnico(),
        'categoria_id' => competenciaInsertar('categorias', ['nombre' => 'Sub-'.competenciaUnico()]),
    ]);
}

function competenciaJugador(): string
{
    return competenciaInsertar('jugadores', [
        'nombres' => 'Jugador', 'apellidos' => 'De Prueba', 'fecha_nacimiento' => '2009-05-10', 'matricula' => 'M-'.competenciaUnico(),
    ]);
}

function competenciaPosicion(): string
{
    return competenciaInsertar('posiciones', ['codigo' => 'defensa', 'nombre' => 'Defensa']);
}

function competenciaLugar(string $nombre = 'Cancha norte'): string
{
    return competenciaInsertar('lugares', ['nombre' => $nombre]);
}

/** @param  array<string, mixed>  $atributos */
function competenciaTemporada(array $atributos = []): string
{
    return competenciaInsertar('temporadas', ['nombre' => 'T-'.competenciaUnico(), 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2026-12-15', ...$atributos]);
}

/**
 * @param  array<string, mixed>  $atributos
 * @return array<string, mixed>
 */
function competenciaDatosDeEntrenamiento(array $atributos = []): array
{
    return [
        'equipo_id' => competenciaEquipo(), 'lugar_id' => competenciaLugar('L-'.competenciaUnico()),
        'inicia_en' => '2026-09-10 23:00:00+00', 'termina_en' => '2026-09-11 00:00:00+00',
        'responsable_id' => competenciaEntrenador(), 'estado' => 'programado', ...$atributos,
    ];
}

/**
 * @param  array<string, mixed>  $atributos
 * @return array<string, mixed>
 */
function competenciaDatosDePartido(array $atributos = []): array
{
    return [
        'equipo_id' => competenciaEquipo(), 'temporada_id' => competenciaTemporada(), 'torneo_id' => null,
        'rival_id' => competenciaInsertar('rivales', ['nombre' => 'R-'.competenciaUnico()]),
        'lugar_id' => competenciaLugar('L-'.competenciaUnico()), 'inicia_en' => '2026-09-12 17:00:00+00',
        'tipo' => 'amistoso', 'condicion' => 'local', 'estado' => 'programado', 'goles_favor' => null, 'goles_contra' => null,
        ...$atributos,
    ];
}

test('RNF-03: lugares y rivales con el mismo nombre en otras mayúsculas chocan, y el borrado lógico libera el nombre', function () {
    foreach (['lugares', 'rivales'] as $tabla) {
        $primero = competenciaInsertar($tabla, ['nombre' => 'Cancha Norte']);

        expect(competenciaRechazo(fn () => competenciaInsertar($tabla, ['nombre' => 'CANCHA NORTE'])))->toBe('23505', $tabla);

        competenciaEliminar($tabla, $primero);

        expect(competenciaRechazo(fn () => competenciaInsertar($tabla, ['nombre' => 'cancha norte'])))->toBeNull($tabla);
    }
});

test('RNF-03: el nombre de lugares, rivales, temporadas y torneos admite hasta 100 caracteres', function () {
    $temporada = competenciaTemporada();
    $atributos = [
        'lugares' => [], 'rivales' => [],
        'temporadas' => ['fecha_inicio' => '2027-01-01', 'fecha_fin' => '2027-02-01'],
        'torneos' => ['temporada_id' => $temporada],
    ];

    foreach ($atributos as $tabla => $extra) {
        expect(competenciaRechazo(fn () => competenciaInsertar($tabla, ['nombre' => str_repeat('a', 101), ...$extra])))->toBe('23514', $tabla)
            ->and(competenciaRechazo(fn () => competenciaInsertar($tabla, ['nombre' => str_repeat('a', 100), ...$extra])))->toBeNull($tabla);
    }
});

test('RNF-03: una temporada con fecha_fin igual o anterior a fecha_inicio la rechaza la base', function () {
    expect(competenciaRechazo(fn () => competenciaTemporada(['fecha_fin' => '2026-08-01'])))->toBe('23514')
        ->and(competenciaRechazo(fn () => competenciaTemporada(['fecha_fin' => '2026-07-31'])))->toBe('23514')
        ->and(competenciaRechazo(fn () => competenciaTemporada(['fecha_fin' => '2026-08-02'])))->toBeNull();
});

test('RNF-03: el nombre de temporada no distingue mayúsculas y se libera tras el borrado lógico', function () {
    $primera = competenciaTemporada(['nombre' => 'Apertura 2026']);

    expect(competenciaRechazo(fn () => competenciaTemporada(['nombre' => 'APERTURA 2026'])))->toBe('23505');

    competenciaEliminar('temporadas', $primera);

    expect(competenciaRechazo(fn () => competenciaTemporada(['nombre' => 'apertura 2026'])))->toBeNull();
});

test('RNF-03: el mismo torneo choca dentro de su temporada, no en otra, y se libera tras el borrado lógico', function () {
    $temporada = competenciaTemporada();
    $otra = competenciaTemporada(['fecha_inicio' => '2027-01-01', 'fecha_fin' => '2027-06-30']);
    $torneo = fn (string $nombre, string $de) => competenciaRechazo(fn () => competenciaInsertar('torneos', ['nombre' => $nombre, 'temporada_id' => $de]));

    expect($torneo('Liga Estatal', $temporada))->toBeNull()
        ->and($torneo('LIGA ESTATAL', $temporada))->toBe('23505')
        ->and($torneo('Liga Estatal', $otra))->toBeNull();

    competenciaEliminar('torneos', (string) DB::connection('propietario')->table('torneos')->where('temporada_id', $temporada)->value('id'));

    expect($torneo('liga estatal', $temporada))->toBeNull()
        ->and($torneo('Copa', Identificador::generar()->valor()))->toBe('23503');
});

test('RNF-03: un entrenamiento que termina antes o al mismo tiempo que empieza lo rechaza la base', function () {
    expect(competenciaRechazo(fn () => competenciaInsertar('entrenamientos', competenciaDatosDeEntrenamiento(['termina_en' => '2026-09-10 23:00:00+00']))))->toBe('23514')
        ->and(competenciaRechazo(fn () => competenciaInsertar('entrenamientos', competenciaDatosDeEntrenamiento(['termina_en' => '2026-09-10 22:59:59+00']))))->toBe('23514')
        ->and(competenciaRechazo(fn () => competenciaInsertar('entrenamientos', competenciaDatosDeEntrenamiento(['termina_en' => '2026-09-10 23:00:01+00']))))->toBeNull();
});

test('RNF-03: los estados de entrenamiento y de asistencia solo admiten sus valores', function () {
    foreach (['programado', 'realizado', 'cancelado'] as $estado) {
        expect(competenciaRechazo(fn () => competenciaInsertar('entrenamientos', competenciaDatosDeEntrenamiento(['estado' => $estado]))))->toBeNull($estado);
    }
    expect(competenciaRechazo(fn () => competenciaInsertar('entrenamientos', competenciaDatosDeEntrenamiento(['estado' => 'suspendido']))))->toBe('23514');

    $entrenamiento = competenciaInsertar('entrenamientos', competenciaDatosDeEntrenamiento());
    $asistencia = fn (string $estado) => competenciaRechazo(fn () => competenciaInsertar('asistencias_entrenamiento', [
        'entrenamiento_id' => $entrenamiento, 'jugador_id' => competenciaJugador(), 'estado' => $estado,
    ]));

    expect($asistencia('presente'))->toBeNull()
        ->and($asistencia('falta'))->toBeNull()
        ->and($asistencia('falta_justificada'))->toBeNull()
        ->and($asistencia('retardo'))->toBe('23514');
});

test('RNF-03: dos asistencias del mismo jugador en el mismo entrenamiento chocan, y el índice es parcial (R-2)', function () {
    $entrenamiento = competenciaInsertar('entrenamientos', competenciaDatosDeEntrenamiento());
    $jugador = competenciaJugador();
    $asistir = fn (?string $de = null) => competenciaRechazo(fn () => competenciaInsertar('asistencias_entrenamiento', [
        'entrenamiento_id' => $de ?? $entrenamiento, 'jugador_id' => $jugador, 'estado' => 'presente',
    ]));

    expect($asistir())->toBeNull()
        ->and($asistir())->toBe('23505')
        ->and($asistir(competenciaInsertar('entrenamientos', competenciaDatosDeEntrenamiento())))->toBeNull();

    competenciaEliminar('asistencias_entrenamiento', (string) DB::connection('propietario')->table('asistencias_entrenamiento')->where('entrenamiento_id', $entrenamiento)->value('id'));

    expect($asistir())->toBeNull();
});

test('RNF-03: tipo, condición y estado de partido solo admiten sus valores', function () {
    foreach (['amistoso', 'oficial', 'interno', 'torneo'] as $tipo) {
        expect(competenciaRechazo(fn () => competenciaInsertar('partidos', competenciaDatosDePartido(['tipo' => $tipo]))))->toBeNull($tipo);
    }
    foreach (['local', 'visitante'] as $condicion) {
        expect(competenciaRechazo(fn () => competenciaInsertar('partidos', competenciaDatosDePartido(['condicion' => $condicion]))))->toBeNull($condicion);
    }
    expect(competenciaRechazo(fn () => competenciaInsertar('partidos', competenciaDatosDePartido(['estado' => 'cancelado']))))->toBeNull()
        ->and(competenciaRechazo(fn () => competenciaInsertar('partidos', competenciaDatosDePartido(['tipo' => 'liga']))))->toBe('23514')
        ->and(competenciaRechazo(fn () => competenciaInsertar('partidos', competenciaDatosDePartido(['condicion' => 'neutral']))))->toBe('23514')
        ->and(competenciaRechazo(fn () => competenciaInsertar('partidos', competenciaDatosDePartido(['estado' => 'suspendido']))))->toBe('23514');
});

test('RNF-03 (DA-02): el resultado existe si y solo si el partido está jugado', function () {
    $partido = fn (array $extra) => competenciaRechazo(fn () => competenciaInsertar('partidos', competenciaDatosDePartido($extra)));

    expect($partido(['estado' => 'jugado', 'goles_favor' => 3, 'goles_contra' => 1]))->toBeNull()
        ->and($partido(['estado' => 'programado', 'goles_favor' => 3, 'goles_contra' => 1]))->toBe('23514')
        ->and($partido(['estado' => 'cancelado', 'goles_favor' => 0, 'goles_contra' => 0]))->toBe('23514')
        ->and($partido(['estado' => 'jugado']))->toBe('23514')
        ->and($partido(['estado' => 'jugado', 'goles_favor' => 3]))->toBe('23514')
        ->and($partido(['estado' => 'jugado', 'goles_contra' => 1]))->toBe('23514')
        ->and($partido(['estado' => 'programado', 'goles_favor' => 2]))->toBe('23514');
});

test('RNF-03: los goles van de 0 a 99', function () {
    $resultado = fn (int $favor, int $contra) => competenciaRechazo(fn () => competenciaInsertar('partidos', competenciaDatosDePartido([
        'estado' => 'jugado', 'goles_favor' => $favor, 'goles_contra' => $contra,
    ])));

    expect($resultado(0, 0))->toBeNull()
        ->and($resultado(99, 99))->toBeNull()
        ->and($resultado(100, 0))->toBe('23514')
        ->and($resultado(0, 100))->toBe('23514')
        ->and($resultado(-1, 0))->toBe('23514')
        ->and($resultado(0, -1))->toBe('23514');
});

test('RNF-03: partidos tiene la clave única (id, equipo_id) para claves foráneas compuestas, y torneo_id admite nulo', function () {
    $partido = competenciaInsertar('partidos', competenciaDatosDePartido());
    $restriccion = DB::connection('propietario')->selectOne(
        "select pg_get_constraintdef(oid) as definicion from pg_constraint where conrelid = 'partidos'::regclass and contype = 'u'",
    );

    expect($restriccion->definicion)->toBe('UNIQUE (id, equipo_id)')
        ->and(DB::connection('propietario')->table('partidos')->where('id', $partido)->value('torneo_id'))->toBeNull();
});

test('RNF-03: el rol de alineación solo admite titular, suplente o nulo, y la posición puede ser nula (DA-03)', function () {
    $partido = competenciaInsertar('partidos', competenciaDatosDePartido());
    $posicion = competenciaPosicion();
    $convocar = fn (?string $rol, ?string $posicionId) => competenciaRechazo(fn () => competenciaInsertar('convocatorias', [
        'partido_id' => $partido, 'jugador_id' => competenciaJugador(), 'posicion_id' => $posicionId, 'rol_alineacion' => $rol,
    ]));

    expect($convocar(null, null))->toBeNull()
        ->and($convocar('titular', $posicion))->toBeNull()
        ->and($convocar('suplente', $posicion))->toBeNull()
        ->and($convocar('banca', $posicion))->toBe('23514')
        ->and($convocar(null, Identificador::generar()->valor()))->toBe('23503');
});

test('RNF-03: un jugador convocado dos veces al mismo partido choca, y el borrado lógico libera el índice', function () {
    $partido = competenciaInsertar('partidos', competenciaDatosDePartido());
    $jugador = competenciaJugador();
    $convocar = fn (?string $a = null) => competenciaRechazo(fn () => competenciaInsertar('convocatorias', ['partido_id' => $a ?? $partido, 'jugador_id' => $jugador]));

    expect($convocar())->toBeNull()
        ->and($convocar())->toBe('23505')
        ->and($convocar(competenciaInsertar('partidos', competenciaDatosDePartido())))->toBeNull();

    competenciaEliminar('convocatorias', (string) DB::connection('propietario')->table('convocatorias')->where('partido_id', $partido)->value('id'));

    expect($convocar())->toBeNull();
});

test('RNF-03: los índices de §5.1 existen con sus columnas', function () {
    $indices = collect(DB::connection('propietario')->select(
        "select indexname, indexdef from pg_indexes where schemaname = 'public' and tablename in ('entrenamientos', 'asistencias_entrenamiento', 'partidos', 'convocatorias')",
    ))->pluck('indexdef', 'indexname');

    expect($indices['ix_entrenamientos_equipo_inicia_en'])->toContain('(equipo_id, inicia_en)')
        ->and($indices['ix_entrenamientos_lugar_inicia_en'])->toContain('(lugar_id, inicia_en)')
        ->and($indices['ix_asistencias_entrenamiento_jugador_id'])->toContain('(jugador_id)')
        ->and($indices['uq_asistencias_entrenamiento_entrenamiento_jugador'])->toContain('(entrenamiento_id, jugador_id) WHERE (eliminado_en IS NULL)')
        ->and($indices['ix_partidos_equipo_inicia_en'])->toContain('(equipo_id, inicia_en)')
        ->and($indices['ix_convocatorias_jugador_id'])->toContain('(jugador_id)')
        ->and($indices['uq_convocatorias_partido_jugador'])->toContain('(partido_id, jugador_id) WHERE (eliminado_en IS NULL)');
});

test('RNF-03: las claves foráneas de Competencia no borran en cascada', function () {
    $datos = competenciaDatosDeEntrenamiento();
    $entrenamiento = competenciaInsertar('entrenamientos', $datos);
    $jugador = competenciaJugador();
    competenciaInsertar('asistencias_entrenamiento', ['entrenamiento_id' => $entrenamiento, 'jugador_id' => $jugador, 'estado' => 'presente']);
    $temporada = competenciaTemporada();
    $torneo = competenciaInsertar('torneos', ['nombre' => 'Liga', 'temporada_id' => $temporada]);
    $partidoDatos = competenciaDatosDePartido(['temporada_id' => $temporada, 'torneo_id' => $torneo, 'tipo' => 'oficial']);
    $partido = competenciaInsertar('partidos', $partidoDatos);
    competenciaInsertar('convocatorias', ['partido_id' => $partido, 'jugador_id' => $jugador, 'posicion_id' => competenciaPosicion(), 'rol_alineacion' => 'titular']);
    $borrar = fn (string $tabla, string $id) => competenciaRechazo(fn () => DB::connection('propietario')->table($tabla)->where('id', $id)->delete());

    expect($borrar('entrenamientos', $entrenamiento))->toBe('23503')
        ->and($borrar('lugares', $datos['lugar_id']))->toBe('23503')
        ->and($borrar('usuarios', $datos['responsable_id']))->toBe('23503')
        ->and($borrar('jugadores', $jugador))->toBe('23503')
        ->and($borrar('partidos', $partido))->toBe('23503')
        ->and($borrar('torneos', $torneo))->toBe('23503')
        ->and($borrar('temporadas', $temporada))->toBe('23503')
        ->and($borrar('rivales', $partidoDatos['rival_id']))->toBe('23503')
        ->and($borrar('equipos', $partidoDatos['equipo_id']))->toBe('23503')
        ->and($borrar('posiciones', (string) DB::connection('propietario')->table('posiciones')->value('id')))->toBe('23503');
});

test('RNF-02: cada tabla de historial de Competencia es de solo inserción, para la aplicación y para el propietario', function () {
    $entrenamiento = competenciaInsertar('entrenamientos', competenciaDatosDeEntrenamiento());
    $partido = competenciaInsertar('partidos', competenciaDatosDePartido());
    $temporada = competenciaTemporada();
    $entidades = [
        'lugares_historial' => competenciaLugar(),
        'rivales_historial' => competenciaInsertar('rivales', ['nombre' => 'Rival']),
        'temporadas_historial' => $temporada,
        'torneos_historial' => competenciaInsertar('torneos', ['nombre' => 'Liga', 'temporada_id' => $temporada]),
        'entrenamientos_historial' => $entrenamiento,
        'asistencias_entrenamiento_historial' => competenciaInsertar('asistencias_entrenamiento', [
            'entrenamiento_id' => $entrenamiento, 'jugador_id' => competenciaJugador(), 'estado' => 'presente',
        ]),
        'partidos_historial' => $partido,
        'convocatorias_historial' => competenciaInsertar('convocatorias', ['partido_id' => $partido, 'jugador_id' => competenciaJugador()]),
    ];

    foreach (HISTORIALES_DE_COMPETENCIA as $tabla => $columna) {
        DB::connection('propietario')->table($tabla)->insert([
            'id' => Identificador::generar()->valor(), $columna => $entidades[$tabla], 'version' => 1, 'operacion' => 'crear',
            'datos_nuevos' => json_encode(['dato' => 'x']), 'usuario_id' => null, 'ocurrido_en' => '2026-09-15 10:30:00+00',
        ]);

        expect(competenciaRechazo(fn () => DB::table($tabla)->update(['motivo' => 'x'])))->not->toBeNull("UPDATE de la aplicación sobre {$tabla}")
            ->and(competenciaRechazo(fn () => DB::table($tabla)->delete()))->not->toBeNull("DELETE de la aplicación sobre {$tabla}")
            ->and(competenciaRechazo(fn () => DB::connection('propietario')->table($tabla)->update(['motivo' => 'x'])))->not->toBeNull("UPDATE del propietario sobre {$tabla}")
            ->and(competenciaRechazo(fn () => DB::connection('propietario')->table($tabla)->delete()))->not->toBeNull("DELETE del propietario sobre {$tabla}")
            ->and(DB::connection('propietario')->table($tabla)->count())->toBe(1);
    }
});

test('RNF-02: el cambio en una tabla de Competencia y su historial se escriben en la misma transacción', function () {
    $autor = competenciaEntrenador();
    $usuario = new class($autor) implements UsuarioActual
    {
        public function __construct(private readonly string $id) {}

        public function identificador(): Identificador
        {
            return Identificador::desdeTexto($this->id);
        }

        public function roles(): array
        {
            return ['entrenador'];
        }
    };
    $escritor = new EscritorDeHistorial(DB::connection(), new RelojFijo(new DateTimeImmutable('2026-09-15 10:30:00+00')), $usuario);
    $id = Identificador::generar();
    $alta = fn (string $nombre) => fn () => DB::table('lugares')->insert(['id' => $id->valor(), 'nombre' => $nombre]);

    $escritor->registrar('lugares', 'lugar_id', $id, 'crear', null, ['nombre' => 'Cancha sur'], $alta('Cancha sur'));

    $fila = DB::table('lugares_historial')->where('lugar_id', $id->valor())->first();
    expect($fila->usuario_id)->toBe($autor)
        ->and($fila->version)->toBe(1)
        ->and(json_decode($fila->datos_nuevos, true))->toBe(['nombre' => 'Cancha sur']);

    $otro = Identificador::generar();
    $fallido = competenciaRechazo(fn () => $escritor->registrar('lugares', 'lugar_id', $otro, 'crear', null, [], function () use ($otro) {
        DB::table('lugares')->insert(['id' => $otro->valor(), 'nombre' => 'Cancha este']);
        DB::table('lugares')->insert(['id' => Identificador::generar()->valor(), 'nombre' => 'CANCHA ESTE']);
    }));

    expect($fallido)->toBe('23505')
        ->and(DB::table('lugares')->where('id', $otro->valor())->count())->toBe(0)
        ->and(DB::table('lugares_historial')->where('lugar_id', $otro->valor())->count())->toBe(0);
});

test('RNF-02: cada tabla de Competencia tiene su historial y su columna de entidad', function () {
    foreach (HISTORIALES_DE_COMPETENCIA as $tabla => $columna) {
        expect(Schema::connection('propietario')->hasColumn($tabla, $columna))->toBeTrue($tabla);
    }
    foreach (TABLAS_DE_COMPETENCIA as $tabla) {
        expect(Schema::connection('propietario')->hasColumns($tabla, ['creado_en', 'creado_por', 'actualizado_en', 'actualizado_por', 'eliminado_en', 'eliminado_por']))->toBeTrue($tabla);
    }
});

test('RNF-03: migrate:rollback de Competencia no falla y se vuelve a aplicar', function () {
    esquemaMarcarSucio();
    $tablas = [...TABLAS_DE_COMPETENCIA, ...array_keys(HISTORIALES_DE_COMPETENCIA)];

    consolaArtisan('migrate:rollback', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    foreach ($tablas as $tabla) {
        expect(Schema::connection('propietario')->hasTable($tabla))->toBeFalse($tabla);
    }

    consolaArtisan('migrate', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    foreach ($tablas as $tabla) {
        expect(Schema::connection('propietario')->hasTable($tabla))->toBeTrue($tabla);
    }
});

test('RNF-03: migrate:fresh deja las tablas de Competencia, sin errores', function () {
    esquemaMarcarSucio();

    consolaArtisan('migrate:fresh', ['--database' => 'propietario', '--force' => true])->assertSuccessful();

    foreach ([...TABLAS_DE_COMPETENCIA, ...array_keys(HISTORIALES_DE_COMPETENCIA)] as $tabla) {
        expect(Schema::connection('propietario')->hasTable($tabla))->toBeTrue($tabla);
    }
});

test('RNF-03: el reinicio rápido de pruebas deja vacías las tablas de Competencia', function () {
    esquemaReconstruir();
    $huella = esquemaEstructura();

    $partido = competenciaInsertar('partidos', competenciaDatosDePartido());
    competenciaInsertar('convocatorias', ['partido_id' => $partido, 'jugador_id' => competenciaJugador()]);
    $entrenamiento = competenciaInsertar('entrenamientos', competenciaDatosDeEntrenamiento());
    competenciaInsertar('asistencias_entrenamiento', ['entrenamiento_id' => $entrenamiento, 'jugador_id' => competenciaJugador(), 'estado' => 'falta']);

    expect(esquemaReiniciar())->toBe('rapido')
        ->and(esquemaEstructura())->toBe($huella);

    foreach (TABLAS_DE_COMPETENCIA as $tabla) {
        expect(DB::connection('propietario')->table($tabla)->count())->toBe(0, $tabla);
    }
});
