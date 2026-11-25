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
}
