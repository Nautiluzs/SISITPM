<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActaBaja;
use App\Models\ActivoFijo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ActaBajaController extends Controller
{
    // ============================================================
    // INDEX — Listado de actas de baja
    // ============================================================
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = ActaBaja::with(['activo', 'usuario', 'aprobador'])->latest();

        if (! $user->puedeVerTodo()) {
            $query->whereHas('activo', fn ($activoQuery) => $activoQuery->paraUsuario($user));
        }

        if ($request->filled('estado') && in_array($request->estado, [
            ActaBaja::ESTADO_PENDIENTE,
            ActaBaja::ESTADO_APROBADA,
            ActaBaja::ESTADO_RECHAZADA,
        ], true)) {
            $query->where('estado', $request->estado);
        }

        $actas = $query->paginate(15)->appends($request->query());
        $pendientesQuery = ActaBaja::where('estado', ActaBaja::ESTADO_PENDIENTE);
        if (! $user->puedeVerTodo()) {
            $pendientesQuery->whereHas('activo', fn ($activoQuery) => $activoQuery->paraUsuario($user));
        }
        $pendientes = $pendientesQuery->count();

        return view('actas.baja.index', compact('actas', 'pendientes'));
    }

    // ============================================================
    // CREATE — Formulario de solicitud de baja
    // ============================================================
    public function create(Request $request)
    {
        $user = Auth::user();

        $query = ActivoFijo::with(['categoria', 'ambiente.carrera', 'custodio'])
            ->paraUsuario($user)
            ->where('estado_registro', '!=', 'BAJA')
            ->where('estado_registro', '!=', 'EN_TRANSFERENCIA');

        if ($request->filled('q')) {
            $bus = $request->q;
            $query->where(function ($s) use ($bus) {
                $s->where('codigo_activo', 'like', "%{$bus}%")
                    ->orWhere('nombre', 'like', "%{$bus}%");
            });
        }

        $activos = $query->orderBy('nombre')->get();

        return view('actas.baja.create', compact('activos'));
    }

    // ============================================================
    // STORE — Registro de baja (Inmediato si es Admin, Solicitud si es Inventariador)
    // ============================================================
    public function store(Request $request)
    {
        $user = Auth::user();

        if (! $user->puedeSolicitarBajaOTransferencia()) {
            abort(403, 'No tiene permisos para solicitar bajas.');
        }

        // ⭐ Capturar el ID del activo de cualquier formato enviado (incluyendo activo_ids[])
        $idActivoSeleccionado = $request->input('id_activo')
            ?? $request->input('activo_id')
            ?? ($request->input('activos')[0] ?? null)
            ?? ($request->input('activo_ids')[0] ?? null);

        $textoMotivo = $request->input('motivo') ?? $request->input('motivo_baja');
        $causalBaja = $request->input('causal_baja');
        $responsableNombre = $request->input('responsable_nombre');
        $responsableCi = $request->input('responsable_ci');

        if (! $textoMotivo) {
            return back()->withInput()->withErrors(['motivo' => 'El motivo de la baja es obligatorio.']);
        }

        if (! $idActivoSeleccionado) {
            return back()->withInput()->withErrors(['id_activo' => 'Debe seleccionar un activo para dar de baja.']);
        }

        $activo = ActivoFijo::paraUsuario($user)->findOrFail($idActivoSeleccionado);

        // Manejar subida de hasta 3 fotos de evidencia
        $fotos = [];
        for ($i = 1; $i <= 3; $i++) {
            $campo = "foto_evidencia_{$i}";
            if ($request->hasFile($campo)) {
                $fotos[$i] = $request->file($campo)->store('actas_baja/evidencias', 'public');
            }
        }

        $numero = 'BAJA-'.now()->format('Y').'-'.str_pad(
            (string) ((ActaBaja::max('id_acta_baja') ?? 0) + 1),
            4, '0', STR_PAD_LEFT
        );

        $esAdmin = $user->esAdmin();

        $acta = DB::transaction(function () use ($request, $activo, $user, $numero, $esAdmin, $idActivoSeleccionado, $textoMotivo, $causalBaja, $responsableNombre, $responsableCi, $fotos) {
            $acta = ActaBaja::create([
                'numero_acta' => $numero,
                'id_activo' => $idActivoSeleccionado,
                'id_usuario' => $user->id_usuario,
                'motivo' => $textoMotivo,
                'causal_baja' => $causalBaja,
                'foto_evidencia_1' => $fotos[1] ?? null,
                'foto_evidencia_2' => $fotos[2] ?? null,
                'foto_evidencia_3' => $fotos[3] ?? null,
                'responsable_nombre' => $responsableNombre,
                'responsable_ci' => $responsableCi,
                'fecha_baja' => now(),
                'observaciones' => $request->input('observaciones') ?? null,
                'estado' => $esAdmin ? ActaBaja::ESTADO_APROBADA : ActaBaja::ESTADO_PENDIENTE,
                'id_aprobador' => $esAdmin ? $user->id_usuario : null,
                'fecha_aprobacion' => $esAdmin ? now() : null,
            ]);

            if ($esAdmin) {
                $activo->update([
                    'estado_registro' => 'BAJA',
                ]);
            } else {
                $activo->update([
                    'estado_registro' => 'EN_REVISION_BAJA',
                ]);
            }

            return $acta;
        });

        $mensaje = $esAdmin
            ? "Acta de baja {$numero} registrada y aplicada de inmediato (Modo Administrador)."
            : "Solicitud de baja {$numero} enviada. Queda pendiente de aprobación de la administración.";

        return redirect()
            ->route('actas.baja.show', $acta)
            ->with('status', $mensaje);
    }

    // ============================================================
    // APROBAR — Solo administración
    // ============================================================
    public function aprobar(Request $request, ActaBaja $acta)
    {
        $this->autorizarAprobacion($acta);

        DB::transaction(function () use ($acta, $request) {
            $acta->update([
                'estado' => ActaBaja::ESTADO_APROBADA,
                'id_aprobador' => $request->user()->id_usuario,
                'fecha_aprobacion' => now(),
            ]);

            $acta->activo?->update([
                'estado_registro' => 'BAJA',
            ]);
        });

        return back()->with('status', "Acta de baja {$acta->numero_acta} aprobada. El activo fue dado de baja correctamente.");
    }

    // ============================================================
    // RECHAZAR — Cancela la solicitud
    // ============================================================
    public function rechazar(Request $request, ActaBaja $acta)
    {
        $this->autorizarAprobacion($acta);

        DB::transaction(function () use ($acta, $request) {
            $acta->update([
                'estado' => ActaBaja::ESTADO_RECHAZADA,
                'id_aprobador' => $request->user()->id_usuario,
                'fecha_aprobacion' => now(),
            ]);

            $acta->activo?->update([
                'estado_registro' => 'ASIGNADO',
            ]);
        });

        return back()->with('status', "Acta de baja {$acta->numero_acta} rechazada. El bien vuelve a estado asignado.");
    }

    protected function autorizarAprobacion(ActaBaja $acta): void
    {
        abort_unless(Auth::user()?->puedeAprobar(), 403, 'Solo la administración aprueba bajas.');
        abort_if($acta->estado !== ActaBaja::ESTADO_PENDIENTE, 422, 'Esta acta ya fue procesada.');
    }

    // ============================================================
    // SHOW — Detalle
    // ============================================================
    public function show(ActaBaja $acta)
    {
        $this->autorizarConsulta($acta);
        $acta->load(['activo.categoria', 'activo.ambiente.carrera', 'usuario', 'aprobador']);

        return view('actas.baja.show', compact('acta'));
    }

    // ============================================================
    // PDF — Carta Horizontal (Landscape)
    // ============================================================
    public function pdf(ActaBaja $acta)
    {
        $this->autorizarConsulta($acta);
        $acta->load(['activo.categoria', 'activo.ambiente.carrera', 'usuario', 'aprobador']);

        // Preparar logo como data URI para que se vea en el PDF
        $logoDataUri = '';
        $logoPath = public_path('images/logo.png');
        if (file_exists($logoPath)) {
            $logoDataUri = 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath));
        }

        // Preparar las 3 fotos de evidencia como data URIs para que se vean en el PDF
        $fotosEvidenciaDataUri = [];
        foreach ([1, 2, 3] as $i) {
            $campo = 'foto_evidencia_'.$i;
            $ruta = $acta->{$campo} ?? null;
            if ($ruta && Storage::disk('public')->exists($ruta)) {
                $contenido = Storage::disk('public')->get($ruta);
                $mime = Storage::disk('public')->mimeType($ruta);
                $fotosEvidenciaDataUri[$i] = 'data:'.$mime.';base64,'.base64_encode($contenido);
            } else {
                $fotosEvidenciaDataUri[$i] = null;
            }
        }

        $pdf = Pdf::loadView('actas.baja.pdf', compact('acta', 'logoDataUri', 'fotosEvidenciaDataUri'))
            ->setPaper('legal', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultMediaType' => 'screen',
                'isFontSubsettingEnabled' => true,
            ]);

        return $pdf->stream('Acta_Baja_'.$acta->numero_acta.'.pdf');
    }

    public function excel(ActaBaja $acta)
    {
        $this->autorizarConsulta($acta);
        $acta->load(['activo.categoria', 'activo.ambiente.carrera', 'usuario', 'aprobador']);

        return response()->view('actas.baja.excel', compact('acta'));
    }

    private function autorizarConsulta(ActaBaja $acta): void
    {
        $user = Auth::user();

        abort_if(
            ! $user->esAdmin()
                && ! $user->esRector()
                && ! ActivoFijo::paraUsuario($user)->whereKey($acta->id_activo)->exists(),
            404
        );
    }

    // ============================================================
    // DAR DE ALTA — Reactivar un activo dado de baja
    // ============================================================
    public function darDeAlta(Request $request, ActaBaja $acta)
    {
        abort_unless(Auth::user()?->esAdmin(), 403, 'Solo la administración puede dar de alta activos.');

        $data = $request->validate([
            'id_ambiente' => 'required|exists:ambientes,id_ambiente',
            'id_categoria' => 'required|exists:categorias,id_categoria',
            'id_custodio' => 'nullable|exists:usuarios,id_usuario',
        ]);

        DB::transaction(function () use ($acta, $data) {
            // Actualizar el estado y ubicación del activo relacionado
            if ($acta->activo) {
                $acta->activo->update([
                    'id_ambiente' => $data['id_ambiente'],
                    'id_categoria' => $data['id_categoria'],
                    'id_custodio' => $data['id_custodio'] ?? $acta->activo->id_custodio,
                    'estado_registro' => 'ASIGNADO',
                ]);
            }

            // Opcional: Marcar el acta como cerrada/anulada o eliminarla lógicamente
            $acta->update([
                'comentario_aprobacion' => ($acta->comentario_aprobacion ? $acta->comentario_aprobacion.' | ' : '').'Activo dado de alta y reasignado.',
            ]);
        });

        return back()->with('status', "El activo de la baja {$acta->numero_acta} ha sido dado de alta y reasignado correctamente.");
    }
}
