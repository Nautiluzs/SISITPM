<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivoFijo;
use App\Models\Ambiente;
use App\Models\Carrera;
use App\Models\Categoria;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AmbienteController extends Controller
{
    // ============================================================
    // INDEX
    // ============================================================
    public function index($id_carrera)
    {
        /** @var User $user */
        $user = Auth::user();

        $idsVisibles = $user->idsCarrerasVisibles();

        abort_if(
            ! $user->puedeVerTodasLasCarreras() && ! in_array((int) $id_carrera, array_map('intval', $idsVisibles ?: []), true),
            403,
            'Esa carrera no le corresponde.'
        );

        $carrera = Carrera::findOrFail($id_carrera);

        $ambientes = $user->ambientesVisibles()
            ->where('id_carrera', $carrera->id_carrera)
            ->withCount('activos')
            ->orderBy('nombre')
            ->get();

        return view('ambientes.index', compact('ambientes', 'carrera'));
    }

    // ============================================================
    // CREATE
    // ============================================================
    public function create($id_carrera)
    {
        $carrera = Carrera::findOrFail($id_carrera);

        return view('ambientes.create', compact('carrera'));
    }

    // ============================================================
    // STORE
    // ============================================================
    public function store(Request $request, $id_carrera)
    {
        $user = Auth::user();
        if (! $user || ! $user->esAdmin()) {
            abort(403, 'No autorizado.');
        }

        $carrera = Carrera::findOrFail($id_carrera);

        $data = $request->validate([
            'codigo' => 'required|string|max:30|unique:ambientes,codigo',
            'nombre' => 'required|string|max:100',
            'bloque' => 'required|string|max:20',
            'piso' => 'required|string|max:10',
        ]);

        $data['id_carrera'] = $carrera->id_carrera;
        $data['codigo'] = strtoupper($data['codigo']);

        Ambiente::create($data);

        return redirect()->route('carreras.show', $carrera->id_carrera)
            ->with('success', 'Ambiente creado correctamente.');
    }

    // ============================================================
    // SHOW: POR ID (único método)
    // ============================================================
    public function show(Request $request, $id)
    {
        /** @var User $user */
        $user = Auth::user();

        $filters = $request->validate([
            'id_categoria' => ['nullable', 'integer', 'exists:categorias,id_categoria'],
        ]);

        $ambiente = $user->ambientesVisibles()->with(['carrera', 'custodios'])->findOrFail($id);
        $idCategoriaSeleccionada = $filters['id_categoria'] ?? null;

        $activosEnAmbiente = fn ($query) => $query
            ->where('id_ambiente', $ambiente->id_ambiente)
            ->where('estado_registro', '!=', 'BAJA');

        $categorias = Categoria::whereHas('activos', $activosEnAmbiente)
            ->withCount(['activos' => $activosEnAmbiente])
            ->orderBy('nombre')
            ->get();

        $activos = ActivoFijo::with(['categoria', 'custodio', 'fuente'])
            ->where('id_ambiente', $ambiente->id_ambiente)
            ->where('estado_registro', '!=', 'BAJA')
            ->when($idCategoriaSeleccionada, fn ($query) => $query->where('id_categoria', $idCategoriaSeleccionada))
            ->orderByDesc('id_activo')
            ->get();

        $totales = [
            'TODOS' => $activos->count(),
            'BUENOS' => $activos->where('estado_fisico', 'B')->count(),
            'REGULARES' => $activos->where('estado_fisico', 'R')->count(),
            'MALOS' => $activos->whereIn('estado_fisico', ['M', 'FF'])->count(),
        ];

        return view('ambientes.show', compact(
            'ambiente',
            'categorias',
            'totales',
            'activos',
            'idCategoriaSeleccionada'
        ));
    }

    // ============================================================
    // UPDATE
    // ============================================================
    public function update(Request $request, $id_carrera, $id_ambiente)
    {
        $user = Auth::user();
        if (! $user || ! $user->esAdmin()) {
            abort(403, 'No autorizado.');
        }

        $ambiente = Ambiente::where('id_carrera', $id_carrera)
            ->findOrFail($id_ambiente);

        $data = $request->validate([
            'codigo' => [
                'required', 'string', 'max:30',
                // ⭐ CLAVE: ignorar el ambiente actual
                Rule::unique('ambientes', 'codigo')->ignore($ambiente->id_ambiente, 'id_ambiente'),
            ],
            'nombre' => 'required|string|max:100',
            'bloque' => 'nullable|string|max:20',
            'piso' => 'nullable|string|max:10',
        ], [
            'codigo.required' => 'El código del ambiente es obligatorio.',
            'codigo.unique' => 'El código ingresado ya pertenece a otro ambiente.',
            'nombre.required' => 'El nombre del ambiente es obligatorio.',
        ]);

        $data['codigo'] = strtoupper($data['codigo']);

        $ambiente->update($data);

        return back()->with('success', 'Ambiente actualizado correctamente.');
    }

    public function asignarCustodios(Request $request, $id)
    {
        $user = Auth::user();
        if (! $user || ! $user->esAdmin()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
            }
            abort(403, 'No autorizado.');
        }

        $ambiente = Ambiente::findOrFail($id);

        $data = $request->validate([
            'usuarios' => 'nullable|array',
            'usuarios.*' => 'required|exists:usuarios,id_usuario',
        ]);

        $usuariosIds = $data['usuarios'] ?? [];

        // Opcional: validar que los usuarios estén activos
        $usuariosInvalidos = User::whereIn('id_usuario', $usuariosIds)
            ->where('estado', '!=', 'ACTIVO')
            ->pluck('id_usuario');
        if (! $usuariosInvalidos->isEmpty()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Algunos usuarios seleccionados no están activos.'], 422);
            }

            return back()->with('error', 'Algunos usuarios seleccionados no están activos.');
        }

        $ambiente->custodios()->sync($usuariosIds);

        Log::info('Custodios asignados al ambiente', [
            'id_ambiente' => $ambiente->id_ambiente,
            'nombre_ambiente' => $ambiente->nombre,
            'custodios_asignados' => $usuariosIds,
            'asignado_por' => $user->id_usuario,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Docentes custodios asignados correctamente al ambiente.',
            ]);
        }

        return back()->with('success', 'Docentes custodios asignados correctamente al ambiente.');
    }

    // ============================================================
    // DESTROY
    // ============================================================
    public function destroy($id_carrera, $id_ambiente)
    {
        $user = Auth::user();
        if (! $user || ! method_exists($user, 'esAdmin') || ! call_user_func([$user, 'esAdmin'])) {
            abort(403, 'No autorizado.');
        }

        $ambiente = Ambiente::where('id_carrera', $id_carrera)
            ->findOrFail($id_ambiente);

        $ambiente->delete();

        return back()->with('success', 'Ambiente eliminado correctamente.');
    }
}
