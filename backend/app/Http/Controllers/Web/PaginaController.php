<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class PaginaController extends Controller
{
    public function ingreso(): View
    {
        return view('ingreso');
    }

    public function panel(): View
    {
        return view('panel');
    }

    public function contrasena(): View
    {
        return view('modulos.contrasena.editar');
    }

    public function jugadores(): View
    {
        return view('modulos.jugadores.index');
    }

    /** Solo entrega la página; los datos del jugador y su alcance los decide la API. */
    public function jugador(string $jugador): View
    {
        return view('modulos.jugadores.ver', ['jugador' => $jugador]);
    }

    public function crearJugador(): View
    {
        return view('modulos.jugadores.crear');
    }

    public function editarJugador(string $jugador): View
    {
        return view('modulos.jugadores.editar', ['jugador' => $jugador]);
    }

    public function estatus(): View
    {
        return view('modulos.estatus.index');
    }

    public function categorias(): View
    {
        return view('modulos.categorias.index');
    }

    public function equipos(): View
    {
        return view('modulos.equipos.index');
    }

    /** Solo entrega la página; el equipo y si el usuario lo entrena los decide la API. */
    public function equipo(string $equipo): View
    {
        return view('modulos.equipos.ver', ['equipo' => $equipo]);
    }
}
