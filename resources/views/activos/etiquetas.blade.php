<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiquetas de Activos</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', Arial, Helvetica, sans-serif;
        }

        @page {
            size: letter portrait;
            margin: 5mm;
        }

        body {
            font-size: 10px;
            color: #000000;
            background: #fff;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .sheet {
            width: 100%;
            max-width: 202mm;
            background: white;
            margin: 0 auto;
        }

        .sheet-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .sheet-header .inst { font-size: 11px; font-weight: 800; color: #000; text-transform: uppercase; }
        .sheet-header .titulo { font-size: 12px; font-weight: 800; color: #000; text-transform: uppercase; }
        .sheet-header .gestion { font-size: 10px; font-weight: 700; color: #000; }

        .subtitulo {
            text-align: center;
            font-size: 9.5px;
            font-weight: 700;
            color: #000;
            text-transform: uppercase;
            margin-bottom: 6px;
            letter-spacing: .5px;
        }

        /* Grid: 3 columnas */
        table.grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 2mm 3mm;
            table-layout: fixed;
        }
        table.grid tr {
            page-break-inside: avoid;
        }
        table.grid td {
            width: 33.33%;
            vertical-align: top;
            padding: 0;
        }

        /* ===== ETIQUETA 65mm x 25mm ===== */
        .etiqueta {
            position: relative;
            width: 65mm;
            height: 25mm;
            border: 1.2px solid #000;
            border-radius: 3px;
            overflow: hidden;
            background: #ffffff;
        }

        .etiqueta-info {
            position: absolute;
            top: 0;
            left: 0;
            width: 43mm;
            height: 25mm;
            padding: 1mm 1.2mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .etiqueta-header {
            display: flex;
            align-items: center;
            gap: 3px;
            padding-bottom: 1px;
            border-bottom: 1px solid #000;
        }
        .etiqueta-logo {
            width: 11px;
            height: 11px;
            object-fit: contain;
            flex-shrink: 0;
        }
        .etiqueta-institucion {
            font-size: 5.5px;
            font-weight: 800;
            color: #000;
            text-transform: uppercase;
            line-height: 1.1;
            text-align: center;
            flex: 1;
        }

        .etiqueta-body {
            display: flex;
            flex-direction: column;
            gap: 1px;
            justify-content: space-around;
            flex: 1;
            padding-top: 1px;
        }

        .etiqueta-row {
            display: flex;
            align-items: center;
            gap: 2px;
            line-height: 1;
        }
        .etiqueta-row .label {
            font-weight: 800;
            color: #000000;
            text-transform: uppercase;
            font-size: 7px;
            min-width: 34px;
            flex-shrink: 0;
        }
        .etiqueta-row .value {
            font-weight: 700;
            color: #000000;
            font-size: 7.5px;
            flex: 1;
        }

        /* Contenedor inferior unificado con la misma fuente y estilo */
        .etiqueta-footer-row {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            gap: 6px;
            line-height: 1;
        }
        .etiqueta-footer-row .gestion-box {
            display: flex;
            align-items: center;
            gap: 2px;
        }
        .etiqueta-footer-row .rv-box {
            display: flex;
            align-items: center;
            gap: 2px;
        }

        /* Destacados */
        .etiqueta-codigo {
            font-size: 8.5px !important;
            font-weight: 900 !important;
            color: #dc2626 !important;
        }

        .etiqueta-nombre {
            font-size: 8px !important;
            font-weight: 800 !important;
            color: #000000 !important;
            text-transform: uppercase;
        }

        .etiqueta-fuente {
            display: inline-block;
            background: #f1f5f9;
            color: #000000;
            font-size: 6.5px;
            font-weight: 800;
            padding: 0px 3px;
            border-radius: 2px;
            border: 0.8px solid #000;
            text-transform: uppercase;
        }

        /* ===== QR SECCIÓN DERECHA ===== */
        .etiqueta-qr {
            position: absolute;
            top: 0;
            right: 0;
            width: 20mm;
            height: 25mm;
            background: #ffffff;
            border-left: 1px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1mm;
        }
        .etiqueta-qr img {
            width: 20mm;
            height: 23mm;
            object-fit: contain;
            display: block;
        }
        .etiqueta-qr-placeholder {
            width: 18mm;
            height: 18mm;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            font-weight: 800;
            color: #dc2626;
            background: #f8fafc;
            border: 1px dashed #000;
            border-radius: 2px;
        }

        @media print {
            body {
                background: white !important;
                padding: 0 !important;
            }
            .sheet {
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body>

    <div class="sheet">
        <div class="sheet-header">
            <div class="inst">INSTITUTO TECNOLÓGICO PUERTO DE MEJILLONES</div>
            <div class="titulo">ETIQUETAS DE ACTIVOS</div>
            <div class="gestion">GESTIÓN {{ date('Y') }}</div>
        </div>

        <div class="subtitulo">
            {{ isset($activos) ? count($activos) : 0 }} etiquetas registradas para impresión
        </div>

        <table class="grid">
            @if(isset($activos) && count($activos) > 0)
                @foreach ($activos as $i => $activo)
                    @if ($i % 3 === 0)
                        <tr>
                    @endif

                    <td>
                        @php
                            $logoImage = $logoDataUri ?? (file_exists(public_path('images/logo.png')) ? asset('images/logo.png') : '');

                            $qrImage = '';
                            if (!empty($activo->qr_data_uri)) {
                                $qrImage = $activo->qr_data_uri;
                            } elseif (!empty($activo->ruta_qr) && \Storage::disk('public')->exists($activo->ruta_qr)) {
                                $qrImage = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get($activo->ruta_qr));
                            }

                            $gestionVal = $activo->fecha_adquisicion ?? $activo->gestion ?? $activo->created_at ?? date('Y');
                            if(is_object($gestionVal) && method_exists($gestionVal, 'format')) {
                                $gestionVal = $gestionVal->format('Y');
                            } else {
                                $gestionVal = substr((string)$gestionVal, 0, 4);
                                if(empty($gestionVal)) $gestionVal = date('Y');
                            }

                            $codigoVal = $activo->codigo_activo ?? $activo->codigo ?? $activo->codigo_patrimonial ?? 'S/C';
                            $unidadVal = $activo->ambiente->nombre ?? $activo->unidad ?? $activo->departamento ?? 'TALLER';
                            $nombreVal = $activo->nombre ?? $activo->descripcion ?? $activo->detalle ?? 'ACTIVO FIJO';

                            $fuenteVal = 'PSP';
                            if(isset($activo->fuente)) {
                                $fuenteVal = is_object($activo->fuente) ? ($activo->fuente->codigo ?? $activo->fuente->nombre ?? 'PSP') : $activo->fuente;
                            } elseif(isset($activo->fuente_financiamiento)) {
                                $fuenteVal = $activo->fuente_financiamiento;
                            }
                        @endphp

                        <div class="etiqueta">
                            <div class="etiqueta-info">
                                <div class="etiqueta-header">
                                    @if($logoImage)
                                        <img src="{{ $logoImage }}" alt="Logo" class="etiqueta-logo">
                                    @endif
                                    <div class="etiqueta-institucion">INSTITUTO TECNOLÒGICO PUERTO DE MEJILLONES</div>
                                </div>

                                <div class="etiqueta-body">
                                    <!-- 1. CÓDIGO -->
                                    <div class="etiqueta-row">
                                        <span class="label">CÓDIGO:</span>
                                        <span class="value etiqueta-codigo">{{ $codigoVal }}</span>
                                    </div>
                                    <!-- 2. ACTIVO -->
                                    <div class="etiqueta-row">
                                        <span class="label">ACTIVO:</span>
                                        <span class="value etiqueta-nombre">{{ $nombreVal }}</span>
                                    </div>
                                    <!-- 3. UNIDAD -->
                                    <div class="etiqueta-row">
                                        <span class="label">UNIDAD:</span>
                                        <span class="value">{{ $unidadVal }}</span>
                                    </div>
                                    <!-- 4. FUENTE -->
                                    <div class="etiqueta-row">
                                        <span class="label">FUENTE:</span>
                                        <span class="etiqueta-fuente">{{ $fuenteVal }}</span>
                                    </div>
                                    <!-- 5. GESTIÓN Y RV UNIFICADOS CON LA MISMA TIPOGRAFÍA -->
                                    <div class="etiqueta-footer-row">
                                        <div class="gestion-box">
                                            <span class="label" style="min-width: 34px; font-size: 7px; font-weight: 800;">GESTIÓN:</span>
                                            <span class="value" style="font-size: 7.5px; font-weight: 700;">{{ $gestionVal }}</span>
                                        </div>
                                        <div class="rv-box">
                                            <span class="label" style="min-width: 14px; font-size: 7px; font-weight: 800; margin-left: 0px;">RV:</span>
                                            <span class="value" style="font-size: 7.5px; font-weight: 700;">
                                                {{ (isset($activo->rv) && $activo->rv === 'SI') ? 'SÍ' : 'NO' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="etiqueta-qr">
                                @if($qrImage)
                                    <img src="{{ $qrImage }}" alt="QR">
                                @else
                                    <div class="etiqueta-qr-placeholder">SIN QR</div>
                                @endif
                            </div>
                        </div>
                    </td>

                    @if ($i % 3 === 2 || $loop->last)
                        </tr>
                    @endif
                @endforeach
            @else
                <tr>
                    <td colspan="3" style="text-align: center; padding: 20px; font-weight: bold;">
                        No hay activos registrados para imprimir.
                    </td>
                </tr>
            @endif
        </table>
    </div>

</body>
</html>
