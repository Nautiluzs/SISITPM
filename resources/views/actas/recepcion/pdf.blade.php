<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de Entrega y Recepción — {{ $activo->codigo_activo }}</title>
    @php
        use Illuminate\Support\Facades\DB;
        use App\Models\User;
        if (!isset($responsables)) {
            $responsables = collect();
            $ambienteId = $activo->id_ambiente ?? null;
            if ($activo->item && !$ambienteId) {
                $ambienteId = $activo->item->id_ambiente ?? null;
            }

            if ($ambienteId) {
                $rows = DB::table('ambiente_user')
                    ->join('usuarios', 'usuarios.id_usuario', '=', 'ambiente_user.id_usuario')
                    ->where('ambiente_user.id_ambiente', $ambienteId)
                    ->where('usuarios.estado', 'ACTIVO')
                    ->orderBy('usuarios.nombre_completo')
                    ->select('usuarios.id_usuario', 'usuarios.nombre_completo', 'usuarios.ci', 'usuarios.cargo', 'usuarios.rol', 'usuarios.estado')
                    ->distinct()
                    ->get();
                foreach ($rows as $row) {
                    $u = new User((array)$row);
                    $u->exists = true;
                    $responsables->push($u);
                }
            }

            if ($responsables->isEmpty() && $activo->id_item) {
                $rows = DB::table('item_usuario')
                    ->join('usuarios', 'usuarios.id_usuario', '=', 'item_usuario.id_usuario')
                    ->where('item_usuario.id_item', $activo->id_item)
                    ->whereNull('item_usuario.fecha_fin')
                    ->where('usuarios.estado', 'ACTIVO')
                    ->select('usuarios.id_usuario', 'usuarios.nombre_completo', 'usuarios.ci', 'usuarios.cargo', 'usuarios.rol', 'usuarios.estado', 'item_usuario.tipo')
                    ->orderByRaw("FIELD(item_usuario.tipo, 'TITULAR', 'RESPONSABLE', 'SECUNDARIO')")
                    ->orderBy('usuarios.nombre_completo')
                    ->get();
                foreach ($rows as $row) {
                    $u = new User((array)$row);
                    $u->exists = true;
                    $responsables->push($u);
                }
            }

            if ($responsables->isEmpty() && $activo->custodio) {
                $responsables = collect([$activo->custodio]);
            }
        }
    @endphp
    <style>
        /* ============ HOJA CARTA HORIZONTAL CON MÁRGENES AMPLIOS DE 2CM ============ */
        @page {
            size: letter landscape;
            margin: 12mm 12mm;
        }

        * { box-sizing: border-box; margin: 0.5%; padding: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.35;
            background: #fff;
        }

        .page-container {
            width: 100%;
            margin: 0 auto;
        }

        /* ============ ENCABEZADO INSTITUCIONAL ============ */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .logo-box {
            width: 60px;
            height: 45px;
            text-align: center;
            font-size: 24px;
        }
        .titulo-instituto {
            text-align: center;
        }
        .titulo-instituto h2 {
            font-size: 13.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 2px;
        }
        .titulo-instituto h3 {
            font-size: 11.5px;
            font-weight: normal;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .titulo-instituto .acta-sub {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1.5px solid #000;
            display: inline-block;
            padding-bottom: 1px;
            margin-bottom: 2px;
        }
        .titulo-instituto .fecha-acta {
            font-size: 10.5px;
            font-weight: bold;
        }

        .cajas-derecha {
            width: 170px;
            text-align: right;
            vertical-align: top;
        }
        .caja-form, .caja-hoja {
            border: 1.5px solid #000;
            padding: 4px 8px;
            font-size: 9.5px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 3px;
            background: #fff;
        }

        /* ============ TEXTO INFORMATIVO ============ */
        .info-cumplimiento {
            border: 1px solid #000;
            padding: 5px 8px;
            font-size: 10px;
            margin-bottom: 8px;
            text-align: justify;
            background: #fff;
        }

        /* ============ TABLA PRINCIPAL DE ÍTEMS ============ */
        table.tabla-principal {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 11px;
        }
        table.tabla-principal th, table.tabla-principal td {
            border: 1px solid #000;
            padding: 5px 7px;
            vertical-align: top;
        }
        table.tabla-principal th {
            background: #f1f1f1;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .col-item { width: 45px; text-align: center; }
        .col-codigo { width: 110px; text-align: center; }
        .col-fuente { width: 70px; text-align: center; }
        .col-est { width: 45px; text-align: center; }
        .col-cant { width: 50px; text-align: center; }
        .col-transferencia { width: 95px; text-align: center; }

        /* ============ CONTENEDOR DE LAS 3 IMÁGENES ============ */
        .imagenes-row {
            margin-top: 6px;
            text-align: center;
            width: 100%;
        }
        .img-table {
            width: 100%;
            border-collapse: collapse;
        }
        .img-table td {
            border: 1px dashed #666;
            padding: 3px;
            text-align: center;
            width: 33.33%;
            background: #fafafa;
        }
        .img-box {
            width: 100%;
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .img-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .img-label {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 2px;
            color: #333;
        }

        /* ============ ÍTEMS ASIGNADOS ============ */
        .tabla-asignados {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 11px;
        }
        .tabla-asignados td {
            border: 1px solid #000;
            padding: 4px 8px;
        }

        /* ============ BLOQUE DE FIRMAS ============ */
        .tabla-firmas {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .tabla-firmas td {
            border: 1px solid #000;
            vertical-align: top;
            padding: 5px;
            width: 50%;
        }
        .espacio-firma {
            height: 40px;
        }
        .linea-firma {
            border-top: 1px solid #000;
            text-align: center;
            padding-top: 2px;
            font-size: 10px;
            font-weight: bold;
        }
        .cargo-firma {
            text-align: center;
            font-size: 9px;
            color: #333;
        }

        /* ============ UNIDADES Y OBSERVACIONES ============ */
        .tabla-unidades {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 10px;
        }
        .tabla-unidades td {
            border: 1px solid #000;
            padding: 4px 7px;
            font-weight: bold;
        }

        .tabla-obs {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
            font-size: 9.5px;
        }
        .tabla-obs td {
            border: 1px solid #000;
            padding: 4px 7px;
            vertical-align: top;
        }

        /* ============ TEXTO LEGAL INFERIOR (MÁS GRANDE Y CLARO) ============ */
        .texto-legal {
            font-size: 11px;
            margin-top: 6px;
            text-align: justify;
            line-height: 1.35;
        }
    </style>
</head>
<body>

<div class="page-container">

    {{-- ENCABEZADO --}}
    <table class="header-table">
        <tr>
            <td class="logo-box">
                @if(file_exists(public_path('images/logo.png')))
                    <img src="{{ public_path('images/logo.png') }}" alt="Logo" style="max-height: 80px; max-width: 50px;">
                @else
                    🏛️
                @endif
            </td>
            <td class="titulo-instituto">
                <h2>INSTITUTO TECNOLÓGICO "PUERTO DE MEJILLONES"</h2>
                <h3>DIRECCIÓN ADMINISTRATIVA</h3>
                <div class="acta-sub">ACTA DE ENTREGA Y RECEPCIÓN</div>
                <div class="fecha-acta">{{ now()->translatedFormat('d \d\e F \d\e Y') }}</div>
            </td>
            <td class="cajas-derecha">
                <div class="caja-form">FORMULARIO N° 1</div>
                <div class="caja-hoja">HOJA N° 1 de 1</div>
            </td>
        </tr>
    </table>

    {{-- TEXTO DE CUMPLIMIENTO --}}
    <div class="info-cumplimiento">
        En cumplimiento al D.S. 0181 Normas Básicas del Sistema de Bienes y Servicios en su Art. 146, 147, 157 (Asignación de Activos Fijos Muebles), la sección de Activos Fijos del Instituto Tecnológico "Puerto de Mejillones", procedió a la verificación y asignación de los siguientes activos:
    </div>

    {{-- TABLA PRINCIPAL DE ACTIVOS --}}
    <table class="tabla-principal">
        <thead>
            <tr>
                <th class="col-item">ITEM</th>
                <th class="col-codigo">CODIGO</th>
                <th class="col-fuente">FUENTE</th>
                <th class="col-est">EST.</th>
                <th class="col-cant">CANT.</th>
                <th>DESCRIPCIÓN</th>
                <th class="col-transferencia">TRANSFERENCIA</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="col-item" style="text-align: center; font-weight: bold;">1</td>
                <td class="col-codigo" style="font-weight: bold; text-align: center;">{{ $activo->codigo_activo }}</td>
                <td class="col-fuente" style="text-align: center;">{{ $activo->fuente->codigo ?? 'GTEL' }}</td>
                <td class="col-est" style="text-align: center; font-weight: bold;">{{ $activo->estado_fisico ?? 'B' }}</td>
                <td class="col-cant" style="text-align: center;">1</td>
                <td>
                    <strong style="text-transform: uppercase;">{{ $activo->nombre }}</strong>,
                    MARCA: {{ $activo->marca ?? '—' }}, MODELO: {{ $activo->modelo ?? '—' }},
                    SERIE: {{ $activo->numero_serie ?? 'S/N' }}.
                    {{ $activo->descripcion ?? '' }}

                    <div class="imagenes-row">
                        <table class="img-table">
                            <tr>
                                <td>
                                    <div class="img-box">
                                        @if($activo->imagen && file_exists(public_path('storage/' . $activo->imagen)))
                                            <img src="{{ public_path('storage/' . $activo->imagen) }}" alt="Frontal">
                                        @elseif($activo->imagen)
                                            <img src="{{ asset('storage/' . $activo->imagen) }}" alt="Frontal">
                                        @else
                                            <span style="font-size:8px; color:#777;">Sin foto frontal</span>
                                        @endif
                                    </div>
                                    <div class="img-label">Vista Frontal</div>
                                </td>
                                <td>
                                    <div class="img-box">
                                        @if($activo->imagen_lateral && file_exists(public_path('storage/' . $activo->imagen_lateral)))
                                            <img src="{{ public_path('storage/' . $activo->imagen_lateral) }}" alt="Lateral">
                                        @elseif($activo->imagen_lateral)
                                            <img src="{{ asset('storage/' . $activo->imagen_lateral) }}" alt="Lateral">
                                        @else
                                            <span style="font-size:8px; color:#777;">Sin foto lateral</span>
                                        @endif
                                    </div>
                                    <div class="img-label">Vista Lateral</div>
                                </td>
                                <td>
                                    <div class="img-box">
                                        @if($activo->imagen_detalle && file_exists(public_path('storage/' . $activo->imagen_detalle)))
                                            <img src="{{ public_path('storage/' . $activo->imagen_detalle) }}" alt="Detalle">
                                        @elseif($activo->imagen_detalle)
                                            <img src="{{ asset('storage/' . $activo->imagen_detalle) }}" alt="Detalle">
                                        @else
                                            <span style="font-size:8px; color:#777;">Sin foto placa/detalle</span>
                                        @endif
                                    </div>
                                    <div class="img-label">Placa / Detalle</div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td class="col-transferencia" style="text-align: center; font-size: 9px; color: #555;">—</td>
            </tr>
        </tbody>
    </table>

    {{-- ÍTEMS ASIGNADOS --}}
    <table class="tabla-asignados">
        <tr>
            <td style="width: 140px; font-weight: bold; background: #f1f1f1;">ITEMS ASIGNADOS</td>
            <td style="width: 45px; text-align: center; font-weight: bold;">1</td>
            <td></td>
        </tr>
    </table>

    {{-- SECCIÓN DE FIRMAS Y RESPONSABLES --}}
    <table class="tabla-firmas">
        <tr>
            @php
                $respList = $responsables->take(2);
            @endphp
            @foreach($respList as $resp)
                <td>
                    <div class="espacio-firma"></div>
                    <div class="linea-firma">{{ $resp->nombre_completo ?? 'RESPONSABLE DE BIEN DE USO' }}</div>
                    <div class="cargo-firma" style="font-weight: bold;">RESPONSABLE DEL BIEN DE USO</div>
                </td>
            @endforeach
            @if($respList->count() == 1)
                <td></td>
            @endif
            <td>
                <div class="espacio-firma"></div>
                <div class="linea-firma">Lic. Ana Lía Zapana Cortez</div>
                <div class="cargo-firma" style="font-weight: bold;">DIRECTORA ADMINISTRATIVA</div>
            </td>
        </tr>
        <tr>
            @foreach($respList as $resp)
                <td>
                    <div style="font-size: 10px;"><strong>NOMBRE:</strong> {{ $resp->nombre_completo ?? '—' }}</div>
                    <div style="font-size: 10px;"><strong>C.I.:</strong> {{ $resp->ci ?? '—' }}</div>
                    <div style="font-size: 10px;"><strong>CARGO:</strong> {{ $resp->cargo ?? $resp->rol ?? 'Custodio' }}</div>
                    <div style="font-size: 10px;"><strong>UNIDAD:</strong> {{ $activo->ambiente->nombre ?? 'ITPM' }}</div>
                </td>
            @endforeach
            @if($respList->count() == 1)
                <td></td>
            @endif
            <td>
                <div style="font-size: 10px;"><strong>NOMBRE:</strong> Lic. Ana Lía Zapana Cortez</div>
                <div style="font-size: 10px;"><strong>C.I.:</strong> 4756925 LP</div>
                <div style="font-size: 10px;"><strong>CARGO:</strong> DIRECTORA ADMINISTRATIVA</div>
                <div style="font-size: 10px;"><strong>UNIDAD:</strong> DIRECCIÓN ADMINISTRATIVA</div>
            </td>
        </tr>
    </table>

    {{-- UNIDADES Y OBSERVACIONES --}}
    <table class="tabla-unidades">
        <tr>
            <td style="width: 50%;">UNIDAD : {{ strtoupper($activo->ambiente->nombre ?? 'ITPM CENTRAL') }}</td>
            <td style="width: 50%;">UNIDAD : DIRECCION ADMINISTRATIVA</td>
        </tr>
    </table>

    <table class="tabla-obs">
        <tr>
            <td style="width: 50%;">
                <b>Observaciones :</b> ASIGNACION PROVISIONAL DE ACTIVOS FIJOS (EQUIPAMIENTO 2025) HASTA REGULARIZACION DE RESOLUCION DEPARTAMENTAL DE DONACION.
            </td>
            <td style="width: 35%;">
                <b>LIBERACION EN APLICACION AL ART. 148 DE SABS</b>
            </td>
            <td style="width: 15%;">
                <b>FECHA:</b>
            </td>
        </tr>
    </table>
    {{-- TEXTO LEGAL INFERIOR --}}
    <div class="texto-legal">
    <strong>IMPORTANTE:</strong> Como responsable del bien de uso me comprometo a dar cumplimiento al D.S.0181 Art. 116 (RESPONSABILIDAD POR EL MANEJO DE BIENES) Parágrafo III. Todos los servidores<br>
        Publicos son responsables por el debido uso, custodia, preservacion y demanda de servicios de mantenimiento de los bienes que les fueran asignados, de acuerdo el régimen de Responsabilidades Por la <br>
        funcion Publica, establecida en la Ley 1178 y sus reglamentos. <br>
        Art.157(PROHIBICIONES PARA LOS FUNCIONARIOS PÚBLICOS SOBRE EL USO DE ACTIVOS FIJOS MUEBLES)Inc A,b,c,d,e,f,h,g <br>
    <strong>NOTA:</strong> COMO RESPONSABLE DEL USO Y CUSTODIO ME COMPROMETO CUMPLIR EL INCISO c: PRESTAR O TRANSFERIR EL BIEN A OTRO EMPLEADO PUBLICO.
    </div>

</div>

</body>
</html>
