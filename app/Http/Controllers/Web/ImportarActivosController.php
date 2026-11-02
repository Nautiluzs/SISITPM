<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivoFijo;
use App\Models\Ambiente;
use App\Models\Carrera;
use App\Models\Categoria;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImportarActivosController extends Controller
{
    protected function authorizeActivoModification(): void
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->puedeModificar()) {
            abort(403, 'No tiene permisos para importar activos. Solo el administrador puede.');
        }
    }

    public function form()
    {
        $this->authorizeActivoModification();

        return view('activos.importar', [
            'carreras' => Carrera::orderBy('id_carrera')->get(['id_carrera', 'nombre']),
            'ambientes' => Ambiente::with('carrera')->orderBy('id_ambiente')->get(['id_ambiente', 'id_carrera', 'nombre']),
            'categorias' => Categoria::with('ambiente')->orderBy('id_categoria')->get(['id_categoria', 'id_ambiente', 'nombre']),
        ]);
    }

    public function template()
    {
        $this->authorizeActivoModification();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Plantilla Activos');
        $headers = ['codigo_activo', 'tipo_bien', 'nombre', 'descripcion', 'marca', 'modelo', 'numero_serie', 'id_ambiente', 'id_categoria', 'id_fuente', 'id_custodio', 'estado_fisico', 'rv', 'fecha_adquisicion', 'valor_adquisicion'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray(['TPM-2026-0045', 'ACTIVO_FIJO', 'ESCRITORIO METALICO', 'Escritorio ejecutivo para dirección', 'IMPORT', 'Standard 3 cajones', 'S/S', 3, 7, 1, 1, 'B', 'NO', '2026-01-05', 1200], null, 'A2');
        $sheet->getStyle('A1:O1')->getFont()->setBold(true);
        $validacionRv = new DataValidation;
        $validacionRv->setType(DataValidation::TYPE_LIST);
        $validacionRv->setErrorStyle(DataValidation::STYLE_STOP);
        $validacionRv->setAllowBlank(false);
        $validacionRv->setShowInputMessage(true);
        $validacionRv->setShowErrorMessage(true);
        $validacionRv->setError('Seleccione SI o NO.');
        $validacionRv->setFormula1('"SI,NO"');
        $sheet->setDataValidation('M2:M1000', $validacionRv);
        foreach (range('A', 'O') as $columna) {
            $sheet->getColumnDimension($columna)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, 'plantilla_de_activos.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeActivoModification();

        $datos = $request->validate([
            'archivo_excel' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ], [
            'archivo_excel.required' => 'Debe seleccionar un archivo de Excel o CSV.',
            'archivo_excel.mimes' => 'El archivo debe ser XLSX, XLS o CSV.',
        ]);

        $archivo = $datos['archivo_excel'];

        try {
            $hojas = $this->leerArchivo($archivo);
        } catch (\Throwable $e) {
            Log::error('Error leyendo archivo de importación de activos', [
                'nombre' => $archivo->getClientOriginalName(),
                'extension' => $archivo->getClientOriginalExtension(),
                'exception' => $e,
            ]);

            return back()->withErrors([
                'archivo_excel' => 'No se pudo leer el archivo. Guárdelo como Excel .xlsx o CSV UTF-8 e inténtelo nuevamente.',
            ]);
        }

        $filas = $hojas[0] ?? [];

        if (count($filas) < 2) {
            return back()->withErrors([
                'archivo_excel' => 'El archivo debe contener encabezados y al menos una fila de datos.',
            ]);
        }

        $encabezados = $this->prepararEncabezados(array_shift($filas));
        $columnasEsperadas = [
            'codigo_activo', 'nombre', 'id_ambiente', 'id_categoria', 'id_fuente',
            'estado_fisico', 'fecha_adquisicion', 'valor_adquisicion',
        ];

        $faltantes = array_diff($columnasEsperadas, $encabezados);
        if (! in_array('ci_custodio', $encabezados, true) && ! in_array('id_custodio', $encabezados, true)) {
            $faltantes[] = 'ci_custodio (o id_custodio en plantillas antiguas)';
        }
        if (! empty($faltantes)) {
            return back()->withErrors([
                'archivo_excel' => 'Faltan columnas obligatorias: '.implode(', ', $faltantes),
            ]);
        }

        $indiceCodigo = array_search('codigo_activo', $encabezados, true);

        $resultado = DB::transaction(function () use ($filas, $encabezados, $indiceCodigo): array {
            $creados = 0;
            $omitidos = 0;
            $errores = [];
            $numeroFila = 2;
            $codigosArchivo = [];
            $codigosExistentes = ActivoFijo::query()
                ->whereIn('codigo_activo', collect($filas)
                    ->filter(fn ($fila) => is_array($fila) && isset($fila[$indiceCodigo]))
                    ->map(fn ($fila) => trim((string) $fila[$indiceCodigo]))
                    ->filter()
                    ->values())
                ->pluck('codigo_activo')
                ->mapWithKeys(fn (string $codigo): array => [strtoupper($codigo) => true]);

            foreach ($filas as $fila) {
                if (count(array_filter($fila, fn ($valor) => $valor !== null && $valor !== '')) === 0) {
                    $numeroFila++;

                    continue;
                }

                $fila = array_pad($fila, count($encabezados), null);
                $fila = array_slice($fila, 0, count($encabezados));
                $filaDatos = array_combine($encabezados, $fila);
                $filaDatos = array_map(
                    fn ($valor) => is_string($valor) ? trim($valor) : $valor,
                    $filaDatos
                );

                $codigo = (string) ($filaDatos['codigo_activo'] ?? '');
                $codigoNormalizado = strtoupper(trim($codigo));
                if (isset($codigosExistentes[$codigoNormalizado])) {
                    $errores[] = "Fila {$numeroFila}: el código {$codigo} ya existe en el inventario.";
                    $omitidos++;
                    $numeroFila++;

                    continue;
                }

                if (isset($codigosArchivo[$codigoNormalizado])) {
                    $errores[] = "Fila {$numeroFila}: el código {$codigo} está repetido dentro del archivo.";
                    $omitidos++;
                    $numeroFila++;

                    continue;
                }
                $codigosArchivo[$codigoNormalizado] = true;

                $tipoBien = strtoupper((string) ($filaDatos['tipo_bien'] ?? 'ACTIVO_FIJO'));
                $estadoFisico = strtoupper((string) ($filaDatos['estado_fisico'] ?? 'B'));
                $filaDatos['tipo_bien'] = $tipoBien;
                $filaDatos['estado_fisico'] = $estadoFisico;
                $filaDatos['rv'] = array_key_exists('rv', $filaDatos)
                    ? $this->normalizarSiNo($filaDatos['rv'])
                    : 'NO';
                $filaDatos['fecha_adquisicion'] = $this->normalizarFecha($filaDatos['fecha_adquisicion'] ?? null);

                $campoCustodio = array_key_exists('ci_custodio', $filaDatos)
                    ? 'ci_custodio'
                    : 'id_custodio';
                $reglasCustodio = $campoCustodio === 'ci_custodio'
                    ? ['required', 'string', 'exists:usuarios,ci']
                    : ['required', 'integer', 'exists:usuarios,id_usuario'];

                $validador = validator($filaDatos, [
                    'codigo_activo' => ['required', 'string', 'max:50', 'unique:activos_fijos,codigo_activo'],
                    'tipo_bien' => ['required', 'in:ACTIVO_FIJO,NO_ACTIVO'],
                    'nombre' => ['required', 'string', 'max:150'],
                    'descripcion' => ['nullable', 'string'],
                    'id_ambiente' => ['required', 'integer', 'exists:ambientes,id_ambiente'],
                    'id_categoria' => ['required', 'integer', 'exists:categorias,id_categoria'],
                    'id_fuente' => ['required', 'integer', 'exists:fuentes_financiamiento,id_fuente'],
                    $campoCustodio => $reglasCustodio,
                    'estado_fisico' => ['required', 'in:B,R,M,FF'],
                    'rv' => ['required', 'in:SI,NO'],
                    'fecha_adquisicion' => ['nullable', 'date'],
                    'valor_adquisicion' => ['nullable', 'numeric', 'min:0'],
                ]);

                if ($validador->fails()) {
                    $errores[] = "Fila {$numeroFila}: ".implode(' ', $validador->errors()->all());
                    $omitidos++;
                    $numeroFila++;

                    continue;
                }

                $usuarioCustodio = isset($filaDatos['ci_custodio'])
                    ? User::where('ci', (string) $filaDatos['ci_custodio'])->first()
                    : User::find((int) $filaDatos['id_custodio']);

                if (! $usuarioCustodio) {
                    $errores[] = "Fila {$numeroFila}: no se encontró el usuario custodio.";
                    $omitidos++;
                    $numeroFila++;

                    continue;
                }

                $activo = ActivoFijo::create([
                    'codigo_activo' => $codigo,
                    'tipo_bien' => $tipoBien,
                    'nombre' => $filaDatos['nombre'],
                    'descripcion' => $filaDatos['descripcion'] ?? null,
                    'marca' => $filaDatos['marca'] ?? null,
                    'modelo' => $filaDatos['modelo'] ?? null,
                    'numero_serie' => $filaDatos['numero_serie'] ?? null,
                    'id_ambiente' => (int) $filaDatos['id_ambiente'],
                    'id_categoria' => (int) $filaDatos['id_categoria'],
                    'id_fuente' => (int) $filaDatos['id_fuente'],
                    'id_custodio' => $usuarioCustodio->id_usuario,
                    'estado_fisico' => $estadoFisico,
                    'rv' => $filaDatos['rv'],
                    'estado_registro' => 'ACTIVO',
                    'fecha_adquisicion' => $this->normalizarFecha($filaDatos['fecha_adquisicion'] ?? null),
                    'valor_adquisicion' => (float) ($filaDatos['valor_adquisicion'] ?? 0),
                ]);

                $this->generarQRParaActivo($activo);
                $creados++;
                $numeroFila++;
            }

            return compact('creados', 'omitidos', 'errores');
        });

        $mensaje = "Importación completada: {$resultado['creados']} activo(s) creado(s).";
        $mensaje .= " Omitidos: {$resultado['omitidos']}.";

        return back()->with('success', $mensaje)
            ->with('errores_importacion', $resultado['errores']);
    }

    private function normalizarFecha(mixed $fecha): ?string
    {
        if ($fecha === null || $fecha === '') {
            return null;
        }

        if ($fecha instanceof \DateTimeInterface) {
            return $fecha->format('Y-m-d');
        }

        if (is_numeric($fecha)) {
            return ExcelDate::excelToDateTimeObject((float) $fecha)->format('Y-m-d');
        }

        $timestamp = strtotime((string) $fecha);

        return $timestamp === false ? (string) $fecha : date('Y-m-d', $timestamp);
    }

    private function normalizarSiNo(mixed $valor): ?string
    {
        $valor = Str::upper(Str::ascii(trim((string) $valor)));

        return match ($valor) {
            'SI', 'S', 'YES', 'Y', '1', 'TRUE', 'VERDADERO' => 'SI',
            'NO', 'N', 'NOT', '0', 'FALSE', 'FALSO' => 'NO',
            default => null,
        };
    }

    private function normalizarEncabezado(mixed $encabezado): string
    {
        $encabezado = Str::snake(Str::ascii(trim((string) $encabezado)));

        return [
            'codigo' => 'codigo_activo',
            'codigo_del_activo' => 'codigo_activo',
            'codigo_activo' => 'codigo_activo',
            'nombre_del_activo' => 'nombre',
            'nombre_activo' => 'nombre',
            'numero_de_serie' => 'numero_serie',
            'id_del_ambiente' => 'id_ambiente',
            'id_del_categoria' => 'id_categoria',
            'id_de_categoria' => 'id_categoria',
            'id_de_la_categoria' => 'id_categoria',
            'id_de_fuente' => 'id_fuente',
            'id_de_custodio' => 'id_custodio',
            'ci_del_custodio' => 'ci_custodio',
            'ci_custodio' => 'ci_custodio',
            'ci_usuario' => 'ci_custodio',
            'estado_fisico_del_activo' => 'estado_fisico',
            'fecha_de_adquisicion' => 'fecha_adquisicion',
            'valor_de_adquisicion' => 'valor_adquisicion',
            'visaf' => 'rv',
            'vsiaf' => 'rv',
            'es_visaf' => 'rv',
            'registro_visaf' => 'rv',
            'tiene_registro_visaf' => 'rv',
            'revaluo' => 'rv',
        ][$encabezado] ?? $encabezado;
    }

    private function prepararEncabezados(array $fila): array
    {
        $encabezados = [];
        $vistos = [];

        foreach ($fila as $encabezado) {
            $normalizado = $this->normalizarEncabezado($encabezado);

            // Algunos Excel contienen "Codigo Activo" y "codigo_activo".
            // Ambos representan la misma columna; se conserva la primera.
            if ($normalizado === '' || isset($vistos[$normalizado])) {
                $encabezados[] = '__ignorar_'.count($encabezados);

                continue;
            }

            $vistos[$normalizado] = true;
            $encabezados[] = $normalizado;
        }

        return $encabezados;
    }

    private function leerArchivo(UploadedFile $archivo): array
    {
        $extension = strtolower($archivo->getClientOriginalExtension());

        if (in_array($extension, ['csv', 'txt'], true)) {
            return [$this->leerCsv($archivo)];
        }

        $hoja = IOFactory::load($archivo->getRealPath())->getActiveSheet();
        $filas = $hoja->toArray(null, true, true, true);

        return [array_map(
            static fn (array $fila): array => array_values($fila),
            $filas
        )];
    }

    private function leerCsv(UploadedFile $archivo): array
    {
        $handle = fopen($archivo->getRealPath(), 'rb');

        if ($handle === false) {
            throw new \RuntimeException('No se pudo abrir el archivo CSV.');
        }

        $primeraLinea = fgets($handle);
        if ($primeraLinea === false) {
            fclose($handle);

            return [];
        }

        $delimitador = substr_count($primeraLinea, ';') > substr_count($primeraLinea, ',') ? ';' : ',';
        rewind($handle);
        $filas = [];

        while (($fila = fgetcsv($handle, 0, $delimitador)) !== false) {
            $filas[] = $fila;
        }

        fclose($handle);

        return $filas;
    }

    private function generarQRParaActivo(ActivoFijo $activo): void
    {
        try {
            $contenido = route('activos.buscarPorCodigo', ['codigo' => $activo->codigo_activo]);
            $svg = QrCode::format('svg')
                ->size(300)
                ->margin(1)
                ->generate($contenido);
            $fileName = 'qrcodes/'.Str::slug($activo->codigo_activo).'-'.uniqid().'.svg';
            Storage::disk('public')->put($fileName, $svg);

            $activo->update([
                'codigo_qr' => $contenido,
                'ruta_qr' => $fileName,
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo generar QR para el activo '.$activo->id_activo.': '.$e->getMessage());
        }
    }
}
