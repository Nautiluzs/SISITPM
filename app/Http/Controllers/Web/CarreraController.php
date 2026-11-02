<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ambiente;
use App\Models\Carrera;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CarreraController extends Controller
{
    // ============================================================
    // INDEX: Listado de todas las carreras
    // ============================================================
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        $idsCarreras = $user->idsCarrerasVisibles();

        $carreras = Carrera::when(
            $idsCarreras === null,
            fn ($q) => $q,
            fn ($q) => $q->whereIn('id_carrera', $idsCarreras)
        )
            ->with([
                'ambientes' => fn ($q) => $q->orderBy('nombre'),
                'jefeCarrera',
            ])
            ->withCount('ambientes')
            ->get();

        $ordenJerarquico = [
            'MEI' => 1,
            'MAZ' => 2,
            'INA' => 3,
            'SIF' => 4,
            'GTR' => 5,
            'ELC' => 6,
            'AGP' => 7,
            'AGRO' => 7,
        ];

        $carreras = $carreras->sortBy(fn ($carrera) => [
            $ordenJerarquico[$carrera->codigo] ?? 99,
            $carrera->nombre,
        ])->values();

        return view('carreras.list', compact('carreras'));
    }

    // ============================================================
    // SHOW: Detalle de una carrera con sus ambientes y custodios
    // ============================================================
    public function show($id_carrera)
    {
        /** @var User $user */
        $user = Auth::user();

        // 1. Cargar la carrera por ID
        $carrera = Carrera::findOrFail($id_carrera);

        $idsCarreras = $user->idsCarrerasVisibles();

        abort_if(
            $idsCarreras !== null && ! in_array((int) $carrera->id_carrera, array_map('intval', $idsCarreras), true),
            403,
            'Esa carrera no le corresponde.'
        );

        // 2. Cargar ambientes asegurando la relación con custodios y conteo de activos
        $ambientes = $user->ambientesVisibles()
            ->where('id_carrera', $carrera->id_carrera)
            ->with('custodios')
            ->withCount('activos')
            ->orderBy('nombre')
            ->get();

        // 3. Nombre para la vista
        $nombre = $carrera->nombre;

        return view('carreras.show', compact('carrera', 'nombre', 'ambientes'));
    }

    // ============================================================
    // CREATE: Formulario para crear carrera
    // ============================================================
    public function create()
    {
        $carreras = Carrera::orderBy('nombre')->get();

        return view('carreras.create', compact('carreras'));
    }

    // ============================================================
    // EDIT: Formulario para editar carrera
    // ============================================================
    public function edit($id_carrera)
    {
        $carrera = Carrera::findOrFail($id_carrera);

        return view('carreras.edit', compact('carrera'));
    }

    // ============================================================
    // STORE: Crear carrera
    // ============================================================
    public function store(Request $request)
    {
        $user = Auth::user();
        if (! $user?->esAdmin()) {
            abort(403, 'No autorizado.');
        }

        $data = $request->validate([
            'codigo' => 'required|string|max:20|unique:carreras,codigo',
            'nombre' => 'required|string|max:100',
        ]);

        Carrera::create([
            'codigo' => strtoupper($data['codigo']),
            'nombre' => $data['nombre'],
        ]);

        return redirect()
            ->route('carreras.index')
            ->with('success', 'Carrera creada correctamente.');
    }

    // ============================================================
    // UPDATE: Editar carrera
    // ============================================================
    public function update(Request $request, $id_carrera)
    {
        $user = Auth::user();
        if (! $user?->esAdmin()) {
            abort(403, 'No autorizado.');
        }

        $carrera = Carrera::findOrFail($id_carrera);

        $data = $request->validate([
            'codigo' => 'required|string|max:20|unique:carreras,codigo,'.$carrera->id_carrera.',id_carrera',
            'nombre' => 'required|string|max:100',
        ]);

        $carrera->update([
            'codigo' => strtoupper($data['codigo']),
            'nombre' => $data['nombre'],
        ]);

        return back()->with('success', 'Carrera actualizada correctamente.');
    }

    // ============================================================
    // DESTROY: Eliminar carrera
    // ============================================================
    public function destroy($id_carrera)
    {
        // 1. Solo admin puede eliminar
        $user = Auth::user();
        if (! $user?->esAdmin()) {
            abort(403, 'No autorizado.');
        }

        // 2. Buscar la carrera
        $carrera = Carrera::findOrFail($id_carrera);

        // 3. Verificar que no tenga ambientes asociados
        $ambientesCount = Ambiente::where('id_carrera', $carrera->id_carrera)->count();

        if ($ambientesCount > 0) {
            return back()->with('error',
                "No se puede eliminar la carrera \"{$carrera->nombre}\" porque tiene {$ambientesCount} ambiente(s) asociado(s). Elimina primero los ambientes."
            );
        }

        // 4. Guardar nombre para el mensaje
        $nombreCarrera = $carrera->nombre;

        // 5. Eliminar
        $carrera->delete();

        // 6. Redirigir al listado
        return redirect()
            ->route('carreras.index')
            ->with('success', "Carrera \"{$nombreCarrera}\" eliminada correctamente.");
    }
}
