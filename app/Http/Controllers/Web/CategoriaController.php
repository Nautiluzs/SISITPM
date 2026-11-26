<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    // ============================================================
    // INDEX — Público (autenticado)
    // ============================================================
    public function index(): View
    {
        $categorias = Categoria::withCount('activos')
            ->orderBy('nombre')
            ->paginate(24);

        return view('categorias.index', compact('categorias'));
    }

    public function create(): View
    {
        return view('categorias.create');
    }

    public function edit(int $id): View
    {
        return view('categorias.edit', [
            'categoria' => Categoria::findOrFail($id),
        ]);
    }

    // ============================================================
    // STORE — Solo admin
    // ============================================================
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->esAdmin()) {
            abort(403, 'No autorizado.');
        }

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
        ], [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
        ]);

        Categoria::create($data);

        return redirect()->route('categorias.index')->with('success', 'Categoría creada exitosamente.');
    }

    // ============================================================
    // UPDATE — Solo admin
    // ============================================================
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->esAdmin()) {
            abort(403, 'No autorizado.');
        }

        $categoria = Categoria::findOrFail($id);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
        ]);

        $categoria->update($data);

        return redirect()->route('categorias.index')->with('success', 'Categoría actualizada correctamente.');
    }

    // ============================================================
    // DESTROY — Solo admin
    // ============================================================
    public function destroy(int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->esAdmin()) {
            abort(403, 'No autorizado.');
        }

        $categoria = Categoria::findOrFail($id);

        if ($categoria->activos()->exists()) {
            return back()->with('error', 'No se puede eliminar: la categoría tiene activos asociados.');
        }

        $categoria->delete();

        return redirect()->route('categorias.index')->with('success', 'Categoría eliminada correctamente.');
    }
}
