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
}
