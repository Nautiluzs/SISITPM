<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de Entrega y Recepción — {{ $activo->codigo_activo }}</title>
    @php
        use Illuminate\Support\Facades\DB;
        use App\Models\User;

        if (!isset($activos)) {
            $activos = collect([$activo]);
        }

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

        if ($activo->custodio && !$responsables->contains('id_usuario', $activo->custodio->id_usuario)) {
            $responsables->push($activo->custodio);
        }
        $responsables = $responsables->unique('id_usuario')->values();

        // Asignación de roles
        $jefeCarrera = $responsables->first(function($u) {
            $texto = strtoupper(($u->cargo ?? '') . ' ' . ($u->rol ?? ''));
            return str_contains($texto, 'JEFE') || str_contains($texto, 'DIRECTOR DE CARRERA');
        });

        $custodios = $responsables->filter(function($u) use ($jefeCarrera) {
            return $jefeCarrera ? $u->id_usuario !== $jefeCarrera->id_usuario : true;
        })->values();

        if ($custodios->isEmpty() && $activo->custodio) {
            $custodios = collect([$activo->custodio]);
        }

        $administrador = new \stdClass();
        $administrador->nombre_completo = 'Lic. Ana Lía Zapana Cortez';
        $administrador->ci = '4756925 LP';
        $administrador->cargo = 'DIRECTORA ADMINISTRATIVA';
        $administrador->unidad = 'DIRECCIÓN ADMINISTRATIVA';
    @endphp
    <style>
        @page {
            size: letter landscape;
            margin: 15mm 10mm 18mm 10mm;

            @bottom-center {
                content: "HOJA N° " counter(page) " de " counter(pages);
                font-size: 9px;
                font-weight: bold;
            }
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.2;
            background: #fff;
        }

        /* ============ ENCABEZADO GLOBAL ============ */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .header-table td { vertical-align: middle; }
        .logo-box { width: 60px; height: 45px; text-align: center; font-size: 24px; }
        .titulo-instituto { text-align: center; }
        .titulo-instituto h2 { font-size: 13.5px; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
        .titulo-instituto h3 { font-size: 11.5px; font-weight: normal; text-transform: uppercase; margin-bottom: 2px; }
        .titulo-instituto .acta-sub {
            font-size: 12px; font-weight: bold; text-transform: uppercase; 
            border-bottom: 1.5px solid #000; display: inline-block; padding-bottom: 1px; margin-bottom: 2px;
        }
        .titulo-instituto .fecha-acta { font-size: 10px; font-weight: bold; }

        .cajas-derecha { width: 170px; text-align: right; vertical-align: top; }
        .caja-form, .caja-hoja {
            border: 1.5px solid #000; padding: 3px 8px; font-size: 9px; font-weight: bold; 
            text-align: center; margin-bottom: 3px; background: #fff;
        }

        /* ============ TEXTOS Y TABLAS ============ */
        .info-cumplimiento {
            border: 1px solid #000; padding: 5px 8px; font-size: 9px; 
            margin-bottom: 8px; text-align: justify; background: #fff;
            page-break-inside: avoid;
        }

        table.tabla-principal { width: 100%; border-collapse: collapse; margin-bottom: 0; font-size: 9px; }
        table.tabla-principal th, table.tabla-principal td { border: 1px solid #000; padding: 3px 5px; vertical-align: middle; }
        table.tabla-principal th { background: #f1f1f1; text-align: center; font-size: 8.5px; font-weight: bold; text-transform: uppercase; }
        table.tabla-principal tr { page-break-inside: avoid; }

        .col-item { width: 35px; text-align: center; }
        .col-codigo { width: 90px; text-align: center; }
        .col-fuente { width: 50px; text-align: center; }
        .col-est { width: 35px; text-align: center; }
        .col-cant { width: 35px; text-align: center; }
        .col-transferencia { width: 75px; text-align: center; }

        /* ============ SECCIÓN DE CIERRE Y FIRMAS (VERTICAL) ============ */
        .seccion-cierre {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            page-break-inside: avoid;
        }
        
        .seccion-cierre > tbody > tr > td {
            border: 1px solid #000;
            vertical-align: top;
            padding: 0;
        }

        /* Caja de Totales (Izquierda) */
        .caja-totales {
            width: 15%;
            text-align: center;
            vertical-align: middle !important;
        }
        .caja-totales .titulo-total { font-size: 9px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .caja-totales .numero-total { font-size: 16px; font-weight: bold; }

        /* Columna de Firmas (Centro) */
        .col-firmas {
            width: 35%;
            padding: 10px !important;
        }
        
        .bloque-firma {
            margin-bottom: 20px;
            text-align: center;
        }
        .bloque-firma:last-child { margin-bottom: 0; }
        
        .espacio-firma { height: 30px; }
        .linea-firma {
            border-top: 1px solid #000; margin: 0 15px 2px 15px; padding-top: 2px;
            font-size: 9px; font-weight: bold; text-transform: uppercase;
        }
        .cargo-firma { font-size: 8.5px; font-weight: bold; margin-bottom: 2px; }
        .detalle-firma { font-size: 8px; line-height: 1.1; }

        /* Columna de Observaciones (Derecha) */
        .col-observaciones {
            width: 50%;
        }
        .col-observaciones table {
            width: 100%; border-collapse: collapse;
        }
        .col-observaciones td {
            border: 1px solid #000; padding: 5px 8px; font-size: 9px; vertical-align: top;
        }
        .col-observaciones .celda-fecha {
            text-align: center; font-weight: bold; vertical-align: middle !important;
        }

        /* ============ TEXTO LEGAL ============ */
        .texto-legal { font-size: 8.5px; margin-top: 10px; text-align: justify; line-height: 1.3; page-break-inside: avoid; }
    </style>
</head>
<body>

{{-- ========================================== --}}
    {{-- ENCABEZADO DINÁMICO --}}
    {{-- ========================================== --}}
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
                <div class="fecha-acta">{{ strtoupper(now()->translatedFormat('d \d\e F \d\e Y')) }}</div>
            </td>
            <td class="cajas-derecha">
                <div class="caja-form">FORMULARIO N° 1</div>
            </td>
        </tr>
    </table>

    {{-- TEXTO INTRODUCTORIO --}}
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
        @foreach($activos as $index => $item)
        <tr>
            <td class="col-item" style="text-align: center; font-weight: bold;">{{ $index + 1 }}</td>
            <td class="col-codigo" style="font-weight: bold; text-align: center;">{{ $item->codigo_activo }}</td>
            <td class="col-fuente" style="text-align: center;">{{ $item->fuente->codigo ?? 'P' }}</td>
            <td class="col-est" style="text-align: center; font-weight: bold;">{{ $item->estado_fisico ?? 'B' }}</td>
            <td class="col-cant" style="text-align: center;">1</td>
            <td style="text-transform: uppercase;">
                {{ $item->nombre }}
                @if($item->marca || $item->modelo || $item->numero_serie)
                    , MARCA: {{ $item->marca ?? '—' }}, MODELO: {{ $item->modelo ?? '—' }}, SERIE: {{ $item->numero_serie ?? 'S/N' }}
                @endif
                {{ $item->descripcion ? ', '.$item->descripcion : '' }}
            </td>
            <td class="col-transferencia" style="text-align: center; font-size: 9px; color: #555;">—</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ========================================== --}}
{{-- BLOQUE DE CIERRE (TOTALES, FIRMAS Y OBSERVACIONES) --}}
{{-- ========================================== --}}
<table class="seccion-cierre">
    <tr>
        <!-- 1. TOTAL ITEMS (Izquierda) -->
        <td class="caja-totales">
            <div class="titulo-total">TOTAL ITEMS ASIGNADOS</div>
            <div class="numero-total">{{ $activos->count() }}</div>
        </td>

        <!-- 2. FIRMAS EN UNA SOLA FILA (JEFE + RESPONSABLE/S + ADMINISTRADOR) -->
        <td class="col-firmas" style="padding: 0;">
            @php
                $activoSingular = isset($activo) ? $activo : ($activos->first() ?? null);
            @endphp
            @include('actas.recepcion._firmas', [
                'activo'      => $activoSingular,
                'responsables'=> $responsables,
                'jefeCarrera' => $jefeCarrera,
                'administrador' => $administrador,
            ])
        </td>

        <!-- 3. OBSERVACIONES Y FECHA (Derecha) -->
        <td class="col-observaciones">
            <table>
                <tr>
                    <td>
                        <b>Observaciones :</b> ASIGNACION PROVISIONAL DE ACTIVOS FIJOS (EQUIPAMIENTO 2025) HASTA REGULARIZACION DE RESOLUCION DEPARTAMENTAL DE DONACION.
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: bold; font-size: 9px;">
                        LIBERACION EN APLICACION AL ART. 148 DE SABS
                    </td>
                </tr>
                <tr>
                    <td class="celda-fecha">
                        <b>FECHA:</b> {{ strtoupper(now()->translatedFormat('d \d\e F \d\e Y')) }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ========================================== --}}
{{-- TEXTO LEGAL INFERIOR --}}
{{-- ========================================== --}}
<div class="texto-legal">
    <strong>IMPORTANTE:</strong> Como responsable del bien de uso me comprometo a dar cumplimiento al D.S.0181 Art. 116 (RESPONSABILIDAD POR EL MANEJO DE BIENES) Parágrafo III. Todos los servidores<br>
    Publicos son responsables por el debido uso, custodia, preservacion y demanda de servicios de mantenimiento de los bienes que les fueran asignados, de acuerdo el régimen de Responsabilidades Por la <br>
    funcion Publica, establecida en la Ley 1178 y sus reglamentos. <br>
    Art.157(PROHIBICIONES PARA LOS FUNCIONARIOS PÚBLICOS SOBRE EL USO DE ACTIVOS FIJOS MUEBLES)Inc A,b,c,d,e,f,h,g <br>
    <strong>NOTA:</strong> COMO RESPONSABLE DEL USO Y CUSTODIO ME COMPROMETO CUMPLIR EL INCISO c: PRESTAR O TRANSFERIR EL BIEN A OTRO EMPLEADO PUBLICO.
</div>

</body>
</html>