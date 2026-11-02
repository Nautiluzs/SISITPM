<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        h1 { color: #123b78; font-size: 18px; }
        .info { margin-bottom: 12px; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #123b78; color: #fff; padding: 6px; border: 1px solid #8da3c0; }
        td { padding: 5px; border: 1px solid #b8c5d6; vertical-align: top; }
        tr:nth-child(even) { background: #f1f5f9; }
        td.imagenes { text-align: center; vertical-align: middle; min-width: 180px; }
        img { width: 58px; height: 58px; object-fit: cover; margin: 3px auto; display: inline-block; vertical-align: middle; }
        .estado { color: #15803d; font-weight: bold; }
        .observado { color: #b45309; font-weight: bold; }
        .sin-imagen { color: #64748b; }
    </style>
</head>
<body>
    <h1>Reporte de verificación física de activos</h1>
    <div class="info">
        <strong>Ambiente:</strong> {{ $ambiente->nombre }} &nbsp;&nbsp;
        <strong>Carrera:</strong> {{ $ambiente->carrera->nombre ?? 'Sin carrera' }} &nbsp;&nbsp;
        <strong>Fecha:</strong> {{ now()->format('d/m/Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Tipo de bien</th>
                <th>RV</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>N.º de serie</th>
                <th>Categoría</th>
                <th>Fuente</th>
                <th>Ambiente</th>
                <th>Custodio</th>
                <th>Estado físico</th>
                <th>Estado registro</th>
                <th>Fecha adquisición</th>
                <th>Valor adquisición</th>
                <th>Imágenes</th>
                <th>Verificación</th>
                <th>Observación</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activos as $activo)
                <tr>
                    <td>{{ $activo->codigo_activo }}</td>
                    <td>{{ $activo->tipo_bien }}</td>
                    <td>{{ $activo->rv ?? 'NO' }}</td>
                    <td>{{ $activo->nombre }}</td>
                    <td>{{ $activo->descripcion ?: '—' }}</td>
                    <td>{{ $activo->marca ?: '—' }}</td>
                    <td>{{ $activo->modelo ?: '—' }}</td>
                    <td>{{ $activo->numero_serie ?: '—' }}</td>
                    <td>{{ $activo->categoria->nombre ?? '—' }}</td>
                    <td>{{ $activo->fuente->nombre ?? '—' }}</td>
                    <td>{{ $activo->ambiente->nombre ?? '—' }}</td>
                    <td>{{ $activo->custodio->nombre_completo ?? 'Sin custodio' }}</td>
                    <td>{{ $activo->estado_fisico ?: '—' }}</td>
                    <td>{{ $activo->estado_registro ?: '—' }}</td>
                    <td>{{ $activo->fecha_adquisicion?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $activo->valor_adquisicion !== null ? number_format((float) $activo->valor_adquisicion, 2) : '—' }}</td>
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
                        {{ $activo->estado_verificacion }}
                    </td>
                    <td>{{ $activo->observacion_verificacion ?: 'Sin observaciones' }}</td>
                </tr>
            @empty
                <tr><td colspan="19">No hay activos verificados para mostrar.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>