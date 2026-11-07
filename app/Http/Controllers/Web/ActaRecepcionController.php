<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivoFijo;
use App\Models\Ambiente;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ActaRecepcionController extends Controller
{
    // ============================================================
    // INDEX
    // ============================================================
    public function index()
    {
        return view('actas.recepcion.index', [
            'ambientes' => Ambiente::with('carrera')->orderBy('nombre')->get(),
        ]);
    }

    // ============================================================
    // PDF — Acta de recepción individual por activo
    // ============================================================
    public function pdf(Request $request, $activo_id = null): View|RedirectResponse
    {
        if (! $activo_id) {
            $activo_id = $request->query('activo_id');
        }

        abort_if(! $activo_id, 400, 'Falta el parámetro activo_id');

        $activo = ActivoFijo::with([
            'categoria',
            'ambiente.carrera',
            'fuente',
            'custodio',
        ])->findOrFail($activo_id);

        return view('actas.recepcion.observaciones', [
            'activos' => collect([$activo]),
            'multiple' => false,
            'action' => route('actas.recepcion.pdf.generate', $activo->id_activo),
        ]);
    }

    public function generarPdf(Request $request, int $activo_id): Response
    {
        $validated = $request->validate([
            'observaciones' => ['nullable', 'string', 'max:5000'],
        ]);

        $activo = ActivoFijo::with([
            'categoria',
            'ambiente.carrera',
            'fuente',
            'custodio',
        ])->findOrFail($activo_id);

        $jefeCarrera = optional($activo->ambiente?->carrera)->jefeCarrera;
        $responsable = Auth::user();
        $observaciones = $validated['observaciones'] ?? '';

        $pdf = Pdf::loadView('actas.recepcion.pdf', compact('activo', 'jefeCarrera', 'responsable', 'observaciones'))
            ->setPaper([0, 0, 612, 936], 'portrait')
            ->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        return $pdf->stream('Acta_Recepcion_'.$activo->codigo_activo.'.pdf');
    }

    // ============================================================
    // PDF — Actas de recepción múltiples (seleccionadas por checkbox)
    // ============================================================
    public function pdfMultiple(Request $request): View|RedirectResponse
    {
        if (! $request->has('ids')) {
            return back()->with('error', 'Selecciona al menos un activo para generar actas de recepción.');
        }

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $activos = ActivoFijo::with([
            'categoria',
            'ambiente.carrera',
            'fuente',
            'custodio',
        ])->whereIn('id_activo', $validated['ids'])
            ->orderBy('codigo_activo')
            ->get();

        if ($activos->isEmpty()) {
            return back()->with('error', 'No se encontraron activos.');
        }

        return view('actas.recepcion.observaciones', [
            'activos' => $activos,
            'multiple' => true,
            'action' => route('actas.recepcion.pdf.multiple.generate', [
                'ids' => $activos->pluck('id_activo')->all(),
            ]),
        ]);
    }

    public function generarPdfMultiple(Request $request): Response|RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
        ]);

        $activos = ActivoFijo::with([
            'categoria',
            'ambiente.carrera',
            'fuente',
            'custodio',
        ])->whereIn('id_activo', $validated['ids'])
            ->orderBy('codigo_activo')
            ->get();

        if ($activos->isEmpty()) {
            return back()->with('error', 'No se encontraron activos.');
        }

        $observaciones = $validated['observaciones'] ?? '';

        $pdf = Pdf::loadView('actas.recepcion.batch', compact('activos', 'observaciones'))
            ->setPaper([0, 0, 936, 612], 'landscape')
            ->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        return $pdf->stream('Actas_Recepcion_'.date('Y-m-d_His').'.pdf');
    }

    // ============================================================
    // PDF — ACTA DE RECEPCIÓN INDIVIDUAL (un solo activo)
    // ============================================================
    public function actaRecepcionActivoPdf(int $activo_id): RedirectResponse
    {
        return redirect()->route('actas.recepcion.pdf', $activo_id);
    }

    // ============================================================
    // EXCEL — ACTA DE RECEPCIÓN INDIVIDUAL (un solo activo)
    // ============================================================
    public function actaRecepcionActivoExcel($activo_id)
    {
        // Cargar el activo con todas sus relaciones
        $activo = ActivoFijo::with([
            'categoria',
            'ambiente.carrera',
            'fuente',
            'custodio',
        ])->findOrFail($activo_id);

        // Construir una colección con un solo activo
        $activos = collect([$activo]);

        // Firmante del acta
        $responsable = Auth::user();

        // Retornar el Excel
        return response()
            ->view('actas.recepcion.excel_individual', compact('activo', 'activos', 'responsable'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="Acta_Recepcion_'.$activo->codigo_activo.'.xls"');
    }
}
