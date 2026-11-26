<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario por aula</title>
    <style>
        @page {
            size: 330mm 215mm;
            margin: 8mm;
            mso-page-orientation: landscape;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .datos td,
        .stats td,
        .inventario th,
        .inventario td {
            border: 1px solid #222;
            padding: 3px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .datos .label {
            width: 15%;
            background: #f0f0f0;
            font-weight: bold;
        }

        .stats {
            margin: 6px 0;
            text-align: center;
        }

        .stats td {
            width: 20%;
            font-weight: bold;
        }

        .inventario th {
            background: #e5e5e5;
            text-align: center;
            vertical-align: middle;
            font-size: 8px;
        }

        .inventario thead {
            display: table-header-group;
        }

        .inventario tr {
            page-break-inside: avoid;
        }

        .center {
            text-align: center;
        }

        .firmas {
            margin-top: 24px;
        }

        .firmas td {
            width: 33.33%;
            text-align: center;
            padding: 28px 12px 4px;
            vertical-align: bottom;
        }

        .linea {
            border-top: 1px solid #000;
            padding-top: 4px;
            font-weight: bold;
        }

        .leyenda {
            margin-top: 10px;
            border-top: 1px solid #999;
            padding-top: 4px;
            font-size: 8px;
        }
    </style>
</head>
<body>
    <table>
        <tr>
            <td><strong>FORMULARIO A</strong></td>
            <td class="center"><strong>INVENTARIO POR AULA</strong></td>
            <td class="center"><strong>GESTIÓN {{ date('Y') }}</strong></td>
        </tr>
        <tr>
            <td colspan="3" class="center"><strong>{{ $ambiente->nombre }} — {{ $ambiente->carrera->nombre ?? '—' }}</strong></td>
        </tr>
    </table>

    <table class="datos" style="margin-top: 6px;">
        <tr>
            <td class="label">AMBIENTE:</td>
            <td>{{ $ambiente->nombre }}</td>
            <td class="label">CÓDIGO:</td>
            <td>{{ $ambiente->codigo ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">CARRERA:</td>
            <td>{{ $ambiente->carrera->nombre ?? '—' }}</td>
            <td class="label">UBICACIÓN:</td>
            <td>Bloque {{ $ambiente->bloque ?? '—' }} — Piso {{ $ambiente->piso ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">FECHA REPORTE:</td>
            <td>{{ date('d/m/Y H:i') }}</td>
            <td class="label">RESPONSABLE:</td>
            <td>{{ Auth::user()->nombre_completo ?? '—' }}</td>
        </tr>
    </table>

    <table class="stats">
        <tr>
            <td>TOTAL: {{ $stats['total'] }}</td>
            <td>BUENOS: {{ $stats['buenos'] }}</td>
            <td>REGULARES: {{ $stats['regulares'] }}</td>
            <td>MALOS: {{ $stats['malos'] }}</td>
            <td>FUERA SERV.: {{ $stats['fuera'] }}</td>
        </tr>
    </table>

    <table class="inventario">
        <colgroup>
            <col style="width: 3%">
            <col style="width: 12%">
            <col style="width: 12%">
            <col style="width: 22%">
            <col style="width: 5%">
            <col style="width: 3%">
            <col style="width: 3%">
            <col style="width: 3%">
            <col style="width: 4%">
            <col style="width: 8%">
            <col style="width: 6%">
            <col style="width: 19%">
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2">N°</th>
                <th rowspan="2">CÓDIGO</th>
                <th rowspan="2">DESCRIPCIÓN</th>
                <th rowspan="2">CARACTERÍSTICAS</th>
                <th rowspan="2">CANT.</th>
                <th colspan="4">ESTADO</th>
                <th rowspan="2">FUENTE</th>
                <th rowspan="2">GESTIÓN</th>
                <th rowspan="2">OBS.</th>
            </tr>
            <tr>
                <th>B</th><th>R</th><th>M</th><th>FF</th>
            </tr>
        </thead>
        <tbody>
            @forelse($agrupados as $i => $g)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{!! nl2br(e($g->codigos)) !!}</td>
                    <td>{{ $g->nombre }}</td>
                    <td>
                        {{ $g->descripcion }}
                        @if($g->marca) | Marca: {{ $g->marca }} @endif
                        @if($g->modelo) | Modelo: {{ $g->modelo }} @endif
                    </td>
                    <td class="center">{{ $g->cantidad }}</td>
                    <td class="center">{{ $g->estado_fisico === 'B' ? 'X' : '' }}</td>
                    <td class="center">{{ $g->estado_fisico === 'R' ? 'X' : '' }}</td>
                    <td class="center">{{ $g->estado_fisico === 'M' ? 'X' : '' }}</td>
                    <td class="center">{{ $g->estado_fisico === 'FF' ? 'X' : '' }}</td>
                    <td class="center">{{ $g->fuente_codigo }}</td>
                    <td class="center">{{ $g->gestion }}</td>
                    <td>{{ $g->observaciones ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="12" class="center">Sin activos en este ambiente</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="center"><strong>TOTALES:</strong></td>
                <td class="center"><strong>{{ $stats['total'] }}</strong></td>
                <td colspan="7"></td>
            </tr>
        </tfoot>
    </table>

    <table class="firmas">
        <tr>
            <td><div class="linea">{{ Auth::user()->nombre_completo ?? 'JEFE DE CARRERA' }}<br>JEFE DE CARRERA</div></td>
            <td><div class="linea">RESPONSABLE DE INVENTARIO<br>INVENTARIADOR</div></td>
            <td><div class="linea">DIRECCIÓN ADMINISTRATIVA<br>ADMINISTRADORA</div></td>
        </tr>
    </table>

    <div class="leyenda">
        <strong>ESTADO:</strong> B BUENO &nbsp; R REGULAR &nbsp; M MALO &nbsp; FF FUERA DE FUNCIONAMIENTO
        <br>
        <strong>FUENTES:</strong> GD GOBIERNO DEPARTAMENTAL &nbsp; GM GOBIERNO MUNICIPAL &nbsp; GN GOBIERNO NACIONAL
        &nbsp; PSP PROYECTO SOCIO PRODUCTIVO &nbsp; ADQ ADQUISICIÓN &nbsp; RPC/RPI RECURSOS PROPIOS &nbsp; OTR OTRO
    </div>
</body>
</html>
