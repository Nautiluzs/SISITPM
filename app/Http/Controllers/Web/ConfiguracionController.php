<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $textoCumplimiento = Setting::get('acta_texto_cumplimiento', '');
        $textoLegal = Setting::get('acta_texto_legal', '');

        return view('configuracion.index', compact('textoCumplimiento', 'textoLegal'));
    }

    public function updateTextoCumplimiento(Request $request)
    {
        $request->validate(['texto_cumplimiento' => 'nullable|string']);
        Setting::set('acta_texto_cumplimiento', $request->input('texto_cumplimiento'));

        return back()->with('success', 'Texto de cumplimiento actualizado.');
    }

    public function updateTextoLegal(Request $request)
    {
        $request->validate(['texto_legal' => 'nullable|string']);
        Setting::set('acta_texto_legal', $request->input('texto_legal'));

        return back()->with('success', 'Texto legal actualizado.');
    }
}
