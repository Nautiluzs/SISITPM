<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivoFijo;
use App\Models\Ambiente;
use App\Models\Carrera;
use App\Models\User;
use App\Models\VerificacionFisica;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VerificacionController extends Controller
{
    public function index(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::user();

        $carrerasQuery = Carrera::orderBy('nombre', 'asc');
        if ($user && ! $user->puedeVerTodasLasCarreras()) {
            $carrerasQuery->whereIn('id_carrera', $user->idsCarrerasVisibles() ?: [0]);
        }
        $carreras = $carrerasQuery->get();
        $puedeVerificar = (bool) $user?->puedeRegistrarVerificacion();

        $ambientes = collect();
        if ($request->filled('carrera_id')) {
            $ambientes = $user->ambientesVisibles()
                ->where('id_carrera', $request->carrera_id)
                ->orderBy('nombre', 'asc')
                ->get();
        }

        $activos = collect();
        $ambienteSeleccionado = null;

        if ($request->filled('ambiente_id')) {
            $ambienteSeleccionado = $user->ambientesVisibles()->with('carrera')->find($request->ambiente_id);
        }

        // El ambiente escaneado tiene que estar dentro del alcance del
        // usuario: nadie verifica donde no le corresponde.
        abort_if($request->filled('ambiente_id') && ! $ambienteSeleccionado, 403, 'Ese ambiente no le corresponde.');

        if ($ambienteSeleccionado) {
            $query = ActivoFijo::with([
                'categoria',
                'ambiente.carrera',
                'custodio',
                'fuente',
                'verificaciones' => function ($query) use ($ambienteSeleccionado) {
                    $query->where('id_ambiente_escaneo', $ambienteSeleccionado->id_ambiente)
                        ->where('estado_aprobacion', VerificacionFisica::ESTADO_PENDIENTE)
                        ->where('verificacion_finalizada', false)
                        ->latest('id_verificacion');
                },
            ])
                ->paraUsuario($user)
                ->where('id_ambiente', $ambienteSeleccionado->id_ambiente);

            $activos = $query->orderBy('codigo_activo', 'asc')->get();
        }

        return view('verificaciones.index', compact(
            'carreras',
            'ambientes',
            'activos',
            'ambienteSeleccionado',
            'puedeVerificar'
        ));
    }

    public function guardarItem(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user?->puedeRegistrarVerificacion()) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'activo_id' => 'required|exists:activos_fijos,id_activo',
            'ambiente_escaneo_id' => 'required|exists:ambientes,id_ambiente',
            'es_correspondencia' => 'required|boolean',
            'estado_fisico' => 'nullable|in:B,R,M,FF',
            'observaciones' => 'nullable|string',
            'solicitar_baja' => 'nullable|boolean',
            'es_sustitucion' => 'nullable|boolean',
            'sustituto_codigo' => 'nullable|string|max:50',
            'sustituto_nombre' => 'nullable|string|max:150',
            'sustituto_marca' => 'nullable|string|max:80',
            'sustituto_modelo' => 'nullable|string|max:80',
            'sustituto_serie' => 'nullable|string|max:80',
            'sustituto_valor' => 'nullable|numeric|min:0',
            'sustituto_fecha' => 'nullable|date',
        ]);

        if (! $user->ambientesVisibles()->whereKey($data['ambiente_escaneo_id'])->exists()) {
            return response()->json([
                'success' => false,
                'error' => 'Ese ambiente no le corresponde.',
            ], 403);
        }

        $activo = ActivoFijo::with('ambiente')->findOrFail($data['activo_id']);

        // El activo debe estar dentro del alcance de quien verifica.
        $permitidos = ActivoFijo::select('id_activo')->paraUsuario($user)->pluck('id_activo');
        if (! $permitidos->contains($activo->id_activo)) {
            return response()->json([
                'success' => false,
                'error' => 'Ese activo no le corresponde.',
            ], 403);
        }

        $esCorrecto = ($activo->id_ambiente == $data['ambiente_escaneo_id']);

        $observaciones = $data['observaciones'] ?? null;
        if (! $esCorrecto && blank($observaciones)) {
            $ambienteEscaneo = Ambiente::findOrFail($data['ambiente_escaneo_id']);
            $observaciones = 'Activo registrado en '.($activo->ambiente?->nombre ?? 'un ambiente distinto')
                .' y encontrado en '.$ambienteEscaneo->nombre.'.';
        }

        if ($request->boolean('solicitar_baja')) {
            $observaciones = ($observaciones ? $observaciones."\n" : '').'[Solicitar baja del activo]';
        }

        $estadoFisicoSolicitado = $data['estado_fisico'] ?? null;
        $cambioEstadoFisicoSolicitado = ! empty($estadoFisicoSolicitado);

        // Sustitución: baja del equipo viejo y alta del nuevo que sale del almacén.
        $datosSustituto = null;
        if ($request->boolean('es_sustitucion') && ! empty($data['sustituto_codigo'])) {
            $observaciones = ($observaciones ? $observaciones."\n" : '')
                .'[Sustitución por '.$data['sustituto_codigo'].']';

            $datosSustituto = [
                'codigo_activo' => $data['sustituto_codigo'],
                'nombre' => $data['sustituto_nombre'] ?? $data['sustituto_codigo'],
                'marca' => $data['sustituto_marca'] ?? null,
                'modelo' => $data['sustituto_modelo'] ?? null,
                'numero_serie' => $data['sustituto_serie'] ?? null,
                'valor_adquisicion' => $data['sustituto_valor'] ?? null,
                'fecha_adquisicion' => $data['sustituto_fecha'] ?? null,
            ];
        }

        $datos = [
            'id_verificador' => $user->id_usuario,
            'fecha_verificacion' => now(),
            'es_correspondencia_correcta' => $esCorrecto,
            'sincronizado_offline' => false,
            'dispositivo_uuid' => substr((string) $request->header('User-Agent'), 0, 50),
            'estado_fisico_reportado' => $estadoFisicoSolicitado ?: $activo->estado_fisico,
            'observaciones' => $observaciones,
            'estado_aprobacion' => VerificacionFisica::ESTADO_PENDIENTE,
            'id_aprobador' => null,
            'fecha_aprobacion' => null,
            'comentario_aprobacion' => null,
            'cambio_estado_fisico_solicitado' => $cambioEstadoFisicoSolicitado,
            'estado_fisico_solicitado' => $estadoFisicoSolicitado,
            'solicitar_baja' => $request->boolean('solicitar_baja'),
            'verificacion_finalizada' => false,
            'datos_extra' => $datosSustituto,
        ];

        // Si la verificación anterior de este activo ya fue resuelta, no se
        // pisa: se registra una nueva para conservar la firma de la
        // administración y el historial.
        $anterior = VerificacionFisica::where('id_activo', $activo->id_activo)
            ->where('id_ambiente_escaneo', $data['ambiente_escaneo_id'])
            ->where(function ($query) {
                $query->whereNotIn('estado_aprobacion', [
                    VerificacionFisica::ESTADO_PENDIENTE,
                    VerificacionFisica::ESTADO_RECHAZADA,
                ])->orWhere('verificacion_finalizada', true);
            })
            ->latest('id_verificacion')
            ->first();

        if ($anterior) {
            $verificacion = VerificacionFisica::create(array_merge($datos, [
                'id_activo' => $activo->id_activo,
                'id_ambiente_escaneo' => $data['ambiente_escaneo_id'],
            ]));

            return response()->json([
                'success' => true,
                'mensaje' => 'Verificación guardada. La anterior ya estaba finalizada o aprobada, así que se conservó.',
                'verificacion' => $verificacion,
            ]);
        }

        $verificacion = VerificacionFisica::updateOrCreate(
            [
                'id_activo' => $activo->id_activo,
                'id_ambiente_escaneo' => $data['ambiente_escaneo_id'],
            ],
            $datos
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Verificación guardada en base de datos.',
            'verificacion' => $verificacion,
        ]);
    }

    public function exportarPdf(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $ambiente = $user->ambientesVisibles()
            ->with('carrera')
            ->findOrFail($request->ambiente_id);
        $verificadosData = json_decode($request->verificados, true) ?? [];

        if (empty($verificadosData)) {
            return redirect()->back()->with('error', 'No hay activos verificados para exportar.');
        }

        $ids = array_column($verificadosData, 'id_activo');
        $activos = ActivoFijo::with(['categoria', 'ambiente.carrera', 'custodio', 'fuente'])
            ->paraUsuario($user)
            ->where('id_ambiente', $ambiente->id_ambiente)
            ->whereIn('id_activo', $ids)
            ->get();

        $map = collect($verificadosData)->keyBy('id_activo');
        $activos->each(function ($act) use ($map) {
            $info = $map->get($act->id_activo);
            $act->estado_verificacion = $info['estado'] ?? 'Verificado';
            $act->observacion_verificacion = $info['observacion'] ?? 'Sin observaciones';

            foreach (['imagen', 'imagen_lateral', 'imagen_detalle'] as $campo) {
                $ruta = $act->{$campo};
                $act->{$campo.'_data'} = $ruta && Storage::disk('public')->exists($ruta)
                    ? 'data:'.Storage::disk('public')->mimeType($ruta).';base64,'.base64_encode(Storage::disk('public')->get($ruta))
                    : null;
            }
        });

        $pdf = Pdf::loadView('verificaciones.reporte_pdf', compact('activos', 'ambiente'))
            ->setPaper('letter', 'landscape')
            ->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        return $pdf->stream('Reporte_Verificacion_'.str_replace(' ', '_', $ambiente->nombre).'_'.date('d_m_Y').'.pdf');
    }

    public function exportarExcel(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $ambiente = $user->ambientesVisibles()
            ->with('carrera')
            ->findOrFail($request->ambiente_id);
        $verificadosData = json_decode($request->verificados, true) ?? [];

        if (empty($verificadosData)) {
            return redirect()->back()->with('error', 'No hay activos verificados para exportar.');
        }

        $ids = array_column($verificadosData, 'id_activo');
        $activos = ActivoFijo::with(['categoria', 'ambiente.carrera', 'custodio', 'fuente'])
            ->paraUsuario($user)
            ->where('id_ambiente', $ambiente->id_ambiente)
            ->whereIn('id_activo', $ids)
            ->get();

        $map = collect($verificadosData)->keyBy('id_activo');
        $activos->each(function ($act) use ($map) {
            $info = $map->get($act->id_activo);
            $act->estado_verificacion = $info['estado'] ?? 'Verificado';
            $act->observacion_verificacion = $info['observacion'] ?? 'Sin observaciones';

            foreach (['imagen', 'imagen_lateral', 'imagen_detalle'] as $campo) {
                $ruta = $act->{$campo};
                $act->{$campo.'_data'} = $ruta && Storage::disk('public')->exists($ruta)
                    ? 'data:'.Storage::disk('public')->mimeType($ruta).';base64,'.base64_encode(Storage::disk('public')->get($ruta))
                    : null;
            }
        });

        return response()->view('verificaciones.reporte_excel', compact('activos', 'ambiente'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="Reporte_Verificacion_'.str_replace(' ', '_', $ambiente->nombre).'_'.date('d_m_Y').'.xls"');
    }

    public function finalizar(Request $request)
    {
        $data = $request->validate([
            'ambiente_id' => 'required|exists:ambientes,id_ambiente',
            'tiempo_transcurrido' => 'nullable|string|max:32',
            'faltantes' => 'sometimes|array',
            'faltantes.*' => 'required|array:id_activo,ubicacion',
            'faltantes.*.id_activo' => 'required|integer|distinct|exists:activos_fijos,id_activo',
            'faltantes.*.ubicacion' => 'required|string|min:2|max:500',
        ], [
            'faltantes.*.ubicacion.required' => 'Indica la ubicación actual o última ubicación conocida de cada activo faltante.',
        ]);

        /** @var User|null $user */
        $user = Auth::user();
        if (! $user?->puedeFinalizarVerificacion()) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $ambiente = $user->ambientesVisibles()->find($data['ambiente_id']);

        if (! $ambiente) {
            return response()->json([
                'success' => false,
                'error' => 'Ese ambiente no le corresponde.',
            ], 403);
        }

        $activos = ActivoFijo::query()
            ->paraUsuario($user)
            ->where('id_ambiente', $ambiente->id_ambiente)
            ->orderBy('codigo_activo')
            ->get(['id_activo', 'codigo_activo', 'nombre', 'estado_fisico']);

        $verificacionesActuales = VerificacionFisica::query()
            ->where('id_ambiente_escaneo', $ambiente->id_ambiente)
            ->whereIn('id_activo', $activos->modelKeys())
            ->where('estado_aprobacion', VerificacionFisica::ESTADO_PENDIENTE)
            ->where('verificacion_finalizada', false)
            ->latest('id_verificacion')
            ->get()
            ->unique('id_activo')
            ->keyBy('id_activo');

        $idsFaltantes = $activos
            ->whereNotIn('id_activo', $verificacionesActuales->keys()->all())
            ->pluck('id_activo')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values();
        $faltantesEnviados = collect($data['faltantes'] ?? []);
        $idsFaltantesEnviados = $faltantesEnviados
            ->pluck('id_activo')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values();

        if ($idsFaltantes->all() !== $idsFaltantesEnviados->all()) {
            return response()->json([
                'success' => false,
                'error' => 'Verifica todos los activos del ambiente e indica la ubicación de cada activo no localizado.',
                'faltantes' => $activos
                    ->whereIn('id_activo', $idsFaltantes->all())
                    ->map(fn ($activo) => [
                        'id_activo' => $activo->id_activo,
                        'codigo_activo' => $activo->codigo_activo,
                        'nombre' => $activo->nombre,
                        'ambiente_esperado' => $ambiente->nombre,
                    ])
                    ->values(),
            ], 422);
        }

        $ubicacionesFaltantes = $faltantesEnviados->keyBy('id_activo');
        $totalActivos = $activos->count();
        $totalVerificados = $verificacionesActuales->filter(
            fn ($verificacion) => $verificacion->es_correspondencia_correcta && blank($verificacion->observaciones)
        )->count();
        $totalNoLocalizados = $idsFaltantes->count();
        $totalObservados = max($totalActivos - $totalVerificados - $totalNoLocalizados, 0);

        DB::transaction(function () use ($activos, $ambiente, $idsFaltantes, $ubicacionesFaltantes, $user, $request) {
            foreach ($activos->whereIn('id_activo', $idsFaltantes->all()) as $activo) {
                $ubicacion = trim($ubicacionesFaltantes->get($activo->id_activo)['ubicacion']);

                VerificacionFisica::create([
                    'id_activo' => $activo->id_activo,
                    'id_ambiente_escaneo' => $ambiente->id_ambiente,
                    'id_verificador' => $user->id_usuario,
                    'fecha_verificacion' => now(),
                    'es_correspondencia_correcta' => false,
                    'sincronizado_offline' => false,
                    'dispositivo_uuid' => substr((string) $request->header('User-Agent'), 0, 50),
                    'estado_fisico_reportado' => $activo->estado_fisico,
                    'observaciones' => 'No localizado en el ambiente esperado ('.$ambiente->nombre.'). Ubicación reportada: '.$ubicacion,
                    'estado_aprobacion' => VerificacionFisica::ESTADO_PENDIENTE,
                    'verificacion_finalizada' => false,
                ]);
            }

            VerificacionFisica::where('id_ambiente_escaneo', $ambiente->id_ambiente)
                ->where('estado_aprobacion', VerificacionFisica::ESTADO_PENDIENTE)
                ->where('verificacion_finalizada', false)
                ->update(['verificacion_finalizada' => true]);
        });

        return response()->json([
            'success' => true,
            'mensaje' => 'Verificación finalizada y enviada a aprobación. No se envió ningún correo.',
            'total_activos' => $totalActivos,
            'total_verificados' => $totalVerificados,
            'total_observados' => $totalObservados,
            'total_no_localizados' => $totalNoLocalizados,
            'tiempo_transcurrido' => $data['tiempo_transcurrido'] ?? 'No registrado',
        ]);
    }

    public function reabrir(Request $request)
    {
        $data = $request->validate([
            'ambiente_id' => 'required|exists:ambientes,id_ambiente',
        ]);

        /** @var User|null $user */
        $user = Auth::user();
        if (! $user?->puedeFinalizarVerificacion()) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }

        $ambiente = $user->ambientesVisibles()->find($data['ambiente_id']);
        if (! $ambiente) {
            return response()->json(['success' => false, 'error' => 'Ese ambiente no le corresponde.'], 403);
        }

        $eliminadas = VerificacionFisica::where('id_ambiente_escaneo', $ambiente->id_ambiente)
            ->where('estado_aprobacion', VerificacionFisica::ESTADO_PENDIENTE)
            ->delete();

        return response()->json([
            'success' => true,
            'mensaje' => 'La verificaci?n se reinici? desde cero. Puedes comenzar nuevamente.',
            'reiniciadas' => $eliminadas,
        ]);
    }
}
