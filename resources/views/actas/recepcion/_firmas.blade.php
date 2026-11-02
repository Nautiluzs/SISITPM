@php
    use App\Models\User;

    // Construir lista de firmantes: Jefe de Carrera + Responsables + Administrador
    $firmantes = [];

    if (isset($jefeCarrera) && $jefeCarrera) {
        $firmantes[] = [
            'nombre' => $jefeCarrera->nombre_completo ?? 'JEFE DE CARRERA',
            'ci'     => $jefeCarrera->ci ?? '—',
            'cargo'  => 'JEFE DE CARRERA',
            'unidad' => optional($activo->ambiente->carrera)->nombre ?? '—',
            'clase'  => 'jefe-carrera',
        ];
    }

    // Responsables (deduplicados)
    if (isset($responsables) && $responsables->isNotEmpty()) {
        foreach ($responsables as $resp) {
            $firmantes[] = [
                'nombre' => $resp->nombre_completo ?? 'RESPONSABLE',
                'ci'     => $resp->ci ?? '—',
                'cargo'  => $resp->cargo ?? $resp->rol ?? 'CUSTODIO / RESPONSABLE',
                'unidad' => $activo->ambiente->nombre ?? 'ITPM',
                'clase'  => 'responsable',
            ];
        }
    } elseif ($activo->custodio) {
        $firmantes[] = [
            'nombre' => $activo->custodio->nombre_completo ?? 'CUSTODIO',
            'ci'     => $activo->custodio->ci ?? '—',
            'cargo'  => $activo->custodio->cargo ?? $activo->custodio->rol ?? 'CUSTODIO / RESPONSABLE',
            'unidad' => $activo->ambiente->nombre ?? 'ITPM',
            'clase'  => 'responsable',
        ];
    }

    // Administrador
    if (isset($administrador) && $administrador) {
        $firmantes[] = [
            'nombre' => $administrador->nombre_completo ?? 'ADMINISTRADOR',
            'ci'     => $administrador->ci ?? '—',
            'cargo'  => $administrador->cargo ?? $administrador->rol ?? 'ADMINISTRADOR',
            'unidad' => optional($activo->ambiente->carrera)->nombre ?? '—',
            'clase'  => 'administrador',
        ];
    }

    $totalFirmantes = count($firmantes);
    $anchoColumna   = $totalFirmantes > 0 ? round(100 / $totalFirmantes) : 100;
    $fuenteFirma    = $totalFirmantes <= 3 ? '10px' : '8px';
    $fuenteDetalle  = $totalFirmantes <= 3 ? '9px' : '7.5px';
@endphp

{{-- FIRMAS EN UNA SOLA FILA --}}
<table class="tabla-firmas">
    <tr>
        @foreach($firmantes as $index => $f)
            <td width="{{ $anchoColumna }}%" style="font-size: {{ $fuenteFirma }};">
                <div class="espacio-firma"></div>
                <div class="linea-firma" style="font-size: {{ $fuenteFirma }};">{{ $f['nombre'] }}</div>
                <div class="cargo-firma" style="font-weight: bold;">{{ $f['cargo'] }}</div>
            </td>
        @endforeach
    </tr>
    <tr>
        @foreach($firmantes as $f)
            <td width="{{ $anchoColumna }}%" style="font-size: {{ $fuenteDetalle }};">
                <div style="margin-bottom: 4px;"><strong>NOMBRE:</strong> {{ $f['nombre'] }}</div>
                <div style="margin-bottom: 4px;"><strong>C.I.:</strong> {{ $f['ci'] }}</div>
                <div style="margin-bottom: 4px;"><strong>CARGO:</strong> {{ $f['cargo'] }}</div>
                <div><strong>UNIDAD:</strong> {{ $f['unidad'] }}</div>
            </td>
        @endforeach
    </tr>
</table>
