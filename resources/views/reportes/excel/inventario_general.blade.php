<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario de activos</title>
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

        .institucional td {
            border: 1px solid #999;
            padding: 3px 5px;
        }

        .inventario th,
        .inventario td {
            border: 1px solid #222;
            padding: 3px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
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

        .right {
            text-align: right;
        }

        .titulo {
            background: #f1f5f9;
            font-weight: bold;
            text-align: center;
            font-size: 12px;
        }

        .firmas {
            margin-top: 28px;
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
    <table class="institucional">
        <tr>
            <td><strong>FORMULARIO A</strong></td>
            <td class="center"><strong>INSTITUTO TECNOLÓGICO "PUERTO DE MEJILLONES"</strong></td>
            <td class="right"><strong>GESTIÓN {{ date('Y') }}</strong></td>
        </tr>
        <tr>
            <td colspan="3" class="titulo">
                @if(isset($ambiente) && $ambiente)
                    INVENTARIO POR AMBIENTE — {{ $ambiente->nombre }}
                @elseif(($tipoBien ?? 'TODOS') === 'NO_ACTIVO')
                    INVENTARIO DE BIENES NO ACTIVOS (MATERIAL DE CONSUMO)
                @elseif(($tipoBien ?? 'TODOS') === 'ACTIVO_FIJO')
                    INVENTARIO DE MAQUINARIA, HERRAMIENTAS Y EQUIPOS DE LABORATORIO
                @else
                    INVENTARIO GENERAL DE BIENES (AGRUPADOS)
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="2"><strong>UBICACIÓN:</strong>
                @if(isset($ambiente) && $ambiente)
                    {{ $ambiente->nombre }}
                @elseif(isset($carrera) && $carrera)
                    {{ $carrera->nombre }}
                @else
                    TODOS LOS AMBIENTES
                @endif
            </td>
            <td><strong>CARRERA:</strong> {{ $carrera->nombre ?? (isset($ambiente) ? ($ambiente->carrera->nombre ?? '—') : '—') }}</td>
        </tr>
        <tr>
            <td><strong>DEPARTAMENTO:</strong> LA PAZ</td>
            <td><strong>PROVINCIA:</strong> MURILLO</td>
            <td><strong>FECHA DEL REPORTE:</strong> {{ date('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <table class="inventario" style="margin-top: 8px;">
        <colgroup>
            <col style="width: 3%">
            <col style="width: 8%">
            <col style="width: 11%">
            <col style="width: 18%">
            <col style="width: 4%">
            <col style="width: 6%">
            <col style="width: 2%">
            <col style="width: 2%">
            <col style="width: 2%">
            <col style="width: 3%">
            <col style="width: 5%">
            <col style="width: 3%">
            <col style="width: 3%">
            <col style="width: 5%">
            <col style="width: 9%">
            <col style="width: 9%">
            <col style="width: 7%">
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2">N°</th>
                <th rowspan="2">CÓDIGO</th>
                <th rowspan="2">DESCRIPCIÓN DEL EQUIPO</th>
                <th rowspan="2">CARACTERÍSTICAS</th>
                <th rowspan="2">CANT.</th>
                <th rowspan="2">COSTO (Bs.)</th>
                <th colspan="4">ESTADO</th>
                <th rowspan="2">FUENTE</th>
                <th colspan="3">FECHA DE ENTREGA / según acta</th>
                <th rowspan="2">USO EN ÁREA DE FORMACIÓN (Carrera/s)</th>
                <th rowspan="2">OBSERVACIONES</th>
                <th rowspan="2">IMAGEN</th>
            </tr>
            <tr>
                <th>B</th><th>R</th><th>M</th><th>FF</th>
                <th>DÍA</th><th>MES</th><th>AÑO</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reportes as $r)
                <tr>
                    <td class="center">{{ $r->nro }}</td>
                    <td>{{ $r->codigos }}</td>
                    <td>{{ $r->descripcion }}</td>
                    <td>{{ $r->caracteristicas }}</td>
                    <td class="center">{{ $r->cantidad }}</td>
                    <td class="right">{{ number_format((float) $r->costo_total, 2, '.', ',') }}</td>
                    <td class="center">{{ $r->estado_fisico === 'B' ? 'X' : '' }}</td>
                    <td class="center">{{ $r->estado_fisico === 'R' ? 'X' : '' }}</td>
                    <td class="center">{{ $r->estado_fisico === 'M' ? 'X' : '' }}</td>
                    <td class="center">{{ $r->estado_fisico === 'FF' ? 'X' : '' }}</td>
                    <td class="center">{{ $r->fuente_codigo ?? $r->fuente ?? '—' }}</td>
                    <td class="center">{{ $r->dia }}</td>
                    <td class="center">{{ $r->mes }}</td>
                    <td class="center">{{ $r->anio }}</td>
                    <td>{{ $r->carrera ?? '—' }}</td>
                    <td>{{ $r->observaciones ?? '—' }}</td>
                    <td class="center">{{ $r->imagen ? 'Imagen registrada' : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="17" class="center">Sin bienes registrados</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="right"><strong>TOTALES:</strong></td>
                <td class="center"><strong>{{ $reportes->sum('cantidad') }}</strong></td>
                <td class="right"><strong>{{ number_format((float) $reportes->sum('costo_total'), 2, '.', ',') }}</strong></td>
                <td colspan="11"></td>
            </tr>
        </tfoot>
    </table>

    <table class="firmas">
        <tr>
            <td><div class="linea">RESPONSABLE DE INVENTARIO</div></td>
            <td><div class="linea">JEFE DE CARRERA</div></td>
            <td><div class="linea">DIRECCIÓN ADMINISTRATIVA</div></td>
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
