@props([
    'name',
    'id' => 'personPicker',
    'selectedId' => null,
    'selectedTexto' => null,
    'placeholder' => 'Buscar por nombre, C.I., cargo o número de ítem…',
    'required' => false,
    'ambienteField' => 'id_ambiente',
])

<div class="person-picker" id="{{ $id }}-wrap" data-person-picker>
    <input type="hidden" name="{{ $name }}" id="{{ $id }}-value"
           value="{{ $selectedId }}">

    {{-- Barra de búsqueda --}}
    <div style="position:relative;">
        <input type="search" id="{{ $id }}" autocomplete="off" role="combobox"
               aria-expanded="false" aria-autocomplete="list"
               aria-controls="{{ $id }}-lista"
               placeholder="{{ $placeholder }}"
               value="{{ $selectedTexto }}"
               style="width:100%;padding:10px 40px 10px 12px;border-radius:8px;border:1px solid #cbd5e1;font-size:14px;background:#fff;box-sizing:border-box;">

        <span id="{{ $id }}-toggle"
              title="Ver todas las personas"
              style="position:absolute;right:6px;top:50%;transform:translateY(-50%);cursor:pointer;padding:6px 8px;border-radius:6px;color:#64748b;font-size:13px;user-select:none;">
            ▾
        </span>
    </div>

    {{-- Resumen de la persona seleccionada --}}
    <div id="{{ $id }}-resumen" style="display:none;margin-top:8px;padding:10px 12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;font-size:12px;color:#166534;">
        <strong id="{{ $id }}-resumen-nombre"></strong>
        <div id="{{ $id }}-resumen-detalle" style="font-size:11px;margin-top:2px;"></div>
    </div>

    {{-- Panel con filtros y lista --}}
    <div id="{{ $id }}-panel"
         style="display:none;margin-top:6px;border:1px solid #cbd5e1;border-radius:10px;background:#fff;box-shadow:0 8px 20px rgba(0,0,0,.10);overflow:hidden;">

        <div style="display:flex;gap:6px;flex-wrap:wrap;padding:10px;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
            <select data-filtro="carrera_id"
                    style="flex:1;min-width:110px;padding:7px 9px;border-radius:7px;border:1px solid #cbd5e1;font-size:12px;background:#fff;">
                <option value="">Todas las carreras</option>
            </select>
            <select data-filtro="unidad"
                    style="flex:1;min-width:110px;padding:7px 9px;border-radius:7px;border:1px solid #cbd5e1;font-size:12px;background:#fff;">
                <option value="">Todas las unidades</option>
            </select>
            <select data-filtro="rol"
                    style="flex:1;min-width:110px;padding:7px 9px;border-radius:7px;border:1px solid #cbd5e1;font-size:12px;background:#fff;">
                <option value="">Todos los roles</option>
            </select>
        </div>

        <ul id="{{ $id }}-lista" role="listbox"
            style="list-style:none;margin:0;padding:4px;max-height:280px;overflow-y:auto;"></ul>

        <div id="{{ $id }}-pie"
             style="padding:8px 12px;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:11px;color:#64748b;font-weight:600;"></div>
    </div>

    <span class="error-inline" data-for="{{ $id }}" style="display:none;color:#dc2626;font-size:11px;font-weight:700;margin-top:4px;">
        {{ $required ? 'Debe seleccionar un custodio.' : 'Seleccione una persona de la lista.' }}
    </span>

    @if($required)
        <small style="display:block;color:#64748b;font-size:11px;font-weight:400;margin-top:4px;">
            Obligatorio. Escriba el número de ítem y el sistema mostrará el nombre de quien lo tiene.
        </small>
    @endif
</div>

@once
    @push('scripts')
    <script>
    window.PersonPicker = (function () {
        const URL_BUSQUEDA = @json(route('usuarios.buscar'));
        let filtrosCache = null;

        function pintarError(wrap, hayError) {
            const visible = wrap.querySelector('input[type="search"]');
            const span = wrap.querySelector('.error-inline');
            visible.style.borderColor = hayError ? '#dc2626' : '#cbd5e1';
            visible.style.background = hayError ? '#fef2f2' : '#fff';
            if (span) span.style.display = hayError ? 'block' : 'none';
        }

        function inicializar(wrap) {
            if (wrap.dataset.iniciado) return;
            wrap.dataset.iniciado = '1';
            if (getComputedStyle(wrap).position === 'static') wrap.style.position = 'relative';

            const visible = wrap.querySelector('input[type="search"]');
            const oculto = document.getElementById(visible.id + '-value');
            const panel = document.getElementById(visible.id + '-panel');
            const lista = document.getElementById(visible.id + '-lista');
            const pie = document.getElementById(visible.id + '-pie');
            const resumen = document.getElementById(visible.id + '-resumen');
            const resumenNombre = document.getElementById(visible.id + '-resumen-nombre');
            const resumenDetalle = document.getElementById(visible.id + '-resumen-detalle');
            const selects = Array.from(panel.querySelectorAll('[data-filtro]'));
            const ambiente = document.getElementById(@json($ambienteField));

            let timer = null;
            let indice = -1;

            function filtrosActivos() {
                const params = new URLSearchParams({ q: visible.value.trim() });
                if (ambiente && ambiente.value) params.set('ambiente_id', ambiente.value);
                selects.forEach((s) => { if (s.value) params.set(s.dataset.filtro, s.value); });
                return params;
            }

            function mostrarResumen(usuario) {
                if (!usuario) { resumen.style.display = 'none'; return; }
                resumen.style.display = 'block';
                resumenNombre.textContent = usuario.nombre_completo;
                const bits = ['C.I. ' + usuario.ci];
                if (usuario.cargo) bits.push(usuario.cargo);
                if (usuario.unidad) bits.push(usuario.unidad);
                if (usuario.carrera) bits.push(usuario.carrera);
                resumenDetalle.textContent = bits.join(' · ')
                    + ((usuario.items || []).length ? '  |  Ítem ' + usuario.items.join(', ') : '  |  Sin ítem asignado');
            }

            function cerrar() {
                panel.style.display = 'none';
                indice = -1;
                visible.setAttribute('aria-expanded', 'false');
            }

            function seleccionar(usuario) {
                oculto.value = usuario.id_usuario;
                visible.value = usuario.texto;
                mostrarResumen(usuario);
                pintarError(wrap, false);
                cerrar();
                visible.dispatchEvent(new CustomEvent('person-picker:selected', { bubbles: true, detail: usuario }));
            }

            function marcar(nuevo) {
                const opciones = lista.querySelectorAll('li[data-usuario]');
                if (!opciones.length) return;
                if (nuevo < 0) indice = opciones.length - 1;
                else if (nuevo >= opciones.length) indice = 0;
                opciones.forEach((li, i) => {
                    li.style.background = i === indice ? '#eff6ff' : 'transparent';
                });
                opciones[indice].scrollIntoView({ block: 'nearest' });
            }

            function elegirIndice() {
                const li = lista.querySelectorAll('li[data-usuario]')[indice];
                if (li) seleccionar(JSON.parse(li.dataset.usuario));
            }

            function pintar(respuesta) {
                if (respuesta.filtros) {
                    filtrosCache = respuesta.filtros;
                    selects.forEach((s) => {
                        const listaF = respuesta.filtros[s.dataset.filtro] || [];
                        const actual = s.value;
                        s.innerHTML = '<option value="">'
                            + ({ carrera_id: 'Todas las carreras', unidad: 'Todas las unidades', rol: 'Todos los roles' })[s.dataset.filtro]
                            + '</option>';
                        listaF.forEach((o) => {
                            const opt = document.createElement('option');
                            opt.value = o.valor;
                            opt.textContent = o.texto;
                            s.appendChild(opt);
                        });
                        s.value = actual;
                    });
                }

                const usuarios = respuesta.resultados || [];
                lista.innerHTML = '';
                let grupoActual = null;

                if (!usuarios.length) {
                    const li = document.createElement('li');
                    li.textContent = respuesta.aviso || 'Sin resultados.';
                    li.style.cssText = 'padding:12px;font-size:12px;font-style:italic;color:'
                        + (respuesta.aviso ? '#b45309' : '#94a3b8') + ';';
                    lista.appendChild(li);
                    pie.textContent = '';
                    return;
                }

                usuarios.forEach((usuario) => {
                    const grupo = usuario.asignado_en_ambiente ? 'ambiente' : 'otros';
                    if (grupo !== grupoActual) {
                        const encabezado = document.createElement('li');
                        encabezado.textContent = grupo === 'ambiente'
                            ? 'Custodios asignados en este ambiente'
                            : 'Otros usuarios activos';
                        encabezado.style.cssText = 'padding:7px 10px 5px;color:#475569;font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.04em;background:#f8fafc;border-bottom:1px solid #e2e8f0;';
                        encabezado.setAttribute('aria-hidden', 'true');
                        lista.appendChild(encabezado);
                        grupoActual = grupo;
                    }

                    const li = document.createElement('li');
                    const seleccionado = String(oculto.value) === String(usuario.id_usuario);
                    li.setAttribute('role', 'option');
                    li.setAttribute('aria-selected', seleccionado ? 'true' : 'false');
                    li.dataset.usuario = JSON.stringify(usuario);
                    li.style.cssText = 'padding:8px 10px;border-radius:6px;cursor:pointer;color:#0f172a;'
                        + (seleccionado ? 'background:#dcfce7;border:1px solid #86efac;' : 'border:1px solid transparent;');
                    li.addEventListener('mouseenter', () => { li.style.background = seleccionado ? '#bbf7d0' : '#eff6ff'; });
                    li.addEventListener('mouseleave', () => { li.style.background = seleccionado ? '#dcfce7' : 'transparent'; });
                    li.addEventListener('click', () => seleccionar(usuario));

                    const items = (usuario.items || []).length
                        ? '<div style="font-size:11px;color:#16a34a;font-weight:800;margin-top:2px;">Ítem ' + usuario.items.join(', ') + '</div>'
                        : '<div style="font-size:11px;color:#94a3b8;margin-top:2px;">Sin ítem</div>';

                    const carrera = usuario.carrera
                        ? '<span style="display:inline-block;background:#f3e8ff;color:#7c3aed;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:800;">' + usuario.carrera + '</span> '
                        : '';
                    const ambienteBadge = usuario.asignado_en_ambiente
                        ? '<span style="display:inline-block;background:#dcfce7;color:#166534;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:800;margin-left:5px;">Asignado aquí</span>'
                        : '';

                    li.innerHTML =
                        '<div style="font-size:13px;font-weight:700;">' + (seleccionado ? '✓ ' : '') + carrera + usuario.nombre_completo + ambienteBadge + '</div>' +
                        '<div style="font-size:11px;color:#64748b;">C.I. ' + usuario.ci
                            + (usuario.cargo ? ' · ' + usuario.cargo : '')
                            + (usuario.unidad ? ' · ' + usuario.unidad : '') + '</div>' +
                        items;

                    lista.appendChild(li);
                });

                pie.textContent = usuarios.length + (usuarios.length === 1 ? ' persona' : ' personas')
                    + (usuarios.length >= 60 ? ' (mostrando las primeras 60, acorte la búsqueda)' : '');
            }

            function cargar() {
                fetch(URL_BUSQUEDA + '?' + filtrosActivos().toString(), {
                    headers: { 'Accept': 'application/json' }
                })
                    .then((r) => r.json())
                    .then((respuesta) => { pintar(respuesta); panel.style.display = 'block'; visible.setAttribute('aria-expanded', 'true'); })
                    .catch(() => { lista.innerHTML = '<li style="padding:12px;font-size:12px;color:#dc2626;">Error al buscar.</li>'; panel.style.display = 'block'; });
            }

            visible.addEventListener('focus', () => { if (panel.style.display === 'none') cargar(); });
            document.getElementById(visible.id + '-toggle').addEventListener('click', () => {
                panel.style.display === 'none' ? cargar() : cerrar();
            });

            visible.addEventListener('input', () => {
                if (oculto.value) { oculto.value = ''; mostrarResumen(null); }
                pintarError(wrap, false);
                clearTimeout(timer);
                if (visible.value.trim().length < 1 && selects.every((s) => !s.value)) {
                    panel.style.display = 'none';
                    return;
                }
                timer = setTimeout(cargar, 220);
            });

            selects.forEach((s) => s.addEventListener('change', cargar));
            if (ambiente) ambiente.addEventListener('change', () => {
                if (panel.style.display !== 'none') cargar();
            });

            visible.addEventListener('keydown', (e) => {
                if (panel.style.display === 'none') return;
                if (e.key === 'ArrowDown') { e.preventDefault(); marcar(indice + 1); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); marcar(indice - 1); }
                else if (e.key === 'Enter') { e.preventDefault(); elegirIndice(); }
                else if (e.key === 'Escape') { cerrar(); }
            });

            document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) cerrar(); });
        }

        document.addEventListener('DOMContentLoaded', () =>
            Array.from(document.querySelectorAll('[data-person-picker]')).forEach(inicializar));

        return {
            inicializar,
            valor: (id) => document.getElementById(id + '-value'),
            marcarError: (id, hayError) => {
                const wrap = document.getElementById(id + '-wrap');
                if (wrap) pintarError(wrap, hayError);
            }
        };
    })();
    </script>
    @endpush
@endonce