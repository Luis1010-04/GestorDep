<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;
use Src\Identidad\Aplicacion\CrearUsuario;
use Src\Identidad\Dominio\RepositorioDeUsuarios;

/**
 * Usuarios de demostracion (docs/hitos/f1-h1.md §5.6; el segundo docente, docs/hitos/f1-h2.md T15): dos docentes y dos
 * entrenadores. Solo desarrollo y pruebas.
 * Estas constantes son la unica fuente de las credenciales: el guion de humo las lee de aqui.
 */
class UsuariosDemoSeeder extends Seeder
{
    public const CORREO_DOCENTE = 'docente@example.test';

    /** Segundo docente (docs/hitos/f1-h2.md §9 paso 13): comprueba que un docente no ve a los alumnos de otro. */
    public const CORREO_DOCENTE_2 = 'docente2@example.test';

    public const CORREO_ENTRENADOR_1 = 'entrenador1@example.test';

    public const CORREO_ENTRENADOR_2 = 'entrenador2@example.test';

    /** Contraseña de desarrollo (mínimo 10 caracteres, RNF-10). */
    public const CONTRASENA = 'demo-clave-1234';

    /** La variable HUMO_CONTRASENA sobrescribe la contraseña de demostración (opcional). */
    public static function contrasena(): string
    {
        return (string) (getenv('HUMO_CONTRASENA') ?: self::CONTRASENA);
    }

    /**
     * Idempotente: un correo que ya existe no se vuelve a crear.
     *
     * @throws RuntimeException con APP_ENV=production
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(__('identidad.demo.produccion'));
        }

        $usuarios = app(RepositorioDeUsuarios::class);
        $crear = app(CrearUsuario::class);

        $demo = [
            [self::CORREO_DOCENTE, 'Docente de demostración', 'docente'],
            [self::CORREO_DOCENTE_2, 'Docente 2 de demostración', 'docente'],
            [self::CORREO_ENTRENADOR_1, 'Entrenador 1 de demostración', 'entrenador'],
            [self::CORREO_ENTRENADOR_2, 'Entrenador 2 de demostración', 'entrenador'],
        ];

        foreach ($demo as [$correo, $nombre, $rol]) {
            if ($usuarios->existeCorreo($correo)) {
                continue;
            }

            $crear->ejecutar($correo, $nombre, [$rol], self::contrasena());
        }
    }
}
