<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivoFijo;
use App\Models\Ambiente;
use App\Models\Carrera;
use App\Models\Categoria;
use App\Models\FuenteFinanciamiento;
use App\Models\Item;
use App\Models\User;
use App\Services\QRCodeService;
use App\Support\PdfHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ActivoController extends Controller
{
    protected QRCodeService $qrService;

    public function __construct(QRCodeService $qrService)
    {
        $this->qrService = $qrService;
    }

    // ==========================================
    // PERMISOS — Solo admin modifica
    // ==========================================
    protected function authorizeActivoModification(): void
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->puedeModificar()) {
            abort(403, 'No tiene permisos para modificar activos. Solo el administrador puede.');
        }
    }

    // ==========================================
    // INDEX — TODOS ven TODOS los activos (Excepto los dados de baja)
    // ==========================================
    public function index(Request $request)
    {
        $query = ActivoFijo::with(['categoria', 'ambiente.carrera', 'fuente', 'custodio'])
            ->where('estado_registro', '!=', 'BAJA'); // ⭐ Oculta los dados de baja del inventario general

        if ($request->filled('buscar')) {
            $s = $request->buscar;
            $query->where(function ($q) use ($s) {
                $q->where('codigo_activo', 'like', "%{$s}%")
                    ->orWhere('nombre', 'like', "%{$s}%")
                    ->orWhere('numero_serie', 'like', "%{$s}%")
                    ->orWhere('marca', 'like', "%{$s}%")
                    ->orWhere('modelo', 'like', "%{$s}%");
            });
        }

        if ($request->filled('id_categoria')) {
            $query->where('id_categoria', $request->id_categoria);
        }

        if ($request->filled('id_ambiente')) {
            $query->where('id_ambiente', $request->id_ambiente);
        }

        $tipoBien = $request->input('tipo_bien', 'TODOS');

        if (in_array($tipoBien, ['ACTIVO_FIJO', 'NO_ACTIVO'], true)) {
            $query->where('tipo_bien', $tipoBien);
        }

        $activos = $query->orderByDesc('id_activo')->get();
        $carreras = Carrera::orderBy('nombre')->get();
        $categorias = Categoria::orderBy('nombre')->get();
        $ambientes = Ambiente::with('carrera')->orderBy('nombre')->get();
        $fuentes = FuenteFinanciamiento::orderBy('nombre')->get();
        $usuarios = User::where('estado', 'ACTIVO')->orderBy('nombre_completo')->get();

        $totales = [
            'TODOS' => ActivoFijo::where('estado_registro', '!=', 'BAJA')->count(),
            'ACTIVO_FIJO' => ActivoFijo::where('estado_registro', '!=', 'BAJA')->where('tipo_bien', 'ACTIVO_FIJO')->count(),
            'NO_ACTIVO' => ActivoFijo::where('estado_registro', '!=', 'BAJA')->where('tipo_bien', 'NO_ACTIVO')->count(),
        ];

        return view('activos.index', compact(
            'activos', 'carreras', 'categorias', 'ambientes', 'fuentes', 'usuarios', 'tipoBien', 'totales'
        ));
    }

    // ==========================================
    // CREATE — Solo admin
    // ==========================================
    public function create(Request $request)
    {
        $this->authorizeActivoModification();

        $idAmbientePreseleccionado = $request->input('id_ambiente');
        $idCategoriaPreseleccionada = $request->input('id_categoria');

        $categorias = Categoria::when($idAmbientePreseleccionado, function ($q) use ($idAmbientePreseleccionado) {
            $q->where('id_ambiente', $idAmbientePreseleccionado);
        })->orderBy('nombre')->get();

        $ambientes = Ambiente::with('carrera')->orderBy('nombre')->get();
        $fuentes = FuenteFinanciamiento::orderBy('nombre')->get();

        $usuariosQuery = User::where('estado', 'ACTIVO')
            ->with(['items' => fn ($q) => $q->whereNull('item_usuario.fecha_fin')])
            ->orderBy('nombre_completo');

        if ($idAmbientePreseleccionado) {
            $usuariosQuery->orderByRaw(
                'CASE WHEN EXISTS (SELECT 1 FROM activos_fijos WHERE activos_fijos.id_custodio = usuarios.id_usuario AND activos_fijos.id_ambiente = ? AND activos_fijos.deleted_at IS NULL)
                    OR EXISTS (SELECT 1 FROM item_usuario
                        INNER JOIN items ON items.id_item = item_usuario.id_item
                        WHERE item_usuario.id_usuario = usuarios.id_usuario
                        AND item_usuario.fecha_fin IS NULL
                        AND items.id_ambiente = ?
                        AND items.deleted_at IS NULL)
                    THEN 0 ELSE 1 END',
                [$idAmbientePreseleccionado, $idAmbientePreseleccionado]
            );
        }

        $usuarios = $usuariosQuery->get();

        $codigoSugerido = 'TPM-'.date('Y').'-'.rand(1000, 9999);

        return view('activos.create', compact(
            'categorias', 'ambientes', 'fuentes', 'usuarios', 'idAmbientePreseleccionado',
            'idCategoriaPreseleccionada', 'codigoSugerido'
        ));
    }

    /**
     * ⭐ Endpoint AJAX para obtener custodios de un ambiente específico
     */
    public function custodiosPorAmbiente($idAmbiente)
    {
        $this->authorizeActivoModification();

        Ambiente::findOrFail($idAmbiente);

        $custodios = User::query()
            ->where('estado', 'ACTIVO')
            ->with(['itemsVigentes', 'activosCustodiados', 'carrera'])
            ->get()
            ->sortBy([
                fn ($usuario) => $usuario->activosCustodiados->contains('id_ambiente', (int) $idAmbiente)
                    || $usuario->itemsVigentes->contains('id_ambiente', (int) $idAmbiente) ? 0 : 1,
                fn ($usuario) => mb_strtolower($usuario->nombre_completo),
            ])
            ->map(function ($usr) use ($idAmbiente) {
                $items = $usr->itemsVigentes->pluck('numero_item')->values();
                $nroItem = $items->isNotEmpty()
                    ? 'Ítems #'.$items->implode(', #')
                    : 'Sin ítem';
                $rol = User::ROLES[$usr->rol] ?? $usr->rol;
                $textoMostrar = "{$nroItem} · C.I.: {$usr->ci} · {$usr->nombre_completo} · {$rol}";
                $textoBusqueda = strtolower($nroItem.' '.$usr->ci.' '.$usr->nombre_completo.' '.$rol);

                return [
                    'id' => $usr->id_usuario,
                    'texto' => $textoMostrar,
                    'busqueda' => $textoBusqueda,
                    'items' => $items->all(),
                    'rol' => $rol,
                    'asignado_en_ambiente' => $usr->activosCustodiados->contains('id_ambiente', (int) $idAmbiente)
                        || $usr->itemsVigentes->contains('id_ambiente', (int) $idAmbiente),
                ];
            })
            ->values();

        return response()->json($custodios);
    }

    // ==========================================
    // STORE — Solo admin (CON 3 IMÁGENES Y VALIDACIÓN FLEXIBLE)
    // ==========================================
    public function store(Request $request)
    {
        $this->authorizeActivoModification();

        $tipoBien = $request->input('tipo_bien', 'ACTIVO_FIJO');

        $data = $request->validate([
            'codigo_activo' => 'required|string|max:50|unique:activos_fijos,codigo_activo',
            'tipo_bien' => 'required|in:ACTIVO_FIJO,NO_ACTIVO',
            'nombre' => 'required|string|max:150',
            'marca' => $tipoBien === 'ACTIVO_FIJO' ? 'required|string|max:80' : 'nullable|string|max:80',
            'modelo' => $tipoBien === 'ACTIVO_FIJO' ? 'required|string|max:80' : 'nullable|string|max:80',
            'numero_serie' => $tipoBien === 'ACTIVO_FIJO' ? 'required|string|max:80' : 'nullable|string|max:80',
            'descripcion' => 'required|string|max:500',
            'id_ambiente' => 'required|exists:ambientes,id_ambiente',
            'id_categoria' => 'required|exists:categorias,id_categoria',
            'id_fuente' => 'required|exists:fuentes_financiamiento,id_fuente',
            'id_custodio' => 'required|exists:usuarios,id_usuario',
            'estado_fisico' => 'required|in:B,R,M,FF',
            'fecha_adquisicion' => 'required|date',
            'valor_adquisicion' => 'required|numeric|min:0',
            'imagen' => 'nullable|image|max:5120',
            'imagen_lateral' => 'nullable|image|max:5120',
            'imagen_detalle' => 'nullable|image|max:5120',
        ], [
            'codigo_activo.required' => 'El código del activo es obligatorio.',
            'codigo_activo.unique' => 'Este código de activo ya está registrado.',
            'nombre.required' => 'El nombre del activo es obligatorio.',
            'marca.required' => 'La marca es obligatoria para activos fijos.',
            'modelo.required' => 'El modelo es obligatorio para activos fijos.',
            'numero_serie.required' => 'El número de serie es obligatorio para activos fijos.',
            'id_ambiente.required' => 'El ambiente es obligatorio.',
            'id_categoria.required' => 'La categoría es obligatoria.',
            'id_fuente.required' => 'La fuente de financiamiento es obligatoria.',
            'id_custodio.required' => 'El custodio responsable es obligatorio.',
        ]);

        $data['tipo_bien'] = $tipoBien;
        $data['rv'] = $request->input('rv', 'NO');
        $cantidad = max(1, min(100, (int) $request->input('cantidad', 1)));
        $data['id_item'] = $this->itemDelCustodio($data['id_custodio'] ?? null);

        $imagenPath = $request->hasFile('imagen') ? $request->file('imagen')->store('activos', 'public') : null;
        $imagenLateralPath = $request->hasFile('imagen_lateral') ? $request->file('imagen_lateral')->store('activos', 'public') : null;
        $imagenDetallePath = $request->hasFile('imagen_detalle') ? $request->file('imagen_detalle')->store('activos', 'public') : null;

        $creados = 0;
        $codigoBase = $data['codigo_activo'];

        DB::transaction(function () use (&$creados, $data, $cantidad, $codigoBase, $imagenPath, $imagenLateralPath, $imagenDetallePath) {
            for ($i = 0; $i < $cantidad; $i++) {
                $item = $data;
                $item['codigo_activo'] = ($cantidad > 1)
                    ? $codigoBase.'-'.str_pad($i + 1, 2, '0', STR_PAD_LEFT)
                    : $codigoBase;

                if (ActivoFijo::where('codigo_activo', $item['codigo_activo'])->exists()) {
                    continue;
                }

                if ($imagenPath) {
                    $item['imagen'] = $imagenPath;
                }
                if ($imagenLateralPath) {
                    $item['imagen_lateral'] = $imagenLateralPath;
                }
                if ($imagenDetallePath) {
                    $item['imagen_detalle'] = $imagenDetallePath;
                }

                $activo = ActivoFijo::create($item);

                try {
                    $qrData = $this->generarQRParaActivo($item['codigo_activo']);
                    $activo->update($qrData);
                } catch (\Throwable $e) {
                    Log::warning('Error generando QR: '.$e->getMessage());
                }

                $creados++;
            }
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "¡{$creados} activo(s) registrado(s) correctamente!"]);
        }

        $idCategoriaOrigen = $request->input('id_categoria_origen') ?? $request->input('id_categoria');
        if ($idCategoriaOrigen) {
            return redirect()->route('categorias.show', $idCategoriaOrigen)
                ->with('success', "¡{$creados} activo(s) registrado(s) correctamente!");
        }

        return redirect()->route('activos.index')
            ->with('success', "¡{$creados} activo(s) registrado(s) correctamente!");
    }

    private function itemDelCustodio($idCustodio)
    {
        if (! $idCustodio) {
            return null;
        }

        return Item::query()
            ->whereHas('custodios', fn ($q) => $q
                ->where('usuarios.id_usuario', $idCustodio)
                ->whereNull('item_usuario.fecha_fin'))
            ->orderBy('id_item')
            ->value('id_item');
    }

    // ==========================================
    // SHOW por categoría — TODOS ven (Excluyendo dados de baja)
    // ==========================================
    public function show($id)
    {
        $categoria = Categoria::with('ambiente.carrera')->findOrFail($id);
        $ambiente = $categoria->ambiente;
        $carrera = $ambiente?->carrera;

        $activos = ActivoFijo::paraUsuario(Auth::user())
            ->where('id_categoria', $categoria->id_categoria)
            ->where('estado_registro', '!=', 'BAJA')
            ->with(['ambiente', 'custodio', 'fuente'])
            ->orderBy('nombre')
            ->orderBy('codigo_activo')
            ->get();

        $agrupados = $activos
            ->groupBy(fn ($a) => $a->nombre.'|'.$a->estado_fisico.'|'.$a->id_fuente.'|'.$a->gestion)
            ->map(function ($grupo) {
                $first = $grupo->first();

                return (object) [
                    'nombre' => $first->nombre,
                    'cantidad' => $grupo->count(),
                    'codigos' => $grupo->pluck('codigo_activo')->implode(', '),
                    'codigos_array' => $grupo->pluck('codigo_activo')->toArray(),
                    'ids_array' => $grupo->pluck('id_activo')->toArray(),
                    'estado_fisico' => $first->estado_fisico,
                    'estado_texto' => $first->estado_fisico_texto,
                    'fuente' => $first->fuente->nombre ?? '—',
                    'fuente_codigo' => $first->fuente->codigo ?? '',
                    'gestion' => $first->gestion,
                    'marca' => $first->marca,
                    'modelo' => $first->modelo,
                ];
            })->values();

        $totalActivos = $activos->count();
        $totalBuenos = $activos->where('estado_fisico', 'B')->count();
        $totalRegulares = $activos->where('estado_fisico', 'R')->count();
        $totalMalos = $activos->whereIn('estado_fisico', ['M', 'FF'])->count();

        $categorias = Categoria::where('id_ambiente', $ambiente->id_ambiente ?? null)->get();
        $fuentes = FuenteFinanciamiento::orderBy('nombre')->get();
        $usuarios = User::where('estado', 'ACTIVO')->orderBy('nombre_completo')->get();
        $carreras = Carrera::orderBy('nombre')->get();

        return view('categorias.show', compact(
            'ambiente', 'carrera', 'categoria', 'activos', 'agrupados',
            'totalActivos', 'totalBuenos', 'totalRegulares', 'totalMalos',
            'categorias', 'fuentes', 'usuarios', 'carreras'
        ));
    }

    // ==========================================
    // EDIT — Devuelve JSON para el modal por AJAX ⭐ CORREGIDO
    // ==========================================
    public function edit(Request $request, $id)
    {
        $this->authorizeActivoModification();
        $activo = ActivoFijo::findOrFail($id);

        if ($request->expectsJson()) {
            return response()->json($activo);
        }

        return view('activos.edit', [
            'activo' => $activo,
            'categorias' => Categoria::orderBy('nombre')->get(),
            'ambientes' => Ambiente::with('carrera')->orderBy('nombre')->get(),
            'fuentes' => FuenteFinanciamiento::orderBy('nombre')->get(),
        ]);
    }

    // ==========================================
    // UPDATE — Solo admin (CON 3 IMÁGENES)
    // ==========================================
    public function update(Request $request, $id)
    {
        $this->authorizeActivoModification();

        $activo = ActivoFijo::findOrFail($id);
        $tipoBien = $request->input('tipo_bien', $activo->tipo_bien);

        $data = $request->validate([
            'codigo_activo' => ['required', 'string', 'max:50', Rule::unique('activos_fijos', 'codigo_activo')->ignore($activo->id_activo, 'id_activo')],
            'tipo_bien' => 'required|in:ACTIVO_FIJO,NO_ACTIVO',
            'rv' => 'nullable|in:SI,NO',
            'nombre' => 'required|string|max:150',
            'marca' => $tipoBien === 'ACTIVO_FIJO' ? 'required|string|max:80' : 'nullable|string|max:80',
            'modelo' => $tipoBien === 'ACTIVO_FIJO' ? 'required|string|max:80' : 'nullable|string|max:80',
            'numero_serie' => $tipoBien === 'ACTIVO_FIJO' ? 'required|string|max:80' : 'nullable|string|max:80',
            'descripcion' => 'required|string|max:500',
            'id_ambiente' => 'required|exists:ambientes,id_ambiente',
            'id_categoria' => 'required|exists:categorias,id_categoria',
            'id_fuente' => 'required|exists:fuentes_financiamiento,id_fuente',
            'id_custodio' => 'required|exists:usuarios,id_usuario',
            'estado_fisico' => 'required|in:B,R,M,FF',
            'fecha_adquisicion' => 'required|date',
            'valor_adquisicion' => 'required|numeric|min:0',
            'imagen' => 'nullable|image|max:5120',
        ]);

        $data['tipo_bien'] = $tipoBien;
        $data['rv'] = $request->input('rv', $activo->rv ?? 'NO');
        $data['id_item'] = $this->itemDelCustodio($data['id_custodio'] ?? null);

        if ($request->hasFile('imagen')) {
            if ($activo->imagen && Storage::disk('public')->exists($activo->imagen)) {
                Storage::disk('public')->delete($activo->imagen);
            }
            $data['imagen'] = $request->file('imagen')->store('activos', 'public');
        } else {
            unset($data['imagen']);
        }

        if (isset($data['codigo_activo']) && $data['codigo_activo'] !== $activo->codigo_activo) {
            try {
                if ($activo->ruta_qr && Storage::disk('public')->exists($activo->ruta_qr)) {
                    Storage::disk('public')->delete($activo->ruta_qr);
                }
                $data = array_merge($data, $this->generarQRParaActivo($data['codigo_activo']));
            } catch (\Throwable $e) {
                Log::warning('Error regenerando QR: '.$e->getMessage());
            }
        }

        $activo->update($data);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Activo actualizado correctamente.']);
        }

        return redirect()->route('categorias.show', $activo->id_categoria)
            ->with('success', 'Activo actualizado correctamente.');
    }

    // ==========================================
    // DESTROY — Solo admin
    // ==========================================
    public function destroy($id)
    {
        $this->authorizeActivoModification();

        $activo = ActivoFijo::findOrFail($id);
        $idCategoria = $activo->id_categoria;

        if ($activo->imagen && Storage::disk('public')->exists($activo->imagen)) {
            Storage::disk('public')->delete($activo->imagen);
        }
        if ($activo->ruta_qr && Storage::disk('public')->exists($activo->ruta_qr)) {
            Storage::disk('public')->delete($activo->ruta_qr);
        }

        DB::transaction(function () use ($activo, $id) {
            // Registros hijos que referencian al activo (FK sin cascade on delete)
            DB::table('verificaciones_fisicas')->where('id_activo', $id)->delete();
            DB::table('actas_transferencia')->where('id_activo', $id)->delete();
            DB::table('actas_baja')->where('id_activo', $id)->delete();

            if (Schema::hasTable('detalle_acta_bajas')) {
                DB::table('detalle_acta_bajas')->where('activo_id', $id)->delete();
            }
            if (Schema::hasTable('detalle_acta_activos')) {
                DB::table('detalle_acta_activos')->where('activo_id', $id)->delete();
            }

            $activo->delete();
        });

        return redirect()->route('categorias.show', $idCategoria)
            ->with('success', 'Activo eliminado correctamente.');
    }

    public function destroyMany(Request $request)
    {
        $this->authorizeActivoModification();

        $datos = $request->validate([
            'activos' => ['required', 'array', 'min:1'],
            'activos.*' => ['integer', 'distinct', 'exists:activos_fijos,id_activo'],
        ], [
            'activos.required' => 'Seleccione al menos un activo.',
        ]);

        DB::transaction(function () use ($datos): void {
            foreach (ActivoFijo::whereIn('id_activo', $datos['activos'])->get() as $activo) {
                foreach ([$activo->imagen, $activo->ruta_qr] as $archivo) {
                    if ($archivo && Storage::disk('public')->exists($archivo)) {
                        Storage::disk('public')->delete($archivo);
                    }
                }

                DB::table('verificaciones_fisicas')->where('id_activo', $activo->id_activo)->delete();
                DB::table('actas_transferencia')->where('id_activo', $activo->id_activo)->delete();
                DB::table('actas_baja')->where('id_activo', $activo->id_activo)->delete();

                if (Schema::hasTable('detalle_acta_bajas')) {
                    DB::table('detalle_acta_bajas')->where('activo_id', $activo->id_activo)->delete();
                }
                if (Schema::hasTable('detalle_acta_activos')) {
                    DB::table('detalle_acta_activos')->where('activo_id', $activo->id_activo)->delete();
                }

                $activo->delete();
            }
        });

        return redirect()->route('activos.index')
            ->with('success', count($datos['activos']).' activo(s) eliminado(s) correctamente.');
    }

    public function buscarPorCodigo(Request $request, $codigo = null)
    {
        /** @var User|null $user */
        $user = Auth::user();

        $codigoLimpio = trim((string) ($codigo ?: $request->query('codigo')));

        if ($codigoLimpio === '') {
            return response()->json([
                'success' => false,
                'message' => 'Indica el código del activo.',
            ], 422);
        }

        if (preg_match('#/activos/codigo/([^/?]+)#', $codigoLimpio, $coincidencias)) {
            $codigoLimpio = urldecode($coincidencias[1]);
        }

        $codigoLimpio = trim($codigoLimpio);

        $activo = ActivoFijo::with(['custodio', 'ambiente.carrera', 'categoria'])
            ->paraUsuario($user)
            ->where('estado_registro', '!=', 'BAJA')
            ->where(function ($q) use ($codigoLimpio) {
                $q->where('codigo_activo', $codigoLimpio)
                    ->orWhere('numero_serie', $codigoLimpio);
            })
            ->first();

        if (! $activo) {
            return response()->json([
                'success' => false,
                'message' => 'El código QR no se encuentra registrado o se encuentra dado de baja.',
            ], 404);
        }

        $ambienteId = $request->query('ambiente_id');
        $esDelAmbiente = $ambienteId ? $activo->id_ambiente == $ambienteId : true;
        $esMio = (int) $activo->id_custodio === (int) $user->id_usuario;

        return response()->json([
            'success' => true,
            'es_del_ambiente' => $esDelAmbiente,
            'es_mi_activo' => $esMio,
            'ambiente_escaneado' => $ambienteId ? (int) $ambienteId === (int) $activo->id_ambiente : null,
            'activo' => [
                'id' => $activo->id_activo,
                'codigo_activo' => $activo->codigo_activo,
                'nombre' => $activo->nombre,
                'marca' => $activo->marca,
                'modelo' => $activo->modelo,
                'numero_serie' => $activo->numero_serie,
                'tipo_bien' => $activo->tipo_bien,
                'tipo_bien_texto' => $activo->tipo_bien_texto,
                'estado_registro' => $activo->estado_registro,
                'estado_registro_texto' => $activo->estado_registro_texto,
                'estado_fisico_texto' => $activo->estado_fisico_texto,
                'id_ambiente' => $activo->id_ambiente,
                'ambiente' => $activo->ambiente?->nombre,
                'id_carrera' => $activo->ambiente?->id_carrera,
                'carrera' => $activo->ambiente?->carrera?->nombre,
                'id_categoria' => $activo->id_categoria,
                'categoria' => $activo->categoria?->nombre,
                'id_custodio' => $activo->id_custodio,
                'custodio' => $activo->custodio?->nombre_completo,
            ],
        ]);
    }

    public function descargarQr($id)
    {
        $activo = ActivoFijo::findOrFail($id);

        if ($activo->ruta_qr && Storage::disk('public')->exists($activo->ruta_qr)) {
            $path = Storage::disk('public')->path($activo->ruta_qr);
            $extension = pathinfo($path, PATHINFO_EXTENSION);

            return response()->download($path, 'QR-'.$activo->codigo_activo.'.'.$extension);
        }

        return back()->with('error', 'El archivo QR no existe. Regenera el activo.');
    }

    public function pdf(Request $request)
    {
        $ambiente = null;
        $categoria = null;
        $tipoBien = $this->tipoBienExportado($request);

        $query = ActivoFijo::with(['categoria', 'ambiente.carrera', 'fuente', 'custodio', 'item'])
            ->where('estado_registro', '!=', 'BAJA')
            ->tipoBien($tipoBien);

        if ($request->filled('id_ambiente')) {
            $ambiente = Ambiente::find($request->id_ambiente);
            $query->where('id_ambiente', $request->id_ambiente);
        }
        if ($request->filled('id_categoria')) {
            $categoria = Categoria::find($request->id_categoria);
            $query->where('id_categoria', $request->id_categoria);
        }
        if ($request->filled('buscar')) {
            $s = $request->buscar;
            $query->where(function ($q) use ($s) {
                $q->where('codigo_activo', 'like', "%{$s}%")
                    ->orWhere('nombre', 'like', "%{$s}%")
                    ->orWhere('numero_serie', 'like', "%{$s}%");
            });
        }

        $activos = $query->orderBy('tipo_bien')->orderBy('nombre')->get();

        $pdf = PdfHelper::oficio(
            'reportes.pdf.inventario_general',
            ['activos' => $activos, 'agrupados' => $this->agruparPorNombre($activos), 'tipoBien' => $tipoBien],
            'landscape'
        );

        return $pdf->stream('Inventario_'.$this->sufijoTipoBien($tipoBien).date('Y-m-d_His').'.pdf');
    }

    public function excel(Request $request)
    {
        $ambiente = null;
        $categoria = null;
        $tipoBien = $this->tipoBienExportado($request);

        $query = ActivoFijo::with(['categoria', 'ambiente.carrera', 'fuente', 'custodio', 'item'])
            ->where('estado_registro', '!=', 'BAJA')
            ->tipoBien($tipoBien);

        if ($request->filled('id_ambiente')) {
            $ambiente = Ambiente::find($request->id_ambiente);
            $query->where('id_ambiente', $request->id_ambiente);
        }
        if ($request->filled('id_categoria')) {
            $categoria = Categoria::find($request->id_categoria);
            $query->where('id_categoria', $request->id_categoria);
        }
        if ($request->filled('buscar')) {
            $s = $request->buscar;
            $query->where(function ($q) use ($s) {
                $q->where('codigo_activo', 'like', "%{$s}%")
                    ->orWhere('nombre', 'like', "%{$s}%")
                    ->orWhere('numero_serie', 'like', "%{$s}%");
            });
        }

        $activos = $query->orderBy('tipo_bien')->orderBy('nombre')->get();

        return response()
            ->view('reportes.excel.inventario_general', compact('activos', 'tipoBien'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition',
                'attachment; filename="Inventario_'.$this->sufijoTipoBien($tipoBien).date('Y-m-d').'.xls"');
    }

    private function tipoBienExportado(Request $request): string
    {
        $tipo = $request->input('tipo_bien', 'TODOS');

        return in_array($tipo, ['ACTIVO_FIJO', 'NO_ACTIVO'], true) ? $tipo : 'TODOS';
    }

    private function sufijoTipoBien(string $tipo): string
    {
        return match ($tipo) {
            'ACTIVO_FIJO' => 'Activos_Fijos_',
            'NO_ACTIVO' => 'No_Activos_',
            default => 'Todos_',
        };
    }

    protected function activoVisible($id): ActivoFijo
    {
        return ActivoFijo::paraUsuario(Auth::user())->where('estado_registro', '!=', 'BAJA')->findOrFail($id);
    }

    private function agruparPorNombre($activos)
    {
        return $activos->groupBy(fn ($a) => $a->nombre.'|'.$a->marca.'|'.$a->modelo)
            ->map(function ($grupo, $clave) {
                $primero = $grupo->first();

                return (object) [
                    'codigos' => $grupo->pluck('codigo_activo')->implode(', '),
                    'nombre' => $primero->nombre,
                    'descripcion' => $primero->descripcion,
                    'marca' => $primero->marca,
                    'modelo' => $primero->modelo,
                    'cantidad' => $grupo->count(),
                    'estado_texto' => $primero->estado_registro_texto,
                    'fuente_codigo' => $primero->fuente?->codigo ?? '—',
                    'gestion' => $primero->gestion,
                    'observaciones' => $primero->observaciones,
                    'tipo_bien_texto' => $primero->tipo_bien_texto,
                ];
            })
            ->values();
    }

    protected function generarQRParaActivo(string $codigoActivo): array
    {
        $contenido = route('activos.buscarPorCodigo', ['codigo' => $codigoActivo]);
        $fileName = 'qrcodes/'.Str::slug($codigoActivo).'-'.uniqid().'.svg';

        $svg = QrCode::format('svg')
            ->size(300)
            ->margin(1)
            ->generate($contenido);

        Storage::disk('public')->put($fileName, $svg);

        return [
            'codigo_qr' => $contenido,
            'qr_code' => $fileName,
            'ruta_qr' => $fileName,
        ];
    }

    public function verGrupo($id_categoria, $nombre)
    {
        $categoria = Categoria::with('ambiente.carrera')->findOrFail($id_categoria);
        $ambiente = $categoria->ambiente;
        $carrera = $ambiente?->carrera;

        $nombreDecoded = urldecode($nombre);

        $activos = ActivoFijo::paraUsuario(Auth::user())
            ->where('id_categoria', $categoria->id_categoria)
            ->where('estado_registro', '!=', 'BAJA')
            ->where('nombre', 'LIKE', "%{$nombreDecoded}%")
            ->with(['ambiente', 'custodio', 'fuente'])
            ->orderBy('codigo_activo')
            ->get();

        $totalActivos = $activos->count();
        $totalBuenos = $activos->where('estado_fisico', 'B')->count();
        $totalRegulares = $activos->where('estado_fisico', 'R')->count();
        $totalMalos = $activos->whereIn('estado_fisico', ['M', 'FF'])->count();

        $fuentes = FuenteFinanciamiento::orderBy('nombre')->get();
        $usuarios = User::where('estado', 'ACTIVO')->orderBy('nombre_completo')->get();
        $carreras = Carrera::orderBy('nombre')->get();

        return view('categorias.grupo', compact(
            'categoria', 'ambiente', 'carrera',
            'activos', 'nombreDecoded',
            'totalActivos', 'totalBuenos', 'totalRegulares', 'totalMalos',
            'fuentes', 'usuarios', 'carreras'
        ));
    }

    public function etiqueta($id)
    {
        $activo = $this->activoVisible($id);
        $qrImage = '';

        if ($activo->ruta_qr && Storage::disk('public')->exists($activo->ruta_qr)) {
            $qrImage = Storage::disk('public')->get($activo->ruta_qr);
        } else {
            try {
                $contenido = route('activos.buscarPorCodigo', ['codigo' => $activo->codigo_activo]);
                $qrImage = QrCode::format('svg')
                    ->size(300)
                    ->margin(1)
                    ->generate($contenido);
            } catch (\Throwable $e) {
                $qrImage = '<p style="color:#dc2626;font-size:10px;">QR no disponible</p>';
            }
        }

        return view('activos.etiqueta', compact('activo', 'qrImage'));
    }

    // ============================================================
    // ETIQUETAS MÚLTIPLES — PDF para imprimir varias de una vez
    // ============================================================
    public function etiquetas(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '256M');

        $ids = $request->input('ids', []);
        $idCategoria = $request->input('id_categoria');

        $query = ActivoFijo::with(['ambiente', 'categoria', 'fuente', 'custodio'])
            ->where('estado_registro', '!=', 'BAJA');

        if (! empty($ids)) {
            $query->whereIn('id_activo', $ids);
        } elseif ($idCategoria) {
            $query->where('id_categoria', $idCategoria);
        }

        $activos = $query->orderBy('codigo_activo')->get();

        if ($activos->isEmpty()) {
            return back()->with('error', 'No hay activos para generar etiquetas.');
        }

        // Generar QRs que faltan (PNG)
        foreach ($activos as $activo) {
            if (! $activo->ruta_qr || ! Storage::disk('public')->exists($activo->ruta_qr)) {
                try {
                    $contenido = route('activos.buscarPorCodigo', ['codigo' => $activo->codigo_activo]);
                    $fileName = 'qrcodes/'.Str::slug($activo->codigo_activo).'-'.uniqid().'.png';
                    $png = QrCode::format('png')
                        ->size(200)
                        ->margin(1)
                        ->generate($contenido);
                    Storage::disk('public')->put($fileName, $png);
                    $activo->update(['ruta_qr' => $fileName, 'qr_code' => $contenido]);
                } catch (\Throwable $e) {
                    Log::warning('Error generando QR para activo '.$activo->id_activo.': '.$e->getMessage());
                }
            }
        }

        // Preparar data URIs para las imágenes (evita peticiones HTTP adicionales en DomPDF)
        $logoDataUri = '';
        $logoPath = public_path('images/logo.png');
        if (file_exists($logoPath)) {
            $logoDataUri = 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath));
        }

        foreach ($activos as $activo) {
            $activo->qr_data_uri = '';
            if ($activo->ruta_qr && Storage::disk('public')->exists($activo->ruta_qr)) {
                $ruta = Storage::disk('public')->path($activo->ruta_qr);
                if (file_exists($ruta)) {
                    $ext = pathinfo($ruta, PATHINFO_EXTENSION);
                    $mime = $ext === 'svg' ? 'image/svg+xml' : 'image/png';
                    $activo->qr_data_uri = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($ruta));
                }
            }
        }

        try {
            $pdf = Pdf::loadView('activos.etiquetas', compact('activos', 'logoDataUri'))
                ->setPaper('letter', 'portrait');

            return $pdf->stream('Etiquetas_'.date('Y-m-d_His').'.pdf');
        } catch (\Throwable $e) {
            Log::error('Error generando PDF de etiquetas: '.$e->getMessage());

            return back()->with('error', 'Error al generar el PDF de etiquetas: '.$e->getMessage());
        }
    }

    // ============================================================
    // NO ACTIVOS — Artículos clasificados como No Activos (ej. grapadoras, lápices)
    // ============================================================
    public function noActivos(Request $request)
    {
        $query = ActivoFijo::with(['categoria', 'ambiente.carrera', 'fuente', 'custodio'])
            ->where('tipo_bien', 'NO_ACTIVO')
            ->where('estado_registro', '!=', 'BAJA');

        if ($request->filled('buscar')) {
            $s = $request->buscar;
            $query->where(function ($q) use ($s) {
                $q->where('codigo_activo', 'like', "%{$s}%")
                    ->orWhere('nombre', 'like', "%{$s}%")
                    ->orWhere('numero_serie', 'like', "%{$s}%")
                    ->orWhere('marca', 'like', "%{$s}%")
                    ->orWhere('modelo', 'like', "%{$s}%");
            });
        }

        $activos = $query->orderByDesc('updated_at')->get();
        $totalNoActivos = $activos->count();

        return view('activos.no_activos', compact('activos', 'totalNoActivos'));
    }

    public function tarjeta($id)
    {
        $activo = ActivoFijo::with([
            'categoria',
            'ambiente.carrera',
            'fuenteFinanciamiento',
            'custodioActual',
        ])->where('estado_registro', '!=', 'BAJA')->findOrFail($id);

        return view('activos.tarjeta', compact('activo'));
    }

    public function activosBaja(Request $request)
    {
        $user = Auth::user();

        $query = ActivoFijo::with(['categoria', 'ambiente.carrera', 'custodio'])
            ->paraUsuario($user)
            ->where('estado_registro', 'BAJA');

        if ($request->filled('q')) {
            $bus = $request->q;
            $query->where(function ($s) use ($bus) {
                $s->where('codigo_activo', 'like', "%{$bus}%")
                    ->orWhere('nombre', 'like', "%{$bus}%")
                    ->orWhere('marca', 'like', "%{$bus}%");
            });
        }

        $activos = $query->orderBy('updated_at', 'desc')->paginate(15)->appends($request->query());

        return view('activos.baja', compact('activos'));
    }
}
