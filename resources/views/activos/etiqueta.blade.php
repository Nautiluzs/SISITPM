<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiqueta — {{ $activo->codigo_activo }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', Arial, sans-serif; }

        html, body {
            background: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .etiqueta-wrapper {
            display: flex;
            align-items: stretch;
            background: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,.15);
            border: 1.5px solid #000;
            width: 800px;
            max-width: 100%;
        }

        /* Columna Izquierda: Logo Grande */
        .etiqueta-logo-box {
            width: 170px;
            background: #f8fafc;
            border-right: 1px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            flex-shrink: 0;
        }

        .etiqueta-logo-box img {
            width: 150px;
            height: 150px;
            object-fit: contain;
            display: block;
        }

        /* Columna Central: Información */
        .etiqueta-info {
            flex: 1;
            padding: 14px 18px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .etiqueta-institucion {
            font-size: 13.5px;
            font-weight: 800;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding-bottom: 6px;
            border-bottom: 1px solid #000;
            margin-bottom: 10px;
            text-align: center;
        }

        .etiqueta-body {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .etiqueta-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            line-height: 1.2;
        }

        .etiqueta-row .label {
            font-weight: 800;
            color: #000;
            text-transform: uppercase;
            font-size: 12.5px;
            letter-spacing: 0.5px;
            min-width: 85px;
        }

        .etiqueta-row .value {
            font-weight: 700;
            color: #000;
            font-size: 14.5px;
        }

        .etiqueta-codigo {
            font-size: 18px !important;
            font-weight: 900 !important;
            color: #dc2626 !important;
        }

        .etiqueta-nombre {
            font-size: 17px !important;
            font-weight: 900 !important;
            color: #000 !important;
            text-transform: uppercase;
        }

        .etiqueta-fuente {
            display: inline-block;
            background: #e2e8f0;
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
            padding: 2px 8px;
            border-radius: 2px;
            border: 1px solid #94a3b8;
            text-transform: uppercase;
        }
        .etiqueta-fuente.bg-dc2626 {
            background: #dc2626 !important;
            color: #ffffff !important;
            border-color: #dc2626 !important;
        }

        /* Columna Derecha: QR */
        .etiqueta-qr {
            width: 170px;
            background: #f1f5f9;
            border-left: 1px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            flex-shrink: 0;
        }

        .etiqueta-qr svg,
        .etiqueta-qr img {
            width: 150px;
            height: 150px;
            display: block;
            background: white;
            padding: 2px;
            border-radius: 2px;
            border: 1px solid #000;
        }

        /* Botones flotantes de acción */
        .acciones {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 100;
        }

        .btn-accion {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
            border: none;
            text-decoration: none;
            box-shadow: 0 8px 16px rgba(0,0,0,.15);
        }

        .btn-imprimir { background: #2563eb; color: white; }
        .btn-volver { background: #e2e8f0; color: #334155; }

        @media print {
            @page { size: A4 landscape; margin: 10mm; }
            html, body { background: white !important; padding: 0 !important; }
            .acciones { display: none !important; }
            .etiqueta-wrapper {
                width: 200mm !important;
                margin: 20mm auto !important;
                box-shadow: none !important;
                border: 2px solid #0f172a !important;
            }
        }
    </style>
</head>
<body>

    <div class="etiqueta-wrapper">
        <!-- Izquierda: Logo Grande -->
        <div class="etiqueta-logo-box">
            @if(file_exists(public_path('images/logo.png')))
                <img src="{{ asset('images/logo.png') }}" alt="Logo">
            @else
                <span>Logo</span>
            @endif
        </div>

        <!-- Centro: Información -->
        <div class="etiqueta-info">
            <div class="etiqueta-institucion">
                INSTITUTO TECNOLOGICO PUERTO DE MEJILLONES
            </div>

            <div class="etiqueta-body">
                <div class="etiqueta-row">
                    <span class="label">GESTIÓN:</span>
                    <span class="value">
                        {{ $activo->fecha_adquisicion ? $activo->fecha_adquisicion->format('Y') : date('Y') }}
                    </span>
                </div>

                <div class="etiqueta-row">
                    <span class="label">CÓDIGO:</span>
                    <span class="value etiqueta-codigo">{{ $activo->codigo_activo }}</span>
                </div>

                <div class="etiqueta-row">
                    <span class="label">UNIDAD:</span>
                    <span class="value">
                        {{ $activo->ambiente->nombre ?? 'TALLER' }}
                    </span>
                </div>

                <div class="etiqueta-row">
                    <span class="label">ACTIVO:</span>
                    <span class="value etiqueta-nombre">{{ $activo->nombre }}</span>
                </div>

                <div class="etiqueta-row">
                    <span class="label">FUENTE:</span>
                    <span class="etiqueta-fuente">
                        {{ $activo->fuente->codigo ?? $activo->fuente->nombre ?? 'PSP' }}
                    </span>
                </div>

                <div class="etiqueta-row">
                    <span class="label">RV:</span>
                    <span class="etiqueta-fuente {{ $activo->rv === 'SI' ? 'bg-dc2626 text-white border-dc2626' : '' }}">
                        {{ $activo->rv === 'SI' ? 'SÍ' : 'NO' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Derecha: QR -->
        <div class="etiqueta-qr">
            {!! $qrImage ?? '' !!}
        </div>
    </div>

    <div class="acciones">
        <a href="{{ route('activos.index') }}" class="btn-accion btn-volver">← Volver</a>
        <button onclick="window.print()" class="btn-accion btn-imprimir">🖨️ Imprimir Etiqueta</button>
    </div>

</body>
</html>
