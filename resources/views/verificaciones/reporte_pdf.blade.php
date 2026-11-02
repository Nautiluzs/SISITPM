<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de verificación</title>
    <style>
        @page { margin: 24px 20px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #172033; }
        h1 { margin: 0 0 4px; font-size: 16px; color: #123b78; }
        .subtitulo { margin-bottom: 12px; color: #526174; font-size: 9px; }
        .cabecera { width: 100%; border-bottom: 2px solid #2563eb; margin-bottom: 10px; padding-bottom: 8px; }
        .cabecera td { vertical-align: top; }
        .ambiente { text-align: right; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th { background: #123b78; color: white; padding: 5px 3px; font-size: 7px; }
        td { border: 1px solid #cbd5e1; padding: 3px; vertical-align: top; word-wrap: break-word; }
        tr:nth-child(even) { background: #f8fafc; }
        .imagenes { width: 112px; text-align: center; vertical-align: middle; }
        .imagenes img { width: 42px; height: 42px; object-fit: cover; margin: 2px auto; border: 1px solid #94a3b8; display: inline-block; vertical-align: middle; }
        .estado { font-weight: bold; color: #15803d; }
        .observado { color: #b45309; }
        .sin-imagen { color: #94a3b8; font-size: 7px; }
        .pie { margin-top: 10px; color: #64748b; font-size: 7px; }
    </style>
</head>
<body>
    <table class="cabecera">
        <tr>
            <td>
                <h1>Reporte de verificación física de activos</h1>
                <div class="subtitulo">Detalle completo de los activos verificados</div>
            </td>
            <td class="ambiente">
                <strong>Ambiente:</strong> {{ $ambiente->nombre }}<br>
                <strong>Carrera:</strong> {{ $ambiente->carrera->nombre ?? 'Sin carrera' }}<br>
                <strong>Fecha:</strong> {{ now()->format('d/m/Y H:i') }}
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width:7%;">Código</th>
                <th style="width:5%;">Tipo / RV</th>
                <th style="width:9%;">Nombre</th>
                <th style="width:13%;">Descripción</th>
                <th style="width:8%;">Marca / Modelo</th>
                <th style="width:8%;">N.º serie</th>
                <th style="width:8%;">Categoría / Fuente</th>
                <th style="width:9%;">Ambiente / Custodio</th>
                <th style="width:7%;">Estado físico / registro</th>
                <th style="width:7%;">Adquisición / valor</th>
                <th class="imagenes">Imágenes</th>
                <th style="width:8%;">Verificación / observación</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activos as $activo)
                <tr>
                    <td>{{ $activo->codigo_activo }}</td>
                    <td>{{ $activo->tipo_bien }}<br>RV: {{ $activo->rv ?? 'NO' }}</td>
                    <td>{{ $activo->nombre }}</td>
                    <td>{{ $activo->descripcion ?: '—' }}</td>
                    <td>{{ $activo->marca ?: '—' }}<br>{{ $activo->modelo ?: '—' }}</td>
                    <td>{{ $activo->numero_serie ?: '—' }}</td>
                    <td>{{ $activo->categoria->nombre ?? '—' }}<br>{{ $activo->fuente->nombre ?? '—' }}</td>
                    <td>{{ $activo->ambiente->nombre ?? '—' }}<br>{{ $activo->custodio->nombre_completo ?? 'Sin custodio' }}</td>
                    <td>{{ $activo->estado_fisico ?: '—' }}<br>{{ $activo->estado_registro ?: '—' }}</td>
                    <td>{{ $activo->fecha_adquisicion?->format('d/m/Y') ?? '—' }}<br>{{ $activo->valor_adquisicion !== null ? number_format((float) $activo->valor_adquisicion, 2) : '—' }}</td>
                    <td class="imagenes">
                        @php $tieneImagen = false; @endphp
                        @foreach(['imagen_data', 'imagen_lateral_data', 'imagen_detalle_data'] as $imagen)
                            @if($activo->{$imagen})
                                @php $tieneImagen = true; @endphp
                                <img src="{{ $activo->{$imagen} }}" alt="Imagen del activo">
                            @endif
                        @endforeach
                        @unless($tieneImagen)<span class="sin-imagen">Sin imagen</span>@endunless
                    </td>
                    <td class="{{ $activo->estado_verificacion === 'Con Observación' ? 'observado' : 'estado' }}">
                        {{ $activo->estado_verificacion }}<br>
                        {{ $activo->observacion_verificacion ?: 'Sin observaciones' }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="12">No hay activos verificados para mostrar.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="pie">Documento generado por SISITPM.</div>
</body>
</html>