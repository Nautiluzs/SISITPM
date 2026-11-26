<?php

namespace App\Http\Controllers;

use App\Models\ActaBaja;
use App\Models\Carrera;
use App\Models\VerificacionFisica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AprobacionVerificacionController extends Controller
{
    // ============================================================
    // INDEX — Lista de verificaciones pendientes
    // ============================================================
    public function index(Request $request)
    {
        $carreras = Carrera::orderBy('nombre', 'asc')->get();

        $query = VerificacionFisica::with([
            'activo.ambiente.carrera',
            'verificador',
            'ambienteEscaneo.carrera',
            'aprobador',
        ])->pendientesAprobacion()->latest('fecha_verificacion');

        if ($request->filled('carrera_id') && $request->carrera_id !== 'todas') {
            $query->whereHas('activo.ambiente', function ($q) use ($request) {
                $q->where('id_carrera', $request->carrera_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('activo', function ($sub) use ($search) {
                    $sub->where('codigo_activo', 'like', "%{$search}%")
                        ->orWhere('nombre', 'like', "%{$search}%");
                })->orWhere('observaciones', 'like', "%{$search}%");
            });
        }

        $verificaciones = $query->paginate(20)->appends($request->query());

        // ⭐ Contar verificaciones finalizadas pendientes de aprobar
        $finalizadasPendientes = VerificacionFisica::where('verificacion_finalizada', true)
            ->where('estado_aprobacion', VerificacionFisica::ESTADO_PENDIENTE)
            ->count();

        return view('verificaciones-aprobacion.index', compact(
            'verificaciones',
            'carreras',
            'finalizadasPendientes'
        ));
    }

    // ============================================================
    // APROBAR individual
    // ============================================================
    public function aprobar(Request $request, $id)
    {
        $request->validate([
            'comentario' => 'nullable|string|max:500',
            'aplicar_cambio_estado_fisico' => 'boolean',
        ]);

        $ver_verification = VerificacionFisica::with('activo')->findOrFail($id);

        if ($ver_verification->estado_aprobacion !== VerificacionFisica::ESTADO_PENDIENTE) {
            return back()->with('error', 'Esta verificación ya fue procesada.');
        }

        DB::beginTransaction();
        try {
            $ver_verification->update([
                'estado_aprobacion' => VerificacionFisica::ESTADO_APROBADA,
                'id_aprobador' => $request->user()->id_usuario,
                'fecha_aprobacion' => now(),
                'comentario_aprobacion' => $request->comentario,
            ]);

            // ⭐ Corregido al nombre exacto de la migración: cambio_estado_fisico_solicitado
            if ($ver_verification->cambio_estado_fisico_solicitado) {
                $ver_verification->activo->update([
                    'estado_fisico' => $ver_verification->estado_fisico_solicitado,
                ]);
            }

            // Una baja nunca se aplica aquí: se genera la solicitud y
            // espera su propia aprobación.
            $baja = null;
            if ($ver_verification->solicitar_baja) {
                $baja = $this->registrarSolicitudBaja($ver_verification);
            }

            DB::commit();

            return redirect()->route('verificaciones-aprobacion.index')
                ->with('status', $baja
                    ? 'Verificación aprobada. La baja quedó registrada como solicitud pendiente.'
                    : 'Verificación aprobada exitosamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al aprobar verificación: '.$e->getMessage());

            return back()->with('error', 'Error al aprobar: '.$e->getMessage());
        }
    }

    /**
     * Traduce la marca "solicitar baja" de la verificación en una
     * solicitud de baja formal, con su número de acta. Si el inventario
     * registering una sustitución, la solicitud guarda los datos del
     * equipo nuevo que sale del almacén.
     */
    protected function registrarSolicitudBaja(VerificacionFisica $verificacion): ?ActaBaja
    {
        $yaSolicitada = ActaBaja::where('id_activo', $verificacion->id_activo)
            ->pendientes()
            ->exists();

        if ($yaSolicitada) {
            return null;
        }

        $datos = $verificacion->datos_extra;
        $sustitucion = is_array($datos) && ! empty($datos['codigo_activo']);

        $siguiente = (ActaBaja::max('id_acta_baja') ?? 0) + 1;

        return ActaBaja::create([
            'numero_acta' => 'BAJA-'.now()->format('Y').'-'.str_pad((string) $siguiente, 4, '0', STR_PAD_LEFT),
            'id_activo' => $verificacion->id_activo,
            'id_usuario' => $verificacion->id_verificador,
            'motivo' => $verificacion->observaciones
                ? Str::limit($verificacion->observaciones, 250, '…')
                : 'Baja solicitada durante la verificación física.',
            'fecha_baja' => now()->toDateString(),
            'estado' => ActaBaja::ESTADO_PENDIENTE,
            'es_sustitucion' => $sustitucion,
            'datos_sustituto' => $sustitucion ? $datos : null,
        ]);
    }

    // ============================================================
    // RECHAZAR individual
    // ============================================================
    public function rechazar(Request $request, $id)
    {
        $request->validate([
            'comentario' => 'required|string|max:500',
        ]);

        $ver_verification = VerificacionFisica::with('activo')->findOrFail($id);

        if ($ver_verification->estado_aprobacion !== VerificacionFisica::ESTADO_PENDIENTE) {
            return back()->with('error', 'Esta verificación ya fue procesada.');
        }

        DB::beginTransaction();
        try {
            $ver_verification->update([
                'estado_aprobacion' => VerificacionFisica::ESTADO_RECHAZADA,
                'id_aprobador' => $request->user()->id_usuario, // ⭐ Corregido a id_aprobador
                'fecha_aprobacion' => now(),
                'comentario_aprobacion' => $request->comentario,
            ]);

            DB::commit();

            return redirect()->route('verificaciones-aprobacion.index')
                ->with('status', 'Verificación rechazada.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al rechazar verificación: '.$e->getMessage());

            return back()->with('error', 'Error al rechazar: '.$e->getMessage());
        }
    }

    // ============================================================
    // CORREGIR
    // ============================================================
    public function corregir(Request $request, $id)
    {
        $request->validate([
            'comentario' => 'nullable|string|max:500',
        ]);

        $ver_verification = VerificacionFisica::with('activo')->findOrFail($id);

        if ($ver_verification->estado_aprobacion !== VerificacionFisica::ESTADO_RECHAZADA) {
            return back()->with('error', 'Solo se pueden corregir verificaciones rechazadas.');
        }

        DB::beginTransaction();
        try {
            $ver_verification->update([
                'estado_aprobacion' => VerificacionFisica::ESTADO_CORREGIDA,
                'comentario_aprobacion' => $request->comentario,
            ]);

            DB::commit();

            return redirect()->route('verificaciones-aprobacion.index')
                ->with('status', 'Verificación marcada como corregida.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al corregir verificación: '.$e->getMessage());

            return back()->with('error', 'Error al corregir: '.$e->getMessage());
        }
    }

    // ============================================================
    // APROBAR masivamente las correctas de un ambiente
    // ============================================================
    public function aprobarCorrectasDeAmbiente(Request $request, $ambienteId)
    {
        $request->validate([
            'comentario' => 'nullable|string|max:500',
        ]);

        // ⭐ Corregido a id_ambiente_escaneo
        $verificaciones = VerificacionFisica::where('id_ambiente_escaneo', $ambienteId)
            ->where('estado_aprobacion', VerificacionFisica::ESTADO_PENDIENTE)
            ->where('es_correspondencia_correcta', true)
            ->where(function ($q) {
                $q->whereNull('observaciones')->orWhere('observaciones', '');
            })
            ->get();

        if ($verificaciones->isEmpty()) {
            return back()->with('error', 'No hay verificaciones correctas para aprobar en este ambiente.');
        }

        DB::beginTransaction();
        try {
            foreach ($verificaciones as $ver) {
                $ver->update([
                    'estado_aprobacion' => VerificacionFisica::ESTADO_APROBADA,
                    'id_aprobador' => $request->user()->id_usuario, // ⭐ Corregido a id_aprobador
                    'fecha_aprobacion' => now(),
                    'comentario_aprobacion' => $request->comentario ?? 'Aprobación masiva de activos correctos.',
                ]);
            }

            DB::commit();

            return redirect()->route('verificaciones-aprobacion.index')
                ->with('status', 'Se aprobaron '.$verificaciones->count().' verificación(es) correctas del ambiente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al aprobar verificaciones masivas: '.$e->getMessage());

            return back()->with('error', 'Error en la aprobación masiva: '.$e->getMessage());
        }
    }
}
