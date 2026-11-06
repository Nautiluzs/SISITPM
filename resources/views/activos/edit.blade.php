@extends('layouts.app')

@section('content')
<div style="max-width: 900px; margin: 0 auto; background: white; border-radius: 18px; padding: 28px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">

    {{-- ENCABEZADO --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h2 style="font-size: 24px; font-weight: 800; color: #0f172a; margin: 0;">
                Editar activo: {{ $activo->codigo_activo }}
            </h2>
            <p style="color: #64748b; font-size: 14px; margin: 4px 0 0 0;">
                Modifica los campos necesarios y guarda los cambios.
            </p>
        </div>
        <a href="{{ route('activos.index') }}"
           style="background: #e2e8f0; color: #334155; padding: 8px 16px; border-radius: 10px; font-weight: 700; text-decoration: none; font-size: 14px;">
            ← Volver
        </a>
    </div>

    {{-- ERRORES DEL SERVIDOR --}}
    @if ($errors->any())
        <div style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:14px 20px;border-radius:12px;margin-bottom:20px;font-size:13px;">
            <strong>⚠️ Corrige los siguientes errores:</strong>
            <ul style="margin:8px 0 0 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ⭐ GALERÍA DE IMÁGENES ACTUALES --}}
    <div id="galeria-imagenes" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px;margin-bottom:20px;">
        <div style="font-size:12px;font-weight:800;color:#2563eb;text-transform:uppercase;margin-bottom:12px;">
            📷 Fotografías actuales
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
            {{-- Frontal --}}
            <div>
                <div style="font-size:11px;font-weight:700;color:#475569;margin-bottom:6px;">Frontal</div>
                @if($activo->imagen)
                    <img src="{{ asset('storage/' . $activo->imagen) }}" alt="Frontal"
                         style="width:100%;height:120px;object-fit:cover;border-radius:8px;border:1px solid #cbd5e1;">
                @else
                    <div style="width:100%;height:120px;border:2px dashed #cbd5e1;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:11px;">
                        Sin imagen
                    </div>
                @endif
            </div>

            {{-- Lateral --}}
            <div id="galeria-lateral">
                <div style="font-size:11px;font-weight:700;color:#475569;margin-bottom:6px;">Lateral</div>
                @if($activo->imagen_lateral)
                    <img src="{{ asset('storage/' . $activo->imagen_lateral) }}" alt="Lateral"
                         style="width:100%;height:120px;object-fit:cover;border-radius:8px;border:1px solid #cbd5e1;">
                @else
                    <div style="width:100%;height:120px;border:2px dashed #cbd5e1;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:11px;">
                        Sin imagen
                    </div>
                @endif
            </div>

            {{-- Detalle --}}
            <div id="galeria-detalle">
                <div style="font-size:11px;font-weight:700;color:#475569;margin-bottom:6px;">Detalle</div>
                @if($activo->imagen_detalle)
                    <img src="{{ asset('storage/' . $activo->imagen_detalle) }}" alt="Detalle"
                         style="width:100%;height:120px;object-fit:cover;border-radius:8px;border:1px solid #cbd5e1;">
                @else
                    <div style="width:100%;height:120px;border:2px dashed #cbd5e1;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:11px;">
                        Sin imagen
                    </div>
                @endif
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('activos.update', $activo->id_activo) }}" enctype="multipart/form-data" id="activo-edit-form">
        @csrf
        @method('PUT')

        <style>
            .edit-grid { display:grid; grid-template-columns: 1fr 1fr; gap:16px; }
            .edit-grid label { display:flex; flex-direction:column; font-size:12px; font-weight:700; color:#334155; gap:4px; }
            .edit-grid label.full { grid-column:span 2; }
            .edit-grid input, .edit-grid select, .edit-grid textarea {
                width:100%; padding:10px 12px; border-radius:8px; border:1px solid #cbd5e1; font-size:14px; background:#fff; box-sizing:border-box;
            }
            .edit-grid input:focus, .edit-grid select:focus, .edit-grid textarea:focus {
                outline:none; border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.1);
            }
            .section-title {
                grid-column:span 2; font-size:11px; font-weight:800; color:#2563eb; text-transform:uppercase;
                margin:8px 0 -6px 0; padding-bottom:6px; border-bottom:1px solid #e2e8f0;
            }
            .input-error { border-color:#dc2626 !important; background:#fef2f2 !important; }
            .error-msg { color:#dc2626; font-size:11px; font-weight:600; margin-top:2px; display:none; }
            .error-msg.visible { display:block; }
            .asterisco { color:#dc2626; margin-left:2px; display:none; }
            .asterisco.visible { display:inline; }

            /* Preview de imágenes */
            .img-preview {
                width: 100%; height: 130px; border: 2px dashed #cbd5e1; border-radius: 10px;
                display: flex; align-items: center; justify-content: center; background: #f8fafc;
                margin-bottom: 8px; overflow: hidden; transition: all 0.2s;
            }
            .img-preview.has-image {
                border-style: solid; border-color: #10b981;
            }
            .img-preview img {
                width: 100%; height: 100%; object-fit: cover; border-radius: 8px;
            }
            .img-preview .placeholder {
                text-align: center; color: #94a3b8;
            }
        </style>

        <div class="edit-grid">

            {{-- ============ IDENTIFICACIÓN ============ --}}
            <div class="section-title">Identificación</div>

            @php $tipoActual = old('tipo_bien', $activo->tipo_bien ?? 'ACTIVO_FIJO'); @endphp

            <div class="full" style="padding:14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
                <div style="font-size:12px;font-weight:800;color:#334155;margin-bottom:8px;">
                    Clasificación del bien *
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <label id="opcion-activo-fijo" style="display:flex;gap:9px;align-items:flex-start;cursor:pointer;padding:11px;background:#fff;border:2px solid #cbd5e1;border-radius:10px;">
                        <input type="radio" name="tipo_bien" value="ACTIVO_FIJO" data-radio-tipo
                               @checked($tipoActual === 'ACTIVO_FIJO')
                               style="margin-top:2px;width:16px;height:16px;">
                        <span>
                            <strong style="display:block;font-size:13px;color:#1d4ed8;">Activo fijo</strong>
                            <small style="font-weight:400;color:#64748b;font-size:11px;">
                                Se capitaliza. Marca, modelo, serie y 3 fotos obligatorias.
                            </small>
                        </span>
                    </label>

                    <label id="opcion-no-activo" style="display:flex;gap:9px;align-items:flex-start;cursor:pointer;padding:11px;background:#fff;border:2px solid #cbd5e1;border-radius:10px;">
                        <input type="radio" name="tipo_bien" value="NO_ACTIVO" data-radio-tipo
                               @checked($tipoActual === 'NO_ACTIVO')
                               style="margin-top:2px;width:16px;height:16px;">
                        <span>
                            <strong style="display:block;font-size:13px;color:#475569;">No activo</strong>
                            <small style="font-weight:400;color:#64748b;font-size:11px;">
                                No se capitaliza. Solo 1 foto, marca/modelo/serie opcionales.
                            </small>
                        </span>
                    </label>
                </div>

                <small id="aviso-serie" style="display:none;margin-top:9px;color:#b45309;font-size:11px;">
                    En los no activos, la marca, el modelo y el número de serie no son obligatorios.
                </small>
            </div>

            {{-- RV (Revaluo) --}}
            <div style="padding:14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;margin-top:12px;">
                <div style="font-size:12px;font-weight:800;color:#334155;margin-bottom:8px;">
                    RV (Revaluo)
                </div>
                <div style="display:flex;gap:10px;align-items:center;">
                    <label id="rv-si-label" style="display:flex;gap:6px;align-items:center;cursor:pointer;padding:8px;background:#fff;border:1px solid #cbd5e1;border-radius:8px;flex:1;">
                        <input type="radio" name="rv" value="SI" id="rv-si" data-radio-rv
                               @checked(old('rv', $activo->rv ?? 'NO') === 'SI')
                               style="margin-top:2px;width:16px;height:16px;">
                        <span style="font-size:13px;color:#166534;">Sí</span>
                    </label>
                    <label id="rv-no-label" style="display:flex;gap:6px;align-items:center;cursor:pointer;padding:8px;background:#fff;border:1px solid #cbd5e1;border-radius:8px;flex:1;">
                        <input type="radio" name="rv" value="NO" id="rv-no" data-radio-rv
                               @checked((old('rv', $activo->rv ?? 'NO') ?? 'NO') === 'NO')
                               style="margin-top:2px;width:16px;height:16px;">
                        <span style="font-size:13px;color:#991b1b;">No</span>
                    </label>
                </div>
                <small style="font-weight:400;color:#64748b;font-size:10px;display:block;margin-top:5px;">
                    Marca si el activo está sujeto a revaluo. Aplica para Activo Fijo y No Activo.
                </small>
            </div>

            <label>Código del activo *
                <input name="codigo_activo" id="codigo_activo" maxlength="50"
                       value="{{ old('codigo_activo', $activo->codigo_activo) }}">
                <span class="error-msg" data-for="codigo_activo"></span>
            </label>

            <label>Código antiguo (opcional)
                <input name="codigo_antiguo" id="codigo_antiguo" maxlength="50"
                       value="{{ old('codigo_antiguo', $activo->codigo_antiguo) }}">
                <span class="error-msg" data-for="codigo_antiguo"></span>
            </label>

            <label>N° de serie <span class="asterisco" id="req-serie">*</span>
                <input name="numero_serie" id="numero_serie" maxlength="80"
                       value="{{ old('numero_serie', $activo->numero_serie) }}">
                <span class="error-msg" data-for="numero_serie"></span>
            </label>

            <label class="full">Nombre del activo *
                <input name="nombre" id="nombre" maxlength="150"
                       value="{{ old('nombre', $activo->nombre) }}">
                <span class="error-msg" data-for="nombre"></span>
            </label>

            {{-- ============ CLASIFICACIÓN ============ --}}
            <div class="section-title">Clasificación</div>

            <label>Categoría *
                <select name="id_categoria" id="id_categoria">
                    <option value="">— Seleccione —</option>
                    @foreach ($categorias as $item)
                        <option value="{{ $item->id_categoria }}"
                            {{ old('id_categoria', $activo->id_categoria) == $item->id_categoria ? 'selected' : '' }}>
                            {{ $item->nombre }}
                        </option>
                    @endforeach
                </select>
                <span class="error-msg" data-for="id_categoria"></span>
            </label>

            <label>Ambiente *
                <select name="id_ambiente" id="id_ambiente">
                    <option value="">— Seleccione —</option>
                    @foreach ($ambientes as $item)
                        <option value="{{ $item->id_ambiente }}"
                            {{ old('id_ambiente', $activo->id_ambiente) == $item->id_ambiente ? 'selected' : '' }}>
                            {{ $item->nombre }}
                        </option>
                    @endforeach
                </select>
                <span class="error-msg" data-for="id_ambiente"></span>
            </label>

            <label>Fuente de financiamiento *
                <select name="id_fuente" id="id_fuente">
                    <option value="">— Seleccione —</option>
                    @foreach ($fuentes as $item)
                        <option value="{{ $item->id_fuente }}"
                            {{ old('id_fuente', $activo->id_fuente) == $item->id_fuente ? 'selected' : '' }}>
                            {{ $item->codigo ? $item->codigo . ' - ' : '' }}{{ $item->nombre }}
                        </option>
                    @endforeach
                </select>
                <span class="error-msg" data-for="id_fuente"></span>
            </label>

            <label>Custodio *
                <x-person-picker
                    name="id_custodio"
                    id="custodioActivo"
                    :selected-id="old('id_custodio', $activo->id_custodio)"
                    :selected-texto="$activo->custodio?->nombre_completo"
                    ambiente-field="id_ambiente"
                    :required="true" />
                <span class="error-msg" data-for="id_custodio"></span>
            </label>

            {{-- ============ ESPECIFICACIONES ============ --}}
            <div class="section-title">Especificaciones técnicas</div>

            <label>Marca <span class="asterisco" id="req-marca">*</span>
                <input name="marca" id="marca" maxlength="80"
                       value="{{ old('marca', $activo->marca) }}">
                <span class="error-msg" data-for="marca"></span>
            </label>

            <label>Modelo <span class="asterisco" id="req-modelo">*</span>
                <input name="modelo" id="modelo" maxlength="80"
                       value="{{ old('modelo', $activo->modelo) }}">
                <span class="error-msg" data-for="modelo"></span>
            </label>

            <label>Fecha de adquisición *
                <input name="fecha_adquisicion" id="fecha_adquisicion" type="date"
                       value="{{ old('fecha_adquisicion', optional($activo->fecha_adquisicion)->format('Y-m-d')) }}">
                <span class="error-msg" data-for="fecha_adquisicion"></span>
            </label>

            <label>Valor de adquisición (Bs) *
                <input name="valor_adquisicion" id="valor_adquisicion" type="number" min="0" step="0.01"
                       value="{{ old('valor_adquisicion', $activo->valor_adquisicion) }}">
                <span class="error-msg" data-for="valor_adquisicion"></span>
            </label>

            <label>Estado físico *
                <select name="estado_fisico" id="estado_fisico">
                    <option value="B"  {{ old('estado_fisico', $activo->estado_fisico) == 'B'  ? 'selected' : '' }}>Bueno</option>
                    <option value="R"  {{ old('estado_fisico', $activo->estado_fisico) == 'R'  ? 'selected' : '' }}>Regular</option>
                    <option value="M"  {{ old('estado_fisico', $activo->estado_fisico) == 'M'  ? 'selected' : '' }}>Malo</option>
                    <option value="FF" {{ old('estado_fisico', $activo->estado_fisico) == 'FF' ? 'selected' : '' }}>Fuera de funcionamiento</option>
                </select>
                <span class="error-msg" data-for="estado_fisico"></span>
            </label>

            <label>Estado de registro *
                <select name="estado_registro" id="estado_registro">
                    <option value="ACTIVO"           {{ old('estado_registro', $activo->estado_registro) == 'ACTIVO'           ? 'selected' : '' }}>Activo</option>
                    <option value="ASIGNADO"         {{ old('estado_registro', $activo->estado_registro) == 'ASIGNADO'         ? 'selected' : '' }}>Asignado</option>
                    <option value="EN_TRANSFERENCIA" {{ old('estado_registro', $activo->estado_registro) == 'EN_TRANSFERENCIA' ? 'selected' : '' }}>En transferencia</option>
                    <option value="BAJA"             {{ old('estado_registro', $activo->estado_registro) == 'BAJA'             ? 'selected' : '' }}>Baja</option>
                </select>
                <span class="error-msg" data-for="estado_registro"></span>
            </label>

            {{-- ============ FOTOGRAFÍAS ============ --}}
            <div class="section-title">📷 Reemplazar fotografías (opcional)</div>

            <label id="bloque-frontal">Foto Frontal <span class="asterisco" id="req-frontal">*</span>
                <div id="preview-frontal" class="img-preview">
                    <div class="placeholder">
                        <div style="font-size: 28px; margin-bottom: 4px;">📷</div>
                        <div style="font-size: 11px;">Dejar vacío para mantener la actual</div>
                    </div>
                </div>
                <input type="file" name="imagen" id="imagen" accept="image/*"
                       onchange="previewImage(this, 'preview-frontal')">
                <small style="font-weight:400;color:#64748b;font-size:10px;">JPG, PNG o WEBP. Máx 5 MB.</small>
                <span class="error-msg" data-for="imagen"></span>
            </label>

            <label id="bloque-lateral">Foto Lateral <span class="asterisco" id="req-lateral">*</span>
                <div id="preview-lateral" class="img-preview">
                    <div class="placeholder">
                        <div style="font-size: 28px; margin-bottom: 4px;">📷</div>
                        <div style="font-size: 11px;">Dejar vacío para mantener la actual</div>
                    </div>
                </div>
                <input type="file" name="imagen_lateral" id="imagen_lateral" accept="image/*"
                       onchange="previewImage(this, 'preview-lateral')">
                <small style="font-weight:400;color:#64748b;font-size:10px;">JPG, PNG o WEBP. Máx 5 MB.</small>
                <span class="error-msg" data-for="imagen_lateral"></span>
            </label>

            <label id="bloque-detalle" class="full">Foto de Detalle <span class="asterisco" id="req-detalle">*</span>
                <div id="preview-detalle" class="img-preview">
                    <div class="placeholder">
                        <div style="font-size: 28px; margin-bottom: 4px;">📷</div>
                        <div style="font-size: 11px;">Dejar vacío para mantener la actual</div>
                    </div>
                </div>
                <input type="file" name="imagen_detalle" id="imagen_detalle" accept="image/*"
                       onchange="previewImage(this, 'preview-detalle')">
                <small style="font-weight:400;color:#64748b;font-size:10px;">JPG, PNG o WEBP. Máx 5 MB.</small>
                <span class="error-msg" data-for="imagen_detalle"></span>
            </label>

            {{-- ============ DETALLES ============ --}}
            <div class="section-title">Detalles y descripción</div>

            <label class="full">Descripción / Características técnicas *
                <textarea name="descripcion" id="descripcion" rows="3">{{ old('descripcion', $activo->descripcion) }}</textarea>
                <span class="error-msg" data-for="descripcion"></span>
            </label>

        </div>

        {{-- ============ ACCIONES ============ --}}
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:24px;border-top:1px solid #e2e8f0;padding-top:18px;">
            <a href="{{ route('activos.index') }}"
               style="background:#e2e8f0;color:#475569;border:none;padding:11px 22px;border-radius:8px;font-weight:700;text-decoration:none;display:inline-block;">
                Cancelar
            </a>
            <button type="submit" id="btn-guardar-edit"
                style="background:#2563eb;color:white;border:none;padding:11px 22px;border-radius:8px;font-weight:700;cursor:pointer;">
                💾 Guardar cambios
            </button>
        </div>
    </form>
</div>

<script>
    // ============================================
    // PREVIEW DE IMÁGENES
    // ============================================
    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        if (!preview) return;

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                preview.classList.add('has-image');
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.innerHTML = `
                <div class="placeholder">
                    <div style="font-size: 28px; margin-bottom: 4px;">📷</div>
                    <div style="font-size: 11px;">Dejar vacío para mantener la actual</div>
                </div>
            `;
            preview.classList.remove('has-image');
        }
    }

    // ============================================
    // LIMPIAR ERROR INLINE
    // ============================================
    function limpiarError(campo) {
        const span = document.querySelector('.error-msg[data-for="' + campo.id + '"]');
        if (span) {
            span.classList.remove('visible');
            span.textContent = '';
        }
        campo.classList.remove('input-error');
    }

    // Al escribir/cambiar cualquier campo, limpiar su error
    document.querySelectorAll('#activo-edit-form input, #activo-edit-form select, #activo-edit-form textarea').forEach(el => {
        if (!el.id) return;
        el.addEventListener('input', () => limpiarError(el));
        el.addEventListener('change', () => limpiarError(el));
    });

    // ============================================
    // TIPO DE BIEN — Dinámico
    // ============================================
    function aplicarTipoBien(tipo) {
        const esFijo = tipo === 'ACTIVO_FIJO';

        // Tarjetas visuales
        const cardFijo = document.getElementById('opcion-activo-fijo');
        const cardNoActivo = document.getElementById('opcion-no-activo');
        if (cardFijo) {
            cardFijo.style.borderColor = esFijo ? '#2563eb' : '#cbd5e1';
            cardFijo.style.background = esFijo ? '#eff6ff' : '#fff';
        }
        if (cardNoActivo) {
            cardNoActivo.style.borderColor = !esFijo ? '#f59e0b' : '#cbd5e1';
            cardNoActivo.style.background = !esFijo ? '#fffbeb' : '#fff';
        }

        // Aviso
        const aviso = document.getElementById('aviso-serie');
        if (aviso) aviso.style.display = esFijo ? 'none' : 'block';

        // Asteriscos marca/modelo/serie/frontal
        ['req-marca','req-modelo','req-serie'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.toggle('visible', esFijo);
        });
        const reqFrontal = document.getElementById('req-frontal');
        if (reqFrontal) reqFrontal.classList.toggle('visible', esFijo);

        // Bloques lateral y detalle (solo ACTIVO_FIJO)
        const bloqueLateral = document.getElementById('bloque-lateral');
        const bloqueDetalle = document.getElementById('bloque-detalle');
        if (bloqueLateral) bloqueLateral.style.display = esFijo ? 'flex' : 'none';
        if (bloqueDetalle) bloqueDetalle.style.display = esFijo ? 'flex' : 'none';

        // Asteriscos lateral y detalle
        ['req-lateral','req-detalle'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.toggle('visible', esFijo);
        });

        // Ocultar bloques de galería lateral y detalle
        const galeriaLateral = document.getElementById('galeria-lateral');
        const galeriaDetalle = document.getElementById('galeria-detalle');
        if (galeriaLateral) galeriaLateral.style.display = esFijo ? 'block' : 'none';
        if (galeriaDetalle) galeriaDetalle.style.display = esFijo ? 'block' : 'none';

        // Ajustar la grilla de la galería según tipo
        const galeria = document.getElementById('galeria-imagenes');
        if (galeria) {
            const grilla = galeria.querySelector('div[style*="grid-template-columns"]');
            if (grilla) {
                grilla.style.gridTemplateColumns = esFijo ? 'repeat(3, 1fr)' : '1fr';
            }
        }

        // Si pasa a NO_ACTIVO, limpiar errores de marca/modelo/serie
        if (!esFijo) {
            ['marca','modelo','numero_serie'].forEach(id => {
                const campo = document.getElementById(id);
                if (campo) limpiarError(campo);
            });
        }
    }

    document.querySelectorAll('[data-radio-tipo]').forEach(r => {
        r.addEventListener('change', () => aplicarTipoBien(r.value));
    });

    // ============================================
    // RV — Selector visual (estilo create)
    // ============================================
    function setRvEdit(valor) {
        const si = document.getElementById('rv-si-label');
        const no = document.getElementById('rv-no-label');
        if (!si || !no) return;
        si.classList.remove('selected');
        no.classList.remove('selected');
        si.style.borderColor = '#cbd5e1';
        no.style.borderColor = '#cbd5e1';
        si.style.background = '#fff';
        no.style.background = '#fff';
        if (valor === 'SI') {
            si.classList.add('selected');
            si.style.borderColor = '#16a34a';
            si.style.background = '#dcfce7';
        } else {
            no.classList.add('selected');
            no.style.borderColor = '#dc2626';
            no.style.background = '#fee2e2';
        }
    }

    document.querySelectorAll('[data-radio-rv]').forEach(r => {
        r.addEventListener('change', () => setRvEdit(r.value));
    });

    // Aplicar al cargar
    (function() {
        const checked = document.querySelector('[data-radio-rv]:checked');
        if (checked) setRvEdit(checked.value);
    })();

    // Aplicar al cargar
    (function() {
        const checked = document.querySelector('[data-radio-tipo]:checked');
        aplicarTipoBien(checked ? checked.value : 'ACTIVO_FIJO');
    })();

    // ============================================
    // VALIDACIÓN AL ENVIAR
    // ============================================
    document.getElementById('activo-edit-form')?.addEventListener('submit', function(e) {
        let hayErrores = false;

        // Limpiar errores previos
        document.querySelectorAll('#activo-edit-form .error-msg').forEach(el => {
            el.classList.remove('visible');
            el.textContent = '';
        });
        document.querySelectorAll('#activo-edit-form .input-error').forEach(el => {
            el.classList.remove('input-error');
        });

        function marcar(campoId, mensaje) {
            const campo = document.getElementById(campoId);
            const span = document.querySelector('.error-msg[data-for="' + campoId + '"]');
            if (span) {
                span.textContent = '⚠️ ' + mensaje;
                span.classList.add('visible');
            }
            if (campo) campo.classList.add('input-error');
            hayErrores = true;
        }

        const tipo = document.querySelector('[data-radio-tipo]:checked')?.value || 'ACTIVO_FIJO';
        const esFijo = tipo === 'ACTIVO_FIJO';

        // Campos base obligatorios
        const requeridosBase = [
            { id: 'codigo_activo', msg: 'El código es obligatorio.' },
            { id: 'nombre', msg: 'El nombre es obligatorio.' },
            { id: 'id_categoria', msg: 'Selecciona una categoría.' },
            { id: 'id_ambiente', msg: 'Selecciona un ambiente.' },
            { id: 'id_fuente', msg: 'Selecciona una fuente de financiamiento.' },
            { id: 'estado_fisico', msg: 'Selecciona un estado físico.' },
            { id: 'estado_registro', msg: 'Selecciona un estado de registro.' },
            { id: 'fecha_adquisicion', msg: 'La fecha de adquisición es obligatoria.' },
            { id: 'valor_adquisicion', msg: 'El valor es obligatorio.' },
            { id: 'descripcion', msg: 'La descripción es obligatoria.' },
        ];

        requeridosBase.forEach(({ id, msg }) => {
            const campo = document.getElementById(id);
            if (!campo) return;
            if ((campo.value || '').trim() === '') marcar(id, msg);
        });

        // Custodio (componente x-person-picker)
        const custodio = document.querySelector('[name="id_custodio"]');
        if (custodio && (custodio.value || '').trim() === '') {
            marcar('custodioActivo', 'Selecciona un custodio responsable.');
        }

        // Si es ACTIVO_FIJO
        if (esFijo) {
            ['marca','modelo','numero_serie'].forEach(id => {
                const campo = document.getElementById(id);
                if (!campo) return;
                if ((campo.value || '').trim() === '') {
                    const nombre = id === 'numero_serie' ? 'N° de serie' : id.charAt(0).toUpperCase() + id.slice(1);
                    marcar(id, 'La ' + nombre + ' es obligatoria para Activo Fijo.');
                }
            });
        }

        if (hayErrores) {
            e.preventDefault();
            const primerError = document.querySelector('#activo-edit-form .input-error');
            if (primerError) {
                primerError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                primerError.focus();
            }
            return false;
        }

        // Deshabilitar botón para evitar doble envío
        const btn = document.getElementById('btn-guardar-edit');
        btn.disabled = true;
        btn.textContent = '⏳ Guardando...';
    });
</script>
@endsection
