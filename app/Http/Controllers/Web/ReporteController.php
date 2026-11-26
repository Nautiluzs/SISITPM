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
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
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

        $idsCarreras = $user->idsCarrerasVisibles();
        $carreras = Carrera::query()
            ->when($idsCarreras !== null, fn ($query) => $query->whereIn('id_carrera', $idsCarreras))
            ->orderBy('nombre')
            ->get();

        $ambientes = $user->ambientesVisibles()
            ->with('carrera')
            ->orderBy('nombre')
            ->get();

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
            ->paraUsuario(Auth::user())
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

        $idsCarreras = Auth::user()->idsCarrerasVisibles();
        $carreras = Carrera::query()
            ->when($idsCarreras !== null, fn ($query) => $query->whereIn('id_carrera', $idsCarreras))
            ->orderBy('nombre')
            ->get();
        $ambientes = Auth::user()->ambientesVisibles()->orderBy('nombre')->get();
        $fuentes = FuenteFinanciamiento::orderBy('nombre')->get();

        return view('reportes.inventario_general', compact('reportes', 'carreras', 'ambientes', 'fuentes', 'tipoBien'));
    }

    public function inventarioGeneralPdf(Request $request)
    {
        $tipoBien = $this->tipoBienReporte($request);

        $query = ActivoFijo::query()
            ->paraUsuario(Auth::user())
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
            $ambiente = $this->ambienteVisible($request->id_ambiente);
            $carrera = $ambiente?->carrera;
        } elseif ($request->filled('id_carrera')) {
            $carrera = $this->carreraVisible($request->id_carrera);
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
            ->paraUsuario(Auth::user())
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

        $ambiente = $request->filled('id_ambiente')
            ? $this->ambienteVisible($request->input('id_ambiente'))
            : null;
        $carrera = $ambiente?->carrera;
        if (! $ambiente && $request->filled('id_carrera')) {
            $carrera = $this->carreraVisible($request->input('id_carrera'));
        }

        $nombreArchivo = 'Inventario_'.$this->sufijoTipoBien($tipoBien).'General_'.date('Y-m-d').'.xls';

        return $this->respuestaExcel(
            'reportes.excel.inventario_general',
            compact('reportes', 'tipoBien', 'ambiente', 'carrera'),
            'Inventario general',
            $nombreArchivo,
            $request
        );
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
            ->paraUsuario(Auth::user())
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
        $ambiente = $this->ambienteVisible($id_ambiente, true);

        // Reutiliza la misma lógica de consulta por ambiente
        return $this->porAmbientePdfAction($ambiente);
    }

    public function porAmbienteExcel(Request $request, int|string $id_ambiente): Response
    {
        $ambiente = $this->ambienteVisible($id_ambiente);
        $reportes = $this->reportesPorAmbiente($ambiente);
        $nombreArchivo = 'Inventario_Ambiente_'.preg_replace('/[^A-Za-z0-9_\-]/', '_', $ambiente->nombre).'_'.date('Y-m-d').'.xls';

        return $this->respuestaExcel(
            'reportes.excel.por_ambiente',
            compact('ambiente', 'reportes'),
            'Inventario por ambiente',
            $nombreArchivo,
            $request
        );
    }

    public function porAula(Request $request, int|string $id_ambiente): View
    {
        $ambiente = $this->ambienteVisible($id_ambiente);
        $datos = $this->datosPorAula($ambiente, $request);

        return view('reportes.por_aula', array_merge($datos, compact('ambiente')));
    }

    public function porAulaPdf(Request $request, int|string $id_ambiente): Response
    {
        $ambiente = $this->ambienteVisible($id_ambiente);
        $datos = $this->datosPorAula($ambiente, $request);
        $nombreArchivo = 'Inventario_Aula_'.preg_replace('/[^A-Za-z0-9_\-]/', '_', $ambiente->nombre).'_'.date('Y-m-d').'.pdf';
        $this->registrarDescarga('Inventario por aula', 'PDF', $nombreArchivo, $request);

        return PdfHelper::oficio('reportes.pdf.por_aula', array_merge($datos, compact('ambiente')), 'landscape')
            ->stream($nombreArchivo);
    }

    public function porAulaExcel(Request $request, int|string $id_ambiente): Response
    {
        $ambiente = $this->ambienteVisible($id_ambiente);
        $datos = $this->datosPorAula($ambiente, $request);
        $nombreArchivo = 'Inventario_Aula_'.preg_replace('/[^A-Za-z0-9_\-]/', '_', $ambiente->nombre).'_'.date('Y-m-d').'.xls';

        return $this->respuestaExcel(
            'reportes.excel.por_aula',
            array_merge($datos, compact('ambiente')),
            'Inventario por aula',
            $nombreArchivo,
            $request
        );
    }

    // ============================================================
    // 3. MÃƒÆ’Ã¢â‚¬Â°TODOS DINÃƒÆ’Ã‚ÂMICOS PARA REPORTES (DESDE EL CENTRO DE REPORTES)
    // ============================================================
    public function pdf(Request $request)
    {
        $tipoBien = $this->tipoBienReporte($request);

        $query = ActivoFijo::query()
            ->paraUsuario(Auth::user())
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
            $ambiente = $this->ambienteVisible($request->id_ambiente, true);
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
            ->paraUsuario(Auth::user())
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
        $ambiente = null;
        $carrera = null;
        if ($request->filled('id_ambiente')) {
            $ambiente = $this->ambienteVisible($request->input('id_ambiente'));
            $carrera = $ambiente->carrera;
        } elseif ($request->filled('id_carrera')) {
            $carrera = $this->carreraVisible($request->input('id_carrera'));
        }

        return $this->respuestaExcel(
            'reportes.excel.inventario_general',
            compact('reportes', 'tipoBien', 'ambiente', 'carrera'),
            'Inventario filtrado',
            $nombreArchivo,
            $request
        );
    }

    protected function porAmbientePdfAction(Ambiente $ambiente)
    {
        $query = ActivoFijo::query()
            ->paraUsuario(Auth::user())
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

    /**
     * @return Collection<int, object>
     */
    private function reportesPorAmbiente(Ambiente $ambiente): Collection
    {
        $activos = ActivoFijo::query()
            ->with('fuente')
            ->paraUsuario(Auth::user())
            ->where('id_ambiente', $ambiente->id_ambiente)
            ->where('estado_registro', '!=', 'BAJA')
            ->orderBy('codigo_activo')
            ->get();

        return $activos
            ->groupBy(fn (ActivoFijo $activo) => serialize([
                $activo->nombre,
                $activo->marca,
                $activo->modelo,
                $activo->descripcion,
                $activo->estado_fisico,
                $activo->id_fuente,
                $activo->observaciones,
                $activo->imagen,
            ]))
            ->values()
            ->map(function (Collection $grupo, int $index) use ($ambiente): object {
                /** @var ActivoFijo $primero */
                $primero = $grupo->first();
                $fecha = $grupo->pluck('fecha_adquisicion')->filter()->sort()->first();
                $caracteristicas = collect([
                    $primero->marca,
                    $primero->modelo,
                    $grupo->pluck('numero_serie')->filter()->unique()->sort()->implode(', '),
                    $primero->descripcion,
                ])->filter()->implode(', ');

                if (is_string($fecha)) {
                    $fecha = Carbon::parse($fecha);
                }

                return (object) [
                    'nro' => $index + 1,
                    'codigos' => $grupo->pluck('codigo_activo')->implode(' '),
                    'ids_array' => $grupo->pluck('id_activo')->all(),
                    'descripcion' => $primero->nombre,
                    'caracteristicas' => $caracteristicas,
                    'cantidad' => $grupo->count(),
                    'costo_total' => $grupo->sum('valor_adquisicion'),
                    'estado_fisico' => $primero->estado_fisico,
                    'estado_fisico_texto' => $this->estadoFisicoTexto($primero->estado_fisico),
                    'fuente' => $primero->fuente?->nombre,
                    'fuente_codigo' => $primero->fuente?->codigo ?? '',
                    'fecha_entrega' => $fecha,
                    'dia' => $fecha?->format('d') ?? '',
                    'mes' => $fecha?->format('m') ?? '',
                    'anio' => $fecha?->format('Y') ?? '',
                    'carrera' => $ambiente->carrera?->nombre,
                    'observaciones' => $primero->observaciones,
                    'imagen' => $primero->imagen,
                ];
            });
    }

    /**
     * @return array{
     *     activos: Collection<int, ActivoFijo>,
     *     agrupados: Collection<int, object>,
     *     stats: array{total: int, buenos: int, regulares: int, malos: int, fuera: int},
     *     categorias: Collection<int, Categoria>
     * }
     */
    private function datosPorAula(Ambiente $ambiente, Request $request): array
    {
        $query = ActivoFijo::query()
            ->with(['fuente', 'categoria'])
            ->paraUsuario(Auth::user())
            ->where('id_ambiente', $ambiente->id_ambiente)
            ->where('estado_registro', '!=', 'BAJA');

        if ($request->filled('nombre')) {
            $query->where('nombre', 'like', '%'.$request->input('nombre').'%');
        }

        if ($request->filled('categoria_id')) {
            $query->where('id_categoria', $request->input('categoria_id'));
        }

        if ($request->filled('estado_fisico')) {
            $query->where('estado_fisico', $request->input('estado_fisico'));
        }

        $activos = $query->orderBy('codigo_activo')->get();
        $agrupados = $activos
            ->groupBy(fn (ActivoFijo $activo) => serialize([
                $activo->nombre,
                $activo->descripcion,
                $activo->marca,
                $activo->modelo,
                $activo->estado_fisico,
                $activo->id_fuente,
                $activo->fecha_adquisicion?->format('Y'),
                $activo->observaciones,
            ]))
            ->values()
            ->map(function (Collection $grupo): object {
                /** @var ActivoFijo $primero */
                $primero = $grupo->first();

                return (object) [
                    'codigos' => $grupo->pluck('codigo_activo')->implode("\n"),
                    'nombre' => $primero->nombre,
                    'descripcion' => $primero->descripcion,
                    'marca' => $primero->marca,
                    'modelo' => $primero->modelo,
                    'cantidad' => $grupo->count(),
                    'estado_fisico' => $primero->estado_fisico,
                    'estado_texto' => $this->estadoFisicoTexto($primero->estado_fisico),
                    'fuente_codigo' => $primero->fuente?->codigo ?? $primero->fuente?->nombre ?? '—',
                    'gestion' => $primero->fecha_adquisicion?->format('Y') ?? '—',
                    'observaciones' => $primero->observaciones,
                ];
            });

        $stats = [
            'total' => $activos->count(),
            'buenos' => $activos->where('estado_fisico', 'B')->count(),
            'regulares' => $activos->where('estado_fisico', 'R')->count(),
            'malos' => $activos->where('estado_fisico', 'M')->count(),
            'fuera' => $activos->where('estado_fisico', 'FF')->count(),
        ];

        $categorias = Categoria::query()
            ->whereIn('id_categoria', $activos->pluck('id_categoria')->unique())
            ->orderBy('nombre')
            ->get();

        return compact('activos', 'agrupados', 'stats', 'categorias');
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function respuestaExcel(
        string $vista,
        array $datos,
        string $tipoReporte,
        string $nombreArchivo,
        ?Request $request = null
    ): Response {
        $contenido = view($vista, $datos)->render();
        $this->registrarDescarga($tipoReporte, 'EXCEL', $nombreArchivo, $request);

        return response("\xEF\xBB\xBF".$contenido, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$nombreArchivo.'"',
        ]);
    }

    private function ambienteVisible(int|string $id, bool $incluirCustodios = false): Ambiente
    {
        $relaciones = $incluirCustodios ? ['carrera', 'custodios'] : ['carrera'];

        return Auth::user()
            ->ambientesVisibles()
            ->with($relaciones)
            ->findOrFail($id);
    }

    private function carreraVisible(int|string $id): Carrera
    {
        $user = Auth::user();
        $idsCarreras = $user->idsCarrerasVisibles();

        return Carrera::query()
            ->when($idsCarreras !== null, fn ($query) => $query->whereIn('id_carrera', $idsCarreras))
            ->findOrFail($id);
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
