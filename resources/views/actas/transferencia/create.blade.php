@extends('layouts.app')

@section('title', 'Registrar Nueva Transferencia')

@section('content')
<div class="container-fluid px-4 py-4">
    <div style="background:white;border-radius:20px;padding:30px;width:100%;max-width:900px;margin:0 auto;box-shadow:0 25px 50px -12px rgba(0,0,0,.15);">

        <h2 style="font-size:20px;font-weight:800;color:#0f172a;margin-bottom:6px;">Registrar Nueva Transferencia</h2>
        <p style="font-size:13px;color:#64748b;margin-bottom:20px;">Selecciona el activo, el ambiente de destino y el nuevo custodio responsable.</p>

        <form id="formTransferencia" method="POST" action="{{ route('actas.transferencia.store') }}" enctype="multipart/form-data"
              data-custodios-url="{{ route('actas.transferencia.custodios', ['idAmbiente' => '__ID_AMBIENTE__']) }}">
            @csrf

            <div style="display:flex;flex-direction:column;gap:16px;">

                {{-- ========================================== --}}
                {{-- 1. ACTIVO A TRANSFERIR (Buscador por Código y Nombre) --}}
                {{-- ========================================== --}}

                {{-- Filtros de activos por carrera y ambiente de origen --}}
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px;">
                    <div style="display:flex;flex-direction:column;gap:6px;min-width:200px;">
                        <label style="font-size:11px;font-weight:700;color:#334155;text-transform:uppercase;">Filtrar activos por Carrera</label>
                        <select name="id_carrera_activo" id="select-carrera-activo-filtro"
                                style="width:100%;padding:8px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;background:#f8fafc;"
                                onchange="filtrarActivosPorCarrera(this.value)">
                            <option value="">— Todas las carreras —</option>
                            @foreach($carrerasDisponibles ?? [] as $car)
                                <option value="{{ $car->id_carrera }}" {{ old('id_carrera_activo', $idCarreraActivoPreselected ?? '') == $car->id_carrera ? 'selected' : '' }}>{{ $car->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:6px;min-width:220px;">
                        <label style="font-size:11px;font-weight:700;color:#334155;text-transform:uppercase;">Filtrar activos por Ambiente Origen</label>
                        <select name="id_ambiente_activo" id="select-ambiente-activo-filtro"
                                style="width:100%;padding:8px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;background:#f8fafc;"
                                onchange="filtrarActivosPorAmbiente(this.value)">
                            <option value="">— Todos los ambientes —</option>
                            @foreach($ambientesParaFiltroActivos ?? [] as $amb)
                                <option value="{{ $amb->id_ambiente }}" {{ old('id_ambiente_activo', $idAmbienteActivoPreselected ?? '') == $amb->id_ambiente ? 'selected' : '' }}
                                        data-carrera-id="{{ $amb->carrera?->id_carrera ?? '' }}">{{ $amb->nombre }} — {{ $amb->carrera?->nombre ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label style="font-size:12px;font-weight:700;display:block;margin-bottom:4px;color:#334155;">Activos a Transferir *</label>
                    <div style="position:relative;">
                        <input type="text" id="buscador-activo-transferencia" placeholder="🔍 Buscar activo por código o nombre..."
                               style="width:100%;padding:10px 12px;padding-right:34px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;box-sizing:border-box;background:#f8fafc;"
                               onkeyup="filtrarActivosTransferencia(this.value)">
                        <button type="button" id="limpiar-activo" onclick="limpiarBuscador('activo')" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;font-size:14px;cursor:pointer;padding:4px;">✕</button>
                        <span style="position:absolute;right:8px;top:8px;font-size:11px;color:#64748b;">Ctrl/Cmd + clic para seleccionar varios</span>
                    </div>
                    <select name="id_activos[]" id="select-activo-transferencia" multiple size="6"
                            style="width:100%;padding:8px;border-radius:0 0 8px 8px;border:1px solid #cbd5e1;border-top:none;font-size:13px;background:white;box-sizing:border-box;"
                            onchange="actualizarResumen()">
                        @foreach($activosDisponibles ?? [] as $a)
                            @php
                                $textoActivo = strtolower($a->codigo_activo . ' ' . $a->nombre . ' ' . ($a->marca ?? ''));
                                $preseleccionados = is_array($idActivoPreselected) ? $idActivoPreselected : (($idActivoPreselected) ? [$idActivoPreselected] : []);
                                $estaSeleccionado = in_array($a->id_activo, $preseleccionados);
                            @endphp
                            <option value="{{ $a->id_activo }}" {{ $estaSeleccionado ? 'selected' : '' }}
                                    data-busqueda="{{ $textoActivo }}"
                                    data-codigo="{{ $a->codigo_activo }}"
                                    data-nombre="{{ $a->nombre }}"
                                    data-origen="{{ $a->ambiente?->nombre ?? 'Sin asignar' }}"
                                    data-carrera-id="{{ $a->ambiente?->carrera?->id_carrera ?? '' }}"
                                    data-ambiente-id="{{ $a->id_ambiente ?? '' }}"
                                    style="padding: 6px; border-bottom: 1px solid #f1f5f9;">
                                [{{ $a->codigo_activo }}] {{ $a->nombre }} <span style="color:#94a3b8;font-size:11px;">({{ $a->marca ?? 'S/M' }})</span><br>
                                <span style="color:#64748b;font-size:11px;">📍 Origen: {{ $a->ambiente?->nombre ?? 'Sin asignar' }}</span>
                            </option>
                        @endforeach
                    </select>
                    <div id="errorActivo" style="display:none;color:#dc2626;font-size:11px;font-weight:700;margin-top:4px;">⚠️ Selecciona al menos un activo.</div>
                    <div id="contadorActivos" style="font-size:11px;color:#64748b;margin-top:2px;">0 activos seleccionados</div>
                </div>

                {{-- ========================================== --}}
                {{-- 1.5. FILTRO POR CARRERA (opcional) --}}
                {{-- ========================================== --}}
                <div>
                    <label style="font-size:12px;font-weight:700;display:block;margin-bottom:4px;color:#334155;">Filtrar ambientes por Carrera</label>
                    <select name="id_carrera" id="select-carrera-filtro"
                            style="width:100%;padding:10px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;background:white;"
                            onchange="filtrarAmbientesPorCarrera(this.value)">
                        <option value="">— Todas las carreras —</option>
                        @foreach($carrerasDisponibles ?? [] as $car)
                            <option value="{{ $car->id_carrera }}" {{ old('id_carrera', $idCarreraPreselected ?? '') == $car->id_carrera ? 'selected' : '' }}>{{ $car->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- ========================================== --}}
                {{-- 2. AMBIENTE DESTINO (Buscador por Nombre o Código) --}}
                {{-- ========================================== --}}
                <div>
                    <label style="font-size:12px;font-weight:700;display:block;margin-bottom:4px;color:#334155;">Ambiente Destino *</label>
                    <div style="position:relative;">
                        <input type="text" id="buscador-ambiente-transferencia" placeholder="🔍 Buscar ambiente por nombre o código..."
                               style="width:100%;padding:10px 12px;padding-right:34px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;box-sizing:border-box;background:#f8fafc;"
                               onkeyup="filtrarAmbientesTransferencia(this.value)">
                        <button type="button" id="limpiar-ambiente" onclick="limpiarBuscador('ambiente')" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;font-size:14px;cursor:pointer;padding:4px;">✕</button>
                    </div>
                    <select name="id_ambiente_destino" id="select-ambiente-transferencia" size="4"
                            style="width:100%;padding:8px;border-radius:0 0 8px 8px;border:1px solid #cbd5e1;border-top:none;font-size:13px;background:white;box-sizing:border-box;"
                            onchange="cargarCustodiosPorAmbiente(this.value)">
                        <option value="" disabled {{ old('id_ambiente_destino', $idAmbientePreselected ?? '') ? '' : 'selected' }}>— Selecciona un ambiente destino —</option>
                        @foreach($ambientesDisponibles ?? [] as $amb)
                            @php
                                $textoAmbiente = strtolower(($amb->codigo ?? '') . ' ' . $amb->nombre . ' ' . ($amb->carrera?->nombre ?? ''));
                            @endphp
                            <option value="{{ $amb->id_ambiente }}" {{ old('id_ambiente_destino', $idAmbientePreselected ?? '') == $amb->id_ambiente ? 'selected' : '' }}
                                    data-busqueda="{{ $textoAmbiente }}"
                                    data-nombre="{{ $amb->nombre }}"
                                    data-carrera="{{ $amb->carrera?->nombre ?? '' }}"
                                    data-carrera-id="{{ $amb->carrera?->id_carrera ?? '' }}"
                                    style="padding: 6px; border-bottom: 1px solid #f1f5f9;">
                                {{ $amb->nombre }} @if(!empty($amb->codigo)) ({{ $amb->codigo }}) @endif — 🎓 {{ $amb->carrera?->nombre ?? '' }}
                            </option>
                        @endforeach
                    </select>
                    <div id="errorAmbiente" style="display:none;color:#dc2626;font-size:11px;font-weight:700;margin-top:4px;">⚠️ Selecciona un ambiente de destino.</div>
                </div>

                {{-- ========================================== --}}
                {{-- 2.5. CATEGORÍA DE DESTINO (opcional, se filtra por ambiente) --}}
                {{-- ========================================== --}}
                <div>
                    <label style="font-size:12px;font-weight:700;display:block;margin-bottom:4px;color:#334155;">Categoría de Destino</label>
                    <select name="id_categoria" id="select-categoria-transferencia" size="4"
                            style="width:100%;padding:8px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;background:white;box-sizing:border-box;"
                            onchange="actualizarResumen()">
                        <option value="">— Categoría del ambiente destino (automática) —</option>
                        @foreach($categoriasDisponibles ?? [] as $cat)
                            @php
                                $textoCategoria = strtolower($cat->id_categoria . ' ' . $cat->nombre . ' ' . ($cat->ambiente?->nombre ?? ''));
                            @endphp
                            <option value="{{ $cat->id_categoria }}" {{ old('id_categoria', $idCategoriaPreselected ?? '') == $cat->id_categoria ? 'selected' : '' }}
                                    data-busqueda="{{ $textoCategoria }}"
                                    data-ambiente-id="{{ $cat->ambiente?->id_ambiente ?? '' }}"
                                    style="padding: 6px; border-bottom: 1px solid #f1f5f9; {{ old('id_ambiente_destino', $idAmbientePreselected ?? '') != ($cat->ambiente?->id_ambiente ?? '') && $idAmbientePreselected ? 'display:none;' : '' }}">
                                ID {{ $cat->id_categoria }} - {{ $cat->nombre }} <span style="color:#64748b;font-size:11px;">(Ambiente: {{ $cat->ambiente?->nombre ?? '—' }})</span>
                            </option>
                        @endforeach
                    </select>
                    <div id="errorCategoria" style="display:none;color:#dc2626;font-size:11px;font-weight:700;margin-top:4px;">⚠️ Selecciona una categoría válida.</div>
                </div>

                {{-- ========================================== --}}
                {{-- 3. NUEVO CUSTODIO (Buscador por Nombre, C.I. o Ítem) --}}
                {{-- ========================================== --}}
                <div>
                    <label style="font-size:12px;font-weight:700;display:block;margin-bottom:4px;color:#334155;">Nuevo Custodio *</label>
                    <div style="position:relative;">
                        <input type="text" id="buscador-custodio-transferencia" placeholder="🔍 Buscar custodio por Nombre, C.I. o N° de Ítem..."
                               style="width:100%;padding:10px 12px;padding-right:34px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;box-sizing:border-box;background:#f8fafc;"
                               onkeyup="filtrarCustodiosTransferencia(this.value)">
                        <button type="button" id="limpiar-custodio" onclick="limpiarBuscador('custodio')" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;font-size:14px;cursor:pointer;padding:4px;">✕</button>
                    </div>
                    <select name="id_custodio" id="select-custodio-transferencia" size="4"
                            style="width:100%;padding:8px;border-radius:0 0 8px 8px;border:1px solid #cbd5e1;border-top:none;font-size:13px;background:white;box-sizing:border-box;">
                        <option value="" disabled {{ old('id_custodio') ? '' : 'selected' }}>— Selecciona un custodio —</option>
                        @foreach($usuarios ?? [] as $u)
                            @php
                                $itemActivo = $u->items->first();
                                $nroItem = $itemActivo ? 'Ítem #' . $itemActivo->numero_item : 'Sin ítem';
                                $textoCustodio = strtolower($u->nombre_completo . ' ' . $u->ci . ' ' . ($itemActivo?->numero_item ?? ''));
                            @endphp
                            <option value="{{ $u->id_usuario }}" {{ old('id_custodio') == $u->id_usuario ? 'selected' : '' }}
                                    data-busqueda="{{ $textoCustodio }}"
                                    style="padding: 6px; border-bottom: 1px solid #f1f5f9;">
                                {{ $u->nombre_completo }} (C.I.: {{ $u->ci }} · {{ $nroItem }})
                            </option>
                        @endforeach
                    </select>
                    <div id="errorCustodio" style="display:none;color:#dc2626;font-size:11px;font-weight:700;margin-top:4px;">⚠️ Selecciona un nuevo custodio.</div>
                    <div id="infoCustodioVacio" style="display:none;font-size:11px;color:#64748b;margin-top:4px;">No hay custodios asignados a este ambiente. Puedes buscar y seleccionar cualquiera de la lista.</div>
                </div>

                {{-- ========================================== --}}
                {{-- 4. FECHA Y OBSERVACIONES --}}
                {{-- ========================================== --}}
                <div>
                    <label style="font-size:12px;font-weight:700;display:block;margin-bottom:4px;color:#334155;">Fecha de Transferencia *</label>
                    <input type="date" name="fecha_transferencia" id="inputFecha" required max="{{ now()->toDateString() }}" value="{{ old('fecha_transferencia', now()->toDateString()) }}"
                           style="width:100%;padding:10px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;background:white;">
                    <div id="errorFecha" style="display:none;color:#dc2626;font-size:11px;font-weight:700;margin-top:4px;">⚠️ La fecha es obligatoria y no puede ser futura.</div>
                </div>

                <div>
                    <label style="font-size:12px;font-weight:700;display:block;margin-bottom:4px;color:#334155;">Observaciones</label>
                    <textarea name="observaciones" rows="2" placeholder="Motivo o detalles de la transferencia..."
                              style="width:100%;padding:10px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;resize:none;">{{ old('observaciones') }}</textarea>
                </div>

            </div>

            {{-- RESUMEN ANTES DE GUARDAR --}}
            <div id="resumenTransferencia" style="display:none;background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:16px;margin-top:20px;">
                <h4 style="font-size:13px;font-weight:800;color:#1d4ed8;margin:0 0 10px 0;display:flex;align-items:center;gap:6px;">📋 Resumen de la transferencia</h4>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;font-size:12px;color:#334155;">
                    <div><strong style="color:#475569;">Activo:</strong> <span id="resumenActivo" style="color:#0f172a;font-weight:700;">—</span></div>
                    <div><strong style="color:#475569;">Origen:</strong> <span id="resumenOrigen" style="color:#0f172a;font-weight:700;">—</span></div>
                    <div><strong style="color:#475569;">Destino:</strong> <span id="resumenDestino" style="color:#059669;font-weight:700;">—</span></div>
                    <div><strong style="color:#475569;">Carrera Destino:</strong> <span id="resumenCarrera" style="color:#0f172a;font-weight:700;">—</span></div>
                    <div><strong style="color:#475569;">Categoría:</strong> <span id="resumenCategoria" style="color:#0f172a;font-weight:700;">—</span></div>
                    <div><strong style="color:#475569;">Nuevo Custodio:</strong> <span id="resumenCustodio" style="color:#0f172a;font-weight:700;">—</span></div>
                    <div><strong style="color:#475569;">Fecha:</strong> <span id="resumenFecha" style="color:#0f172a;font-weight:700;">—</span></div>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px;border-top:1px solid #e2e8f0;padding-top:16px;margin-top:20px;">
                <a href="{{ route('actas.transferencia.index') }}" style="background:#e2e8f0;color:#475569;border:none;padding:10px 18px;border-radius:8px;font-weight:700;text-decoration:none;font-size:13px;display:inline-flex;align-items:center;">Cancelar</a>
                <button type="submit" id="btnRegistrar" style="background:#ea580c;color:white;border:none;padding:10px 18px;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px;">Registrar Transferencia</button>
            </div>
        </form>
    </div>
</div>

<script>
    let datosActivos = [];
    let datosAmbientes = [];
    let datosCustodios = [];
    let solicitudCustodios = 0;

    document.addEventListener('DOMContentLoaded', function () {
        const selActivo = document.getElementById('select-activo-transferencia');
        const selAmbiente = document.getElementById('select-ambiente-transferencia');
        const selCustodio = document.getElementById('select-custodio-transferencia');

        for (let i = 0; i < selActivo.options.length; i++) {
            datosActivos.push({ id: selActivo.options[i].value, busqueda: (selActivo.options[i].getAttribute('data-busqueda') || '').toLowerCase() });
        }
        for (let i = 0; i < selAmbiente.options.length; i++) {
            datosAmbientes.push({ id: selAmbiente.options[i].value, busqueda: (selAmbiente.options[i].getAttribute('data-busqueda') || '').toLowerCase() });
        }
        for (let i = 0; i < selCustodio.options.length; i++) {
            datosCustodios.push({ id: selCustodio.options[i].value, busqueda: (selCustodio.options[i].getAttribute('data-busqueda') || '').toLowerCase() });
        }

        actualizarResumen();
        actualizarContadorActivos();
    });

    function filtrarActivosTransferencia(texto) {
        const filtro = texto.toLowerCase().trim();
        const tokens = filtro.split(/\s+/).filter(t => t.length > 0);
        const carreraFiltro = document.getElementById('select-carrera-activo-filtro')?.value;
        const ambienteFiltro = document.getElementById('select-ambiente-activo-filtro')?.value;
        const select = document.getElementById('select-activo-transferencia');
        for (let i = 0; i < select.options.length; i++) {
            const opt = select.options[i];
            const busqueda = opt.getAttribute('data-busqueda') || '';
            const coincideTexto = tokens.every(t => busqueda.includes(t));
            const coincideCarrera = !carreraFiltro || opt.getAttribute('data-carrera-id') === carreraFiltro;
            const coincideAmbiente = !ambienteFiltro || opt.getAttribute('data-ambiente-id') === ambienteFiltro;
            opt.style.display = (coincideTexto && coincideCarrera && coincideAmbiente) ? "" : "none";
        }
        actualizarContadorActivos();
        actualizarResumen();
    }

    function filtrarActivosPorCarrera(idCarrera) {
        const selectAmbiente = document.getElementById('select-ambiente-activo-filtro');
        const ambienteSeleccionado = selectAmbiente.selectedOptions[0];
        if (ambienteSeleccionado && idCarrera && ambienteSeleccionado.getAttribute('data-carrera-id') !== idCarrera) {
            selectAmbiente.value = '';
        }

        const ambienteFiltro = selectAmbiente.value;
        const input = document.getElementById('buscador-activo-transferencia');
        const texto = input.value.toLowerCase().trim();
        const tokens = texto.split(/\s+/).filter(t => t.length > 0);
        const select = document.getElementById('select-activo-transferencia');
        for (let i = 0; i < select.options.length; i++) {
            const opt = select.options[i];
            const busqueda = opt.getAttribute('data-busqueda') || '';
            const coincideTexto = tokens.every(t => busqueda.includes(t));
            const coincideCarrera = !idCarrera || opt.getAttribute('data-carrera-id') === idCarrera;
            const coincideAmbiente = !ambienteFiltro || opt.getAttribute('data-ambiente-id') === ambienteFiltro;
            opt.style.display = (coincideTexto && coincideCarrera && coincideAmbiente) ? "" : "none";
        }
        actualizarContadorActivos();
        actualizarResumen();
    }

    function filtrarActivosPorAmbiente(idAmbiente) {
        const carreraFiltro = document.getElementById('select-carrera-activo-filtro')?.value;
        const input = document.getElementById('buscador-activo-transferencia');
        const texto = input.value.toLowerCase().trim();
        const tokens = texto.split(/\s+/).filter(t => t.length > 0);
        const select = document.getElementById('select-activo-transferencia');
        for (let i = 0; i < select.options.length; i++) {
            const opt = select.options[i];
            const busqueda = opt.getAttribute('data-busqueda') || '';
            const coincideTexto = tokens.every(t => busqueda.includes(t));
            const coincideCarrera = !carreraFiltro || opt.getAttribute('data-carrera-id') === carreraFiltro;
            const coincideAmbiente = !idAmbiente || opt.getAttribute('data-ambiente-id') === idAmbiente;
            opt.style.display = (coincideTexto && coincideCarrera && coincideAmbiente) ? "" : "none";
        }
        actualizarContadorActivos();
        actualizarResumen();
    }

    function filtrarAmbientesTransferencia(texto) {
        const filtro = texto.toLowerCase().trim();
        const tokens = filtro.split(/\s+/).filter(t => t.length > 0);
        const carreraFiltro = document.getElementById('select-carrera-filtro').value;
        const select = document.getElementById('select-ambiente-transferencia');
        for (let i = 0; i < select.options.length; i++) {
            const opt = select.options[i];
            const busqueda = opt.getAttribute('data-busqueda') || '';
            const coincideTexto = tokens.every(t => busqueda.includes(t));
            const coincideCarrera = !carreraFiltro || opt.getAttribute('data-carrera-id') === carreraFiltro;
            opt.style.display = (coincideTexto && coincideCarrera) ? "" : "none";
        }
        actualizarResumen();
    }

    function filtrarAmbientesPorCarrera(idCarrera) {
        const select = document.getElementById('select-ambiente-transferencia');
        const input = document.getElementById('buscador-ambiente-transferencia');
        const texto = input.value.toLowerCase().trim();
        const tokens = texto.split(/\s+/).filter(t => t.length > 0);
        for (let i = 0; i < select.options.length; i++) {
            const opt = select.options[i];
            const busqueda = opt.getAttribute('data-busqueda') || '';
            const coincideTexto = tokens.every(t => busqueda.includes(t));
            const coincideCarrera = !idCarrera || opt.getAttribute('data-carrera-id') === idCarrera;
            opt.style.display = (coincideTexto && coincideCarrera) ? "" : "none";
        }
        // Resetear selección de ambiente si ya no está visible
        if (select.value && select.options[select.selectedIndex].style.display === 'none') {
            select.value = '';
        }
        // Filtrar categorías por carrera también
        filtrarCategoriasPorCarrera();
        actualizarResumen();
    }

    function filtrarCategoriasPorAmbiente(idAmbiente) {
        const select = document.getElementById('select-categoria-transferencia');
        for (let i = 0; i < select.options.length; i++) {
            const opt = select.options[i];
            const ambienteId = opt.getAttribute('data-ambiente-id') || '';
            const esPrimera = opt.value === '';
            opt.style.display = esPrimera || !idAmbiente || ambienteId === idAmbiente ? "" : "none";
        }
        // Resetear categoría si ya no está visible
        const selectedOpt = select.options[select.selectedIndex];
        if (selectedOpt && selectedOpt.style.display === 'none') {
            select.value = '';
        }
    }

    function filtrarCategoriasPorCarrera() {
        const carreraFiltro = document.getElementById('select-carrera-filtro').value;
        const select = document.getElementById('select-categoria-transferencia');
        const ambienteSelect = document.getElementById('select-ambiente-transferencia');
        // Si hay un ambiente seleccionado, priorizar ese filtro
        const idAmbiente = ambienteSelect.value;
        for (let i = 0; i < select.options.length; i++) {
            const opt = select.options[i];
            const ambienteId = opt.getAttribute('data-ambiente-id') || '';
            const esPrimera = opt.value === '';
            let coincideCarrera = true;
            if (carreraFiltro && !idAmbiente) {
                // Buscar el ambiente de esta categoría para ver si coincide con la carrera
                const ambOpt = Array.from(document.getElementById('select-ambiente-transferencia').options)
                    .find(a => a.value === ambienteId && a.style.display !== 'none');
                coincideCarrera = ambOpt ? ambOpt.getAttribute('data-carrera-id') === carreraFiltro : false;
            }
            opt.style.display = esPrimera || (!idAmbiente && !carreraFiltro) || (!idAmbiente && coincideCarrera) || (idAmbiente && ambienteId === idAmbiente) ? "" : "none";
        }
    }

    function filtrarCustodiosTransferencia(texto) {
        const filtro = texto.toLowerCase().trim();
        const tokens = filtro.split(/\s+/).filter(t => t.length > 0);
        const select = document.getElementById('select-custodio-transferencia');
        for (let i = 0; i < select.options.length; i++) {
            const opt = select.options[i];
            const busqueda = opt.getAttribute('data-busqueda') || '';
            const coincide = tokens.every(t => busqueda.includes(t));
            opt.style.display = coincide ? "" : "none";
        }
        actualizarResumen();
    }

    function limpiarBuscador(tipo) {
        const input = document.getElementById('buscador-' + tipo + '-transferencia');
        const select = document.getElementById('select-' + tipo + '-transferencia');
        input.value = '';
        for (let i = 0; i < select.options.length; i++) {
            select.options[i].style.display = "";
        }
        if (tipo === 'activo') {
            // Resetear filtros de carrera y ambiente de activos
            document.getElementById('select-carrera-activo-filtro').value = '';
            document.getElementById('select-ambiente-activo-filtro').value = '';
            for (let i = 0; i < select.options.length; i++) {
                select.options[i].selected = false;
            }
            document.getElementById('contadorActivos').textContent = '0 activos seleccionados';
        } else if (tipo === 'ambiente') {
            solicitudCustodios++;
            select.value = '';
            document.getElementById('select-custodio-transferencia').innerHTML = '<option value="">Selecciona un ambiente primero</option>';
            document.getElementById('infoCustodioVacio').style.display = 'none';
            // Resetear categorías
            const catSelect = document.getElementById('select-categoria-transferencia');
            for (let i = 0; i < catSelect.options.length; i++) {
                catSelect.options[i].style.display = "";
            }
            catSelect.value = '';
        } else if (tipo === 'custodio') {
            select.value = '';
        }
        actualizarResumen();
    }

    async function cargarCustodiosPorAmbiente(idAmbiente) {
        const solicitudActual = ++solicitudCustodios;
        const selectCustodio = document.getElementById('select-custodio-transferencia');
        const infoVacio = document.getElementById('infoCustodioVacio');
        selectCustodio.innerHTML = '<option value="">Cargando custodios...</option>';
        selectCustodio.disabled = true;
        infoVacio.style.display = 'none';

        if (!idAmbiente) {
            selectCustodio.innerHTML = '<option value="">Selecciona un ambiente primero</option>';
            selectCustodio.disabled = false;
            actualizarResumen();
            return;
        }

        try {
            const form = document.getElementById('formTransferencia');
            const url = form.dataset.custodiosUrl.replace('__ID_AMBIENTE__', encodeURIComponent(idAmbiente));
            const resp = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            if (!resp.ok) throw new Error('Error cargando custodios');
            const data = await resp.json();
            if (solicitudActual !== solicitudCustodios) {
                return;
            }

            selectCustodio.innerHTML = '<option value="">— Selecciona un custodio —</option>';
            if (data.custodios.length === 0) {
                selectCustodio.innerHTML = '<option value="">No hay custodios asignados a este ambiente</option>';
                infoVacio.textContent = 'No hay custodios asignados ni usuarios activos disponibles.';
                infoVacio.style.display = 'block';
            } else {
                data.custodios.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.texto;
                    opt.setAttribute('data-busqueda', c.busqueda);
                    selectCustodio.appendChild(opt);
                });
                if (data.fallback) {
                    infoVacio.textContent = 'No hay custodios asignados a este ambiente. Se muestran todos los usuarios activos.';
                    infoVacio.style.display = 'block';
                }
            }
        } catch (e) {
            if (solicitudActual !== solicitudCustodios) {
                return;
            }
            selectCustodio.innerHTML = '<option value="">Error al cargar custodios</option>';
            infoVacio.textContent = 'No se pudieron cargar los custodios. Intenta seleccionar el ambiente de nuevo.';
            infoVacio.style.display = 'block';
            console.error(e);
        }
        selectCustodio.disabled = false;
        // Filtrar categorías por el ambiente seleccionado
        filtrarCategoriasPorAmbiente(idAmbiente);
        actualizarResumen();
    }

    function actualizarResumen() {
        const selActivo = document.getElementById('select-activo-transferencia');
        const selAmbiente = document.getElementById('select-ambiente-transferencia');
        const selCustodio = document.getElementById('select-custodio-transferencia');
        const selCategoria = document.getElementById('select-categoria-transferencia');
        const fecha = document.getElementById('inputFecha');

        const resumen = document.getElementById('resumenTransferencia');
        const ambOpt = selAmbiente.selectedOptions[0] || null;
        const custOpt = selCustodio.selectedOptions[0] || null;
        const catOpt = selCategoria.selectedOptions[0] || null;

        // Contar activos seleccionados (multiple)
        const activosSeleccionados = Array.from(selActivo.selectedOptions).map(o => o.value);

        const activoTexto = activosSeleccionados.length > 0 ? activosSeleccionados.length + ' activo(s) seleccionado(s)' : '—';
        const origenes = Array.from(selActivo.selectedOptions)
            .map(option => option.getAttribute('data-origen'))
            .filter((origen, index, lista) => origen && lista.indexOf(origen) === index);
        const origenTexto = origenes.length > 0 ? origenes.join(', ') : '—';
        const destinoTexto = ambOpt?.value ? ambOpt.textContent : '—';
        const carreraTexto = ambOpt?.value ? (ambOpt.getAttribute('data-carrera') || '—') : '—';
        const categoriaTexto = catOpt && catOpt.value ? catOpt.textContent : '—';
        const custodioTexto = custOpt && custOpt.value ? custOpt.textContent : '—';
        const fechaTexto = fecha.value ? fecha.value : '—';

        document.getElementById('resumenActivo').textContent = activoTexto;
        document.getElementById('resumenOrigen').textContent = origenTexto;
        document.getElementById('resumenDestino').textContent = destinoTexto;
        document.getElementById('resumenCarrera').textContent = carreraTexto;
        document.getElementById('resumenCategoria').textContent = categoriaTexto;
        document.getElementById('resumenCustodio').textContent = custodioTexto;
        document.getElementById('resumenFecha').textContent = fechaTexto;

        resumen.style.display = (activosSeleccionados.length > 0 && ambOpt?.value && custOpt?.value && fecha.value) ? 'block' : 'none';
    }

    function actualizarContadorActivos() {
        const select = document.getElementById('select-activo-transferencia');
        const count = select.selectedOptions.length;
        document.getElementById('contadorActivos').textContent = count + ' activo(s) seleccionado(s)';
    }

    function validarTransferencia() {
        let isValid = true;

        const activo = document.getElementById('select-activo-transferencia');
        const errActivo = document.getElementById('errorActivo');
        if (!activo.value || activo.selectedOptions.length === 0) {
            errActivo.style.display = 'block'; activo.style.borderColor = '#dc2626'; isValid = false;
        } else { errActivo.style.display = 'none'; activo.style.borderColor = '#cbd5e1'; }

        const ambiente = document.getElementById('select-ambiente-transferencia');
        const errAmbiente = document.getElementById('errorAmbiente');
        if (!ambiente.value) {
            errAmbiente.style.display = 'block'; ambiente.style.borderColor = '#dc2626'; isValid = false;
        } else { errAmbiente.style.display = 'none'; ambiente.style.borderColor = '#cbd5e1'; }

        const custodio = document.getElementById('select-custodio-transferencia');
        const errCustodio = document.getElementById('errorCustodio');
        if (!custodio.value) {
            errCustodio.style.display = 'block'; custodio.style.borderColor = '#dc2626'; isValid = false;
        } else { errCustodio.style.display = 'none'; custodio.style.borderColor = '#cbd5e1'; }

        const fecha = document.getElementById('inputFecha');
        const errFecha = document.getElementById('errorFecha');
        if (!fecha.value) {
            errFecha.textContent = '⚠️ La fecha es obligatoria.';
            errFecha.style.display = 'block'; fecha.style.borderColor = '#dc2626'; isValid = false;
        } else if (fecha.value > fecha.max) {
            errFecha.textContent = '⚠️ La fecha no puede ser futura.';
            errFecha.style.display = 'block'; fecha.style.borderColor = '#dc2626'; isValid = false;
        } else {
            errFecha.style.display = 'none'; fecha.style.borderColor = '#cbd5e1';
        }

        return isValid;
    }

    document.getElementById('formTransferencia')?.addEventListener('submit', function (event) {
        if (!validarTransferencia()) {
            event.preventDefault();
            return;
        }

        const boton = document.getElementById('btnRegistrar');
        boton.disabled = true;
        boton.textContent = 'Registrando...';
    });

    document.getElementById('select-activo-transferencia')?.addEventListener('change', actualizarContadorActivos);
    document.getElementById('select-custodio-transferencia')?.addEventListener('change', actualizarResumen);
    document.getElementById('inputFecha')?.addEventListener('input', actualizarResumen);
</script>
@endsection
