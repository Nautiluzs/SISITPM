@extends('layouts.app')

@section('content')
<div style="display:grid;gap:20px;">
    @if (session('success'))
        <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#166534;padding:14px 20px;border-radius:12px;font-weight:700;">✓ {{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:14px 20px;border-radius:12px;font-weight:700;">✗ {{ session('error') }}</div>
    @endif

    <nav aria-label="Ruta de navegación" style="background:white;border:1px solid #e2e8f0;padding:14px 20px;border-radius:14px;color:#475569;font-size:13px;font-weight:700;">
        <a href="{{ route('carreras.show', $ambiente->id_carrera) }}" style="color:#2563eb;text-decoration:none;">🎓 {{ $ambiente->carrera?->nombre ?? 'Carrera' }}</a>
        <span style="padding:0 8px;color:#94a3b8;">/</span>
        <span>🏢 {{ $ambiente->nombre }}</span>
        <span style="padding:0 8px;color:#94a3b8;">/</span>
        <span>📦 Activos</span>
    </nav>

    <section style="background:linear-gradient(120deg,#0f766e 0%,#0d9488 100%);padding:28px 32px;border-radius:18px;color:white;display:grid;grid-template-columns:minmax(0,1.4fr) minmax(320px, .8fr);gap:24px;align-items:center;box-shadow:0 12px 24px -8px rgba(15,118,110,.3);">
        <div>
            <span style="display:inline-block;background:rgba(255,255,255,.2);padding:6px 14px;border-radius:20px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;">Inventario del ambiente</span>
            <h1 style="font-size:30px;font-weight:800;margin:10px 0 4px;">Activos</h1>
            <p style="opacity:.9;font-size:14px;margin:0 0 18px;">Administración de activos dentro de <strong>{{ $ambiente->nombre }}</strong></p>

            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                @if (auth()->check() && auth()->user()->esAdmin())
                    <a href="{{ route('activos.create', ['id_ambiente' => $ambiente->id_ambiente]) }}" style="background:#10b981;color:white;padding:11px 18px;border-radius:10px;font-weight:800;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">➕ Nuevo activo</a>
                    <button type="button" onclick="submitSelectedAssets('selected-labels-form')" style="background:#f59e0b;color:#fff;padding:11px 18px;border:0;border-radius:10px;font-weight:800;cursor:pointer;">🏷️ Imprimir seleccionados</button>
                    <button type="button" onclick="submitSelectedAssets('selected-receipts-form')" style="background:rgba(15,23,42,.2);color:white;padding:11px 18px;border:0;border-radius:10px;font-weight:800;cursor:pointer;">📋 Actas de recepción</button>
                    <a href="{{ route('activos.etiquetas', array_filter(['id_ambiente' => $ambiente->id_ambiente, 'id_categoria' => $idCategoriaSeleccionada])) }}" target="_blank" rel="noopener" style="background:#7c3aed;color:white;padding:11px 18px;border-radius:10px;font-weight:800;text-decoration:none;">🏷️ Imprimir todas</a>
                    <a href="{{ route('categorias.index') }}" style="background:rgba(15,23,42,.2);color:white;padding:11px 18px;border-radius:10px;font-weight:800;text-decoration:none;">⚙ Categorías</a>
                @endif
                @if (auth()->check() && auth()->user()->esAdmin())
                    <button type="button" id="bulk-delete-button" onclick="deleteSelectedAssets()" disabled style="background:#dc2626;color:white;padding:11px 18px;border:0;border-radius:10px;font-weight:800;cursor:not-allowed;opacity:.5;">🗑️ Eliminar seleccionados</button>
                @endif
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(2,minmax(120px,1fr));gap:12px;">
            @foreach ([
                ['value' => $totales['TODOS'], 'label' => 'Total', 'color' => '#ffffff'],
                ['value' => $totales['BUENOS'], 'label' => 'Buenos', 'color' => '#86efac'],
                ['value' => $totales['REGULARES'], 'label' => 'Regulares', 'color' => '#fde047'],
                ['value' => $totales['MALOS'], 'label' => 'Malos / F.F.', 'color' => '#fda4af'],
            ] as $stat)
                <div style="background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.2);padding:16px;border-radius:13px;text-align:center;">
                    <div style="font-size:28px;font-weight:800;color:{{ $stat['color'] }};">{{ $stat['value'] }}</div>
                    <div style="font-size:10px;font-weight:800;opacity:.9;text-transform:uppercase;margin-top:3px;">{{ $stat['label'] }}</div>
                </div>
            @endforeach
        </div>
    </section>

    @if ($categorias->isNotEmpty())
        <form method="GET" action="{{ route('ambientes.show', $ambiente->id_ambiente) }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;background:white;border:1px solid #e2e8f0;padding:16px 20px;border-radius:14px;">
            <div style="min-width:280px;">
                <label for="id_categoria" style="display:block;font-size:11px;font-weight:800;color:#475569;margin-bottom:6px;text-transform:uppercase;">Filtrar por categoría</label>
                <select id="id_categoria" name="id_categoria" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;background:white;">
                    <option value="">Todas las categorías</option>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->id_categoria }}" @selected($idCategoriaSeleccionada == $categoria->id_categoria)>
                            ID {{ $categoria->id_categoria }} - {{ $categoria->nombre }} ({{ $categoria->activos_count }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" style="background:#2563eb;color:white;border:0;padding:10px 16px;border-radius:8px;font-weight:700;cursor:pointer;">Filtrar por categoría</button>
            @if ($idCategoriaSeleccionada)
                <a href="{{ route('ambientes.show', $ambiente->id_ambiente) }}" style="background:#e2e8f0;color:#475569;padding:10px 16px;border-radius:8px;font-weight:700;text-decoration:none;">Limpiar</a>
            @endif
        </form>
    @endif

    <section style="background:white;border-radius:16px;border:1px solid #e2e8f0;padding:24px;box-shadow:0 4px 8px -4px rgba(0,0,0,.08);">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
            <h2 style="font-size:19px;font-weight:800;color:#0f172a;margin:0;">📦 Activos individuales ({{ $activos->count() }})</h2>
            @if (auth()->check() && auth()->user()->esAdmin() && $activos->isNotEmpty())
                <label style="display:flex;align-items:center;gap:8px;color:#475569;font-size:13px;font-weight:700;">
                    <input type="checkbox" id="select-all-activos" onchange="selectAllAssets(this.checked)">
                    Seleccionar todos
                </label>
            @endif
        </div>

        @if (auth()->check() && auth()->user()->esAdmin())
            <form id="bulk-delete-form" method="POST" action="{{ route('activos.destroyMany') }}" style="display:none;">
                @csrf
                @method('DELETE')
                <div id="bulk-delete-inputs"></div>
            </form>
            <form id="selected-labels-form" method="GET" action="{{ route('activos.etiquetas') }}" target="_blank" rel="noopener" style="display:none;"></form>
        @endif
        <form id="selected-receipts-form" method="GET" action="{{ route('actas.recepcion.pdf.multiple') }}" target="_blank" rel="noopener" style="display:none;"></form>

        @if ($activos->isNotEmpty())
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:12px;vertical-align:middle;min-width:1050px;">
                    <thead>
                        <tr style="background:#f1f5f9;color:#475569;text-transform:uppercase;font-size:10px;font-weight:800;">
                            @if (auth()->check() && auth()->user()->esAdmin())
                                <th style="padding:13px 8px;text-align:center;">Sel.</th>
                            @endif
                            <th style="padding:13px 8px;text-align:center;">Nº</th>
                            <th style="padding:13px 8px;text-align:center;">QR</th>
                            <th style="padding:13px 8px;text-align:center;">Imagen</th>
                            <th style="padding:13px 8px;text-align:left;">Código</th>
                            <th style="padding:13px 8px;text-align:left;">Tipo</th>
                            <th style="padding:13px 8px;text-align:left;">Características</th>
                            <th style="padding:13px 8px;text-align:left;">Descripción</th>
                            <th style="padding:13px 8px;text-align:left;">Custodio</th>
                            <th style="padding:13px 8px;text-align:left;">Fecha ingreso</th>
                            <th style="padding:13px 8px;text-align:center;">Estado</th>
                            <th style="padding:13px 8px;text-align:center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($activos as $index => $activo)
                            @php
                                $badge = match ($activo->estado_fisico) {
                                    'B' => ['bg' => '#dcfce7', 'color' => '#166534', 'label' => 'BUENO'],
                                    'R' => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'REGULAR'],
                                    'M' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'MALO'],
                                    'FF' => ['bg' => '#e5e7eb', 'color' => '#374151', 'label' => 'F.F.'],
                                    default => ['bg' => '#f1f5f9', 'color' => '#475569', 'label' => '—'],
                                };
                            @endphp
                            <tr style="border-bottom:1px solid #e2e8f0;">
                                @if (auth()->check() && auth()->user()->esAdmin())
                                    <td style="padding:13px 8px;text-align:center;">
                                        <input type="checkbox" value="{{ $activo->id_activo }}" class="activo-checkbox" onchange="updateSelectionControls()">
                                    </td>
                                @endif
                                <td style="padding:13px 8px;text-align:center;color:#64748b;font-weight:800;">{{ $index + 1 }}</td>
                                <td style="padding:13px 8px;text-align:center;">
                                    @if ($activo->ruta_qr)
                                        <img src="{{ asset('storage/'.$activo->ruta_qr) }}" alt="QR {{ $activo->codigo_activo }}" style="width:42px;height:42px;object-fit:contain;">
                                    @else
                                        <span style="color:#94a3b8;">Sin QR</span>
                                    @endif
                                </td>
                                <td style="padding:13px 8px;text-align:center;">
                                    @if ($activo->imagen)
                                        <img src="{{ asset('storage/'.$activo->imagen) }}" alt="Foto {{ $activo->nombre }}" style="width:42px;height:42px;object-fit:cover;border-radius:7px;">
                                    @else
                                        <span style="color:#94a3b8;font-style:italic;">Sin foto</span>
                                    @endif
                                </td>
                                <td style="padding:13px 8px;">
                                    <strong style="display:block;color:#0f172a;">{{ $activo->codigo_activo }}</strong>
                                    @if ($activo->codigo_antiguo)
                                        <small style="display:block;color:#64748b;margin-top:3px;">Antiguo: {{ $activo->codigo_antiguo }}</small>
                                    @endif
                                    <small style="display:block;color:#64748b;margin-top:3px;">Serie: {{ $activo->numero_serie ?: 'S/N' }}</small>
                                </td>
                                <td style="padding:13px 8px;">
                                    <span style="display:inline-block;background:#dbeafe;color:#1d4ed8;padding:6px 8px;border-radius:7px;font-size:10px;font-weight:800;">
                                        {{ match ($activo->tipo_bien) {
                                            'ACTIVO_FIJO' => 'Activo fijo',
                                            'NO_ACTIVO' => 'No activo',
                                            default => $activo->tipo_bien,
                                        } }}
                                    </span>
                                    <small style="display:block;color:#64748b;margin-top:5px;">{{ $activo->categoria?->codigo }} - {{ $activo->categoria?->nombre }}</small>
                                </td>
                                <td style="padding:13px 8px;min-width:130px;">
                                    <strong style="display:block;color:#1e293b;">{{ $activo->nombre }}</strong>
                                    <small style="display:block;color:#64748b;margin-top:4px;">{{ trim(($activo->marca ?? '').' '.($activo->modelo ?? '')) }}</small>
                                </td>
                                <td style="padding:13px 8px;max-width:180px;">
                                    @if ($activo->descripcion)
                                        <span style="display:block;background:#f8fafc;padding:7px 9px;border:1px solid #e2e8f0;border-radius:7px;color:#64748b;font-style:italic;line-height:1.4;">{{ $activo->descripcion }}</span>
                                    @else
                                        <span style="color:#94a3b8;">Sin descripción</span>
                                    @endif
                                </td>
                                <td style="padding:13px 8px;min-width:130px;">
                                    <strong style="display:block;color:#0f172a;">{{ $activo->custodio?->nombre_completo ?? 'Sin asignar' }}</strong>
                                    <small style="display:block;color:#64748b;margin-top:4px;">{{ $activo->fuente?->nombre ?? '—' }}</small>
                                </td>
                                <td style="padding:13px 8px;white-space:nowrap;color:#334155;font-weight:600;">
                                    {{ $activo->fecha_adquisicion?->format('d/m/Y') ?? $activo->created_at?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td style="padding:13px 8px;text-align:center;">
                                    <span style="display:inline-block;background:{{ $badge['bg'] }};color:{{ $badge['color'] }};padding:5px 8px;border-radius:6px;font-size:10px;font-weight:800;">{{ $badge['label'] }}</span>
                                </td>
                                <td style="padding:13px 8px;">
                                    <div style="display:grid;grid-template-columns:repeat(3,30px);justify-content:center;gap:5px;">
                                        <a href="{{ route('activos.tarjeta', $activo->id_activo) }}" title="Ver activo" style="width:30px;height:30px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">👁️</a>
                                        @if (auth()->check() && auth()->user()->esAdmin())
                                            <a href="{{ route('activos.edit', $activo->id_activo) }}" title="Editar activo" style="width:30px;height:30px;background:#fef9c3;color:#ca8a04;border:1px solid #fde047;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">✏️</a>
                                        @endif
                                        <a href="{{ route('activos.descargar_qr', $activo->id_activo) }}" title="Descargar QR" style="width:30px;height:30px;background:#f3e8ff;color:#7c3aed;border:1px solid #ddd6fe;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">⬇️</a>
                                        @if (auth()->check() && auth()->user()->esAdmin())
                                            <a href="{{ route('activos.etiquetas', ['ids' => [$activo->id_activo]]) }}" target="_blank" rel="noopener" title="Imprimir etiqueta" style="width:30px;height:30px;background:#fef3c7;color:#b45309;border:1px solid #fde68a;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">🏷️</a>
                                            <a href="{{ route('actas.recepcion.pdf', $activo->id_activo) }}" target="_blank" rel="noopener" title="Acta de recepción" style="width:30px;height:30px;background:#d1fae5;color:#059669;border:1px solid #a7f3d0;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">📋</a>
                                            <form method="POST" action="{{ route('activos.destroy', $activo->id_activo) }}" onsubmit="return confirm('¿Eliminar este activo?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Eliminar activo" style="width:30px;height:30px;background:#dc2626;color:white;border:1px solid #b91c1c;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;">🗑️</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="text-align:center;padding:56px 20px;color:#64748b;">
                <div style="font-size:46px;margin-bottom:10px;">📦</div>
                <h3 style="font-size:17px;color:#334155;margin:0 0 6px;">No hay activos para mostrar</h3>
                <p style="font-size:13px;margin:0;">{{ $idCategoriaSeleccionada ? 'Prueba con otra categoría o limpia el filtro.' : 'Registra un activo para comenzar.' }}</p>
            </div>
        @endif

    </section>
</div>
@endsection

@section('scripts')
    <script>
        function selectedAssetIds() {
            return Array.from(document.querySelectorAll('.activo-checkbox:checked')).map((checkbox) => checkbox.value);
        }

        function updateSelectionControls() {
            const selectedCount = selectedAssetIds().length;
            const checkboxes = document.querySelectorAll('.activo-checkbox');
            const selectAll = document.getElementById('select-all-activos');
            const deleteButton = document.getElementById('bulk-delete-button');

            if (selectAll) {
                selectAll.checked = checkboxes.length > 0 && selectedCount === checkboxes.length;
                selectAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
            }

            if (deleteButton) {
                deleteButton.disabled = selectedCount === 0;
                deleteButton.style.opacity = selectedCount === 0 ? '.5' : '1';
                deleteButton.style.cursor = selectedCount === 0 ? 'not-allowed' : 'pointer';
            }
        }

        function selectAllAssets(checked) {
            document.querySelectorAll('.activo-checkbox').forEach((checkbox) => {
                checkbox.checked = checked;
            });
            updateSelectionControls();
        }

        function submitSelectedAssets(formId) {
            const ids = selectedAssetIds();
            if (ids.length === 0) {
                alert('Selecciona al menos un activo.');
                return;
            }

            const form = document.getElementById(formId);
            form.querySelectorAll('input[name="ids[]"]').forEach((input) => input.remove());
            ids.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                form.appendChild(input);
            });
            form.requestSubmit();
        }

        function deleteSelectedAssets() {
            const ids = selectedAssetIds();
            if (ids.length === 0) {
                alert('Selecciona al menos un activo.');
                return;
            }
            if (!confirm('¿Eliminar los activos seleccionados? Esta acción no se puede deshacer.')) {
                return;
            }

            const form = document.getElementById('bulk-delete-form');
            const inputs = document.getElementById('bulk-delete-inputs');
            inputs.replaceChildren();
            ids.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'activos[]';
                input.value = id;
                inputs.appendChild(input);
            });
            form.requestSubmit();
        }

        document.addEventListener('DOMContentLoaded', updateSelectionControls);
    </script>
@endsection
