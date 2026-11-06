@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-4">
        {{-- ⭐ BREADCRUMB --}}
        <div style="background:white;padding:12px 20px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:8px;font-size:13px;box-shadow:0 2px 4px rgba(0,0,0,.04);border:1px solid #e2e8f0;flex-wrap:wrap;">
            <a href="{{ isset($carrera) ? route('carreras.show', $carrera->id_carrera) : '#' }}" style="color:#2563eb;text-decoration:none;font-weight:700;">
                🎓 {{ $carrera->nombre ?? 'Carrera' }}
            </a>
            <span style="color:#94a3b8;">/</span>
            <a href="{{ isset($ambiente) ? route('ambientes.show', $ambiente->id_ambiente) : '#' }}" style="color:#2563eb;text-decoration:none;font-weight:700;">
                🏢 {{ $ambiente->nombre ?? 'Ambiente' }}
            </a>
            <span style="color:#94a3b8;">/</span>
            <a href="{{ isset($categoria) ? route('categorias.show', $categoria->id_categoria) : '#' }}" style="color:#2563eb;text-decoration:none;font-weight:700;">
                📁 {{ $categoria->nombre ?? 'Categoría' }}
            </a>
            <span style="color:#94a3b8;">/</span>
            <span style="color:#0f172a;font-weight:700;">📦 {{ $nombreDecoded ?? 'Grupo' }}</span>
        </div>

        {{-- ============ ENCABEZADO ============ --}}
        <div style="background:linear-gradient(135deg,#0f766e 0%,#0d9488 100%);padding:28px;border-radius:16px;color:white;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px;margin-bottom:24px;box-shadow:0 10px 15px -3px rgba(15,118,110,.2);">
            <div style="flex:1;min-width:280px;">
                <span style="background:rgba(255,255,255,.2);padding:5px 12px;border-radius:20px;font-size:11px;font-weight:800;text-transform:uppercase;">
                    Activos
                </span>
                <h1 style="font-size:28px;font-weight:800;margin:8px 0 4px 0;">{{ $nombreDecoded ?? 'Activos Fijos' }}</h1>
                <p style="opacity:.9;font-size:14px;margin:0;">
                    Administración de activos dentro de <strong>{{ $categoria->nombre ?? '' }}</strong>
                </p>

                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;">
                    @if (auth()->check() && auth()->user()->esAdmin() && isset($ambiente))
                        <a href="{{ route('activos.create', ['id_ambiente' => $ambiente->id_ambiente, 'id_categoria' => $categoria->id_categoria]) }}"
                            style="background:#10b981;color:white;padding:10px 18px;border-radius:10px;font-weight:700;border:none;cursor:pointer;font-size:14px;display:inline-flex;align-items:center;gap:6px;transition:all .2s;"
                            onmouseover="this.style.background='#059669';" onmouseout="this.style.background='#10b981';">
                            ➕ Nuevo Activo
                        </a>
                        <button type="button" onclick="imprimirSeleccionadosCat()"
                            style="background:#f59e0b;color:white;padding:10px 18px;border-radius:10px;font-weight:700;border:none;cursor:pointer;font-size:14px;display:inline-flex;align-items:center;gap:6px;transition:all .2s;"
                            onmouseover="this.style.background='#d97706';" onmouseout="this.style.background='#f59e0b';">
                            🏷️ Imprimir seleccionados
                        </button>
                        <button type="button" onclick="generarActasRecepcion()"
                            style="background:#0f766e;color:white;padding:10px 18px;border-radius:10px;font-weight:700;border:none;cursor:pointer;font-size:14px;display:inline-flex;align-items:center;gap:6px;transition:all .2s;"
                            onmouseover="this.style.background='#0d9488';" onmouseout="this.style.background='#0f766e';">
                            📋 Actas de Recepción
                        </button>
                        <a href="{{ route('activos.etiquetas', ['id_categoria' => $categoria->id_categoria ?? '']) }}" target="_blank"
                            style="background:#7c3aed;color:white;padding:10px 18px;border-radius:10px;font-weight:700;border:none;cursor:pointer;font-size:14px;display:inline-flex;align-items:center;gap:6px;transition:all .2s;text-decoration:none;"
                            onmouseover="this.style.background='#6b21a8';" onmouseout="this.style.background='#7c3aed';">
                            🏷️ Imprimir todas
                        </a>
                        <form id="bulk-delete-form" method="POST" action="{{ route('activos.destroyMany') }}" onsubmit="return confirm('¿Eliminar los activos seleccionados? Esta acción no se puede deshacer.');" style="margin:0;">
                        @csrf
                        @method('DELETE')
                    </form>
                    <button type="submit" form="bulk-delete-form" id="bulk-delete-button" disabled
                        style="padding:9px 14px;background:#dc2626;color:#fff;border:0;border-radius:8px;font-weight:800;cursor:pointer;opacity:.5;">
                        🗑️ Eliminar seleccionados
                    </button>
                    @endif
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;width:380px;flex-shrink:0;">
                <div style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);padding:18px 12px;border-radius:12px;text-align:center;">
                    <div style="font-size:24px;font-weight:800;">{{ $totalActivos ?? 0 }}</div>
                    <div style="font-size:11px;font-weight:700;opacity:.9;text-transform:uppercase;margin-top:4px;">Total</div>
                </div>
                <div style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);padding:18px 12px;border-radius:12px;text-align:center;">
                    <div style="font-size:24px;font-weight:800;color:#86efac;">{{ $totalBuenos ?? 0 }}</div>
                    <div style="font-size:11px;font-weight:700;opacity:.9;text-transform:uppercase;margin-top:4px;">Buenos</div>
                </div>
                <div style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);padding:18px 12px;border-radius:12px;text-align:center;">
                    <div style="font-size:24px;font-weight:800;color:#fde047;">{{ $totalRegulares ?? 0 }}</div>
                    <div style="font-size:11px;font-weight:700;opacity:.9;text-transform:uppercase;margin-top:4px;">Regulares</div>
                </div>
                <div style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);padding:18px 12px;border-radius:12px;text-align:center;">
                    <div style="font-size:24px;font-weight:800;color:#fca5a5;">{{ $totalMalos ?? 0 }}</div>
                    <div style="font-size:11px;font-weight:700;opacity:.9;text-transform:uppercase;margin-top:4px;">Malos/F.F.</div>
                </div>
        </div>
    </div>

    <script>
        function toggleSelectAllCat(checkbox) {
            document.querySelectorAll('.etiqueta-check-cat').forEach(ch => {
                ch.checked = checkbox.checked;
            });
        }

        function imprimirSeleccionadosCat() {
            const checks = document.querySelectorAll('.etiqueta-check-cat:checked');
            if (checks.length === 0) {
                alert('Selecciona al menos un activo para imprimir etiquetas.');
                return;
            }
            const params = new URLSearchParams();
            checks.forEach(ch => params.append('ids[]', ch.value));
            window.open('{{ route('activos.etiquetas') }}?' + params.toString(), '_blank');
        }

        function generarActasRecepcion() {
            const checks = document.querySelectorAll('.etiqueta-check-cat:checked');
            if (checks.length === 0) {
                alert('Selecciona al menos un activo para generar actas de recepción.');
                return;
            }
            const params = new URLSearchParams();
            checks.forEach(ch => params.append('ids[]', ch.value));
            window.open('{{ route('actas.recepcion.pdf.multiple') }}?' + params.toString(), '_blank');
        }
    </script>

        {{-- ============ LISTADO DE ACTIVOS ============ --}}
        <div id="activos-container" style="background:white;border-radius:16px;border:1px solid #e2e8f0;padding:25px;box-shadow:0 4px 6px -1px rgba(0,0,0,.05);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
                <h3 style="font-size:18px;font-weight:800;color:#0f172a;margin:0;">
                    📦 Activos individuales (<span id="total-activos">{{ $totalActivos ?? 0 }}</span>)
                </h3>
                @if (auth()->check() && auth()->user()->esAdmin())
                    <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:#475569;cursor:pointer;">
                        <input type="checkbox" onclick="toggleSelectAllCat(this)" style="width:16px;height:16px;">
                        Seleccionar todos
                    </label>
                @endif
            </div>

            @if (isset($activos) && $activos->count() > 0)
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:13px;vertical-align:middle;">
                        <thead>
                            <tr style="background:#f1f5f9;color:#475569;text-transform:uppercase;font-size:11px;font-weight:800;">
                                @if (auth()->check() && auth()->user()->esAdmin())
                                    <th style="padding:14px 10px;text-align:center;width:45px;">SEL</th>
                                @endif
                                <th style="padding:14px 10px;text-align:center;width:45px;">Nº</th>
                                <th style="padding:14px 10px;text-align:center;width:65px;">QR</th>
                                <th style="padding:14px 10px;text-align:center;width:65px;">IMAGEN</th>
                                <th style="padding:14px 10px;text-align:left;">CÓDIGO</th>
                                <th style="padding:14px 10px;text-align:center;width:100px;">TIPO</th>
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
                                    @if (auth()->check() && auth()->user()->esAdmin())
                                        <td style="padding:12px 10px;text-align:center;">
                                            <input type="checkbox" class="etiqueta-check-cat" value="{{ $a->id_activo }}" style="width:16px;height:16px;">
                                        </td>
                                    @endif
                                    <td style="padding:12px 10px;text-align:center;color:#64748b;font-weight:800;">{{ $i + 1 }}</td>
                                    <td style="padding:12px 10px;text-align:center;">
                                        @if ($a->ruta_qr)
                                            <img src="{{ asset('storage/' . $a->ruta_qr) }}" alt="QR" style="width:42px;height:42px;object-fit:contain;display:block;margin:0 auto;">
                                        @else
                                            <span style="font-size:11px;color:#94a3b8;">Sin QR</span>
                                        @endif
                                    </td>
                                    <td style="padding:12px 10px;text-align:center;">
                                        @if ($a->imagen)
                                            <img src="{{ asset('storage/' . $a->imagen) }}" alt="Foto" style="width:42px;height:42px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;display:block;margin:0 auto;">
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
                                    <td style="padding:12px 10px;text-align:center;">
                                        @if($a->tipo_bien === 'NO_ACTIVO')
                                            <span style="font-size:10px;background:#fef3c7;color:#92400e;padding:4px 8px;border-radius:6px;font-weight:800;display:inline-block;">No Activo</span>
                                        @else
                                            <span style="font-size:10px;background:#dbeafe;color:#1e40af;padding:4px 8px;border-radius:6px;font-weight:800;display:inline-block;">Activo Fijo</span>
                                        @endif
                                    </td>
                                    <td style="padding:12px 10px;">
                                        <div style="font-weight:700;color:#1e293b;font-size:13px;">{{ $a->nombre }}</div>
                                        <div style="font-size:11px;color:#64748b;margin-top:2px;">{{ $a->marca ?? 'S/M' }} {{ $a->modelo ?? '' }}</div>
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
                                                <button type="button" onclick="confirmarEliminarActivo({{ $a->id_activo }}, '{{ addslashes($a->codigo_activo) }}', '{{ addslashes($a->nombre) }}')"
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
                    <h3 style="margin:0 0 6px 0;font-size:16px;color:#64748b;">No hay activos en este grupo</h3>
                    <p style="margin:0;font-size:13px;">Registra un activo nuevo para comenzar.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MODAL: NUEVO ACTIVO --}}
    {{-- ============================================================ --}}
    @if(false)
    <div id="activo-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.7);backdrop-filter:blur(6px);z-index:9999;justify-content:center;align-items:center;padding:16px;" onmousedown="closeActivoModal(event)">

        <div style="background:#ffffff; border-radius:20px; width:100%; max-width:880px; max-height:94vh; display:flex; flex-direction:column; box-shadow:0 30px 60px -12px rgba(0,0,0,0.4); border:1px solid #e2e8f0; overflow:hidden; animation:modalSlideUp 0.25s ease-out;" onmousedown="event.stopPropagation()">

            <style>
                @keyframes modalSlideUp {
                    from { opacity:0; transform:translateY(20px) scale(0.98); }
                    to { opacity:1; transform:translateY(0) scale(1); }
                }
                .af-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
                .af-grid > div { display:flex; flex-direction:column; }
                .af-label { font-size:11px; font-weight:800; color:#475569; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px; }
                .af-input {
                    width:100%; padding:10px 14px; border-radius:10px; border:1.5px solid #e2e8f0; font-size:13px; font-weight:500; color:#0f172a;
                    box-sizing:border-box; background:#f8fafc; transition:all 0.2s ease; font-family:inherit;
                }
                .af-input:hover { border-color:#cbd5e1; }
                .af-input:focus { outline:none; border-color:#2563eb; background:#ffffff; box-shadow:0 0 0 4px rgba(37,99,235,0.1); }
                .af-span-2 { grid-column:span 2; }
                .af-box { background:#f8fafc; padding:16px; border-radius:14px; border:1.5px solid #e2e8f0; }
                .af-error { display:none; color:#ef4444; font-size:11px; font-weight:700; margin-top:5px; }
                .af-error.visible { display:block; }
                .af-input-error { border-color:#ef4444 !important; background:#fef2f2 !important; }
                .af-asterisco { color:#ef4444; margin-left:2px; display:none; font-weight:900; }
                .af-asterisco.visible { display:inline; }
                .af-dropzone {
                    width:100%; height:110px; border:2px dashed #cbd5e1; border-radius:12px; display:flex; flex-direction:column;
                    align-items:center; justify-content:center; background:#ffffff; overflow:hidden; margin-bottom:8px; transition:all 0.2s; cursor:pointer;
                }
                .af-dropzone:hover { border-color:#2563eb; background:#f0f6ff; }
                .btn-secondary { background:#f1f5f9; color:#475569; border:none; padding:11px 22px; border-radius:10px; font-weight:700; font-size:13px; cursor:pointer; transition:all 0.2s; }
                .btn-secondary:hover { background:#e2e8f0; color:#1e293b; }
                .btn-primary { background:linear-gradient(135deg,#2563eb,#1d4ed8); color:#ffffff; border:none; padding:11px 24px; border-radius:10px; font-weight:700; font-size:13px; cursor:pointer; box-shadow:0 4px 12px -2px rgba(37,99,235,0.4); transition:all 0.2s; }
                .btn-primary:hover { background:linear-gradient(135deg,#1d4ed8,#1e40af); box-shadow:0 8px 16px -4px rgba(37,99,235,0.5); transform:translateY(-1px); }
                .btn-primary:disabled { opacity:0.6; cursor:not-allowed; transform:none; }

                /* Custodio selector */
                .custodio-wrapper {
                    border:1.5px solid #e2e8f0; border-radius:12px; overflow:hidden; background:#ffffff;
                    transition:all 0.2s;
                }
                .custodio-wrapper:focus-within { border-color:#2563eb; box-shadow:0 0 0 4px rgba(37,99,235,0.1); }
                .custodio-search {
                    width:100%; padding:12px 16px; border:none; border-bottom:1.5px solid #f1f5f9; font-size:13px; outline:none;
                    background:#f8fafc; font-weight:500; color:#0f172a; box-sizing:border-box;
                }
                .custodio-search::placeholder { color:#94a3b8; font-weight:500; }
                .custodio-search:focus { background:#ffffff; }
                .custodio-list {
                    width:100%; padding:6px; border:none; font-size:13px; background:white; outline:none;
                    max-height:160px; overflow-y:auto;
                }
                .custodio-list option {
                    padding:10px 14px; border-radius:8px; cursor:pointer; color:#1e293b; font-weight:600;
                    margin-bottom:2px; transition:background 0.15s; border:1px solid transparent;
                }
                .custodio-list option:hover { background:#eff6ff; }
                .custodio-list option:checked { background:#2563eb; color:white; }
                .custodio-list option:checked::before { content:'✓ '; font-weight:900; }

                /* ⭐ RV Selector */
                .rv-box {
                    display:flex; align-items:center; gap:16px; margin-bottom:12px; padding:12px 16px;
                    background:#f0fdf4; border:1.5px solid #bbf7d0; border-radius:12px; flex-wrap:wrap;
                }
                .rv-label-title { font-size:13px; font-weight:800; color:#166534; }
                .rv-option {
                    display:flex; align-items:center; gap:6px; cursor:pointer; font-size:13px; font-weight:800;
                    padding:6px 14px; border-radius:8px; border:2px solid transparent; transition:all 0.15s;
                }
                .rv-option input { width:16px; height:16px; cursor:pointer; margin:0; }
                .rv-option.si { color:#166534; }
                .rv-option.si:hover { background:#dcfce7; }
                .rv-option.no { color:#991b1b; }
                .rv-option.no:hover { background:#fee2e2; }
                .rv-option.si.selected { background:#dcfce7; border-color:#16a34a; }
                .rv-option.no.selected { background:#fee2e2; border-color:#dc2626; }
                .rv-hint { font-size:10px; color:#64748b; font-weight:600; margin-left:auto; }
            </style>

            {{-- Header --}}
            <div style="padding:20px 26px; border-bottom:1.5px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; background:linear-gradient(135deg,#f8fafc,#ffffff);">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div style="background:linear-gradient(135deg,#2563eb,#1d4ed8); color:#ffffff; width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; box-shadow:0 4px 10px -2px rgba(37,99,235,0.4);">📦</div>
                    <div>
                        <h3 style="margin:0; font-size:17px; font-weight:800; color:#0f172a;">Registrar Nuevo Activo</h3>
                        <p style="margin:0; font-size:12px; color:#64748b; font-weight:500;">Complete los datos del bien para incorporarlo al inventario.</p>
                    </div>
                </div>
                <button type="button" onclick="closeActivoModal()" style="background:#f1f5f9; border:none; border-radius:10px; width:36px; height:36px; display:flex; align-items:center; justify-content:center; font-size:16px; cursor:pointer; color:#64748b; transition:all 0.2s; font-weight:700;" onmouseover="this.style.background='#e2e8f0'; this.style.color='#1e293b';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#64748b';">✕</button>
            </div>

            {{-- Formulario Body --}}
            <form id="activo-modal-form" method="POST" action="{{ route('activos.store') }}" enctype="multipart/form-data" novalidate style="overflow-y:auto; padding:24px 26px;">
                @csrf
                <input type="hidden" name="id_ambiente" id="crear-id-ambiente" value="{{ $ambiente->id_ambiente ?? '' }}">
                <input type="hidden" name="id_categoria_origen" value="{{ $categoria->id_categoria ?? '' }}">

                <div class="af-grid">

                    {{-- Clasificación --}}
                    <div class="af-span-2 af-box" style="display:flex; align-items:center; justify-content:space-between; border-left:4px solid #2563eb; background:linear-gradient(90deg,#eff6ff,#ffffff);">
                        <div>
                            <strong style="font-size:13px; color:#0f172a; display:block; font-weight:800;">🏷️ Tipo de Clasificación</strong>
                            <span id="ayuda-tipo-bien" style="font-size:11px; color:#64748b; font-weight:500;">Activo Fijo: marca, modelo y serie son obligatorios (3 fotos).</span>
                        </div>
                        <select name="tipo_bien" id="select-tipo-bien" onchange="cambiarTipoBien(this.value)" class="af-input" style="width:auto; font-weight:800; padding:9px 16px; background:white;">
                            <option value="ACTIVO_FIJO">🔹 Activo Fijo</option>
                            <option value="NO_ACTIVO">🔸 No Activo</option>
                        </select>
                    </div>

                    {{-- Código & Categoría --}}
                    <div>
                        <label class="af-label">Código <span class="af-asterisco visible">*</span></label>
                        <input type="text" name="codigo_activo" id="crear-codigo-activo" maxlength="50" class="af-input" value="ACT-{{ date('Y') }}-{{ rand(1000, 9999) }}">
                        <div class="af-error" data-for="crear-codigo-activo"></div>
                    </div>

                    <div>
                        <label class="af-label">Categoría (Ambiente) <span class="af-asterisco visible">*</span></label>
                        <select name="id_categoria" id="crear-id-categoria" class="af-input">
                            <option value="">— Seleccionar categoría —</option>
                            @foreach (\App\Models\Categoria::where('id_ambiente', $ambiente->id_ambiente ?? 0)->orderBy('nombre')->get() as $cat)
                                <option value="{{ $cat->id_categoria }}" {{ (isset($categoria) && $categoria->id_categoria == $cat->id_categoria) ? 'selected' : '' }}>
                                    [{{ $cat->codigo }}] {{ $cat->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <div class="af-error" data-for="crear-id-categoria"></div>
                    </div>

                    {{-- Cantidad & Fecha --}}
                    <div>
                        <label class="af-label">Cantidad <span class="af-asterisco visible">*</span></label>
                        <input type="number" name="cantidad" id="crear-cantidad" min="1" max="100" value="1" class="af-input">
                        <div class="af-error" data-for="crear-cantidad"></div>
                    </div>

                    <div>
                        <label class="af-label">Fecha Adquisición <span class="af-asterisco visible">*</span></label>
                        <input type="date" name="fecha_adquisicion" id="crear-fecha-adquisicion" value="{{ date('Y-m-d') }}" class="af-input">
                        <div class="af-error" data-for="crear-fecha-adquisicion"></div>
                    </div>

                    {{-- Nombre --}}
                    <div class="af-span-2">
                        <label class="af-label">Nombre del Activo <span class="af-asterisco visible">*</span></label>
                        <input type="text" name="nombre" id="crear-nombre" maxlength="150" placeholder="Ej. Computadora portátil Dell XPS 15" class="af-input">
                        <div class="af-error" data-for="crear-nombre"></div>
                    </div>

                    {{-- Marca, Modelo, Serie --}}
                    <div>
                        <label class="af-label" id="lbl-marca">Marca <span class="af-asterisco" id="crear-req-marca">*</span></label>
                        <input type="text" name="marca" id="crear-input-marca" maxlength="80" placeholder="Ej. Dell, HP, Samsung" class="af-input">
                        <div class="af-error" data-for="crear-input-marca"></div>
                    </div>

                    <div>
                        <label class="af-label" id="lbl-modelo">Modelo <span class="af-asterisco" id="crear-req-modelo">*</span></label>
                        <input type="text" name="modelo" id="crear-input-modelo" maxlength="80" placeholder="Ej. Latitude 3420" class="af-input">
                        <div class="af-error" data-for="crear-input-modelo"></div>
                    </div>

                    <div class="af-span-2">
                        <label class="af-label" id="lbl-serie">N° Serie <span class="af-asterisco" id="crear-req-serie">*</span></label>
                        <input type="text" name="numero_serie" id="crear-input-serie" maxlength="80" placeholder="Ej. SN-9876543210" class="af-input">
                        <div class="af-error" data-for="crear-input-serie"></div>
                    </div>

                    {{-- Fuente, Estado, Valor --}}
                    <div>
                        <label class="af-label">Fuente Financiamiento <span class="af-asterisco visible">*</span></label>
                        <select name="id_fuente" id="crear-id-fuente" class="af-input">
                            <option value="">— Seleccionar —</option>
                            @foreach (\App\Models\FuenteFinanciamiento::orderBy('nombre')->get() as $f)
                                <option value="{{ $f->id_fuente }}">{{ $f->codigo }} - {{ $f->nombre }}</option>
                            @endforeach
                        </select>
                        <div class="af-error" data-for="crear-id-fuente"></div>
                    </div>

                    <div>
                        <label class="af-label">Estado Físico <span class="af-asterisco visible">*</span></label>
                        <select name="estado_fisico" id="crear-estado-fisico" class="af-input">
                            <option value="B">🟢 Bueno</option>
                            <option value="R">🟡 Regular</option>
                            <option value="M">🔴 Malo</option>
                            <option value="FF">⚫ Fuera de funcionamiento</option>
                        </select>
                        <div class="af-error" data-for="crear-estado-fisico"></div>
                    </div>

                    <div class="af-span-2">
                        <label class="af-label">Valor Adquisición (Bs.) <span class="af-asterisco visible">*</span></label>
                        <input type="number" name="valor_adquisicion" id="crear-valor-adquisicion" step="0.01" min="0" value="0.00" class="af-input">
                        <div class="af-error" data-for="crear-valor-adquisicion"></div>
                    </div>

                    {{-- ⭐ CUSTODIO RESPONSABLE + RV (RV ARRIBA) --}}
                    <div class="af-span-2">

                        {{-- ⭐ SELECTOR RV (ARRIBA DEL LABEL - SIEMPRE VISIBLE) --}}
                        <div class="rv-box">
                            <span class="rv-label-title">🔖 RV:</span>
                            <label class="rv-option si" id="crear-rv-si-label" onclick="setRvCrear('SI')">
                                <input type="radio" name="rv" value="SI" id="crear-rv-si">
                                Sí
                            </label>
                            <label class="rv-option no selected" id="crear-rv-no-label" onclick="setRvCrear('NO')">
                                <input type="radio" name="rv" value="NO" id="crear-rv-no" checked>
                                No
                            </label>
                            <span class="rv-hint">Aplica para Activo Fijo y No Activo</span>
                        </div>

                        <label class="af-label">👤 Custodio Responsable <span class="af-asterisco visible">*</span></label>

                        <div class="custodio-wrapper">
                                <input type="text" id="buscador-custodio-modal"
                                    class="custodio-search"
                                    placeholder="🔍 Buscar por Ítem, C.I. o Nombre..."
                                    onkeyup="filtrarCustodiosModal(this.value)"
                                    autocomplete="off">
                                <select name="id_custodio" id="crear-id-custodio" size="5" class="custodio-list">
                                    <option value="">— Seleccione custodio del ambiente —</option>
                                </select>
                        </div>
                        <div class="af-error" data-for="crear-id-custodio"></div>
                        <div style="font-size:10px; color:#94a3b8; margin-top:6px; font-weight:600;">
                            💡 Los custodios se muestran como: <strong>Ítem · C.I. · Nombre Completo</strong>
                        </div>
                    </div>

                    {{-- Descripción --}}
                    <div class="af-span-2">
                        <label class="af-label">Descripción / Observaciones <span class="af-asterisco visible">*</span></label>
                        <textarea name="descripcion" id="crear-descripcion" rows="3" maxlength="500" placeholder="Detalles relevantes del activo..." class="af-input" style="resize:none;"></textarea>
                        <div class="af-error" data-for="crear-descripcion"></div>
                    </div>

                    {{-- FOTOGRAFÍAS --}}
                    <div class="af-span-2 af-box">
                        <div style="font-size:12px; font-weight:800; color:#1e293b; margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                            <span style="font-size:16px;">📷</span>
                            <span>Fotografías del Activo</span>
                            <span id="lbl-fotos-info" style="font-weight:600; color:#64748b; font-size:11px;"></span>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px;">
                            {{-- Frontal --}}
                            <div>
                                <label class="af-label" style="font-size:10px;">Foto Frontal <span class="af-asterisco visible" id="crear-req-foto-frontal">*</span></label>
                                <div class="af-dropzone" id="crear-preview-frontal">
                                    <span style="font-size:24px; color:#94a3b8;">🖼️</span>
                                    <span style="color:#94a3b8; font-size:11px; margin-top:4px; font-weight:600;">Vista frontal</span>
                                </div>
                                <input type="file" name="imagen" id="crear-imagen" accept="image/*" class="af-input" style="padding:6px; font-size:11px;" onchange="previewImage(this, 'crear-preview-frontal')">
                                <div class="af-error" data-for="crear-imagen"></div>
                            </div>

                            {{-- Lateral --}}
                            <div id="crear-bloque-lateral">
                                <label class="af-label" style="font-size:10px;">Foto Lateral <span class="af-asterisco visible" id="crear-req-foto-lateral">*</span></label>
                                <div class="af-dropzone" id="crear-preview-lateral">
                                    <span style="font-size:24px; color:#94a3b8;">🖼️</span>
                                    <span style="color:#94a3b8; font-size:11px; margin-top:4px; font-weight:600;">Vista lateral</span>
                                </div>
                                <input type="file" name="imagen_lateral" id="crear-imagen_lateral" accept="image/*" class="af-input" style="padding:6px; font-size:11px;" onchange="previewImage(this, 'crear-preview-lateral')">
                                <div class="af-error" data-for="crear-imagen_lateral"></div>
                            </div>

                            {{-- Detalle --}}
                            <div id="crear-bloque-detalle">
                                <label class="af-label" style="font-size:10px;">Foto Detalle <span class="af-asterisco visible" id="crear-req-foto-detalle">*</span></label>
                                <div class="af-dropzone" id="crear-preview-detalle">
                                    <span style="font-size:24px; color:#94a3b8;">🏷️</span>
                                    <span style="color:#94a3b8; font-size:11px; margin-top:4px; font-weight:600;">Placa / Serial</span>
                                </div>
                                <input type="file" name="imagen_detalle" id="crear-imagen_detalle" accept="image/*" class="af-input" style="padding:6px; font-size:11px;" onchange="previewImage(this, 'crear-preview-detalle')">
                                <div class="af-error" data-for="crear-imagen_detalle"></div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Footer Buttons --}}
                <div style="display:flex; justify-content:flex-end; gap:12px; border-top:1.5px solid #f1f5f9; padding-top:20px; margin-top:24px;">
                    <button type="button" class="btn-secondary" onclick="closeActivoModal()">Cancelar</button>
                    <button type="submit" id="btn-guardar-activo" class="btn-primary">💾 Guardar Activo</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ============================================================ --}}
    {{-- MODAL: EDITAR ACTIVO --}}
    {{-- ============================================================ --}}
    @if(false)
    <div id="edit-activo-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;justify-content:center;align-items:center;padding:20px;" onmousedown="closeEditActivoModal(event)">
        <div style="background:white;border-radius:20px;padding:30px;width:100%;max-width:800px;max-height:90vh;overflow-y:auto;box-shadow:0 25px 50px -12px rgba(0,0,0,.25);" onmousedown="event.stopPropagation()">

            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;border-bottom:1px solid #e2e8f0;padding-bottom:12px;">
                <h3 style="margin:0;font-size:18px;font-weight:800;color:#0f172a;">✏️ Editar Activo</h3>
                <button type="button" onclick="closeEditActivoModal()" style="background:#f1f5f9;border:none;border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;font-size:18px;cursor:pointer;color:#475569;">×</button>
            </div>

            <form id="edit-activo-modal-form" method="POST" enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')
                <input type="hidden" name="id_activo" id="edit-id-activo">
                <input type="hidden" name="id_ambiente" id="edit-id-ambiente" value="{{ $ambiente->id_ambiente ?? '' }}">
                <input type="hidden" name="id_categoria_origen" value="{{ $categoria->id_categoria ?? '' }}">

                <div class="af-grid">
                    <div class="af-span-2 af-box" style="display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <strong style="font-size: 13px; color: #0f172a; display: block;">Tipo de Clasificación</strong>
                            <span id="edit-ayuda-tipo-bien" style="font-size: 11px; color: #64748b;">Activo Fijo: marca, modelo y serie obligatorios (3 fotos).</span>
                        </div>
                        <select name="tipo_bien" id="edit-select-tipo-bien" onchange="cambiarTipoBienEdit(this.value)"
                            style="padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 700; font-size: 13px; background: white;">
                            <option value="ACTIVO_FIJO">🔹 Activo Fijo</option>
                            <option value="NO_ACTIVO">🔸 No Activo</option>
                        </select>
                    </div>

                    <div>
                        <label class="af-label">Código *</label>
                        <input type="text" name="codigo_activo" id="edit-codigo-activo" maxlength="50" class="af-input">
                        <div class="af-error" data-for="edit-codigo-activo"></div>
                    </div>

                    <div>
                        <label class="af-label">Categoría (Ambiente) *</label>
                        <select name="id_categoria" id="edit-id-categoria" class="af-input">
                            <option value="">— Seleccionar categoría —</option>
                            @foreach (\App\Models\Categoria::where('id_ambiente', $ambiente->id_ambiente ?? 0)->orderBy('nombre')->get() as $cat)
                                <option value="{{ $cat->id_categoria }}">[{{ $cat->codigo }}] {{ $cat->nombre }}</option>
                            @endforeach
                        </select>
                        <div class="af-error" data-for="edit-id-categoria"></div>
                    </div>

                    <div>
                        <label class="af-label">Cantidad *</label>
                        <input type="number" name="cantidad" id="edit-cantidad" min="1" max="100" class="af-input">
                        <div class="af-error" data-for="edit-cantidad"></div>
                    </div>
                    <div>
                        <label class="af-label">Fecha Adquisición *</label>
                        <input type="date" name="fecha_adquisicion" id="edit-fecha-adquisicion" class="af-input">
                        <div class="af-error" data-for="edit-fecha-adquisicion"></div>
                    </div>

                    <div class="af-span-2">
                        <label class="af-label">Nombre del Activo *</label>
                        <input type="text" name="nombre" id="edit-nombre" maxlength="150" class="af-input">
                        <div class="af-error" data-for="edit-nombre"></div>
                    </div>

                    <div>
                        <label class="af-label" id="edit-lbl-marca">Marca <span class="af-asterisco" id="edit-req-marca">*</span></label>
                        <input type="text" name="marca" id="edit-input-marca" maxlength="80" class="af-input">
                        <div class="af-error" data-for="edit-input-marca"></div>
                    </div>
                    <div>
                        <label class="af-label" id="edit-lbl-modelo">Modelo <span class="af-asterisco" id="edit-req-modelo">*</span></label>
                        <input type="text" name="modelo" id="edit-input-modelo" maxlength="80" class="af-input">
                        <div class="af-error" data-for="edit-input-modelo"></div>
                    </div>
                    <div>
                        <label class="af-label" id="edit-lbl-serie">N° Serie <span class="af-asterisco" id="edit-req-serie">*</span></label>
                        <input type="text" name="numero_serie" id="edit-input-serie" maxlength="80" class="af-input">
                        <div class="af-error" data-for="edit-input-serie"></div>
                    </div>

                    <div>
                        <label class="af-label">Fuente *</label>
                        <select name="id_fuente" id="edit-id-fuente" class="af-input">
                            <option value="">— Seleccionar —</option>
                            @foreach (\App\Models\FuenteFinanciamiento::orderBy('nombre')->get() as $f)
                                <option value="{{ $f->id_fuente }}">{{ $f->codigo }} - {{ $f->nombre }}</option>
                            @endforeach
                        </select>
                        <div class="af-error" data-for="edit-id-fuente"></div>
                    </div>
                    <div>
                        <label class="af-label">Estado Físico *</label>
                        <select name="estado_fisico" id="edit-estado-fisico" class="af-input">
                            <option value="B">Bueno</option>
                            <option value="R">Regular</option>
                            <option value="M">Malo</option>
                            <option value="FF">Fuera de funcionamiento</option>
                        </select>
                        <div class="af-error" data-for="edit-estado-fisico"></div>
                    </div>
                    <div>
                        <label class="af-label">Valor (Bs.) *</label>
                        <input type="number" name="valor_adquisicion" id="edit-valor-adquisicion" step="0.01" min="0" class="af-input">
                        <div class="af-error" data-for="edit-valor-adquisicion"></div>
                    </div>

                    {{-- ⭐ CUSTODIO + RV EDITAR (RV ARRIBA) --}}
                    <div class="af-span-2">

                        {{-- ⭐ SELECTOR RV EDITAR (ARRIBA DEL LABEL - SIEMPRE VISIBLE) --}}
                        <div class="rv-box">
                            <span class="rv-label-title">🔖 RV:</span>
                            <label class="rv-option si" id="edit-rv-si-label" onclick="setRvEditar('SI')">
                                <input type="radio" name="rv" value="SI" id="edit-rv-si">
                                Sí
                            </label>
                            <label class="rv-option no selected" id="edit-rv-no-label" onclick="setRvEditar('NO')">
                                <input type="radio" name="rv" value="NO" id="edit-rv-no" checked>
                                No
                            </label>
                            <span class="rv-hint">Aplica para Activo Fijo y No Activo</span>
                        </div>

                        <label class="af-label">Custodio Responsable *</label>

                        <input type="text" id="edit-buscador-custodio-modal" placeholder="🔍 Buscar custodio por Nombre, C.I. o N° de Ítem..."
                            style="width:100%;padding:9px 12px;border-radius:8px 8px 0 0;border:1px solid #cbd5e1;font-size:13px;box-sizing:border-box;background:#f8fafc;"
                            onkeyup="filtrarCustodiosEditModal(this.value)">
                        <select name="id_custodio" id="edit-id-custodio" size="4"
                            style="width:100%;padding:8px;border-radius:0 0 8px 8px;border:1px solid #cbd5e1;border-top:none;font-size:13px;background:white;box-sizing:border-box;">
                            <option value="">— Seleccione custodio del ambiente —</option>
                        </select>
                        <div class="af-error" data-for="edit-id-custodio"></div>
                    </div>

                    <div class="af-span-2">
                        <label class="af-label">Descripción *</label>
                        <textarea name="descripcion" id="edit-descripcion" rows="2" maxlength="500" class="af-input" style="resize:none;"></textarea>
                        <div class="af-error" data-for="edit-descripcion"></div>
                    </div>

                    {{-- 3 FOTOS EDITAR --}}
                    <div class="af-span-2 af-box">
                        <div style="font-size:12px;font-weight:800;display:block;margin-bottom:8px;color:#1e293b;">
                            📷 Fotografías del Activo <span id="edit-lbl-fotos-info" style="font-weight:600;color:#64748b;">(Dejar vacío para mantener)</span>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
                            <div>
                                <label class="af-label">Foto Frontal</label>
                                <div id="edit-preview-frontal" style="margin-bottom:4px;font-size:10px;color:#64748b;"></div>
                                <input type="file" name="imagen" id="edit-imagen" accept="image/*" class="af-input" style="padding:6px;font-size:11px;">
                            </div>
                            <div id="edit-bloque-lateral">
                                <label class="af-label">Foto Lateral</label>
                                <div id="edit-preview-lateral" style="margin-bottom:4px;font-size:10px;color:#64748b;"></div>
                                <input type="file" name="imagen_lateral" id="edit-imagen_lateral" accept="image/*" class="af-input" style="padding:6px;font-size:11px;">
                            </div>
                            <div id="edit-bloque-detalle">
                                <label class="af-label">Foto Detalle</label>
                                <div id="edit-preview-detalle" style="margin-bottom:4px;font-size:10px;color:#64748b;"></div>
                                <input type="file" name="imagen_detalle" id="edit-imagen_detalle" accept="image/*" class="af-input" style="padding:6px;font-size:11px;">
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:10px;border-top:1px solid #e2e8f0;padding-top:16px;margin-top:20px;">
                    <button type="button" onclick="closeEditActivoModal()" style="background:#e2e8f0;color:#475569;border:none;padding:10px 18px;border-radius:8px;font-weight:700;cursor:pointer;">Cancelar</button>
                    <button type="submit" id="btn-actualizar-activo" style="background:#2563eb;color:white;border:none;padding:10px 18px;border-radius:8px;font-weight:700;cursor:pointer;">💾 Actualizar Activo</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function mostrarError(idCampo, mensaje) {
            const div = document.querySelector(`.af-error[data-for="${idCampo}"]`);
            const campo = document.getElementById(idCampo);
            if (div) { div.textContent = '⚠️ ' + mensaje; div.classList.add('visible'); }
            if (campo) campo.classList.add('af-input-error');
        }

        function limpiarError(idCampo) {
            const div = document.querySelector(`.af-error[data-for="${idCampo}"]`);
            const campo = document.getElementById(idCampo);
            if (div) { div.textContent = ''; div.classList.remove('visible'); }
            if (campo) campo.classList.remove('af-input-error');
        }

        function limpiarTodosLosErrores() {
            document.querySelectorAll('.af-error').forEach(el => { el.textContent = ''; el.classList.remove('visible'); });
            document.querySelectorAll('.af-input-error').forEach(el => el.classList.remove('af-input-error'));
        }

        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (!preview) return;
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">`;
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.innerHTML = `<span style="color:#94a3b8;font-size:11px;">Sin imagen</span>`;
            }
        }

        function aplicarTipoBien(tipo, prefijo) {
            const esFijo = tipo === 'ACTIVO_FIJO';
            const reqMarca = document.getElementById(prefijo + '-req-marca');
            const reqModelo = document.getElementById(prefijo + '-req-modelo');
            const reqSerie = document.getElementById(prefijo + '-req-serie');

            if (reqMarca) reqMarca.classList.toggle('visible', esFijo);
            if (reqModelo) reqModelo.classList.toggle('visible', esFijo);
            if (reqSerie) reqSerie.classList.toggle('visible', esFijo);

            const ayuda = document.getElementById(prefijo === 'crear' ? 'ayuda-tipo-bien' : 'edit-ayuda-tipo-bien');
            if (ayuda) {
                ayuda.textContent = esFijo ? 'Activo Fijo: marca, modelo y serie obligatorios (3 fotos).' : 'No Activo: marca, modelo y serie opcionales.';
            }

            if (!esFijo) {
                ['marca', 'modelo', 'numero_serie'].forEach(c => {
                    const id = `${prefijo}-input-${c === 'numero_serie' ? 'serie' : c}`;
                    limpiarError(id);
                });
            }
        }

        function cambiarTipoBien(tipo) { aplicarTipoBien(tipo, 'crear'); }
        function cambiarTipoBienEdit(tipo) { aplicarTipoBien(tipo, 'edit'); }

        /* ⭐ FUNCIONES RV (SELECTOR SI/NO) */
        function setRvCrear(valor) {
            const si = document.getElementById('crear-rv-si');
            const no = document.getElementById('crear-rv-no');
            const lblSi = document.getElementById('crear-rv-si-label');
            const lblNo = document.getElementById('crear-rv-no-label');
            if (si) si.checked = (valor === 'SI');
            if (no) no.checked = (valor === 'NO');
            if (lblSi) lblSi.classList.toggle('selected', valor === 'SI');
            if (lblNo) lblNo.classList.toggle('selected', valor === 'NO');
        }

        function setRvEditar(valor) {
            const si = document.getElementById('edit-rv-si');
            const no = document.getElementById('edit-rv-no');
            const lblSi = document.getElementById('edit-rv-si-label');
            const lblNo = document.getElementById('edit-rv-no-label');
            if (si) si.checked = (valor === 'SI');
            if (no) no.checked = (valor === 'NO');
            if (lblSi) lblSi.classList.toggle('selected', valor === 'SI');
            if (lblNo) lblNo.classList.toggle('selected', valor === 'NO');
        }

        function filtrarCustodiosModal(texto) {
            const filtro = texto.toLowerCase().trim();
            const select = document.getElementById('crear-id-custodio');
            for (let i = 0; i < select.options.length; i++) {
                const opt = select.options[i];
                const busqueda = opt.getAttribute('data-busqueda') || "";
                const coincideTexto = busqueda.includes(filtro);
                opt.style.display = coincideTexto ? "" : "none";
            }
        }

        function filtrarCustodiosEditModal(texto) {
            const filtro = texto.toLowerCase().trim();
            const select = document.getElementById('edit-id-custodio');
            for (let i = 0; i < select.options.length; i++) {
                const opt = select.options[i];
                const busqueda = opt.getAttribute('data-busqueda') || "";
                opt.style.display = busqueda.includes(filtro) ? "" : "none";
            }
        }

        function validar(tipoBien, prefijo) {
            limpiarTodosLosErrores();
            let hayErrores = false;
            const esFijo = tipoBien === 'ACTIVO_FIJO';

            const requeridos = [
                { id: prefijo + '-codigo-activo', msg: 'El código es obligatorio.' },
                { id: prefijo + '-id-categoria', msg: 'Selecciona una categoría.' },
                { id: prefijo + '-cantidad', msg: 'La cantidad es obligatoria.' },
                { id: prefijo + '-fecha-adquisicion', msg: 'La fecha es obligatoria.' },
                { id: prefijo + '-nombre', msg: 'El nombre es obligatorio.' },
                { id: prefijo + '-id-fuente', msg: 'Selecciona una fuente.' },
                { id: prefijo + '-estado-fisico', msg: 'Selecciona un estado físico.' },
                { id: prefijo + '-valor-adquisicion', msg: 'El valor es obligatorio.' },
                { id: prefijo + '-id-custodio', msg: 'Selecciona un custodio.' },
                { id: prefijo + '-descripcion', msg: 'La descripción es obligatoria.' },
            ];

            if (esFijo) {
                requeridos.push(
                    { id: prefijo + '-input-marca', msg: 'La marca es obligatoria.' },
                    { id: prefijo + '-input-modelo', msg: 'El modelo es obligatorio.' },
                    { id: prefijo + '-input-serie', msg: 'El N° de serie es obligatorio.' }
                );
            }

            requeridos.forEach(({ id, msg }) => {
                const campo = document.getElementById(id);
                if (!campo) return;
                if ((campo.value || '').trim() === '') {
                    mostrarError(id, msg);
                    hayErrores = true;
                }
            });

            return !hayErrores;
        }

        async function openActivoModal(event) {
            if (event) { event.stopPropagation(); event.stopImmediatePropagation(); }
            const form = document.getElementById('activo-modal-form');
            if (form) form.reset();
            limpiarTodosLosErrores();
            const selectTipo = document.getElementById('select-tipo-bien');
            if (selectTipo) selectTipo.value = 'ACTIVO_FIJO';
            cambiarTipoBien('ACTIVO_FIJO');
            // ⭐ Reset RV a "NO" por defecto
            setRvCrear('NO');
            const idAmbiente = document.getElementById('crear-id-ambiente')?.value || '';
            const modal = document.getElementById('activo-modal');
            if (modal) {
                modal.style.display = 'flex';
                modal.dataset.opened = Date.now();
                modal.dataset.lock = '1';
            }
            try {
                await cargarCustodiosModal(idAmbiente);
            } finally {
                if (modal) {
                    setTimeout(() => {
                        modal.dataset.lock = '0';
                        delete modal.dataset.lock;
                    }, 800);
                }
            }
        }

        async function cargarCustodiosModal(idAmbiente) {
            const select = document.getElementById('crear-id-custodio');
            if (!select) return;
            select.innerHTML = '<option value="">— Cargando custodios del ambiente —</option>';
            if (!idAmbiente) {
                select.innerHTML = '<option value="">— Seleccione ambiente primero —</option>';
                return;
            }
            try {
                const res = await fetch(`/activos/custodios/${idAmbiente}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                select.innerHTML = '<option value="">— Seleccione custodio del ambiente —</option>';
                let grupoActual = null;
                (data || []).forEach(c => {
                    const grupo = c.asignado_en_ambiente ? 'ambiente' : 'otros';
                    if (grupo !== grupoActual) {
                        const encabezado = document.createElement('option');
                        encabezado.textContent = grupo === 'ambiente'
                            ? '── Custodios asignados en este ambiente ──'
                            : '── Otros usuarios activos ──';
                        encabezado.disabled = true;
                        encabezado.style.fontWeight = '700';
                        select.appendChild(encabezado);
                        grupoActual = grupo;
                    }
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = `${String(c.id) === String(idCustodioSeleccionado) ? '✓ ' : ''}${c.texto}${c.asignado_en_ambiente ? ' · Asignado aquí' : ''}`;
                    opt.setAttribute('data-busqueda', c.busqueda || '');
                    if (String(c.id) === String(idCustodioSeleccionado)) {
                        opt.selected = true;
                        opt.style.backgroundColor = '#dcfce7';
                        opt.style.color = '#166534';
                    }
                    select.appendChild(opt);
                });
            } catch (e) {
                console.error(e);
                select.innerHTML = '<option value="">— Error al cargar custodios —</option>';
            }
        }

        function closeActivoModal(e) {
            const modal = document.getElementById('activo-modal');
            if (!modal) return;
            if (modal.dataset.lock === '1') return;
            if (e && e.target && e.target.id !== 'activo-modal') return;
            if (modal.dataset.opened && (Date.now() - parseInt(modal.dataset.opened)) < 250) {
                return;
            }
            modal.style.display = 'none';
        }

        document.getElementById('activo-modal-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const tipoBien = document.getElementById('select-tipo-bien').value;
            if (!validar(tipoBien, 'crear')) return;

            const form = this;
            const btn = document.getElementById('btn-guardar-activo');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '⏳ Guardando...';
            }

            try {
                const formData = new FormData(form);
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    credentials: 'same-origin',
                });
                const json = await res.json();
                if (res.ok && json.success !== false) {
                    closeActivoModal();
                    mostrarToast('✅ Activo creado correctamente. Recargando...', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    mostrarErroresServidor(json, 'crear');
                }
            } catch (err) {
                console.error(err);
                mostrarToast('⚠️ Error de conexión.', 'error');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '💾 Guardar Activo';
                }
            }
        });

        async function openEditActivoModal(idActivo, event) {
            if (event) { event.stopPropagation(); event.preventDefault(); event.stopImmediatePropagation(); }
            try {
                const res = await fetch(`{{ url('/activos') }}/${idActivo}/edit`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('Error');
                const data = await res.json();

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

                // ⭐ Cargar valor RV
                setRvEditar(data.rv === 'SI' ? 'SI' : 'NO');

                document.getElementById('edit-preview-frontal').innerHTML = data.imagen ? `✓ Foto frontal guardada` : `Sin foto frontal`;
                document.getElementById('edit-preview-lateral').innerHTML = data.imagen_lateral ? `✓ Foto lateral guardada` : `Sin foto lateral`;
                document.getElementById('edit-preview-detalle').innerHTML = data.imagen_detalle ? `✓ Foto detalle guardada` : `Sin foto detalle`;

                cambiarTipoBienEdit(data.tipo_bien || 'ACTIVO_FIJO');
                limpiarTodosLosErrores();

                document.getElementById('edit-activo-modal-form').action = `{{ url('/activos') }}/${idActivo}`;

                const modal = document.getElementById('edit-activo-modal');
                if (modal) {
                    modal.style.display = 'flex';
                    modal.dataset.opened = Date.now();
                    modal.dataset.lock = '1';
                }
                try {
                    await cargarCustodiosEditModal(data.id_ambiente || '', data.id_custodio || '');
                } finally {
                    if (modal) {
                        setTimeout(() => {
                            modal.dataset.lock = '0';
                            delete modal.dataset.lock;
                        }, 800);
                    }
                }
            } catch (err) {
                console.error(err);
                mostrarToast('❌ Error al cargar el activo', 'error');
            }
        }

        async function cargarCustodiosEditModal(idAmbiente, idCustodioSeleccionado = '') {
            const select = document.getElementById('edit-id-custodio');
            if (!select) return;
            select.innerHTML = '<option value="">— Cargando custodios del ambiente —</option>';
            if (!idAmbiente) {
                select.innerHTML = '<option value="">— Seleccione ambiente primero —</option>';
                return;
            }
            try {
                const res = await fetch(`/activos/custodios/${idAmbiente}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                select.innerHTML = '<option value="">— Seleccione custodio del ambiente —</option>';
                (data || []).forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.texto;
                    opt.setAttribute('data-busqueda', c.busqueda || '');
                    select.appendChild(opt);
                });
                if (idCustodioSeleccionado) {
                    select.value = idCustodioSeleccionado;
                }
                select.addEventListener('change', () => {
                    Array.from(select.options).forEach(opt => {
                        if (!opt.value) return;
                        const marcado = opt.value === select.value;
                        const texto = opt.textContent.replace(/^✓\s*/, '');
                        opt.textContent = marcado ? `✓ ${texto}` : texto;
                        opt.style.backgroundColor = marcado ? '#dcfce7' : '';
                        opt.style.color = marcado ? '#166534' : '';
                    });
                }, { once: true });
            } catch (e) {
                console.error(e);
                select.innerHTML = '<option value="">— Error al cargar custodios —</option>';
            }
        }

        function closeEditActivoModal(e) {
            const modal = document.getElementById('edit-activo-modal');
            if (!modal) return;
            if (modal.dataset.lock === '1') return;
            if (e && e.target && e.target.id !== 'edit-activo-modal') return;
            if (modal.dataset.opened && (Date.now() - parseInt(modal.dataset.opened)) < 250) {
                return;
            }
            modal.style.display = 'none';
        }

        // Bloquear cierre forzado por JS global mientras el modal está abriendo/cargando
        document.addEventListener('DOMContentLoaded', function() {
            const modalNuevo = document.getElementById('activo-modal');
            if (modalNuevo) {
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.attributeName === 'style') {
                            if (modalNuevo.dataset.lock === '1' && modalNuevo.style.display === 'none') {
                                modalNuevo.style.display = 'flex';
                            }
                        }
                    });
                });
                observer.observe(modalNuevo, { attributes: true });
            }

            const modalEditar = document.getElementById('edit-activo-modal');
            if (modalEditar) {
                const observer2 = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.attributeName === 'style') {
                            if (modalEditar.dataset.lock === '1' && modalEditar.style.display === 'none') {
                                modalEditar.style.display = 'flex';
                            }
                        }
                    });
                });
                observer2.observe(modalEditar, { attributes: true });
            }
        });

        document.getElementById('edit-activo-modal-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const tipoBien = document.getElementById('edit-select-tipo-bien').value;
            if (!validar(tipoBien, 'edit')) return;

            const form = this;
            const btn = document.getElementById('btn-actualizar-activo');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '⏳ Actualizando...';
            }

            try {
                const formData = new FormData(form);
                formData.append('_method', 'PUT');
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    credentials: 'same-origin',
                });
                const json = await res.json();
                if (res.ok && json.success !== false) {
                    closeEditActivoModal();
                    mostrarToast('✅ Activo actualizado correctamente. Recargando...', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    mostrarErroresServidor(json, 'edit');
                }
            } catch (err) {
                console.error(err);
                mostrarToast('⚠️ Error de conexión.', 'error');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '💾 Actualizar Activo';
                }
            }
        });

        function mostrarErroresServidor(json, prefijo) {
            limpiarTodosLosErrores();
            if (!json.errors) return;
            Object.keys(json.errors).forEach(campo => {
                const mensaje = json.errors[campo][0];
                const map = {
                    'codigo_activo': prefijo + '-codigo-activo',
                    'nombre': prefijo + '-nombre',
                    'id_categoria': prefijo + '-id-categoria',
                    'id_fuente': prefijo + '-id-fuente',
                    'id_custodio': prefijo + '-id-custodio',
                    'estado_fisico': prefijo + '-estado-fisico',
                    'marca': prefijo + '-input-marca',
                    'modelo': prefijo + '-input-modelo',
                    'numero_serie': prefijo + '-input-serie',
                };
                if (map[campo] && document.getElementById(map[campo])) {
                    mostrarError(map[campo], mensaje);
                }
            });
        }

        function confirmarEliminarActivo(id, codigo, nombre) {
            Swal.fire({
                title: '¿Eliminar activo?',
                html: `<b>"${nombre}"</b><br><span style="font-size:12px;color:#64748b;">${codigo}</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `{{ url('/activos') }}/${id}`;
                    form.innerHTML = `@csrf @method('DELETE')`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function mostrarToast(mensaje, tipo = 'success') {
            const bg = tipo === 'success' ? '#dcfce7' : '#fee2e2';
            const color = tipo === 'success' ? '#166534' : '#991b1b';
            const toast = document.createElement('div');
            toast.style.cssText = `position:fixed;top:20px;right:20px;z-index:99999;background:${bg};color:${color};padding:12px 20px;border-radius:10px;font-weight:700;box-shadow:0 4px 12px rgba(0,0,0,0.1);`;
            toast.textContent = mensaje;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        document.addEventListener('DOMContentLoaded', () => {
            cambiarTipoBien('ACTIVO_FIJO');
            cambiarTipoBienEdit('ACTIVO_FIJO');
            setRvCrear('NO');
            setRvEditar('NO');
        });
    </script>
@endsection
