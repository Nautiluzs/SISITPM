<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario General Agrupado</title>
    @include('reportes.pdf._styles')
    <style>
        @page {
            size: 330mm 215mm;
            margin: 8mm;
        }
        body {
            font-family: sans-serif;
            font-size: 8.5px;
            color: #000;
        }
        .header .titulo {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 4px;
        }
        table.inventario th {
            background-color: #2563eb !important;
            color: #ffffff !important;
            text-align: center;
            vertical-align: middle;
            font-size: 7.5px;
            padding: 3px;
        }
        table.inventario td {
            padding: 3px;
            vertical-align: middle;
            font-size: 8px;
            border: 1px solid #cbd5e1;
        }
        table.datos td {
            font-size: 8.5px;
            padding: 2px 4px;
        }
        .leyendas-texto-libre {
            margin-top: 12px;
            font-size: 6.5px;
            color: #1e293b;
            line-height: 1.4;
        }
    </style>
</head>
<body>

    {{-- ENCABEZADO --}}
    <div class="header">
        <table style="width:100%;">
            <tr>
                <td style="text-align:left; font-size:8.5px; font-weight:bold;">FORMULARIO A</td>
                <td style="text-align:center; font-size:8.5px; font-weight:bold;">
                    EQUIPOS OTORGADOS EL MINISTERIO DE EDUCACIÓN
                </td>
                <td style="text-align:right; font-size:8.5px; font-weight:bold;">GESTIÓN {{ date('Y') }}</td>
            </tr>
        </table>
        <div class="titulo" style="margin-top:2px;">
            @if(($tipoBien ?? 'TODOS') === 'NO_ACTIVO')
                INVENTARIO DE BIENES NO ACTIVOS (MATERIAL DE CONSUMO)
            @elseif(($tipoBien ?? 'TODOS') === 'ACTIVO_FIJO')
                INVENTARIO DE MAQUINARIA, HERRAMIENTAS Y EQUIPOS DE LABORATORIO
            @else
                INVENTARIO GENERAL DE BIENES (AGRUPADOS)
            @endif
        </div>
    </div>

    {{-- DATOS INSTITUCIONALES --}}
    @php
        $rector = \App\Models\User::where('rol', 'RECTOR')->where('estado', 'ACTIVO')->first()
               ?? \App\Models\User::where('rol', 'DIRECTOR')->where('estado', 'ACTIVO')->first();
        $admin = \App\Models\User::where('rol', 'ADMINISTRADOR')->where('estado', 'ACTIVO')->first();
    @endphp
    <table class="datos" style="width:100%; margin-bottom: 6px;">
        <tr>
            <td class="label" style="font-weight:bold; width:15%;">INSTITUTO:</td>
            <td colspan="3" style="width:45%;">TECNOLÓGICO "PUERTO DE MEJILLONES"</td>
            <td class="label" style="font-weight:bold; width:15%;">DEPARTAMENTO:</td>
            <td style="width:25%;">LA PAZ</td>
        </tr>
        <tr>
            <td class="label" style="font-weight:bold;">DIRECTOR/RECTOR:</td>
            <td colspan="3">{{ $rector ? $rector->nombre_completo : 'LIC. JIMMY OVIDIO SIRPA CHOQUE' }}</td>
            <td class="label" style="font-weight:bold;">PROVINCIA:</td>
            <td>MURILLO</td>
        </tr>
        <tr>
            <td class="label" style="font-weight:bold;">DIR. ADMINISTRATIVA:</td>
            <td colspan="3">{{ $admin ? $admin->nombre_completo : 'LIC. ANA LÍA ZAPANA CORTEZ' }}</td>
            <td class="label" style="font-weight:bold;">MUNICIPIO:</td>
            <td>LA PAZ</td>
        </tr>
        <tr>
            <td class="label" style="font-weight:bold;">UBICACIÓN:</td>
            <td colspan="3">
                @if($ambiente)
                    {{ $ambiente->nombre }} — {{ $ambiente->carrera->nombre ?? '—' }}
                @elseif($carrera)
                    {{ $carrera->nombre }}
                @else
                    TODOS LOS AMBIENTES
                @endif
            </td>
            <td class="label" style="font-weight:bold;">CIUDAD:</td>
            <td>EL ALTO</td>
        </tr>
    </table>

    {{-- TABLA PRINCIPAL --}}
    <table class="inventario" style="width:100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th rowspan="2" style="width:22px;">Nº</th>
                <th rowspan="2" style="width:60px;">CÓDIGO</th>
                <th rowspan="2" style="width:100px;">DESCRIPCIÓN DEL EQUIPO</th>
                <th rowspan="2" style="width:130px;">CARACTERÍSTICAS</th>
                <th rowspan="2" style="width:30px;">CANT.</th>
                <th rowspan="2" style="width:45px;">COSTO (Bs.)</th>
                <th colspan="4">ESTADO</th>
                <th rowspan="2" style="width:40px;">FUENTE</th>
                <th colspan="3">FECHA DE ENTREGA / según acta</th>
                <th rowspan="2" style="width:80px;">USO EN ÁREA DE FORMACIÓN (Carrera/s)</th>
                <th rowspan="2" style="width:90px;">OBSERVACIONES</th>
                <th rowspan="2" style="width:40px;">IMAGEN</th>
            </tr>
            <tr>
                <th style="width:16px;">B</th>
                <th style="width:16px;">R</th>
                <th style="width:16px;">M</th>
                <th style="width:20px;">FF</th>
                <th style="width:22px;">DÍA</th>
                <th style="width:22px;">MES</th>
                <th style="width:30px;">AÑO</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reportes as $r)
                <tr>
                    <td style="text-align:center;">{{ $r->nro }}</td>
                    <td style="text-align:center;">{{ $r->codigos }}</td>
                    <td>{{ $r->descripcion }}</td>
                    <td>{{ $r->caracteristicas }}</td>
                    <td style="text-align:center;">{{ $r->cantidad }}</td>
                    <td style="text-align:right;">{{ number_format($r->costo_total, 2) }}</td>
                    <td style="text-align:center; font-weight:bold;">{{ $r->estado_fisico == 'B' ? 'X' : '' }}</td>
                    <td style="text-align:center; font-weight:bold;">{{ $r->estado_fisico == 'R' ? 'X' : '' }}</td>
                    <td style="text-align:center; font-weight:bold;">{{ $r->estado_fisico == 'M' ? 'X' : '' }}</td>
                    <td style="text-align:center; font-weight:bold;">{{ $r->estado_fisico == 'FF' ? 'X' : '' }}</td>
                    <td style="text-align:center;">{{ $r->fuente_codigo ?? $r->fuente ?? '—' }}</td>
                    <td style="text-align:center;">{{ $r->dia }}</td>
                    <td style="text-align:center;">{{ $r->mes }}</td>
                    <td style="text-align:center;">{{ $r->anio }}</td>
                    <td>{{ $r->carrera }}</td>
                    <td>{{ $r->observaciones ?? '—' }}</td>
                    <td style="text-align:center;">
                        @if($r->imagen)
                            <img src="{{ public_path('storage/'.$r->imagen) }}" alt="Img" style="width:25px;height:18px;object-fit:cover;border-radius:2px;border:1px solid #ccc;">
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="17" style="text-align:center;">Sin bienes registrados</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right; font-weight:bold;">TOTALES:</td>
                <td style="text-align:center; font-weight:bold;">{{ $reportes->sum('cantidad') }}</td>
                <td style="text-align:right; font-weight:bold;">{{ number_format($reportes->sum('costo_total'), 2) }}</td>
                <td colspan="11"></td>
            </tr>
        </tfoot>
    </table>

    {{-- ========================================================= --}}
    {{-- FIRMAS DINÁMICAS --}}
    {{-- ========================================================= --}}
    @php
        $todosIds = $reportes->pluck('ids_array')->collapse()->unique()->values()->toArray();
        $firmantes = [];
        $idsAgregados = [];
        $ambientePrincipal = null;
        if (isset($ambiente) && $ambiente) {
            $ambientePrincipal = $ambiente;
        }

        if (!empty($todosIds)) {
            $ambientePrincipal = $ambientePrincipal ?? \App\Models\Ambiente::whereHas('activos', function($q) use ($todosIds) {
                $q->whereIn('activos_fijos.id_activo', $todosIds);
            })->with('carrera', 'custodios')->first();
        }

        if ($ambientePrincipal) {
            foreach ($ambientePrincipal->custodios as $c) {
                if (!in_array($c->id_usuario, $idsAgregados)) {
                    $firmantes[] = ['nombre' => $c->nombre_completo, 'ci' => $c->ci, 'rol' => 'CUSTODIO'];
                    $idsAgregados[] = $c->id_usuario;
                }
            }
        }

        if (!empty($todosIds)) {
            $custodiosPorActivo = \App\Models\User::whereHas('activosCustodiados', function($q) use ($todosIds) {
                $q->whereIn('activos_fijos.id_activo', $todosIds);
            })->where('estado', 'ACTIVO')->distinct()->get(['id_usuario', 'nombre_completo', 'ci']);
            foreach ($custodiosPorActivo as $c) {
                if (!in_array($c->id_usuario, $idsAgregados)) {
                    $firmantes[] = ['nombre' => $c->nombre_completo, 'ci' => $c->ci, 'rol' => 'CUSTODIO'];
                    $idsAgregados[] = $c->id_usuario;
                }
            }
        }

        if ($ambientePrincipal && $ambientePrincipal->carrera) {
            $jefeCarrera = $ambientePrincipal->carrera->jefeCarrera()->first();
            if (!$jefeCarrera) {
                $jefeCarrera = \App\Models\User::where('id_carrera', $ambientePrincipal->carrera->id_carrera)
                    ->where('rol', 'JEFE_CARRERA')->where('estado', 'ACTIVO')->first();
            }
            if ($jefeCarrera && !in_array($jefeCarrera->id_usuario, $idsAgregados)) {
                $firmantes[] = ['nombre' => $jefeCarrera->nombre_completo, 'ci' => $jefeCarrera->ci, 'rol' => 'JEFE DE CARRERA'];
                $idsAgregados[] = $jefeCarrera->id_usuario;
            }
        }

        $inventariador = \App\Models\User::where('rol', 'INVENTARIADOR')->where('estado', 'ACTIVO')->first();
        if ($inventariador && !in_array($inventariador->id_usuario, $idsAgregados)) {
            $firmantes[] = ['nombre' => $inventariador->nombre_completo, 'ci' => $inventariador->ci, 'rol' => 'INVENTARIADOR'];
            $idsAgregados[] = $inventariador->id_usuario;
        }

        $rector = \App\Models\User::where('rol', 'RECTOR')->where('estado', 'ACTIVO')->first()
               ?? \App\Models\User::where('rol', 'DIRECTOR')->where('estado', 'ACTIVO')->first();
        if ($rector && !in_array($rector->id_usuario, $idsAgregados)) {
            $firmantes[] = ['nombre' => $rector->nombre_completo, 'ci' => $rector->ci, 'rol' => 'RECTOR'];
            $idsAgregados[] = $rector->id_usuario;
        }

        if (empty($firmantes)) {
            $firmantes = [
                ['nombre' => 'Lic. Patricia Regina Flores Chuquimia', 'ci' => '3451328', 'rol' => 'CUSTODIO'],
                ['nombre' => 'Lic. Johnny Chavez Quispe', 'ci' => '6032467', 'rol' => 'CUSTODIO'],
                ['nombre' => 'Lic. Eliza Nina Coronel', 'ci' => '4286280', 'rol' => 'CUSTODIO'],
                ['nombre' => 'ANA LÍA ZAPANA CORTEZ', 'ci' => '4756925', 'rol' => 'ADMINISTRADOR'],
                ['nombre' => 'Lic. Juan Carlos Osco', 'ci' => '4243543', 'rol' => 'INVENTARIADOR'],
                ['nombre' => 'Lic. Jhimmy Ovidio Sirpa', 'ci' => '4960313', 'rol' => 'RECTOR'],
            ];
        } else {
            $firmantes[] = ['nombre' => 'ANA LÍA ZAPANA CORTEZ', 'ci' => '4756925', 'rol' => 'ADMINISTRADOR'];
        }

        $cols = 3;
        $chunks = array_chunk($firmantes, $cols);
    @endphp

    {{-- 🔽 ESPACIO ANTES DE LAS FIRMAS 🔽 --}}
    <div style="height: 25px; line-height: 25px; font-size: 1px; color: #000;">&nbsp;</div>

    {{-- 🔽 TABLA DE FIRMAS 🔽 --}}
    <table style="width:100%; border-collapse:collapse;">
        @foreach ($chunks as $fila)
            <tr>
                @foreach ($fila as $f)
                    <td style="width:33.33%; vertical-align:top; text-align:center; padding: 25px 10px 20px 10px; color:#000;">

                        {{-- ESPACIO PARA FIRMAR (igual en todas las filas) --}}
                        <div style="height: 50px; line-height: 1px; font-size: 1px; color:#000;">&nbsp;</div>

                        {{-- LÍNEA DE FIRMA --}}
                        <div style="border-bottom: 1.5px solid #000; width: 85%; margin: 0 auto 4px auto;"></div>

                        <div style="font-weight:bold; font-size:9px; color:#000; margin-bottom:1px;">
                            {{ $f['nombre'] }}
                        </div>
                        <div style="font-size:8px; color:#333; margin-bottom:1px;">
                            C.I.: {{ $f['ci'] }}
                        </div>
                        <div style="font-size:7.5px; color:#555; text-transform:uppercase; letter-spacing:0.3px;">
                            {{ $f['rol'] }}
                        </div>
                    </td>
                @endforeach
                @for ($i = count($fila); $i < $cols; $i++)
                    <td style="width:33.33%;"></td>
                @endfor
            </tr>
            {{-- Separación entre filas de firmas --}}
            <tr><td colspan="{{ $cols }}" style="height: 10px; font-size:1px; color:#000;">&nbsp;</td></tr>
        @endforeach
    </table>

    {{-- 🔽 ESPACIO ANTES DE LAS LEYENDAS 🔽 --}}
    <div style="height: 15px; line-height: 15px; font-size: 1px; color:#000;">&nbsp;</div>

    {{-- LEYENDAS EN TEXTO PURO --}}
    <div class="leyendas-texto-libre">
        <div style="margin-bottom: 3px;">
            <strong>ESTADO:</strong>
            <strong>B</strong> BUENO &nbsp;&bull;&nbsp;
            <strong>R</strong> REGULAR &nbsp;&bull;&nbsp;
            <strong>M</strong> MALO &nbsp;&bull;&nbsp;
            <strong>FF</strong> FUERA DE FUNCIONAMIENTO
        </div>
        <div>
            <strong>FUENTES:</strong>
            <strong>GD</strong> GOBIERNO DEPARTAMENTAL &nbsp;&bull;&nbsp;
            <strong>GM</strong> GOBIERNO MUNICIPAL &nbsp;&bull;&nbsp;
            <strong>GN</strong> GOBIERNO NACIONAL &nbsp;&bull;&nbsp;
            <strong>PSP</strong> PROYECTO SOCIO PRODUCTIVO &nbsp;&bull;&nbsp;
            <strong>ADQ</strong> ADQUISICIÓN &nbsp;&bull;&nbsp;
            <strong>RPC</strong> RECURSOS PROPIOS POR CARRERA &nbsp;&bull;&nbsp;
            <strong>RPI</strong> RECURSOS PROPIOS INSTITUCIONALES &nbsp;&bull;&nbsp;
            <strong>OTR</strong> OTRO
        </div>
    </div>

</body>
</html>