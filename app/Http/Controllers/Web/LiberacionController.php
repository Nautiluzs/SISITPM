<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivoFijo;
use App\Models\Ambiente;
use App\Models\User;
use App\Support\PdfHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * LIBERACIÓN DE CUSTODIA
 *
 * Al cierre de cada gestión los custodios de un ambiente firman el acta y
 * la entregan al jefe de carrera de su carrera.
 *
 * El documento NO mueve la custodia dentro del sistema: la entrega se
 * hace de forma manual y el bien sigue figurando con su custodio actual.
 * El acta solo deja constancia de qué bienes se liberaron y quién los
 * tenía.
 */
class LiberacionController extends Controller
{
    // ============================================================
    // INDEX — Ambientes que ya se pueden liberar
    // ============================================================
    public function index()
    {
        $user = Auth::user();

        $ambientes = Ambiente::query()
            ->with('carrera.jefeCarrera')
            ->whereHas('activos')
            ->withCount(['activos as activos_fijos_count' => fn ($q) => $q->where('tipo_bien', 'ACTIVO_FIJO')])
            ->withCount(['activos as no_activos_count' => fn ($q) => $q->where('tipo_bien', 'NO_ACTIVO')])
            ->orderBy('nombre')
            ->get();

        // Un ambiente queda disponible si el usuario es custodio de él o
        // si lleva la administración de los bienes.
        $ambientes = $ambientes->filter(fn (Ambiente $a) => $this->puedeLiberar($user, $a))
            ->map(function (Ambiente $a) {
                $a->setRelation('custodios', $this->custodiosDe($a));

                return $a;
            })
            ->values();

        return view('actas.liberacion.index', compact('ambientes'));
    }

    // ============================================================
    // PDF — Acta de liberación
    // ============================================================
    public function pdf(Request $request, $ambiente_id)
    {
        [$ambiente, $custodios, $receptor, $activos, $tipo] = $this->datosActa($request, $ambiente_id);

        $pdf = PdfHelper::oficio(
            'actas.liberacion.pdf',
            compact('ambiente', 'custodios', 'receptor', 'activos', 'tipo')
        );

        return $pdf->stream($this->nombreArchivo($ambiente, 'pdf'));
    }

    // ============================================================
    // EXCEL — Mismo contenido que el acta, en hoja de cálculo
    // ============================================================
    public function excel(Request $request, $ambiente_id)
    {
        [$ambiente, $custodios, $receptor, $activos, $tipo] = $this->datosActa($request, $ambiente_id);

        return response()
            ->view('actas.liberacion.excel', compact('ambiente', 'custodios', 'receptor', 'activos', 'tipo'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition',
                'attachment; filename="'.$this->nombreArchivo($ambiente, 'xls').'"');
    }

    // ============================================================
    // DATOS DEL ACTA
    // ============================================================
    private function datosActa(Request $request, $ambiente_id): array
    {
        $user = Auth::user();
        $ambiente = Ambiente::with('carrera.jefeCarrera')->findOrFail($ambiente_id);

        abort_unless($this->puedeLiberar($user, $ambiente), 403, 'No custodian bienes de este ambiente.');

        $custodios = $this->custodiosDe($ambiente);
        $receptor = $this->receptorDe($ambiente);
        $tipo = $this->tipoSolicitado($request);

        $activos = ActivoFijo::where('id_ambiente', $ambiente->id_ambiente)
            ->with(['categoria', 'fuente', 'custodio'])
            ->tipoBien($tipo)
            ->orderBy('tipo_bien')
            ->orderBy('codigo_activo')
            ->get();

        return [$ambiente, $custodios, $receptor, $activos, $tipo];
    }

    /**
     * Quien custodia el ambiente: los que tienen un ítem del ambiente y
     * los que tienen bienes registrados ahí. Puede haber 1, 2 o más.
     * Todos ellos firman la liberación.
     */
    private function custodiosDe(Ambiente $ambiente)
    {
        $idsPorBienes = ActivoFijo::where('id_ambiente', $ambiente->id_ambiente)
            ->whereNotNull('id_custodio')
            ->distinct()
            ->pluck('id_custodio');

        $idsPorItems = User::query()
            ->join('item_usuario', 'item_usuario.id_usuario', '=', 'usuarios.id_usuario')
            ->join('items', 'items.id_item', '=', 'item_usuario.id_item')
            ->where('items.id_ambiente', $ambiente->id_ambiente)
            ->whereNull('item_usuario.fecha_fin')
            ->pluck('usuarios.id_usuario');

        return User::query()
            ->where('estado', 'ACTIVO')
            ->whereIn('id_usuario', $idsPorBienes->merge($idsPorItems)->unique())
            ->orderBy('nombre_completo')
            ->get()
            ->map(function (User $custodio) use ($ambiente) {
                $custodio->setRelation('itemsDelAmbiente', $custodio->itemsVigentes()
                    ->where('items.id_ambiente', $ambiente->id_ambiente)
                    ->get(['items.id_item', 'items.numero_item', 'item_usuario.tipo']));

                return $custodio;
            });
    }

    /**
     * Quien recibe la liberación: el jefe de carrera de la carrera del
     * ambiente. Los espacios administrativos no tienen jefe de carrera,
     * así que recibe el último administrador activo.
     */
    private function receptorDe(Ambiente $ambiente)
    {
        return $ambiente->carrera?->jefeCarrera
            ?? User::where('rol', 'ADMINISTRADOR')->where('estado', 'ACTIVO')
                ->orderByDesc('id_usuario')->first();
    }

    private function puedeLiberar(User $user, Ambiente $ambiente): bool
    {
        if ($user->esAdmin() || $user->esInventariador() || $user->esRector()) {
            return true;
        }

        if ($user->custodiaAmbiente($ambiente->id_ambiente)) {
            return true;
        }

        // El jefe de carrera es quien recibe la liberación: también puede
        // abrir el acta para firmarla.
        return (int) $ambiente->carrera?->jefeCarrera?->id_usuario === (int) $user->id_usuario;
    }

    private function tipoSolicitado(Request $request): string
    {
        $tipo = $request->input('tipo_bien', 'TODOS');

        return in_array($tipo, ['ACTIVO_FIJO', 'NO_ACTIVO'], true) ? $tipo : 'TODOS';
    }

    private function nombreArchivo(Ambiente $ambiente, string $extension): string
    {
        $base = 'Liberacion_'.$ambiente->codigo.'_'.date('Y');

        return $base.'.'.$extension;
    }
}
