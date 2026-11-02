<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class ConfiguracionController extends Controller
{
    public function index()
    {
        return view('configuracion.index');
    }
}
