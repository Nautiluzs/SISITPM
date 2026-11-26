<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActaBaja;
use App\Models\ActaTransferencia;
use App\Models\ActivoFijo;
use App\Models\Carrera;
use App\Models\User;
use App\Models\VerificacionFisica;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        // Todo el tablero se construye sobre el alcance del usuario: un
        // custodio ve los números de sus ambientes, no los de toda la
        // institución.
        $activos = fn () => ActivoFijo::paraUsuario($user);

        // ============================================================
        // 1. TOTALES GENERALES
        // ============================================================
        $totalActivos = $activos()->count();
        $activosBuenos = $activos()->where('estado_fisico', 'B')->count();
        $enReparacion = $activos()->where('estado_fisico', 'R')->count();
        $enMantenimiento = $activos()->where('estado_fisico', 'M')->count();
        $fueraServicio = $activos()->where('estado_fisico', 'FF')->count();

        $totalAmbientes = $user->ambientesVisibles()->count();
        $totalCarreras = Carrera::when(
            $user->idsCarrerasVisibles() === null,
            fn ($q) => $q,
            fn ($q) => $q->whereIn('id_carrera', $user->idsCarrerasVisibles())
        )->count();

        $totalUsuarios = $user->esAdmin()
            ? User::where('estado', 'ACTIVO')->count()
            : $activos()->whereNotNull('id_custodio')->distinct('id_custodio')->count('id_custodio');

        // ============================================================
        // 2. DATOS PARA GRÁFICA DE BARRAS (por mes)
        // ============================================================
        $meses = [];
        $activosPorMes = [];

        // Últimos 12 meses
        for ($i = 11; $i >= 0; $i--) {
            $fecha = Carbon::now()->subMonths($i);
            $meses[] = $fecha->translatedFormat('M Y'); // Ej: "Oct 2025"

            // Contar activos registrados en ese mes
            $activosPorMes[] = $activos()
                ->whereYear('created_at', $fecha->year)
                ->whereMonth('created_at', $fecha->month)
                ->count();
        }

        $maxRegistrosMensual = max($activosPorMes);
        $mesesConMasRegistros = $maxRegistrosMensual > 0
            ? array_map(
                fn ($indice) => $meses[$indice],
                array_keys($activosPorMes, $maxRegistrosMensual, true)
            )
            : [];

        // ============================================================
        // 3. DATOS PARA GRÁFICA DE DONA (estados)
        // ============================================================
        $estadosData = [
            'labels' => ['Buenos', 'Regulares', 'Malos', 'Fuera de servicio'],
            'valores' => [$activosBuenos, $enReparacion, $enMantenimiento, $fueraServicio],
            'colores' => ['#10b981', '#f59e0b', '#f97316', '#ef4444'],
        ];

        // ============================================================
        // 4. ÚLTIMOS ACTIVOS REGISTRADOS
        // ============================================================
        $ultimosActivos = $activos()->with(['categoria', 'ambiente'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // ============================================================
        // 5. ACTIVOS POR CARRERA (para gráfico horizontal)
        // ============================================================
        $idsCarreras = $user->idsCarrerasVisibles();

        $carrerasVisibles = Carrera::when(
            $idsCarreras === null,
            fn ($q) => $q,
            fn ($q) => $q->whereIn('id_carrera', $idsCarreras)
        );

        $activosPorCarrera = $carrerasVisibles->withCount('ambientes')
            ->get()
            ->map(function ($carrera) use ($activos) {
                $carrera->total_activos = $activos()
                    ->whereHas('ambiente', fn ($q) => $q->where('id_carrera', $carrera->id_carrera))
                    ->count();

                return $carrera;
            })
            ->sortByDesc('total_activos')
            ->take(6)
            ->values();

        // ============================================================
        // 6. ACTIVIDADES RECIENTES (opcional)
        // ============================================================
        $recentActivities = collect();

        // Lo que la administradora tiene pendiente de decidir.
        $pendientes = $user->esAdmin() ? [
            'verificaciones' => VerificacionFisica::pendientesAprobacion()->count(),
            'bajas' => ActaBaja::pendientes()->count(),
            'transferencias' => ActaTransferencia::pendientes()->count(),
        ] : null;

        return view('welcome', compact(
            'totalActivos',
            'activosBuenos',
            'enReparacion',
            'enMantenimiento',
            'fueraServicio',
            'totalAmbientes',
            'totalCarreras',
            'totalUsuarios',
            'meses',
            'activosPorMes',
            'maxRegistrosMensual',
            'mesesConMasRegistros',
            'estadosData',
            'ultimosActivos',
            'activosPorCarrera',
            'recentActivities',
            'pendientes'
        ));
    }
}
