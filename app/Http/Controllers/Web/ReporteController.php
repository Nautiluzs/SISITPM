<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivoFijo;
use App\Models\Ambiente;
use App\Models\Carrera;
use App\Models\Categoria;
use App\Models\FuenteFinanciamiento;
use App\Models\HistorialReporte;
use App\Support\PdfHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReporteController extends Controller
{
    // ============================================================
    // PANEL PRINCIPAL DE REPORTES
    // ============================================================
    public function index(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'mes_historial' => ['nullable', 'integer', 'between:1,12'],
            'anio_historial' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        // Totales generales excluyendo los dados de baja
        $queryBase = ActivoFijo::paraUsuario($user)->where('estado_registro', '!=', 'BAJA');

        $totalBuenos = (clone $queryBase)->where('estado_fisico', 'B')->count();
        $totalRegulares = (clone $queryBase)->where('estado_fisico', 'R')->count();
        $totalMalos = (clone $queryBase)->where('estado_fisico', 'M')->count();
        $totalFueraServ = (clone $queryBase)->where('estado_fisico', 'FF')->count();

        $carreras = Carrera::when(! $user || ! ($user->esAdmin() || $user->esOperativo()), function ($q) use ($user) {
            $ids = $user?->idsCarrerasVisibles();
            if ($ids === null) {
                return $q;
            }

            return $q->whereIn('id_carrera', $ids);
        })->orderBy('nombre')->get();

        $ambientes = Ambiente::with('carrera')->when(! $user || ! ($user->esAdmin() || $user->esOperativo()), function ($q) use ($user) {
            $ids = $user?->idsAmbientesVisibles();
            if (empty($ids)) {
                return $q->whereRaw('1=0');
            }

            return $q->whereIn('id_ambiente', $ids);
        })->orderBy('nombre')->get();

        $categorias = Categoria::orderBy('nombre')->get();
        $historialQuery = HistorialReporte::with('usuario')->latest();
        if ($request->filled('mes_historial')) {
            $historialQuery->whereMonth('created_at', $request->integer('mes_historial'));
        }
        if ($request->filled('anio_historial')) {
            $historialQuery->whereYear('created_at', $request->integer('anio_historial'));
        }
        $historialReportes = $historialQuery->limit(100)->get();

        return view('reportes.index', compact(
            'totalBuenos', 'totalRegulares', 'totalMalos', 'totalFueraServ',
            'carreras', 'ambientes', 'categorias', 'historialReportes'
        ));
    }

    // ============================================================
    // 1. INVENTARIO GENERAL
    // ============================================================
    public function inventarioGeneral(Request $request)
    {
        $tipoBien = $this->tipoBienReporte($request);

        $query = ActivoFijo::query()
            ->selectRaw('
                GROUP_CONCAT(activos_fijos.codigo_activo ORDER BY activos_fijos.codigo_activo SEPARATOR " ") as codigos,
                GROUP_CONCAT(activos_fijos.id_activo ORDER BY activos_fijos.id_activo SEPARATOR ",") as ids_array,
                activos_fijos.nombre as descripcion,
                CONCAT_WS(", ",
                    activos_fijos.marca,
                    activos_fijos.modelo,
                    GROUP_CONCAT(DISTINCT activos_fijos.numero_serie ORDER BY activos_fijos.numero_serie SEPARATOR ", "),
                    activos_fijos.descripcion
                ) as caracteristicas,
                COUNT(*) as cantidad,
                SUM(activos_fijos.valor_adquisicion) as costo_total,
                activos_fijos.estado_fisico,
                fuentes_financiamiento.nombre as fuente,
                fuentes_financiamiento.codigo as fuente_codigo,
                MIN(activos_fijos.fecha_adquisicion) as fecha_entrega,
                carreras.nombre as carrera,
                activos_fijos.observaciones,
                activos_fijos.imagen
            ')
            ->join('categorias', 'activos_fijos.id_categoria', '=', 'categorias.id_categoria')
            ->join('ambientes', 'activos_fijos.id_ambiente', '=', 'ambientes.id_ambiente')
            ->join('carreras', 'ambientes.id_carrera', '=', 'carreras.id_carrera')
            ->leftJoin('fuentes_financiamiento', 'activos_fijos.id_fuente', '=', 'fuentes_financiamiento.id_fuente')
            ->where('activos_fijos.estado_registro', '!=', 'BAJA')
            ->tipoBien($tipoBien);

        if ($request->filled('id_carrera')) {
            $query->where('carreras.id_carrera', $request->id_carrera);
        }
        if ($request->filled('id_ambiente')) {
            $query->where('activos_fijos.id_ambiente', $request->id_ambiente);
        }
        if ($request->filled('estado_fisico')) {
            $query->where('activos_fijos.estado_fisico', $request->estado_fisico);
        }
        if ($request->filled('fuente')) {
            $query->where('activos_fijos.id_fuente', $request->fuente);
        }
        if ($request->filled('anio')) {
            $query->whereYear('activos_fijos.fecha_adquisicion', $request->anio);
        }

        $query->groupBy(
            'activos_fijos.nombre',
            'activos_fijos.marca',
            'activos_fijos.modelo',
            'activos_fijos.descripcion',
            'activos_fijos.estado_fisico',
            'fuentes_financiamiento.nombre',
            'carreras.nombre',
            'activos_fijos.observaciones',
            'activos_fijos.imagen'
        )
            ->orderBy('activos_fijos.modelo')
            ->orderBy('activos_fijos.marca')
            ->orderBy('activos_fijos.nombre');

        $reportes = $query->get()->map(function ($item, $index) {
            $fecha = $item->fecha_entrega;
            if (is_string($fecha)) {
                $fecha = Carbon::parse($fecha);
            }

            return (object) [
                'nro' => $index + 1,
                'codigos' => $item->codigos,
                'ids_array' => explode(',', $item->ids_array),
                'descripcion' => $item->descripcion,
                'caracteristicas' => $item->caracteristicas,
                'cantidad' => $item->cantidad,
                'costo_total' => $item->costo_total,
                'estado_fisico' => $item->estado_fisico,
                'estado_fisico_texto' => $this->estadoFisicoTexto($item->estado_fisico),
                'fuente' => $item->fuente,
                'fuente_codigo' => $item->fuente_codigo,
                'fecha_entrega' => $fecha,
                'dia' => $fecha ? $fecha->format('d') : '',
                'mes' => $fecha ? $fecha->format('m') : '',
                'anio' => $fecha ? $fecha->format('Y') : '',
                'carrera' => $item->carrera,
                'observaciones' => $item->observaciones,
                'imagen' => $item->imagen,
            ];
        });

        $carreras = Carrera::orderBy('nombre')->get();
        $ambientes = Ambiente::orderBy('nombre')->get();
        $fuentes = FuenteFinanciamiento::orderBy('nombre')->get();

        return view('reportes.inventario_general', compact('reportes', 'carreras', 'ambientes', 'fuentes', 'tipoBien'));
    }

    public function inventarioGeneralPdf(Request $request)
    {
        $tipoBien = $this->tipoBienReporte($request);

        $query = ActivoFijo::query()
            ->selectRaw('
                GROUP_CONCAT(activos_fijos.codigo_activo ORDER BY activos_fijos.codigo_activo SEPARATOR " ") as codigos,
                activos_fijos.nombre as descripcion,
                CONCAT_WS(", ",
                    activos_fijos.marca,
                    activos_fijos.modelo,
                    GROUP_CONCAT(DISTINCT activos_fijos.numero_serie ORDER BY activos_fijos.numero_serie SEPARATOR ", "),
                    activos_fijos.descripcion
                ) as caracteristicas,
                COUNT(*) as cantidad,
                SUM(activos_fijos.valor_adquisicion) as costo_total,
                activos_fijos.estado_fisico,
                fuentes_financiamiento.nombre as fuente,
                fuentes_financiamiento.codigo as fuente_codigo,
                MIN(activos_fijos.fecha_adquisicion) as fecha_entrega,
                carreras.nombre as carrera,
                activos_fijos.observaciones,
                activos_fijos.imagen
            ')
            ->join('categorias', 'activos_fijos.id_categoria', '=', 'categorias.id_categoria')
            ->join('ambientes', 'activos_fijos.id_ambiente', '=', 'ambientes.id_ambiente')
            ->join('carreras', 'ambientes.id_carrera', '=', 'carreras.id_carrera')
            ->leftJoin('fuentes_financiamiento', 'activos_fijos.id_fuente', '=', 'fuentes_financiamiento.id_fuente')
            ->where('activos_fijos.estado_registro', '!=', 'BAJA')
            ->tipoBien($tipoBien);

        if ($request->filled('id_carrera')) {
            $query->where('carreras.id_carrera', $request->id_carrera);
        }
        if ($request->filled('id_ambiente')) {
            $query->where('activos_fijos.id_ambiente', $request->id_ambiente);
        }
        if ($request->filled('estado_fisico')) {
            $query->where('activos_fijos.estado_fisico', $request->estado_fisico);
        }
        if ($request->filled('fuente')) {
            $query->where('activos_fijos.id_fuente', $request->fuente);
        }
        if ($request->filled('anio')) {
            $query->whereYear('activos_fijos.fecha_adquisicion', $request->anio);
        }

        $query->groupBy(
            'activos_fijos.nombre',
            'activos_fijos.marca',
            'activos_fijos.modelo',
            'activos_fijos.descripcion',
            'activos_fijos.estado_fisico',
            'fuentes_financiamiento.nombre',
            'carreras.nombre',
            'activos_fijos.observaciones',
            'activos_fijos.imagen'
        )
            ->orderBy('activos_fijos.modelo')
            ->orderBy('activos_fijos.marca')
            ->orderBy('activos_fijos.nombre');

        $reportes = $query->get()->map(function ($item, $index) {
            $fecha = $item->fecha_entrega;
            if (is_string($fecha)) {
                $fecha = Carbon::parse($fecha);
            }

            return (object) [
                'nro' => $index + 1,
                'codigos' => $item->codigos,
                'ids_array' => [],
                'descripcion' => $item->descripcion,
                'caracteristicas' => $item->caracteristicas,
                'cantidad' => $item->cantidad,
                'costo_total' => $item->costo_total,
                'estado_fisico' => $item->estado_fisico,
                'estado_fisico_texto' => $this->estadoFisicoTexto($item->estado_fisico),
                'fuente' => $item->fuente,
                'fuente_codigo' => $item->fuente_codigo,
                'fecha_entrega' => $fecha,
                'dia' => $fecha ? $fecha->format('d') : '',
                'mes' => $fecha ? $fecha->format('m') : '',
                'anio' => $fecha ? $fecha->format('Y') : '',
                'carrera' => $item->carrera,
                'observaciones' => $item->observaciones,
                'imagen' => $item->imagen,
            ];
        });

        // Determinar ambiente y carrera para el encabezado del PDF
        $ambiente = null;
        $carrera = null;
        if ($request->filled('id_ambiente')) {
            $ambiente = Ambiente::with('carrera')->find($request->id_ambiente);
            $carrera = $ambiente?->carrera;
        } elseif ($request->filled('id_carrera')) {
            $carrera = Carrera::find($request->id_carrera);
        }

        $nombreArchivo = 'Inventario_'.$this->sufijoTipoBien($tipoBien).'General_'.date('Y-m-d').'.pdf';
        $this->registrarDescarga('Inventario general', 'PDF', $nombreArchivo, $request);
        $pdf = PdfHelper::oficio('reportes.pdf.inventario_general', compact('reportes', 'tipoBien', 'ambiente', 'carrera'), 'landscape');

        return $pdf->stream($nombreArchivo);
    }

    public function inventarioGeneralExcel(Request $request)
    {
        $tipoBien = $this->tipoBienReporte($request);

        $query = ActivoFijo::query()
            ->selectRaw('
                GROUP_CONCAT(activos_fijos.codigo_activo ORDER BY activos_fijos.codigo_activo SEPARATOR " ") as codigos,
                activos_fijos.nombre as descripcion,
                CONCAT_WS(", ",
                    activos_fijos.marca,
                    activos_fijos.modelo,
                    GROUP_CONCAT(DISTINCT activos_fijos.numero_serie ORDER BY activos_fijos.numero_serie SEPARATOR ", "),
                    activos_fijos.descripcion
                ) as caracteristicas,
                COUNT(*) as cantidad,
                SUM(activos_fijos.valor_adquisicion) as costo_total,
                activos_fijos.estado_fisico,
                fuentes_financiamiento.nombre as fuente,
                fuentes_financiamiento.codigo as fuente_codigo,
                MIN(activos_fijos.fecha_adquisicion) as fecha_entrega,
                carreras.nombre as carrera,
                activos_fijos.observaciones,
                activos_fijos.imagen
            ')
            ->join('categorias', 'activos_fijos.id_categoria', '=', 'categorias.id_categoria')
            ->join('ambientes', 'activos_fijos.id_ambiente', '=', 'ambientes.id_ambiente')
            ->join('carreras', 'ambientes.id_carrera', '=', 'carreras.id_carrera')
            ->leftJoin('fuentes_financiamiento', 'activos_fijos.id_fuente', '=', 'fuentes_financiamiento.id_fuente')
            ->where('activos_fijos.estado_registro', '!=', 'BAJA')
            ->tipoBien($tipoBien);

        if ($request->filled('id_carrera')) {
            $query->where('carreras.id_carrera', $request->id_carrera);
        }
        if ($request->filled('id_ambiente')) {
            $query->where('activos_fijos.id_ambiente', $request->id_ambiente);
        }
        if ($request->filled('estado_fisico')) {
            $query->where('activos_fijos.estado_fisico', $request->estado_fisico);
        }
        if ($request->filled('fuente')) {
            $query->where('activos_fijos.id_fuente', $request->fuente);
        }
        if ($request->filled('anio')) {
            $query->whereYear('activos_fijos.fecha_adquisicion', $request->anio);
        }

        $query->groupBy(
            'activos_fijos.nombre',
            'activos_fijos.marca',
            'activos_fijos.modelo',
            'activos_fijos.descripcion',
            'activos_fijos.estado_fisico',
            'fuentes_financiamiento.nombre',
            'carreras.nombre',
            'activos_fijos.observaciones',
            'activos_fijos.imagen'
        )
            ->orderBy('activos_fijos.modelo')
            ->orderBy('activos_fijos.marca')
            ->orderBy('activos_fijos.nombre');

        $reportes = $query->get()->map(function ($item, $index) {
            $fecha = $item->fecha_entrega;
            if (is_string($fecha)) {
                $fecha = Carbon::parse($fecha);
            }

            return (object) [
                'nro' => $index + 1,
                'codigos' => $item->codigos,
                'ids_array' => [],
                'descripcion' => $item->descripcion,
                'caracteristicas' => $item->caracteristicas,
                'cantidad' => $item->cantidad,
                'costo_total' => $item->costo_total,
                'estado_fisico' => $item->estado_fisico,
                'estado_fisico_texto' => $this->estadoFisicoTexto($item->estado_fisico),
                'fuente' => $item->fuente,
                'fuente_codigo' => $item->fuente_codigo,
                'fecha_entrega' => $fecha,
                'dia' => $fecha ? $fecha->format('d') : '',
                'mes' => $fecha ? $fecha->format('m') : '',
                'anio' => $fecha ? $fecha->format('Y') : '',
                'carrera' => $item->carrera,
                'observaciones' => $item->observaciones,
                'imagen' => $item->imagen,
            ];
        });

        $nombreArchivo = 'Inventario_'.$this->sufijoTipoBien($tipoBien).'General_'.date('Y-m-d').'.xls';
        $this->registrarDescarga('Inventario general', 'EXCEL', $nombreArchivo, $request);

        return response()
            ->view('reportes.excel.inventario_general', compact('reportes', 'tipoBien'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="'.$nombreArchivo.'"');
    }

    private function tipoBienReporte(Request $request): string
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

    // ============================================================
    // 2. INVENTARIO POR AMBIENTE
    // ============================================================
    public function porAmbiente($id_ambiente)
    {
        $ambiente = $this->ambienteVisible($id_ambiente);

        $query = ActivoFijo::query()
            ->selectRaw('
                GROUP_CONCAT(activos_fijos.codigo_activo ORDER BY activos_fijos.codigo_activo SEPARATOR " ") as codigos,
                activos_fijos.nombre as descripcion,
                CONCAT_WS(", ",
                    activos_fijos.marca,
                    activos_fijos.modelo,
                    GROUP_CONCAT(DISTINCT activos_fijos.numero_serie ORDER BY activos_fijos.numero_serie SEPARATOR ", "),
                    activos_fijos.descripcion
                ) as caracteristicas,
                COUNT(*) as cantidad,
                SUM(activos_fijos.valor_adquisicion) as costo_total,
                activos_fijos.estado_fisico,
                fuentes_financiamiento.nombre as fuente,
                MIN(activos_fijos.fecha_adquisicion) as fecha_entrega,
                carreras.nombre as carrera,
                activos_fijos.observaciones,
                activos_fijos.imagen
            ')
            ->join('categorias', 'activos_fijos.id_categoria', '=', 'categorias.id_categoria')
            ->join('ambientes', 'activos_fijos.id_ambiente', '=', 'ambientes.id_ambiente')
            ->join('carreras', 'ambientes.id_carrera', '=', 'carreras.id_carrera')
            ->leftJoin('fuentes_financiamiento', 'activos_fijos.id_fuente', '=', 'fuentes_financiamiento.id_fuente')
            ->where('activos_fijos.estado_registro', '!=', 'BAJA')
            ->where('activos_fijos.id_ambiente', $id_ambiente)
            ->groupBy(
                'activos_fijos.nombre',
                'activos_fijos.marca',
                'activos_fijos.modelo',
                'activos_fijos.descripcion',
                'activos_fijos.estado_fisico',
                'fuentes_financiamiento.nombre',
                'carreras.nombre',
                'activos_fijos.observaciones',
                'activos_fijos.imagen'
            )
            ->orderBy('activos_fijos.modelo')
            ->orderBy('activos_fijos.marca')
            ->orderBy('activos_fijos.nombre');

        $reportes = $query->get()->map(function ($item, $index) {
            $fecha = $item->fecha_entrega;
            if (is_string($fecha)) {
                $fecha = Carbon::parse($fecha);
            }

            return (object) [
                'nro' => $index + 1,
                'codigos' => $item->codigos,
                'ids_array' => [],
                'descripcion' => $item->descripcion,
                'caracteristicas' => $item->caracteristicas,
                'cantidad' => $item->cantidad,
                'costo_total' => $item->costo_total,
                'estado_fisico' => $item->estado_fisico,
                'estado_fisico_texto' => $this->estadoFisicoTexto($item->estado_fisico),
                'fuente' => $item->fuente,
                'fuente_codigo' => $item->fuente_codigo ?? '',
                'fecha_entrega' => $fecha,
                'dia' => $fecha ? $fecha->format('d') : '',
                'mes' => $fecha ? $fecha->format('m') : '',
                'anio' => $fecha ? $fecha->format('Y') : '',
                'carrera' => $item->carrera,
                'observaciones' => $item->observaciones,
                'imagen' => $item->imagen,
            ];
        });

        return view('reportes.por_ambiente', compact('ambiente', 'reportes'));
    }

    public function porAmbientePdf($id_ambiente)
    {
        $ambiente = Ambiente::with('carrera', 'custodios')->findOrFail($id_ambiente);

        // Reutiliza la misma lógica de consulta por ambiente
        return $this->porAmbientePdfAction($ambiente);
    }

    public function porAmbienteExcel($id_ambiente)
    {
        $ambiente = $this->ambienteVisible($id_ambiente);
        $nombreArchivo = 'Inventario_Ambiente_'.preg_replace('/[^A-Za-z0-9_\-]/', '_', $ambiente->nombre).'_'.date('Y-m-d').'.xls';
        $this->registrarDescarga('Inventario por ambiente', 'EXCEL', $nombreArchivo);

        return response()
            ->view('reportes.excel.por_ambiente', compact('ambiente'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="'.$nombreArchivo.'"');
    }

    // ============================================================
    // 3. MÃƒÆ’Ã¢â‚¬Â°TODOS DINÃƒÆ’Ã‚ÂMICOS PARA REPORTES (DESDE EL CENTRO DE REPORTES)
    // ============================================================
    public function pdf(Request $request)
    {
        $tipoBien = $this->tipoBienReporte($request);

        $query = ActivoFijo::query()
            ->selectRaw('
                GROUP_CONCAT(activos_fijos.codigo_activo ORDER BY activos_fijos.codigo_activo SEPARATOR " ") as codigos,
                activos_fijos.nombre as descripcion,
                CONCAT_WS(", ",
                    activos_fijos.marca,
                    activos_fijos.modelo,
                    GROUP_CONCAT(DISTINCT activos_fijos.numero_serie ORDER BY activos_fijos.numero_serie SEPARATOR ", "),
                    activos_fijos.descripcion
                ) as caracteristicas,
                COUNT(*) as cantidad,
                SUM(activos_fijos.valor_adquisicion) as costo_total,
                activos_fijos.estado_fisico,
                fuentes_financiamiento.nombre as fuente,
                fuentes_financiamiento.codigo as fuente_codigo,
                MIN(activos_fijos.fecha_adquisicion) as fecha_entrega,
                carreras.nombre as carrera,
                activos_fijos.observaciones,
                activos_fijos.imagen
            ')
            ->join('categorias', 'activos_fijos.id_categoria', '=', 'categorias.id_categoria')
            ->join('ambientes', 'activos_fijos.id_ambiente', '=', 'ambientes.id_ambiente')
            ->join('carreras', 'ambientes.id_carrera', '=', 'carreras.id_carrera')
            ->leftJoin('fuentes_financiamiento', 'activos_fijos.id_fuente', '=', 'fuentes_financiamiento.id_fuente')
            ->where('activos_fijos.estado_registro', '!=', 'BAJA')
            ->tipoBien($tipoBien);

        if ($request->filled('id_ambiente')) {
            $query->where('activos_fijos.id_ambiente', $request->id_ambiente);
        }
        if ($request->filled('id_carrera')) {
            $query->where('carreras.id_carrera', $request->id_carrera);
        }
        if ($request->filled('id_categoria')) {
            $query->where('activos_fijos.id_categoria', $request->id_categoria);
        }

        $query->groupBy(
            'activos_fijos.nombre',
            'activos_fijos.marca',
            'activos_fijos.modelo',
            'activos_fijos.descripcion',
            'activos_fijos.estado_fisico',
            'fuentes_financiamiento.nombre',
            'carreras.nombre',
            'activos_fijos.observaciones',
            'activos_fijos.imagen'
        )
            ->orderBy('activos_fijos.modelo')
            ->orderBy('activos_fijos.marca')
            ->orderBy('activos_fijos.nombre');

        $reportes = $query->get()->map(function ($item, $index) {
            $fecha = $item->fecha_entrega;
            if (is_string($fecha)) {
                $fecha = Carbon::parse($fecha);
            }

            return (object) [
                'nro' => $index + 1,
                'codigos' => $item->codigos,
                'ids_array' => [],
                'descripcion' => $item->descripcion,
                'caracteristicas' => $item->caracteristicas,
                'cantidad' => $item->cantidad,
                'costo_total' => $item->costo_total,
                'estado_fisico' => $item->estado_fisico,
                'estado_fisico_texto' => $this->estadoFisicoTexto($item->estado_fisico),
                'fuente' => $item->fuente,
                'fuente_codigo' => $item->fuente_codigo ?? '',
                'fecha_entrega' => $fecha,
                'dia' => $fecha ? $fecha->format('d') : '',
                'mes' => $fecha ? $fecha->format('m') : '',
                'anio' => $fecha ? $fecha->format('Y') : '',
                'carrera' => $item->carrera,
                'observaciones' => $item->observaciones,
                'imagen' => $item->imagen,
            ];
        });

        $ambiente = null;
        if ($request->filled('id_ambiente')) {
            $ambiente = Ambiente::with('carrera', 'custodios')->find($request->id_ambiente);
        }

        $nombreArchivo = 'Inventario_Filtro_'.date('Y-m-d').'.pdf';
        $this->registrarDescarga('Inventario filtrado', 'PDF', $nombreArchivo, $request);
        $pdf = PdfHelper::oficio('reportes.pdf.inventario_general', compact('reportes', 'tipoBien', 'ambiente'), 'landscape');

        return $pdf->stream($nombreArchivo);
    }

    public function excel(Request $request)
    {
        $tipoBien = $this->tipoBienReporte($request);

        $query = ActivoFijo::query()
            ->selectRaw('
                GROUP_CONCAT(activos_fijos.codigo_activo ORDER BY activos_fijos.codigo_activo SEPARATOR " ") as codigos,
                activos_fijos.nombre as descripcion,
                CONCAT_WS(", ",
                    activos_fijos.marca,
                    activos_fijos.modelo,
                    GROUP_CONCAT(DISTINCT activos_fijos.numero_serie ORDER BY activos_fijos.numero_serie SEPARATOR ", "),
                    activos_fijos.descripcion
                ) as caracteristicas,
                COUNT(*) as cantidad,
                SUM(activos_fijos.valor_adquisicion) as costo_total,
                activos_fijos.estado_fisico,
                fuentes_financiamiento.nombre as fuente,
                fuentes_financiamiento.codigo as fuente_codigo,
                MIN(activos_fijos.fecha_adquisicion) as fecha_entrega,
                carreras.nombre as carrera,
                activos_fijos.observaciones,
                activos_fijos.imagen
            ')
            ->join('categorias', 'activos_fijos.id_categoria', '=', 'categorias.id_categoria')
            ->join('ambientes', 'activos_fijos.id_ambiente', '=', 'ambientes.id_ambiente')
            ->join('carreras', 'ambientes.id_carrera', '=', 'carreras.id_carrera')
            ->leftJoin('fuentes_financiamiento', 'activos_fijos.id_fuente', '=', 'fuentes_financiamiento.id_fuente')
            ->where('activos_fijos.estado_registro', '!=', 'BAJA')
            ->tipoBien($tipoBien);

        if ($request->filled('id_ambiente')) {
            $query->where('activos_fijos.id_ambiente', $request->id_ambiente);
        }
        if ($request->filled('id_carrera')) {
            $query->where('carreras.id_carrera', $request->id_carrera);
        }
        if ($request->filled('id_categoria')) {
            $query->where('activos_fijos.id_categoria', $request->id_categoria);
        }

        $query->groupBy(
            'activos_fijos.nombre',
            'activos_fijos.marca',
            'activos_fijos.modelo',
            'activos_fijos.descripcion',
            'activos_fijos.estado_fisico',
            'fuentes_financiamiento.nombre',
            'carreras.nombre',
            'activos_fijos.observaciones',
            'activos_fijos.imagen'
        )
            ->orderBy('activos_fijos.modelo')
            ->orderBy('activos_fijos.marca')
            ->orderBy('activos_fijos.nombre');

        $reportes = $query->get()->map(function ($item, $index) {
            $fecha = $item->fecha_entrega;
            if (is_string($fecha)) {
                $fecha = Carbon::parse($fecha);
            }

            return (object) [
                'nro' => $index + 1,
                'codigos' => $item->codigos,
                'ids_array' => [],
                'descripcion' => $item->descripcion,
                'caracteristicas' => $item->caracteristicas,
                'cantidad' => $item->cantidad,
                'costo_total' => $item->costo_total,
                'estado_fisico' => $item->estado_fisico,
                'estado_fisico_texto' => $this->estadoFisicoTexto($item->estado_fisico),
                'fuente' => $item->fuente,
                'fuente_codigo' => $item->fuente_codigo ?? '',
                'fecha_entrega' => $fecha,
                'dia' => $fecha ? $fecha->format('d') : '',
                'mes' => $fecha ? $fecha->format('m') : '',
                'anio' => $fecha ? $fecha->format('Y') : '',
                'carrera' => $item->carrera,
                'observaciones' => $item->observaciones,
                'imagen' => $item->imagen,
            ];
        });

        $nombreArchivo = 'Inventario_Filtro_'.date('Y-m-d').'.xls';
        $this->registrarDescarga('Inventario filtrado', 'EXCEL', $nombreArchivo, $request);

        return response()
            ->view('reportes.excel.inventario_general', compact('reportes', 'tipoBien'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="'.$nombreArchivo.'"');
    }

    protected function porAmbientePdfAction(Ambiente $ambiente)
    {
        $query = ActivoFijo::query()
            ->selectRaw('
                GROUP_CONCAT(activos_fijos.codigo_activo ORDER BY activos_fijos.codigo_activo SEPARATOR " ") as codigos,
                activos_fijos.nombre as descripcion,
                CONCAT_WS(", ",
                    activos_fijos.marca,
                    activos_fijos.modelo,
                    GROUP_CONCAT(DISTINCT activos_fijos.numero_serie ORDER BY activos_fijos.numero_serie SEPARATOR ", "),
                    activos_fijos.descripcion
                ) as caracteristicas,
                COUNT(*) as cantidad,
                SUM(activos_fijos.valor_adquisicion) as costo_total,
                activos_fijos.estado_fisico,
                fuentes_financiamiento.nombre as fuente,
                MIN(activos_fijos.fecha_adquisicion) as fecha_entrega,
                carreras.nombre as carrera,
                activos_fijos.observaciones,
                activos_fijos.imagen
            ')
            ->join('categorias', 'activos_fijos.id_categoria', '=', 'categorias.id_categoria')
            ->join('ambientes', 'activos_fijos.id_ambiente', '=', 'ambientes.id_ambiente')
            ->join('carreras', 'ambientes.id_carrera', '=', 'carreras.id_carrera')
            ->leftJoin('fuentes_financiamiento', 'activos_fijos.id_fuente', '=', 'fuentes_financiamiento.id_fuente')
            ->where('activos_fijos.estado_registro', '!=', 'BAJA')
            ->where('activos_fijos.id_ambiente', $ambiente->id_ambiente)
            ->groupBy(
                'activos_fijos.nombre',
                'activos_fijos.marca',
                'activos_fijos.modelo',
                'activos_fijos.descripcion',
                'activos_fijos.estado_fisico',
                'fuentes_financiamiento.nombre',
                'carreras.nombre',
                'activos_fijos.observaciones',
                'activos_fijos.imagen'
            )
            ->orderBy('activos_fijos.modelo')
            ->orderBy('activos_fijos.marca')
            ->orderBy('activos_fijos.nombre');

        $reportes = $query->get()->map(function ($item, $index) {
            $fecha = $item->fecha_entrega;
            if (is_string($fecha)) {
                $fecha = Carbon::parse($fecha);
            }

            return (object) [
                'nro' => $index + 1,
                'codigos' => $item->codigos,
                'ids_array' => [],
                'descripcion' => $item->descripcion,
                'caracteristicas' => $item->caracteristicas,
                'cantidad' => $item->cantidad,
                'costo_total' => $item->costo_total,
                'estado_fisico' => $item->estado_fisico,
                'estado_fisico_texto' => $this->estadoFisicoTexto($item->estado_fisico),
                'fuente' => $item->fuente,
                'fuente_codigo' => $item->fuente_codigo ?? '',
                'fecha_entrega' => $fecha,
                'dia' => $fecha ? $fecha->format('d') : '',
                'mes' => $fecha ? $fecha->format('m') : '',
                'anio' => $fecha ? $fecha->format('Y') : '',
                'carrera' => $item->carrera,
                'observaciones' => $item->observaciones,
                'imagen' => $item->imagen,
            ];
        });

        $nombreArchivo = 'Inventario_Ambiente_'.preg_replace('/[^A-Za-z0-9_\-]/', '_', $ambiente->nombre).'_'.date('Y-m-d').'.pdf';
        $this->registrarDescarga('Inventario por ambiente', 'PDF', $nombreArchivo);
        $pdf = PdfHelper::oficio('reportes.pdf.por_ambiente', compact('ambiente', 'reportes'), 'landscape');

        return $pdf->stream($nombreArchivo);
    }

    private function registrarDescarga(string $tipoReporte, string $formato, string $nombreArchivo, ?Request $request = null): void
    {
        HistorialReporte::create([
            'id_usuario' => Auth::id(),
            'tipo_reporte' => $tipoReporte,
            'formato' => $formato,
            'nombre_archivo' => $nombreArchivo,
            'filtros' => $request?->only(['tipo_bien', 'id_carrera', 'id_ambiente', 'id_categoria', 'estado_fisico', 'fuente', 'anio']),
        ]);
    }

    protected function estadoFisicoTexto($estado): string
    {
        return match ($estado) {
            'B' => 'BUENO',
            'R' => 'REGULAR',
            'M' => 'MALO',
            'FF' => 'FUERA DE FUNCIONAMIENTO',
            default => 'DESCONOCIDO',
        };
    }
}
