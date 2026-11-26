<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivoFijo;
use App\Models\Ambiente;
use App\Models\User;
use App\Models\VerificacionFisica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Recibe lo que el inventario acumuló en el dispositivo mientras no había
 * señal. Todo entra en estado PENDIENTE: la administración sigue siendo la
 * que aprueba.
 */
class SincronizacionController extends Controller
{
    /**
     * Catálogo que el dispositivo guarda para poder escanear sin internet:
     * los activos del ambiente con su código, tipo y custodio.
     */
    public function catalogo(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $ambienteId = $request->integer('ambiente_id') ?: null;

        $query = ActivoFijo::paraUsuario($user)
            ->with(['categoria', 'ambiente', 'custodio'])
            ->orderBy('codigo_activo');

        if ($ambienteId) {
            abort_if(! $user->ambientesVisibles()->whereKey($ambienteId)->exists(), 403, 'Ese ambiente no le corresponde.');
            $query->where('id_ambiente', $ambienteId);
        }

        return response()->json([
            'success' => true,
            'descargado_en' => now()->toIso8601String(),
            'activos' => $query->get()->map(fn ($activo) => [
                'id_activo' => $activo->id_activo,
                'codigo_activo' => $activo->codigo_activo,
                'nombre' => $activo->nombre,
                'marca' => $activo->marca,
                'modelo' => $activo->modelo,
                'tipo_bien' => $activo->tipo_bien,
                'estado_registro' => $activo->estado_registro,
                'id_ambiente' => $activo->id_ambiente,
                'ambiente' => $activo->ambiente?->nombre,
                'id_custodio' => $activo->id_custodio,
                'custodio' => $activo->custodio?->nombre_completo,
            ]),
        ]);
    }

    /**
     * Envía la cola local acumulada. Acepta un lote por escaneo o por
     * sesión; devuelve el detalle de lo que se registró y de lo que se
     * rechazó, para que el dispositivo borre solo lo ya guardado.
     */
    public function verificaciones(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->puedeRegistrarVerificacion()) {
            return response()->json([
                'success' => false,
                'error' => 'No tiene permisos para registrar verificaciones.',
            ], 403);
        }

        $data = $request->validate([
            'verificaciones' => 'required|array|min:1',
            'verificaciones.*.id_local' => 'required|string|max:64',
            'verificaciones.*.codigo_activo' => ['required', 'string', Rule::exists('activos_fijos', 'codigo_activo')->whereNull('deleted_at')],
            'verificaciones.*.ambiente_escaneo_id' => 'required|exists:ambientes,id_ambiente',
            'verificaciones.*.fecha_verificacion' => 'required|date',
            'verificaciones.*.estado_fisico' => 'nullable|in:B,R,M,FF',
            'verificaciones.*.observaciones' => 'nullable|string',
            'verificaciones.*.solicitar_baja' => 'nullable|boolean',
            'verificaciones.*.es_sustitucion' => 'nullable|boolean',
            'verificaciones.*.sustituto' => 'nullable|array',
            'verificaciones.*.sustituto.codigo_activo' => 'nullable|string|max:50',
            'verificaciones.*.sustituto.nombre' => 'nullable|string|max:150',
            'verificaciones.*.sustituto.marca' => 'nullable|string|max:80',
            'verificaciones.*.sustituto.modelo' => 'nullable|string|max:80',
            'verificaciones.*.sustituto.numero_serie' => 'nullable|string|max:80',
            'verificaciones.*.sustituto.valor_adquisicion' => 'nullable|numeric|min:0',
        ], [
            'verificaciones.*.codigo_activo.exists' => 'Ese código de activo no existe en el sistema.',
        ]);

        $registradas = [];
        $rechazadas = [];
        $ambientesVisibles = $user->idsAmbientesVisibles();

        DB::transaction(function () use ($data, $request, $user, $ambientesVisibles, &$registradas, &$rechazadas) {
            foreach ($data['verificaciones'] as $item) {
                if ($ambientesVisibles !== null
                    && ! in_array((int) $item['ambiente_escaneo_id'], array_map('intval', $ambientesVisibles), true)) {
                    $rechazadas[] = ['id_local' => $item['id_local'], 'motivo' => 'El ambiente no le corresponde.'];

                    continue;
                }

                $activo = ActivoFijo::where('codigo_activo', $item['codigo_activo'])->first();

                // El activo debe estar dentro del ámbito del usuario.
                if (! $activo || ! ActivoFijo::paraUsuario($user)->whereKey($activo->id_activo)->exists()) {
                    $rechazadas[] = ['id_local' => $item['id_local'], 'motivo' => 'El activo no le corresponde.'];

                    continue;
                }

                $esCorrecto = (int) $activo->id_ambiente === (int) $item['ambiente_escaneo_id'];

                $observaciones = $item['observaciones'] ?? null;
                if (! $esCorrecto && blank($observaciones)) {
                    $activo->loadMissing('ambiente');
                    $ambienteEscaneo = Ambiente::findOrFail($item['ambiente_escaneo_id']);
                    $observaciones = 'Activo registrado en '.($activo->ambiente?->nombre ?? 'un ambiente distinto')
                        .' y encontrado en '.$ambienteEscaneo->nombre.'.';
                }

                if (! empty($item['solicitar_baja'])) {
                    $observaciones = ($observaciones ? $observaciones."\n" : '').'[Solicitar baja del activo]';
                }

                $sustituto = $item['sustituto'] ?? null;

                if (! empty($item['es_sustitucion']) && ! empty($sustituto['codigo_activo'])) {
                    $observaciones = ($observaciones ? $observaciones."\n" : '')
                        .'[Sustitución por '.$sustituto['codigo_activo'].']';
                }

                $estadoSolicitado = $item['estado_fisico'] ?? null;

                $verificacion = VerificacionFisica::create([
                    'id_activo' => $activo->id_activo,
                    'id_ambiente_escaneo' => $item['ambiente_escaneo_id'],
                    'id_verificador' => $user->id_usuario,
                    'fecha_verificacion' => $item['fecha_verificacion'],
                    'es_correspondencia_correcta' => $esCorrecto,
                    'sincronizado_offline' => true,
                    'dispositivo_uuid' => substr((string) $request->header('User-Agent'), 0, 50),
                    'datos_extra' => ! empty($sustituto['codigo_activo']) ? $sustituto : null,
                    'estado_fisico_reportado' => $estadoSolicitado ?: $activo->estado_fisico,
                    'observaciones' => $observaciones,
                    'estado_aprobacion' => VerificacionFisica::ESTADO_PENDIENTE,
                    'cambio_estado_fisico_solicitado' => ! empty($estadoSolicitado),
                    'estado_fisico_solicitado' => $estadoSolicitado,
                    'solicitar_baja' => ! empty($item['solicitar_baja']),
                    'verificacion_finalizada' => false,
                ]);

                $registradas[] = [
                    'id_local' => $item['id_local'],
                    'id_verificacion' => $verificacion->id_verificacion,
                ];
            }
        });

        Log::info('Sincronización offline', [
            'usuario' => $user->id_usuario,
            'registradas' => count($registradas),
            'rechazadas' => count($rechazadas),
        ]);

        return response()->json([
            'success' => true,
            'mensaje' => 'Se registering '.count($registradas).' verificación(es). Quedan pendientes de aprobación.',
            'registradas' => $registradas,
            'rechazadas' => $rechazadas,
        ]);
    }
}
