<?php

namespace Database\Seeders;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Src\Academico\Aplicacion\InscribirJugador;
use Src\Academico\Aplicacion\RegistrarCalificacion;
use Src\Academico\Dominio\TipoDeCalificacion;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Dominio\Reloj;
use Src\Competencia\Aplicacion\ConvocarJugador;
use Src\Competencia\Aplicacion\RegistrarAsistencias;
use Src\Competencia\Dominio\EstadoDeAsistencia;
use Src\CondicionFisica\Aplicacion\RegistrarMedicion;
use Src\Identidad\Infraestructura\Modelos\UsuarioModelo;
use Src\Plantel\Aplicacion\AsignarJugadorAEquipo;
use Src\Rendimiento\Aplicacion\CapturarRendimiento;
use Src\Salud\Aplicacion\CrearAviso;

/**
 * Volumen de RNF-06 (docs/hitos/f1-h7.md §8, T08): 15 equipos, 25 usuarios (15 entrenadores y 10 docentes) y 375
 * jugadores, 25 por equipo. Los 375 son el supuesto de AB-02 [Propuesta], pendiente de confirmar con la institución.
 * No forma parte de `DatabaseSeeder` (§5.2): solo lo ejecuta la prueba de capacidad (`tests/Feature/Volumen/`).
 *
 * Los usuarios, los equipos, los jugadores, el periodo, las materias, los partidos y los entrenamientos se insertan directo,
 * como en los demás seeders. Las escrituras por jugador pasan por los casos de uso que toman el candado de la fila del
 * jugador de T06 (`CandadoDeJugadores`):
 * - membresía (`AsignarJugadorAEquipo`);
 * - inscripción y calificación (`InscribirJugador`, `RegistrarCalificacion`);
 * - medición (`RegistrarMedicion`) y aviso de lesión (`CrearAviso`);
 * - convocatoria, asistencia a entrenamiento y rendimiento (`ConvocarJugador`, `RegistrarAsistencias`, `CapturarRendimiento`).
 *
 * Cada caso de uso corre con su autor como usuario de la sesión. Las fechas salen del `Reloj`, para que el periodo escolar
 * y la temporada sean los vigentes. Los nombres son de prueba, sin datos de personas reales.
 */
final class VolumenSeeder extends Seeder
{
    public const EQUIPOS = 15;

    public const JUGADORES_POR_EQUIPO = 25;

    public const DOCENTES = 10;

    public const CONVOCADOS_POR_PARTIDO = 11;

    /** Contraseña de los 25 usuarios; solo de prueba. */
    public const CLAVE = 'volumen-clave-de-prueba';

    private const POSICIONES = ['portero', 'defensa', 'medio', 'delantero'];

    private ConnectionInterface $db;

    private DateTimeImmutable $ahora;

    /** @var array<string, UsuarioModelo> */
    private array $modelos = [];

    public function run(): void
    {
        $this->call([
            RolesSeeder::class, PosicionesSeeder::class, CriterioGeneralSeeder::class, ParametrosInstitucionalesSeeder::class,
            MetricasSeeder::class, PlantillasDeFabricaSeeder::class, TiposPruebaSeeder::class, RangosMedicionesCorporalesSeeder::class,
        ]);

        $this->db = DB::connection('propietario');
        $this->ahora = app(Reloj::class)->ahora()->setTimezone(new DateTimeZone('UTC'));

        try {
            $entrenadores = $this->usuarios('e', self::EQUIPOS, 'entrenador');
            $docentes = $this->usuarios('d', self::DOCENTES, 'docente');
            $equipos = $this->equipos($entrenadores);
            $jugadores = $this->jugadores($equipos);

            $this->membresias($equipos, $jugadores);
            $this->academico($docentes, $jugadores);
            $this->condicionFisicaYSalud($equipos, $jugadores);
            $this->competenciaYRendimiento($equipos, $jugadores);
        } finally {
            Auth::forgetGuards();
        }
    }

    /**
     * @return list<string> identificadores, en orden
     */
    private function usuarios(string $prefijo, int $cuantos, string $rol): array
    {
        $hash = Hash::make(self::CLAVE);
        $rolId = $this->db->table('roles')->where('codigo', $rol)->value('id');
        $ids = [];
        for ($i = 1; $i <= $cuantos; $i++) {
            $id = Identificador::generar()->valor();
            $numero = sprintf('%02d', $i);
            $this->db->table('usuarios')->insert([
                'id' => $id, 'nombre' => "Volumen {$prefijo}{$numero}", 'correo' => "volumen-{$prefijo}{$numero}@ejemplo.test",
                'contrasena_hash' => $hash, 'activo' => true,
            ]);
            $this->db->table('usuario_rol')->insert(['usuario_id' => $id, 'rol_id' => $rolId]);
            $ids[] = $id;
        }

        return $ids;
    }

    /**
     * @param  list<string>  $entrenadores
     * @return list<array{id: string, entrenador: string}>
     */
    private function equipos(array $entrenadores): array
    {
        $categoria = $this->insertar('categorias', ['nombre' => 'Volumen']);
        $desde = $this->dias(-365);

        $equipos = [];
        foreach ($entrenadores as $indice => $entrenador) {
            $id = $this->insertar('equipos', ['nombre' => sprintf('Volumen %02d', $indice + 1), 'categoria_id' => $categoria]);
            $this->insertar('entrenador_equipo', ['usuario_id' => $entrenador, 'equipo_id' => $id, 'desde' => $desde]);
            $equipos[] = ['id' => $id, 'entrenador' => $entrenador];
        }

        return $equipos;
    }

    /**
     * @param  list<array{id: string, entrenador: string}>  $equipos
     * @return list<list<array{id: string, matricula: string, posicion: string}>> los jugadores de cada equipo
     */
    private function jugadores(array $equipos): array
    {
        $porEquipo = [];
        $numero = 0;
        foreach ($equipos as $e => $equipo) {
            $porEquipo[$e] = [];
            for ($j = 0; $j < self::JUGADORES_POR_EQUIPO; $j++) {
                $numero++;
                $matricula = sprintf('VOL-%04d', $numero);
                $id = $this->insertar('jugadores', [
                    'nombres' => sprintf('Jugador %03d', $numero), 'apellidos' => sprintf('Volumen %03d', $numero),
                    'fecha_nacimiento' => '2009-05-10', 'matricula' => $matricula,
                ]);
                $porEquipo[$e][] = ['id' => $id, 'matricula' => $matricula, 'posicion' => self::POSICIONES[$j % count(self::POSICIONES)]];
            }
        }

        return $porEquipo;
    }

    /**
     * @param  list<array{id: string, entrenador: string}>  $equipos
     * @param  list<list<array{id: string, matricula: string, posicion: string}>>  $jugadores
     */
    private function membresias(array $equipos, array $jugadores): void
    {
        $asignar = app(AsignarJugadorAEquipo::class);
        foreach ($equipos as $e => $equipo) {
            $this->como($equipo['entrenador']);
            foreach ($jugadores[$e] as $jugador) {
                $asignar->ejecutar(Identificador::desdeTexto($equipo['id']), $jugador['matricula'], $jugador['posicion']);
            }
        }
    }

    /**
     * Un periodo vigente y una materia por docente. Cada jugador se inscribe en una materia y recibe una calificación
     * parcial; uno de cada cinco, reprobatoria, para que haya alertas académicas.
     *
     * @param  list<string>  $docentes
     * @param  list<list<array{id: string, matricula: string, posicion: string}>>  $jugadores
     */
    private function academico(array $docentes, array $jugadores): void
    {
        $periodo = $this->insertar('periodos_escolares', ['nombre' => 'Volumen', 'fecha_inicio' => $this->dias(-90), 'fecha_fin' => $this->dias(90)]);
        $materias = [];
        foreach ($docentes as $indice => $docente) {
            $asignatura = $this->insertar('asignaturas', ['clave' => sprintf('VOL-%02d', $indice + 1), 'nombre' => sprintf('Volumen %02d', $indice + 1), 'grado' => 1]);
            $materias[] = ['id' => $this->insertar('materias', ['asignatura_id' => $asignatura, 'periodo_id' => $periodo, 'docente_id' => $docente]), 'docente' => $docente];
        }

        $inscribir = app(InscribirJugador::class);
        $calificar = app(RegistrarCalificacion::class);
        $numero = 0;
        foreach (array_merge(...$jugadores) as $jugador) {
            $materia = $materias[$numero % count($materias)];
            $this->como($materia['docente']);
            $inscripcion = $inscribir->ejecutar(Identificador::desdeTexto($materia['id']), $jugador['matricula']);
            $valor = NumeroDecimal::desdeTexto($numero % 5 === 0 ? '4.50' : '8.00');
            $calificar->ejecutar($inscripcion->id, Identificador::desdeTexto($materia['docente']), TipoDeCalificacion::Parcial, 1, $valor, null);
            $numero++;
        }
    }

    /**
     * Una medición de cada jugador y un aviso de lesión del primer jugador de cada equipo, registrados por su entrenador.
     *
     * @param  list<array{id: string, entrenador: string}>  $equipos
     * @param  list<list<array{id: string, matricula: string, posicion: string}>>  $jugadores
     */
    private function condicionFisicaYSalud(array $equipos, array $jugadores): void
    {
        $medir = app(RegistrarMedicion::class);
        $avisar = app(CrearAviso::class);
        foreach ($equipos as $e => $equipo) {
            $this->como($equipo['entrenador']);
            foreach ($jugadores[$e] as $jugador) {
                $medir->ejecutar(Identificador::desdeTexto($jugador['id']), ['equipo_id' => $equipo['id'], 'peso_kg' => '68.50', 'estatura_cm' => '172.00']);
            }
            $avisar->ejecutar(Identificador::desdeTexto($jugadores[$e][0]['id']), 'Molestia en el tobillo (volumen)', $this->dias(-1), false);
        }
    }

    /**
     * Por equipo: un partido con 11 convocados, que se juega y recibe el rendimiento de cada convocado, y un entrenamiento
     * realizado con la asistencia de todo el equipo.
     *
     * @param  list<array{id: string, entrenador: string}>  $equipos
     * @param  list<list<array{id: string, matricula: string, posicion: string}>>  $jugadores
     */
    private function competenciaYRendimiento(array $equipos, array $jugadores): void
    {
        $base = [
            'temporada_id' => $this->insertar('temporadas', ['nombre' => 'Volumen', 'fecha_inicio' => $this->dias(-120), 'fecha_fin' => $this->dias(60)]),
            'rival_id' => $this->insertar('rivales', ['nombre' => 'Rival de volumen']),
            'lugar_id' => $this->insertar('lugares', ['nombre' => 'Cancha de volumen']),
        ];
        $metricas = $this->metricasPorPosicion();
        $convocar = app(ConvocarJugador::class);
        $capturar = app(CapturarRendimiento::class);
        $asistir = app(RegistrarAsistencias::class);

        foreach ($equipos as $e => $equipo) {
            $this->como($equipo['entrenador']);
            $partido = $this->insertar('partidos', [
                ...$base, 'equipo_id' => $equipo['id'], 'inicia_en' => $this->instante(-3), 'tipo' => 'amistoso', 'condicion' => 'local',
                'estado' => 'programado',
            ]);
            $convocados = array_slice($jugadores[$e], 0, self::CONVOCADOS_POR_PARTIDO);
            foreach ($convocados as $jugador) {
                $convocar->ejecutar(Identificador::desdeTexto($partido), $jugador['id']);
            }
            $this->db->table('partidos')->where('id', $partido)->update(['estado' => 'jugado', 'goles_favor' => 2, 'goles_contra' => 1]);
            foreach ($convocados as $indice => $jugador) {
                $capturar->ejecutar(Identificador::desdeTexto($partido), [
                    'jugador_id' => $jugador['id'], 'minutos' => 90, 'fue_titular' => true, 'tarjetas_amarillas' => $indice % 3 === 0 ? 1 : 0,
                    'tarjetas_rojas' => 0, 'calificacion_general' => 5 + $indice % 5, 'observacion' => null,
                ], $metricas[$jugador['posicion']] ?? throw new LogicException('posicion_sin_metricas'));
            }

            $entrenamiento = $this->insertar('entrenamientos', [
                'equipo_id' => $equipo['id'], 'lugar_id' => $base['lugar_id'], 'responsable_id' => $equipo['entrenador'],
                'inicia_en' => $this->instante(-2), 'termina_en' => $this->instante(-2, 1), 'estado' => 'realizado',
            ]);
            $asistir->ejecutar(Identificador::desdeTexto($entrenamiento), array_map(
                fn (array $jugador, int $indice): array => ['jugador_id' => $jugador['id'], 'estado' => $indice % 4 === 0 ? EstadoDeAsistencia::Falta : EstadoDeAsistencia::Presente],
                $jugadores[$e],
                array_keys($jugadores[$e]),
            ));
        }
    }

    /** @return array<string, list<array{metrica_id: string, valor: int}>> las métricas obligatorias de cada posición, con valor 1 */
    private function metricasPorPosicion(): array
    {
        $filas = $this->db->table('metricas_posicion')
            ->join('posiciones', 'posiciones.id', '=', 'metricas_posicion.posicion_id')
            ->where('metricas_posicion.obligatoria', true)
            ->orderBy('metricas_posicion.orden')
            ->get(['posiciones.codigo', 'metricas_posicion.metrica_id']);

        $porPosicion = [];
        foreach ($filas as $fila) {
            $porPosicion[(string) $fila->codigo][] = ['metrica_id' => (string) $fila->metrica_id, 'valor' => 1];
        }

        return $porPosicion;
    }

    /** Pone al usuario como el de la sesión para el caso de uso siguiente (`UsuarioActual`). */
    private function como(string $usuario): void
    {
        $this->modelos[$usuario] ??= UsuarioModelo::query()->findOrFail($usuario);
        Auth::guard()->setUser($this->modelos[$usuario]);
    }

    /** @param  array<string, mixed>  $atributos */
    private function insertar(string $tabla, array $atributos): string
    {
        $id = Identificador::generar()->valor();
        $this->db->table($tabla)->insert(['id' => $id, ...$atributos]);

        return $id;
    }

    /** La fecha de hoy (UTC) desplazada en días, `AAAA-MM-DD`. */
    private function dias(int $desplazamiento): string
    {
        return $this->ahora->modify("{$desplazamiento} days")->format('Y-m-d');
    }

    /** Un instante de hace o dentro de unos días, a las 18:00 UTC más las horas dadas. */
    private function instante(int $dias, int $horas = 0): string
    {
        return $this->ahora->modify("{$dias} days")->setTime(18 + $horas, 0)->format('Y-m-d H:i:sP');
    }
}
