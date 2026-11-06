@extends('layouts.app')

@section('content')

    <section class="panel" style="padding: 24px;">

        {{-- ============ ENCABEZADO ============ --}}
        <div style="background:linear-gradient(135deg,#0f766e 0%,#0d9488 100%);padding:28px;border-radius:16px;color:white;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px;margin-bottom:24px;box-shadow:0 10px 15px -3px rgba(15,118,110,.2);">
            <div style="flex:1;min-width:280px;">
                <span style="background:rgba(255,255,255,.2);padding:5px 12px;border-radius:20px;font-size:11px;font-weight:800;text-transform:uppercase;">Inventario</span>
                <h2 style="font-size:26px;font-weight:800;margin:8px 0 4px 0;">Inventario de Activos</h2>
                <p style="opacity:.9;font-size:14px;margin:0;">Consulta y administra los activos fijos y no activos del sistema.</p>
            </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            @if (auth()->check() && auth()->user()->esAdmin())
                <a href="{{ route('activos.create') }}"
                    style="background:#10b981;color:white;padding:10px 18px;border-radius:10px;font-weight:700;border:none;cursor:pointer;font-size:14px;display:inline-flex;align-items:center;gap:6px;transition:all .2s;text-decoration:none;"
                    onmouseover="this.style.background='#059669';" onmouseout="this.style.background='#10b981';">
                    ➕ Nuevo Activo
                </a>
                <a href="{{ route('activos.etiquetas') }}" target="_blank"
                    style="background:#7c3aed;color:white;padding:10px 18px;border-radius:10px;font-weight:700;border:none;cursor:pointer;font-size:14px;display:inline-flex;align-items:center;gap:6px;transition:all .2s;text-decoration:none;"
                    onmouseover="this.style.background='#6b21a8';" onmouseout="this.style.background='#7c3aed';">
                    🏷️ Imprimir Etiquetas
                </a>
            @endif
        </div>

        {{-- ============ TARJETAS DE ESTADÍSTICAS (dentro del encabezado) ============ --}}
        <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
            <div style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);padding:14px 12px;border-radius:10px;text-align:center;min-width:120px;">
                <div style="font-size:22px;font-weight:800;">{{ $totales['TODOS'] ?? 0 }}</div>
                <div style="font-size:10px;font-weight:700;opacity:.9;text-transform:uppercase;margin-top:2px;">Total</div>
            </div>
            <div style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);padding:14px 12px;border-radius:10px;text-align:center;min-width:120px;">
                <div style="font-size:22px;font-weight:800;color:#86efac;">{{ $totales['ACTIVO_FIJO'] ?? 0 }}</div>
                <div style="font-size:10px;font-weight:700;opacity:.9;text-transform:uppercase;margin-top:2px;">Activos Fijos</div>
            </div>
            <div style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);padding:14px 12px;border-radius:10px;text-align:center;min-width:120px;">
                <div style="font-size:22px;font-weight:800;color:#fde047;">{{ $totales['NO_ACTIVO'] ?? 0 }}</div>
                <div style="font-size:10px;font-weight:700;opacity:.9;text-transform:uppercase;margin-top:2px;">No Activos</div>
            </div>
        </div>
    </div>

        {{-- ============ FILTROS ============ --}}
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:20px;">
            <div style="display:flex;flex-direction:column;gap:6px;">
                <label style="font-size:11px;font-weight:700;color:#334155;text-transform:uppercase;">Carrera</label>
                <select name="id_carrera" style="width:160px;padding:8px 12px;border-radius:8px;border:1.5px solid #e2e8f0;background:#f8fafc;font-size:13px;">
                    <option value="">— Todas las carreras —</option>
                    @foreach($carreras as $car)
                        <option value="{{ $car->id_carrera }}" {{ request('id_carrera') == $car->id_carrera ? 'selected' : '' }}>{{ $car->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                <label style="font-size:11px;font-weight:700;color:#334155;text-transform:uppercase;">Ambiente</label>
                <select name="id_ambiente" style="width:170px;padding:8px 12px;border-radius:8px;border:1.5px solid #e2e8f0;background:#f8fafc;font-size:13px;">
                    <option value="">— Todos los ambientes —</option>
                    @foreach($ambientes as $amb)
                        <option value="{{ $amb->id_ambiente }}" {{ request('id_ambiente') == $amb->id_ambiente ? 'selected' : '' }}>{{ $amb->nombre }} ({{ optional($amb->carrera)->nombre }})</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                <label style="font-size:11px;font-weight:700;color:#334155;text-transform:uppercase;">Categoría</label>
                <select name="id_categoria" style="width:170px;padding:8px 12px;border-radius:8px;border:1.5px solid #e2e8f0;background:#f8fafc;font-size:13px;">
                    <option value="">— Todas las categorías —</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat->id_categoria }}" {{ request('id_categoria') == $cat->id_categoria ? 'selected' : '' }}>{{ $cat->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                <label style="font-size:11px;font-weight:700;color:#334155;text-transform:uppercase;">Tipo de Bien</label>
                <select name="tipo_bien" style="width:150px;padding:8px 12px;border-radius:8px;border:1.5px solid #e2e8f0;background:#f8fafc;font-size:13px;">
                    <option value="TODOS" {{ request('tipo_bien','TODOS') == 'TODOS' ? 'selected' : '' }}>Todos</option>
                    <option value="ACTIVO_FIJO" {{ request('tipo_bien') == 'ACTIVO_FIJO' ? 'selected' : '' }}>Activo Fijo</option>
                    <option value="NO_ACTIVO" {{ request('tipo_bien') == 'NO_ACTIVO' ? 'selected' : '' }}>No Activo</option>
                </select>
            </div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                <label style="font-size:11px;font-weight:700;color:#334155;text-transform:uppercase;">Buscar</label>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Código actual o antiguo, nombre, serie, marca, modelo..." style="width:260px;padding:8px 12px;border-radius:8px;border:1.5px solid #e2e8f0;background:#f8fafc;font-size:13px;">
            </div>
            <button type="submit" style="padding:8px 20px;background:#2563eb;color:white;border:none;border-radius:8px;font-weight:700;cursor:pointer;">
                🔍 Filtrar
            </button>
            <a href="{{ route('activos.index') }}" style="padding:8px 20px;background:#e2e8f0;color:#475569;border:none;border-radius:8px;font-weight:700;cursor:pointer;text-decoration:none;">
                Limpiar
            </a>
        </form>

        {{-- ============ LISTADO DE ACTIVOS ============ --}}
        <div id="activos-container"
            style="background:white;border-radius:16px;border:1px solid #e2e8f0;padding:25px;box-shadow:0 4px 6px -1px rgba(0,0,0,.05);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
                <h3 style="font-size:18px;font-weight:800;color:#0f172a;margin:0;">
                    📦 Activos individuales (<span id="total-activos">{{ $activos->count() }}</span>)
                </h3>
                @if (auth()->check() && auth()->user()->esAdmin())
                    <form id="bulk-delete-form" method="POST" action="{{ route('activos.destroyMany') }}" onsubmit="return prepararEliminacionMasiva(event);" style="margin:0;">
                        @csrf
                        @method('DELETE')
                        <div id="bulk-delete-inputs"></div>
                    </form>
                    <button type="button" id="bulk-delete-button" disabled
                        onclick="enviarEliminacionMasiva()"
                        style="padding:9px 14px;background:#dc2626;color:#fff;border:0;border-radius:8px;font-weight:800;cursor:pointer;opacity:.5;">
                        🗑️ Eliminar seleccionados
                    </button>
                @endif
            </div>

            @if ($activos->count() > 0)
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:13px;vertical-align:middle;">
                        <thead>
                            <tr style="background:#f1f5f9;color:#475569;text-transform:uppercase;font-size:11px;font-weight:800;">
                                <th style="padding:14px 10px;text-align:center;width:45px;">
                                    @if (auth()->check() && auth()->user()->esAdmin())
                                        <input type="checkbox" id="select-all-activos" title="Seleccionar todos"
                                            onchange="seleccionarTodosActivos(this.checked)">
                                    @endif
                                    Nº
                                </th>
                                <th style="padding:14px 10px;text-align:center;width:65px;">QR</th>
                                <th style="padding:14px 10px;text-align:center;width:65px;">IMAGEN</th>
                                <th style="padding:14px 10px;text-align:left;">CÓDIGO</th>
                                <th style="padding:14px 10px;text-align:left;">CARACTERÍSTICAS</th>
                                <th style="padding:14px 10px;text-align:left;">DESCRIPCIÓN</th>
                                <th style="padding:14px 10px;text-align:left;">CUSTODIO</th>
                                <th style="padding:14px 10px;text-align:left;width:110px;">FECHA INGRESO</th>
                                <th style="padding:14px 10px;text-align:center;width:90px;">ESTADO</th>
                                <th style="padding:14px 10px;text-align:center;width:220px;">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody id="activos-tbody">
                            @foreach ($activos as $i => $a)
                                <tr style="border-bottom:1px solid #e2e8f0;">
                                    <td style="padding:12px 10px;text-align:center;color:#64748b;font-weight:800;">
                                        @if (auth()->check() && auth()->user()->esAdmin())
                                            <input type="checkbox" value="{{ $a->id_activo }}" class="activo-checkbox"
                                                onchange="actualizarBotonEliminacion()">
                                        @endif
                                        {{ $i + 1 }}
                                    </td>
                                    <td style="padding:12px 10px;text-align:center;">
                                        @if ($a->ruta_qr)
                                            <img src="{{ asset('storage/' . $a->ruta_qr) }}" alt="QR"
                                                style="width:42px;height:42px;object-fit:contain;display:block;margin:0 auto;">
                                        @else
                                            <span style="font-size:11px;color:#94a3b8;">Sin QR</span>
                                        @endif
                                    </td>
                                    <td style="padding:12px 10px;text-align:center;">
                                        @if ($a->imagen)
                                            <img src="{{ asset('storage/' . $a->imagen) }}" alt="Foto"
                                                style="width:42px;height:42px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;display:block;margin:0 auto;">
                                        @else
                                            <span style="font-size:11px;color:#94a3b8;font-style:italic;">Sin foto</span>
                                        @endif
                                    </td>
                                    <td style="padding:12px 10px;">
                                        <div style="font-weight:800;color:#0f172a;font-size:13px;">{{ $a->codigo_activo }}</div>
                                        @if ($a->codigo_antiguo)
                                            <div style="font-size:11px;color:#64748b;margin-top:2px;">Código antiguo: {{ $a->codigo_antiguo }}</div>
                                        @endif
                                        <div style="font-size:11px;color:#64748b;margin-top:2px;">Serie: {{ $a->numero_serie ?? 'S/N' }}</div>
                                    </td>
                                    <td style="padding:12px 10px;">
                                        <div style="font-weight:700;color:#1e293b;font-size:13px;">{{ $a->nombre }}</div>
                                        <div style="font-size:11px;color:#64748b;margin-top:2px;">{{ $a->marca }} {{ $a->modelo }}</div>
                                    </td>
                                    <td style="padding:12px 10px;">
                                        @if (!empty($a->descripcion))
                                            <div style="font-size:11px;color:#475569;font-style:italic;background:#f8fafc;padding:6px 10px;border-radius:6px;border:1px solid #e2e8f0;line-height:1.35;max-width:220px;">
                                                {{ $a->descripcion }}
                                            </div>
                                        @else
                                            <span style="font-size:11px;color:#94a3b8;font-style:italic;">Sin descripción</span>
                                        @endif
                                    </td>
                                    <td style="padding:12px 10px;">
                                        <div style="font-size:12px;color:#0f172a;font-weight:700;">{{ $a->custodio->nombre_completo ?? 'Sin asignar' }}</div>
                                        <div style="font-size:11px;color:#64748b;margin-top:2px;">{{ $a->fuente->nombre ?? ($a->fuente->codigo ?? '—') }}</div>
                                    </td>
                                    <td style="padding:12px 10px;font-size:12px;color:#334155;font-weight:600;">
                                        {{ $a->fecha_adquisicion ? \Carbon\Carbon::parse($a->fecha_adquisicion)->format('d/m/Y') : ($a->created_at ? $a->created_at->format('d/m/Y') : '—') }}
                                    </td>
                                    <td style="padding:12px 10px;text-align:center;">
                                        @php
                                            $badge = match ($a->estado_fisico) {
                                                'B' => ['bg' => '#dcfce7', 'color' => '#166534', 'texto' => 'BUENO'],
                                                'R' => ['bg' => '#fef3c7', 'color' => '#92400e', 'texto' => 'REGULAR'],
                                                'M' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'texto' => 'MALO'],
                                                'FF' => ['bg' => '#6b7280', 'color' => 'white', 'texto' => 'F.F.'],
                                                default => ['bg' => '#f1f5f9', 'color' => '#475569', 'texto' => '—'],
                                            };
                                        @endphp
                                        <span style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }};padding:4px 8px;border-radius:6px;font-weight:800;font-size:10px;display:inline-block;">
                                            {{ $badge['texto'] }}
                                        </span>
                                    </td>
                                    <td style="padding:12px 10px;text-align:center;">
                                        <div style="display:flex;gap:4px;justify-content:center;align-items:center;flex-wrap:wrap;">
                                            <a href="{{ route('activos.tarjeta', $a->id_activo) }}" target="_blank" title="Ver tarjeta"
                                                style="width:30px;height:30px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;font-size:13px;">👁️</a>

                                            @if (auth()->check() && auth()->user()->esAdmin())
                                                <a href="{{ route('activos.edit', $a->id_activo) }}" title="Editar"
                                                    style="width:30px;height:30px;background:#fef9c3;color:#ca8a04;border:1px solid #fde047;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:13px;">
                                                    ✏️
                                                </a>
                                            @endif

                                            <a href="{{ route('activos.descargar_qr', $a->id_activo) }}" title="Descargar QR"
                                                style="width:30px;height:30px;background:#f3e8ff;color:#7c3aed;border:1px solid #ddd6fe;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;font-size:13px;">⬇️</a>

                                            @if (auth()->check() && auth()->user()->esAdmin())
                                            <a href="{{ route('activos.etiqueta', $a->id_activo) }}" target="_blank" title="Etiqueta"
                                                style="width:30px;height:30px;background:#fef3c7;color:#f59e0b;border:1px solid #fde68a;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;font-size:13px;">🏷️</a>
                                            @endif
                                            @if (auth()->check() && auth()->user()->esAdmin())
                                            <a href="{{ route('actas.recepcion.pdf', $a->id_activo) }}" target="_blank" title="Acta de Alta"
                                                style="width:30px;height:30px;background:#d1fae5;color:#059669;border:1px solid #a7f3d0;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;font-size:13px;">📋</a>
                                            @endif
                                            @if (auth()->check() && auth()->user()->esAdmin())
                                                <button type="button"
                                                    onclick="confirmarEliminarActivo({{ $a->id_activo }}, '{{ addslashes($a->codigo_activo) }}', '{{ addslashes($a->nombre) }}')"
                                                    title="Eliminar"
                                                    style="width:30px;height:30px;background:#dc2626;color:white;border:1px solid #b91c1c;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:13px;">
                                                    🗑️
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="text-align:center;padding:60px 20px;color:#94a3b8;font-weight:600;">
                    <div style="font-size:48px;margin-bottom:12px;">📦</div>
                    <h3 style="margin:0 0 6px 0;font-size:16px;color:#64748b;">No hay activos registrados</h3>
                    <p style="margin:0;font-size:13px;">Registra un activo nuevo para comenzar.</p>
                </div>
            @endif
        </div>

    </section>

    {{-- Scripts del modal de activo (mismo que grupo/show) --}}
    <script>
        function openActivoModal() {
            document.getElementById('activo-modal')?.style.setProperty('display', 'flex', 'important');
        }

        function openEditActivoModal(idActivo) {
            fetch(`{{ url('/activos') }}/${idActivo}/edit`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin',
            })
            .then(res => { if (!res.ok) throw new Error('Error'); return res.json(); })
            .then(data => {
                document.getElementById('edit-id-activo').value = data.id_activo;
                document.getElementById('edit-codigo-activo').value = data.codigo_activo || '';
                document.getElementById('edit-id-categoria').value = data.id_categoria || '';
                document.getElementById('edit-cantidad').value = data.cantidad || 1;
                document.getElementById('edit-fecha-adquisicion').value = data.fecha_adquisicion ? data.fecha_adquisicion.substring(0, 10) : '{{ date('Y-m-d') }}';
                document.getElementById('edit-nombre').value = data.nombre || '';
                document.getElementById('edit-input-marca').value = data.marca || '';
                document.getElementById('edit-input-modelo').value = data.modelo || '';
                document.getElementById('edit-input-serie').value = data.numero_serie || '';
                document.getElementById('edit-id-fuente').value = data.id_fuente || '';
                document.getElementById('edit-estado-fisico').value = data.estado_fisico || 'B';
                document.getElementById('edit-valor-adquisicion').value = data.valor_adquisicion || 0;
                document.getElementById('edit-descripcion').value = data.descripcion || '';
                document.getElementById('edit-select-tipo-bien').value = data.tipo_bien || 'ACTIVO_FIJO';
                document.getElementById('edit-rv-si').checked = data.rv === 'SI';
                document.getElementById('edit-rv-no').checked = data.rv !== 'SI';

                cambiarTipoBienEdit(data.tipo_bien || 'ACTIVO_FIJO');
                setRvEdit(data.rv === 'SI' ? 'SI' : 'NO');

                cambiarTipoBienEdit(data.tipo_bien || 'ACTIVO_FIJO');
                limpiarTodosLosErrores();

                document.getElementById('edit-activo-modal-form').action = `{{ url('/activos') }}/${idActivo}`;
                document.getElementById('edit-activo-modal').style.display = 'flex';
            })
            .catch(err => { console.error(err); mostrarToast('❌ Error al cargar el activo', 'error'); });
        }

        function cambiarTipoBienEdit(tipo) { aplicarTipoBien(tipo, 'edit'); }

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

        function actualizarBotonEliminacion() {
            const seleccionados = document.querySelectorAll('.activo-checkbox:checked').length;
            const button = document.getElementById('bulk-delete-button');
            const selectAll = document.getElementById('select-all-activos');
            const total = document.querySelectorAll('.activo-checkbox').length;

            if (button) {
                button.disabled = seleccionados === 0;
                button.style.opacity = seleccionados === 0 ? '0.5' : '1';
                button.style.cursor = seleccionados === 0 ? 'not-allowed' : 'pointer';
            }

            if (selectAll) {
                selectAll.checked = total > 0 && seleccionados === total;
                selectAll.indeterminate = seleccionados > 0 && seleccionados < total;
            }
        }

        function seleccionarTodosActivos(seleccionar) {
            document.querySelectorAll('.activo-checkbox').forEach((checkbox) => {
                checkbox.checked = seleccionar;
            });
            actualizarBotonEliminacion();
        }

        document.addEventListener('DOMContentLoaded', actualizarBotonEliminacion);

        function prepararEliminacionMasiva(event) {
            const seleccionados = Array.from(document.querySelectorAll('.activo-checkbox:checked'));
            if (seleccionados.length === 0) {
                event.preventDefault();
                alert('Seleccione al menos un activo.');
                return false;
            }

            return confirm('¿Eliminar los activos seleccionados? Esta acción no se puede deshacer.');
        }

        function enviarEliminacionMasiva() {
            const seleccionados = Array.from(document.querySelectorAll('.activo-checkbox:checked'));
            if (seleccionados.length === 0) {
                alert('Seleccione al menos un activo.');
                return;
            }

            if (!confirm('¿Eliminar los activos seleccionados? Esta acción no se puede deshacer.')) {
                return;
            }

            const contenedor = document.getElementById('bulk-delete-inputs');
            contenedor.innerHTML = '';
            seleccionados.forEach((checkbox) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'activos[]';
                input.value = checkbox.value;
                contenedor.appendChild(input);
            });

            document.getElementById('bulk-delete-form').submit();
        }
    </script>

@endsection
