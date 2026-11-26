<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario por Categoría</title>
    @include('reportes.pdf._styles')
</head>
<body>

    <div class="header">
        <table style="width:100%;">
            <tr>
                <td style="text-align:left; font-size:9px; font-weight:bold;">FORMULARIO A</td>
                <td style="text-align:center; font-size:9px; font-weight:bold;">
                    EQUIPOS OTORGADOS EL MINISTERIO DE EDUCACIÓN
                </td>
                <td style="text-align:right; font-size:9px; font-weight:bold;">GESTIÓN {{ date('Y') }}</td>
            </tr>
        </table>
        <div class="titulo" style="margin-top:3px;">
            INVENTARIO POR CATEGORÍA
        </div>
    </div>

    <table class="datos">
        <tr>
            <td class="label">INSTITUTO:</td>
            <td colspan="3">TECNOLÓGICO "PUERTO DE MEJILLONES"</td>
            <td class="label">DEPARTAMENTO:</td>
            <td>LA PAZ</td>
        </tr>
        <tr>
            <td class="label">CATEGORÍA:</td>
            <td colspan="3"><strong>{{ $categoria->nombre }}</strong></td>
            <td class="label">PROVINCIA:</td>
            <td>MURILLO</td>
        </tr>
        <tr>
            <td class="label">ID CATEGORÍA:</td>
            <td>{{ $categoria->id_categoria }}</td>
            <td class="label">AMBIENTE:</td>
            <td>{{ $categoria->ambiente->nombre ?? '—' }}</td>
            <td class="label">CIUDAD:</td>
            <td>EL ALTO</td>
        </tr>
    </table>

    {{-- RESUMEN --}}
    <table class="stats" style="width:100%; border-collapse:collapse; font-size:8px; margin-bottom:6px;">
        <tr>
            <td style="border:1px solid #000; padding:3px; text-align:center; width:25%;">
                <span style="font-size:11px; font-weight:bold; display:block;">{{ $activos->count() }}</span>
                TOTAL
            </td>
            <td style="border:1px solid #000; padding:3px; text-align:center; width:25%;">
                <span style="font-size:11px; font-weight:bold; display:block;">{{ $activos->where('estado_fisico','B')->count() }}</span>
                BUENOS
            </td>
            <td style="border:1px solid #000; padding:3px; text-align:center; width:25%;">
                <span style="font-size:11px; font-weight:bold; display:block;">{{ $activos->where('estado_fisico','R')->count() }}</span>
                REGULARES
            </td>
            <td style="border:1px solid #000; padding:3px; text-align:center; width:25%;">
                <span style="font-size:11px; font-weight:bold; display:block;">{{ $activos->whereIn('estado_fisico',['M','FF'])->count() }}</span>
                MALOS / FF
            </td>
        </tr>
    </table>

    {{-- TABLA --}}
    <table class="inventario">
        <thead>
            <tr>
                <th class="col-num">N°</th>
                <th class="col-cod">CÓDIGO</th>
                <th class="col-desc">DESCRIPCIÓN</th>
                <th class="col-carac">CARACTERÍSTICAS</th>
                <th class="col-cant">CANT.</th>
                <th class="col-estado">ESTADO</th>
                <th class="col-fuente">FUENTE</th>
                <th class="col-fecha">GESTIÓN</th>
                <th class="col-obs">OBS.</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($agrupados as $i => $g)
                <tr>
                    <td class="col-num">{{ $i + 1 }}</td>
                    <td class="col-cod">{!! nl2br(e($g->codigos)) !!}</td>
                    <td class="col-desc">{{ $g->nombre }}</td>
                    <td class="col-carac">{{ $g->descripcion }}
                        @if($g->marca) <br><strong>Marca:</strong> {{ $g->marca }} @endif
                        @if($g->modelo) <br><strong>Modelo:</strong> {{ $g->modelo }} @endif
                    </td>
                    <td class="col-cant">{{ $g->cantidad }}</td>
                    <td class="col-estado">{{ $g->estado_texto }}</td>
                    <td class="col-fuente">{{ $g->fuente_codigo }}</td>
                    <td class="col-fecha">{{ $g->gestion }}</td>
                    <td class="col-obs">{{ $g->observaciones }}</td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center;">Sin activos en esta categoría</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="leyenda">
        <table>
            <tr>
                <td><strong>B</strong> = BUENO</td>
                <td><strong>R</strong> = REGULAR</td>
                <td><strong>M</strong> = MALO</td>
                <td><strong>FF</strong> = FUERA DE FUNCIONAMIENTO</td>
            </tr>
        </table>
    </div>

    <table class="firmas">
        <tr>
            <td><div class="linea">Responsable del Ambiente<br>C.I.: —</div></td>
            <td><div class="linea">Docente Laboratorio 3<br>C.I.: —</div></td>
            <td><div class="linea">Dirección Administrativa<br>C.I.: —</div></td>
        </tr>
    </table>

</body>
</html>
