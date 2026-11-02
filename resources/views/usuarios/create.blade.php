@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">

    <div style="max-width:800px;margin:0 auto;background:white;padding:28px;border-radius:18px;box-shadow:0 4px 12px rgba(0,0,0,.05);">

        {{-- ENCABEZADO --}}
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <div>
                <h2 style="font-size:24px;font-weight:800;color:#0f172a;margin:0;">Nuevo Usuario</h2>
                <p style="color:#64748b;font-size:14px;margin:4px 0 0 0;">
                    Registra un nuevo usuario y asígnale su rol y permisos.
                </p>
            </div>
            <a href="{{ route('usuarios.index') }}"
               style="background:#e2e8f0;color:#334155;padding:8px 16px;border-radius:10px;font-weight:700;text-decoration:none;font-size:14px;">
                ← Volver
            </a>
        </div>

        {{-- ERRORES --}}
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

        <form method="POST" action="{{ route('usuarios.store') }}">
            @csrf

            <style>
                .form-grid { display:grid;grid-template-columns:1fr 1fr;gap:16px; }
                .form-grid label { display:flex;flex-direction:column;font-size:12px;font-weight:700;color:#334155;gap:4px; }
                .form-grid label.full { grid-column:span 2; }
                .form-grid input, .form-grid select {
                    width:100%;padding:10px 12px;border-radius:8px;border:1px solid #cbd5e1;font-size:14px;background:#fff;box-sizing:border-box;
                }
                .form-grid input:focus, .form-grid select:focus {
                    outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1);
                }
                .section-title {
                    grid-column:span 2;font-size:11px;font-weight:800;color:#2563eb;text-transform:uppercase;
                    margin:8px 0 -6px 0;padding-bottom:6px;border-bottom:1px solid #e2e8f0;
                }
                .input-error { border-color:#dc2626 !important; background:#fef2f2 !important; }
                .error-msg { color:#dc2626; font-size:11px; font-weight:600; margin-top:2px; display:block; }
            </style>

            <div class="form-grid">

                {{-- IDENTIFICACIÓN --}}
                <div class="section-title">Identificación</div>

                <label>C.I. *
                    <input name="ci" maxlength="20"
                           class="{{ $errors->has('ci') ? 'input-error' : '' }}"
                           value="{{ old('ci') }}">
                    @error('ci') <span class="error-msg">{{ $message }}</span> @enderror
                </label>

                <label>Email *
                    <input name="email" type="email"
                           class="{{ $errors->has('email') ? 'input-error' : '' }}"
                           value="{{ old('email') }}">
                    @error('email') <span class="error-msg">{{ $message }}</span> @enderror
                </label>

                <label class="full">Nombre Completo *
                    <input name="nombre_completo" maxlength="150"
                           class="{{ $errors->has('nombre_completo') ? 'input-error' : '' }}"
                           value="{{ old('nombre_completo') }}"
                           oninput="this.value = this.value.toLocaleUpperCase('es-ES')">
                    <small style="display:block;margin-top:4px;color:#2563eb;font-size:12px;">
                        Aviso: el nombre se guardará en MAYÚSCULAS automáticamente.
                    </small>
                    @error('nombre_completo') <span class="error-msg">{{ $message }}</span> @enderror
                </label>

                {{-- CARGO Y UNIDAD --}}
                <div class="section-title">Cargo y Unidad</div>

                <label>Cargo *
                    <input name="cargo" maxlength="100"
                           class="{{ $errors->has('cargo') ? 'input-error' : '' }}"
                           value="{{ old('cargo') }}">
                    @error('cargo') <span class="error-msg">{{ $message }}</span> @enderror
                </label>

                <label>Unidad *
                    <input name="unidad" maxlength="100"
                           class="{{ $errors->has('unidad') ? 'input-error' : '' }}"
                           value="{{ old('unidad') }}">
                    @error('unidad') <span class="error-msg">{{ $message }}</span> @enderror
                </label>

                {{-- ROL Y CARRERA --}}
                <div class="section-title">Rol y Asignación</div>

                <label>Rol *
                    <select name="rol" class="{{ $errors->has('rol') ? 'input-error' : '' }}">
                        <option value="">— Seleccione rol —</option>
                        <option value="ADMINISTRADOR"     {{ old('rol') == 'ADMINISTRADOR'     ? 'selected' : '' }}>Administrador</option>
                        <option value="INVENTARIADOR"     {{ old('rol') == 'INVENTARIADOR'     ? 'selected' : '' }}>Inventariador</option>
                        <option value="AYUDANTE"          {{ old('rol') == 'AYUDANTE'          ? 'selected' : '' }}>Ayudante</option>
                        <option value="DOCENTE_CUSTODIO"  {{ old('rol') == 'DOCENTE_CUSTODIO'  ? 'selected' : '' }}>Docente Custodio</option>
                        <option value="JEFE_CARRERA"      {{ old('rol') == 'JEFE_CARRERA'      ? 'selected' : '' }}>Jefe de Carrera</option>
                        <option value="RECTOR"            {{ old('rol') == 'RECTOR'            ? 'selected' : '' }}>Rector</option>
                    </select>
                    @error('rol') <span class="error-msg">{{ $message }}</span> @enderror
                </label>

                <label>Carrera
                    <select name="carrera_id" class="{{ $errors->has('carrera_id') ? 'input-error' : '' }}">
                        <option value="">— Sin carrera —</option>
                        @foreach ($carreras as $c)
                            <option value="{{ $c->id_carrera }}"
                                {{ old('carrera_id') == $c->id_carrera ? 'selected' : '' }}>
                                {{ $c->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('carrera_id') <span class="error-msg">{{ $message }}</span> @enderror
                </label>

                {{-- ÍTEMS --}}
                <div class="section-title">Ítems</div>

                <div class="full" style="padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;">
                    <div style="font-size:12px;font-weight:700;color:#166534;margin-bottom:10px;">
                        Números de ítem que tiene asignados
                    </div>

                    <div id="items-container" style="display:flex;flex-direction:column;gap:8px;">
                        @php $itemsSeleccionados = array_filter((array) old('items', [])); @endphp
                        @foreach($itemsSeleccionados as $num)
                            <div class="item-fila" style="display:flex;gap:8px;align-items:center;">
                                <input name="items[]" value="{{ $num }}" list="lista-items"
                                       style="flex:1;padding:9px 11px;border-radius:8px;border:1px solid #cbd5e1;font-size:14px;">
                                <button type="button" class="quitar-item"
                                        style="background:#fee2e2;color:#dc2626;border:1px solid #fecaca;padding:9px 12px;border-radius:8px;font-weight:800;cursor:pointer;">Quitar</button>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" id="agregar-item"
                            style="margin-top:8px;background:#16a34a;color:white;border:none;padding:8px 14px;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px;">
                        + Agregar ítem
                    </button>

                    <datalist id="lista-items">
                        @foreach($items as $it)
                            <option value="{{ $it->numero_item }}">{{ $it->descripcion }}</option>
                        @endforeach
                    </datalist>

                    <small style="display:block;margin-top:8px;color:#166534;font-size:11px;">
                        Puedes escribir uno nuevo y se creará automáticamente. Un ítem puede tener 2, 3 o más custodios:
                        los activos pertenecen al ítem, no a la persona.
                    </small>
                    @error('items') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

                {{-- PERMISO DE VERIFICACIÓN --}}
                <div class="section-title">Permiso de Verificación</div>

                <div class="full" style="padding:12px;background:#eff6ff;border:1px solid #dbeafe;border-radius:10px;">
                    <label style="display:flex;align-items:center;cursor:pointer;font-size:13px;color:#1e40af;gap:8px;flex-direction:row;">
                        <input type="checkbox"
                               name="puede_verificar"
                               value="1"
                               {{ old('puede_verificar') ? 'checked' : '' }}
                               style="width:18px;height:18px;">
                        <span style="font-weight:700;">¿Puede verificar activos?</span>
                    </label>
                    <small style="display:block;margin-top:6px;color:#64748b;font-size:11px;">
                        Si está marcado, este usuario podrá acceder al módulo de <strong>Verificación Física</strong>.
                    </small>
                </div>

            </div>

            {{-- ACCIONES --}}
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:24px;border-top:1px solid #e2e8f0;padding-top:18px;">
                <a href="{{ route('usuarios.index') }}"
                   style="background:#e2e8f0;color:#475569;border:none;padding:11px 22px;border-radius:8px;font-weight:700;text-decoration:none;display:inline-block;">
                    Cancelar
                </a>
                <button type="submit"
                    style="background:#2563eb;color:white;border:none;padding:11px 22px;border-radius:8px;font-weight:700;cursor:pointer;">
                    💾 Crear Usuario
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var contenedor = document.getElementById('items-container');

    function nuevaFila() {
        var fila = document.createElement('div');
        fila.className = 'item-fila';
        fila.style.cssText = 'display:flex;gap:8px;align-items:center;';
        fila.innerHTML = '<input name="items[]" list="lista-items" style="flex:1;padding:9px 11px;border-radius:8px;border:1px solid #cbd5e1;font-size:14px;">' +
                        '<button type="button" class="quitar-item" style="background:#fee2e2;color:#dc2626;border:1px solid #fecaca;padding:9px 12px;border-radius:8px;font-weight:800;cursor:pointer;">Quitar</button>';
        contenedor.appendChild(fila);
    }

    document.getElementById('agregar-item').addEventListener('click', nuevaFila);

    contenedor.addEventListener('click', function (e) {
        if (e.target.classList.contains('quitar-item')) {
            e.target.closest('.item-fila').remove();
        }
    });
})();
</script>
@endsection
