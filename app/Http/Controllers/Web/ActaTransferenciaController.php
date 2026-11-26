<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActaTransferencia;
use App\Models\ActivoFijo;
use App\Models\Ambiente;
use App\Models\Carrera;
use App\Models\Categoria;
use App\Models\User;
use App\Support\PdfHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActaTransferenciaController extends Controller
{
    // ============================================================
    // INDEX — Listado de transferencias
    // ============================================================
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = ActaTransferencia::with([
            'activo',
            'origenAmbiente',
            'destinoAmbiente',
            'custodioAnterior',
            'custodioNuevo',
            'aprobador',
        ])->latest();

        if (! $user->esAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('id_custodio_anterior', $user->id_usuario)
                    ->orWhere('id_custodio_nuevo', $user->id_usuario);
            });
        }

        if ($request->filled('estado') && in_array($request->estado, [
            ActaTransferencia::ESTADO_PENDIENTE,
            ActaTransferencia::ESTADO_APROBADA,
            ActaTransferencia::ESTADO_RECHAZADA,
        ], true)) {
            $query->where('estado', $request->estado);
        }

        $transferencias = $query->paginate(15)->appends($request->query());

        $ambientesVisibles = $user->ambientesVisibles()->with('carrera')->orderBy('nombre')->get();

        return view('actas.transferencia.index', [
            'transferencias' => $transferencias,
            'activos' => ActivoFijo::paraUsuario($user)->where('estado_registro', '!=', 'BAJA')->get(),
            'ambientes' => $ambientesVisibles,
            'usuarios' => User::where('estado', 'ACTIVO')->orderBy('nombre_completo')->get(),
            'pendientes' => ActaTransferencia::pendientes()->count(),
        ]);
    }

    // ============================================================
    // CREATE — Formulario de nueva transferencia (CORREGIDO)
    // ============================================================
    public function create(Request $request)
    {
        $user = Auth::user();

        // Obtener activos disponibles para la transferencia
        $queryActivos = ActivoFijo::with(['categoria', 'ambiente.carrera', 'custodio'])
            ->paraUsuario($user)
            ->where('estado_registro', '!=', 'BAJA')
            ->where('estado_registro', '!=', 'EN_TRANSFERENCIA');

        // Filtros para activos: carrera y ambiente de origen
        $idCarreraActivoPreselected = $request->input('id_carrera_activo');
        $idAmbienteActivoPreselected = $request->input('id_ambiente_activo');

        if ($idCarreraActivoPreselected) {
            $queryActivos->whereHas('ambiente', function ($q) use ($idCarreraActivoPreselected) {
                $q->where('id_carrera', $idCarreraActivoPreselected);
            });
        }
        if ($idAmbienteActivoPreselected) {
            $queryActivos->where('id_ambiente', $idAmbienteActivoPreselected);
        }

        if ($request->filled('q')) {
            $bus = $request->q;
            $queryActivos->where(function ($s) use ($bus) {
                $s->where('codigo_activo', 'like', "%{$bus}%")
                    ->orWhere('nombre', 'like', "%{$bus}%");
            });
        }

        $activosDisponibles = $queryActivos->orderBy('nombre')->get();

        // Obtener ambientes de destino disponibles
        $queryAmbientes = Ambiente::with('carrera')->orderBy('nombre');

        // Filtrar por carrera si se especifica (?id_carrera=X)
        $idCarreraPreselected = $request->input('id_carrera');
        if ($idCarreraPreselected) {
            $queryAmbientes->where('id_carrera', $idCarreraPreselected);
        }

        $ambientesDisponibles = $queryAmbientes->get();

        // Obtener carreras para el filtro
        $carrerasDisponibles = Carrera::orderBy('nombre')->get();

        // Obtener ambientes para el filtro de activos (origen)
        $ambientesParaFiltroActivos = Ambiente::with('carrera')->orderBy('nombre')->get();

        // Obtener categorías para el destino (se filtran por ambiente en el frontend)
        $categoriasDisponibles = Categoria::with('ambiente.carrera')->orderBy('nombre')->get();

        // Preselecciones desde query string (?id_activo=X&id_ambiente_destino=Y&id_categoria=Z)
        $idActivoPreselected = $request->input('id_activo');
        $idAmbientePreselected = $request->input('id_ambiente_destino');
        $idCategoriaPreselected = $request->input('id_categoria');

        // Obtener usuarios activos (nuevos custodios)
        $custodiosQuery = User::where('estado', 'ACTIVO')
            ->with(['items' => fn ($q) => $q->whereNull('item_usuario.fecha_fin')])
            ->orderBy('nombre_completo');

        // Si se preseleccionó un ambiente destino, filtrar solo custodios de ese ambiente
        if ($idAmbientePreselected) {
            $custodiosQuery->where(function ($q) use ($idAmbientePreselected) {
                $q->whereHas('activosCustodiados', function ($sub) use ($idAmbientePreselected) {
                    $sub->where('id_ambiente', $idAmbientePreselected);
                })
                    ->orWhereHas('itemsVigentes', function ($sub) use ($idAmbientePreselected) {
                        $sub->where('items.id_ambiente', $idAmbientePreselected);
                    })
                    ->orWhereExists(function ($sub) use ($idAmbientePreselected) {
                        $sub->select(DB::raw(1))
                            ->from('ambiente_user')
                            ->whereColumn('ambiente_user.id_usuario', 'usuarios.id_usuario')
                            ->where('ambiente_user.id_ambiente', $idAmbientePreselected);
                    });
            });
        }

        $usuarios = $custodiosQuery->get();
        if ($usuarios->isEmpty() && $idAmbientePreselected) {
            $usuarios = User::where('estado', 'ACTIVO')
                ->with(['items' => fn ($q) => $q->whereNull('item_usuario.fecha_fin')])
                ->orderBy('nombre_completo')
                ->get();
        }

        return view('actas.transferencia.create', compact(
            'activosDisponibles',
            'ambientesDisponibles',
            'usuarios',
            'categoriasDisponibles',
            'carrerasDisponibles',
            'ambientesParaFiltroActivos',
            'idActivoPreselected',
            'idAmbientePreselected',
            'idCategoriaPreselected',
            'idCarreraPreselected',
            'idCarreraActivoPreselected',
            'idAmbienteActivoPreselected'
        ));
    }

    /**
     * Endpoint AJAX para obtener custodios de un ambiente específico (transferencia).
     */
    public function custodiosPorAmbiente(Request $request, $idAmbiente)
    {
        Log::info('Endpoint custodiosPorAmbiente llamado', [
            'idAmbiente' => $idAmbiente,
            'user_id' => $request->user()?->id_usuario,
        ]);

        $usuarios = User::where('estado', 'ACTIVO')
            ->with(['items' => fn ($q) => $q->whereNull('item_usuario.fecha_fin')])
            ->where(function ($q) use ($idAmbiente) {
                // Usuarios que tienen activos custodiados en este ambiente
                $q->whereHas('activosCustodiados', function ($sub) use ($idAmbiente) {
                    $sub->where('id_ambiente', $idAmbiente);
                })
                // O usuarios con ítems vigentes en este ambiente
                    ->orWhereHas('itemsVigentes', function ($sub) use ($idAmbiente) {
                        $sub->where('items.id_ambiente', $idAmbiente);
                    })
                // O usuarios asignados directamente en la tabla pivot ambiente_user (botón "Asignar" de carreras)
                    ->orWhereExists(function ($sub) use ($idAmbiente) {
                        $sub->select(DB::raw(1))
                            ->from('ambiente_user')
                            ->whereColumn('ambiente_user.id_usuario', 'usuarios.id_usuario')
                            ->where('ambiente_user.id_ambiente', $idAmbiente);
                    });
            })
            ->orderBy('nombre_completo')
            ->get();

        $fallback = $usuarios->isEmpty();
        if ($fallback) {
            $usuarios = User::where('estado', 'ACTIVO')
                ->with(['items' => fn ($q) => $q->whereNull('item_usuario.fecha_fin')])
                ->orderBy('nombre_completo')
                ->get();
        }

        $custodios = $usuarios
            ->map(function ($usr) {
                $itemActivo = $usr->items->first();
                $nroItem = $itemActivo ? 'Ítem #'.$itemActivo->numero_item : 'Sin ítem';
                $textoMostrar = "{$usr->nombre_completo} (C.I.: {$usr->ci} · {$nroItem})";
                $textoBusqueda = strtolower($usr->nombre_completo.' '.$usr->ci.' '.($itemActivo?->numero_item ?? ''));

                return [
                    'id' => $usr->id_usuario,
                    'texto' => $textoMostrar,
                    'busqueda' => $textoBusqueda,
                ];
            })
            ->values();

        Log::info('Custodios encontrados para ambiente', [
            'idAmbiente' => $idAmbiente,
            'count' => $custodios->count(),
            'fallback' => $fallback,
        ]);

        return response()->json([
            'custodios' => $custodios,
            'fallback' => $fallback,
        ]);
    }

    // ============================================================
    // STORE — Registrar la transferencia (uno o varios activos)
    // ============================================================
    public function store(Request $request)
    {
        $user = Auth::user();

        if (! $user->puedeSolicitarBajaOTransferencia()) {
            abort(403, 'No tiene permisos para solicitar transferencias.');
        }

        $data = $request->validate([
            'id_activos' => 'required|array|min:1',
            'id_activos.*' => 'required|exists:activos_fijos,id_activo',
            'id_ambiente_destino' => 'required|exists:ambientes,id_ambiente',
            'id_categoria' => 'nullable|exists:categorias,id_categoria',
            'id_custodio' => 'required|exists:usuarios,id_usuario',
            'observaciones' => 'nullable|string|max:500',
            'fecha_transferencia' => 'required|date|before_or_equal:today',
        ], [
            'id_activos.required' => 'Debe seleccionar al menos un activo.',
            'id_activos.array' => 'La selección de activos no es válida.',
            'id_ambiente_destino.required' => 'Debe seleccionar el ambiente destino.',
            'id_custodio.required' => 'Debe seleccionar el nuevo custodio.',
            'fecha_transferencia.required' => 'La fecha de transferencia es obligatoria.',
            'fecha_transferencia.before_or_equal' => 'La fecha de transferencia no puede ser futura.',
        ]);

        $idsActivos = array_unique(array_map('intval', $data['id_activos']));

        $activos = ActivoFijo::with(['categoria', 'ambiente.carrera', 'custodio'])
            ->whereIn('id_activo', $idsActivos)
            ->get();

        if ($activos->count() !== count($idsActivos)) {
            return back()->withInput()->with('error', 'Uno o más activos seleccionados no existen.');
        }

        $permitidos = ActivoFijo::select('id_activo')->paraUsuario($user)->pluck('id_activo');
        foreach ($activos as $activo) {
            if (! $permitidos->contains($activo->id_activo)) {
                abort(403, 'El activo '.$activo->codigo_activo.' no pertenece a su ámbito.');
            }
            if ($activo->estado_registro === 'BAJA') {
                return back()->withInput()->with('error', 'No se puede transferir el activo '.$activo->codigo_activo.' porque está dado de baja.');
            }
            if ($activo->estado_registro === 'EN_TRANSFERENCIA') {
                return back()->withInput()->with('error', 'El activo '.$activo->codigo_activo.' ya tiene una transferencia pendiente de aprobación.');
            }
            if ((int) $data['id_custodio'] === (int) $activo->id_custodio && (int) $data['id_ambiente_destino'] === (int) $activo->id_ambiente) {
                return back()->withInput()->with('error', 'El activo '.$activo->codigo_activo.' ya pertenece a ese custodio y se encuentra en el mismo ambiente.');
            }
        }

        $activoPrimero = $activos->first();

        $numero = 'TRANS-'.now()->format('Y').'-'.str_pad(
            (string) ((ActaTransferencia::max('id_acta_transferencia') ?? 0) + 1),
            4, '0', STR_PAD_LEFT
        );

        $esAdmin = $user->esAdmin();

        $acta = DB::transaction(function () use ($data, $activos, $activoPrimero, $user, $numero, $esAdmin) {
            $acta = ActaTransferencia::create([
                'numero_acta' => $numero,
                'id_activo' => $activoPrimero->id_activo,
                'id_origen_ambiente' => $activoPrimero->id_ambiente,
                'id_destino_ambiente' => $data['id_ambiente_destino'],
                'id_custodio_anterior' => $activoPrimero->id_custodio ?? $user->id_usuario,
                'id_custodio_nuevo' => $data['id_custodio'],
                'fecha_transferencia' => $data['fecha_transferencia'],
                'observaciones' => $data['observaciones'] ?? null,
                'estado' => $esAdmin ? ActaTransferencia::ESTADO_APROBADA : ActaTransferencia::ESTADO_PENDIENTE,
                'id_aprobador' => $esAdmin ? $user->id_usuario : null,
                'fecha_aprobacion' => $esAdmin ? now() : null,
                'estado_anterior' => $activoPrimero->estado_registro,
            ]);

            // Insertar cada activo en la tabla pivote
            foreach ($activos as $activo) {
                $acta->activos()->attach($activo->id_activo, ['cantidad' => 1]);
            }

            if ($esAdmin) {
                // Usar la categoría seleccionada en el formulario, si se proporcionó
                if (! empty($data['id_categoria'])) {
                    $categoriaDestino = Categoria::find($data['id_categoria']);
                } else {
                    // ⭐ Localizar una categoría existente en el ambiente destino que coincida en nombre,
                    // o tomar la primera categoría válida del nuevo ambiente de destino.
                    $categoriaDestino = Categoria::where('nombre', $activoPrimero->categoria->nombre ?? '')
                        ->first()
                        ?? Categoria::query()->orderBy('id_categoria')->first();

                    // Si el ambiente destino no tiene ninguna categoría creada todavía, creamos una por defecto
                    if (! $categoriaDestino) {
                        $categoriaDestino = Categoria::create([
                            'id_ambiente' => $data['id_ambiente_destino'],
                            'nombre' => 'General - '.($activoPrimero->categoria->nombre ?? 'Activos'),
                        ]);
                    }
                }

                // ⭐ Traslado completo e inmediato de todos los activos
                foreach ($activos as $activo) {
                    $activo->update([
                        'id_ambiente' => $data['id_ambiente_destino'],
                        'id_categoria' => $categoriaDestino->id_categoria,
                        'id_custodio' => $data['id_custodio'],
                        'estado_registro' => 'ASIGNADO',
                    ]);
                }
            } else {
                foreach ($activos as $activo) {
                    $activo->update([
                        'estado_registro' => 'EN_TRANSFERENCIA',
                    ]);
                }
            }

            return $acta;
        });

        $mensaje = $esAdmin
            ? "Transferencia {$numero} registrada y aplicada de inmediato al nuevo ambiente."
            : "Transferencia {$numero} solicitada. Queda pendiente de aprobación de la administración.";

        return redirect()
            ->route('actas.transferencia.show', $acta)
            ->with('status', $mensaje);
    }

    // ============================================================
    // APROBAR — Solo administración. Aquí sí se mueve el bien.
    // ============================================================
    public function aprobar(Request $request, ActaTransferencia $acta)
    {
        $this->autorizarAprobacion($acta);

        $data = $request->validate([
            'comentario' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($acta, $data, $request) {
            $acta->update([
                'estado' => ActaTransferencia::ESTADO_APROBADA,
                'id_aprobador' => $request->user()->id_usuario,
                'fecha_aprobacion' => now(),
                'comentario_aprobacion' => $data['comentario'] ?? null,
            ]);

            foreach ($acta->activos as $activo) {
                $activo->update([
                    'id_ambiente' => $acta->id_destino_ambiente,
                    'id_custodio' => $acta->id_custodio_nuevo,
                    'estado_registro' => 'ASIGNADO',
                ]);
            }
        });

        return back()->with('status', "Transferencia {$acta->numero_acta} aprobada. Los bienes ya figuran en el ambiente destino.");
    }

    // ============================================================
    // RECHAZAR — Deja el bien como estaba.
    // ============================================================
    public function rechazar(Request $request, ActaTransferencia $acta)
    {
        $this->autorizarAprobacion($acta);

        $data = $request->validate([
            'comentario' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($acta, $data, $request) {
            $acta->update([
                'estado' => ActaTransferencia::ESTADO_RECHAZADA,
                'id_aprobador' => $request->user()->id_usuario,
                'fecha_aprobacion' => now(),
                'comentario_aprobacion' => $data['comentario'] ?? 'Rechazado por la administración.',
            ]);

            foreach ($acta->activos as $activo) {
                $activo->update([
                    'estado_registro' => $acta->estado_anterior ?: 'ASIGNADO',
                ]);
            }
        });

        return back()->with('status', "Transferencia {$acta->numero_acta} rechazada. El bien sigue en su ambiente de origen.");
    }

    protected function autorizarAprobacion(ActaTransferencia $acta): void
    {
        abort_unless(Auth::user()?->puedeAprobar(), 403, 'Solo la administración aprueba transferencias.');
        abort_if(! $acta->esPendiente(), 422, 'Esta transferencia ya fue procesada.');
    }

    // ============================================================
    // SHOW — Ver detalle de una transferencia
    // ============================================================
    public function show(ActaTransferencia $acta)
    {
        $this->authorizeActa($acta);

        $acta->load([
            'activos.categoria',
            'activos.ambiente.carrera',
            'activos.fuente',
            'origenAmbiente.carrera',
            'destinoAmbiente.carrera',
            'custodioAnterior.carrera',
            'custodioNuevo.carrera',
        ]);

        return view('actas.transferencia.show', compact('acta'));
    }

    // ============================================================
    // PDF — Acta de Transferencia en tamaño OFICIO HORIZONTAL
    // ============================================================
    public function pdf(ActaTransferencia $acta)
    {
        $this->authorizeActa($acta);

        $acta->load([
            'activos.categoria',
            'activos.ambiente.carrera',
            'activos.fuente',
            'origenAmbiente.carrera',
            'destinoAmbiente.carrera',
            'custodioAnterior.carrera',
            'custodioNuevo.carrera',
        ]);

        $pdf = PdfHelper::oficio('actas.transferencia.pdf', compact('acta'), 'landscape');

        return $pdf->stream('Acta_Transferencia_'.$acta->numero_acta.'.pdf');
    }

    // ============================================================
    // EXCEL — Acta de Transferencia en formato tabular
    // ============================================================
    public function excel(ActaTransferencia $acta)
    {
        $this->authorizeActa($acta);

        $acta->load([
            'activos.categoria',
            'activos.ambiente.carrera',
            'activos.fuente',
            'origenAmbiente.carrera',
            'destinoAmbiente.carrera',
            'custodioAnterior.carrera',
            'custodioNuevo.carrera',
        ]);

        return response()->view('actas.transferencia.excel', compact('acta'));
    }

    protected function authorizeActa(ActaTransferencia $acta): void
    {
        $user = Auth::user();

        if (! $user) {
            abort(403, 'No autenticado.');
        }

        if ($user->esAdmin()) {
            return;
        }

        if ((int) $acta->id_custodio_anterior === (int) $user->id_usuario) {
            return;
        }
        if ((int) $acta->id_custodio_nuevo === (int) $user->id_usuario) {
            return;
        }

        if ($user->esOperativo()) {
            return;
        }

        $ambientesVisibles = $user->idsAmbientesVisibles();

        if (! empty($ambientesVisibles)) {
            $visibles = $ambientesVisibles
                ->map(fn ($id) => (int) $id)
                ->all();

            if (in_array((int) $acta->id_origen_ambiente, $visibles, true)
                || in_array((int) $acta->id_destino_ambiente, $visibles, true)) {
                return;
            }
        }

        abort(403, 'No autorizado para ver esta acta.');
    }
}
