@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">

<style>
    /* ============================================ */
    /* ICONOS SVG PROFESIONALES                     */
    /* ============================================ */
    .btn-accion {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.18s ease;
        padding: 0;
        background: transparent;
    }

    .btn-accion svg {
        display: block;
        transition: transform 0.18s ease;
    }

    .btn-accion:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.12);
    }

    .btn-accion:hover svg {
        transform: scale(1.1);
    }

    .btn-accion:active {
        transform: translateY(0);
    }

    /* ✏️ Editar (amarillo) */
    .btn-editar {
        background: #fef9c3;
        color: #ca8a04;
        border-color: #fde047;
    }
    .btn-editar:hover {
        background: #ca8a04;
        color: white;
        border-color: #ca8a04;
    }

    /* 📧 Email (azul) */
    .btn-email {
        background: #dbeafe;
        color: #2563eb;
        border-color: #bfdbfe;
    }
    .btn-email:hover {
        background: #2563eb;
        color: white;
        border-color: #2563eb;
    }

    /* 🔲 QR (púrpura) */
    .btn-qr {
        background: #f3e8ff;
        color: #7c3aed;
        border-color: #ddd6fe;
    }
    .btn-qr:hover {
        background: #7c3aed;
        color: white;
        border-color: #7c3aed;
    }

    /* 🗑️ Eliminar (rojo) */
    .btn-eliminar {
        background: #fee2e2;
        color: #dc2626;
        border-color: #fecaca;
    }
    .btn-eliminar:hover {
        background: #dc2626;
        color: white;
        border-color: #dc2626;
    }

    /* 🚫 Deshabilitado (gris) */
    .btn-disabled {
        background: #f1f5f9;
        color: #94a3b8;
        border-color: #e2e8f0;
        cursor: not-allowed;
        opacity: 0.6;
    }
    .btn-disabled:hover {
        transform: none;
        box-shadow: none;
    }
</style>

    {{-- MENSAJES --}}
    @if (session('success'))
        <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#166534;padding:14px 20px;border-radius:12px;margin-bottom:20px;font-weight:700;">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:14px 20px;border-radius:12px;margin-bottom:20px;font-weight:700;">
            ✗ {{ session('error') }}
        </div>
    @endif

    {{-- ENCABEZADO --}}
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
        <div>
            <h2 style="font-size:24px;font-weight:800;color:#0f172a;margin:0;">Gestión de Usuarios</h2>
            <p style="color:#64748b;font-size:14px;margin:4px 0 0 0;">Administra usuarios y sus permisos de verificación.</p>
        </div>
        <a href="{{ route('usuarios.create') }}"
           style="background:#2563eb;color:white;padding:10px 18px;border-radius:10px;font-weight:700;text-decoration:none;">
            + Nuevo Usuario
        </a>
    </div>

    {{-- FILTROS --}}
    <div style="background:white;border-radius:12px;border:1px solid #e2e8f0;padding:16px 20px;margin-bottom:20px;display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
        <form method="GET" action="{{ route('usuarios.index') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;flex:1;">
            <div style="min-width:220px;">
                <label style="font-size:12px;font-weight:700;display:block;margin-bottom:4px;color:#334155;">Filtrar por Rol</label>
                <select name="rol" onchange="this.form.submit()" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid #cbd5e1;background:#fff;">
                    <option value="">Todos los roles</option>
                    <option value="ADMINISTRADOR"    {{ request('rol') == 'ADMINISTRADOR'    ? 'selected' : '' }}>Administrador</option>
                    <option value="INVENTARIADOR"    {{ request('rol') == 'INVENTARIADOR'    ? 'selected' : '' }}>Inventariador</option>
                    <option value="AYUDANTE"         {{ request('rol') == 'AYUDANTE'         ? 'selected' : '' }}>Ayudante</option>
                    <option value="DOCENTE_CUSTODIO" {{ request('rol') == 'DOCENTE_CUSTODIO' ? 'selected' : '' }}>Docente Custodio</option>
                    <option value="JEFE_CARRERA"     {{ request('rol') == 'JEFE_CARRERA'     ? 'selected' : '' }}>Jefe de Carrera</option>
                    <option value="RECTOR"           {{ request('rol') == 'RECTOR'           ? 'selected' : '' }}>Rector</option>
                </select>
            </div>

            <div style="min-width:280px;flex:1;">
                <label style="font-size:12px;font-weight:700;display:block;margin-bottom:4px;color:#334155;">Buscar</label>
                <input type="text" name="buscar" value="{{ request('buscar') }}"
                       placeholder="Buscar por nombre, CI o email..."
                       style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid #cbd5e1;">
            </div>

            <button type="submit" style="background:#2563eb;color:white;border:none;padding:10px 20px;border-radius:8px;font-weight:700;cursor:pointer;">
                🔍 Filtrar
            </button>

            @if(request('rol') || request('buscar'))
                <a href="{{ route('usuarios.index') }}"
                   style="background:#e2e8f0;color:#475569;padding:10px 16px;border-radius:8px;font-weight:700;text-decoration:none;font-size:13px;">
                    ✕ Limpiar
                </a>
            @endif
        </form>
    </div>

    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:16px;">
        <button type="button" id="reenviar-seleccionados" style="background:#2563eb;color:#fff;border:0;padding:10px 14px;border-radius:8px;font-weight:700;cursor:pointer;">
            📧 Reenviar a seleccionados
        </button>
        <form method="POST" action="{{ route('usuarios.reenviar_credenciales_todos') }}" id="form-reenviar-todos" style="margin:0;">
            @csrf
            <button type="submit" style="background:#0f766e;color:#fff;border:0;padding:10px 14px;border-radius:8px;font-weight:700;cursor:pointer;">
                📧 Reenviar a todos
            </button>
        </form>
        <button type="button" id="eliminar-seleccionados" style="background:#dc2626;color:#fff;border:0;padding:10px 14px;border-radius:8px;font-weight:700;cursor:pointer;">
            🗑️ Eliminar seleccionados
        </button>
        <form method="POST" action="{{ route('usuarios.reenviar_credenciales_seleccionados') }}" id="form-reenviar-seleccionados" style="display:none;">
            @csrf
            <div class="bulk-usuarios-inputs"></div>
        </form>
        <form method="POST" action="{{ route('usuarios.destroy_seleccionados') }}" id="form-eliminar-seleccionados" style="display:none;">
            @csrf
            <div class="bulk-usuarios-inputs"></div>
        </form>
    </div>

    {{-- TABLA --}}
    <div style="background:white;border-radius:16px;border:1px solid #e2e8f0;padding:20px;box-shadow:0 4px 6px -1px rgba(0,0,0,.05);">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#f1f5f9;color:#334155;text-transform:uppercase;font-size:11px;">
                        <th style="padding:10px;text-align:center;"><input type="checkbox" id="seleccionar-todos-usuarios" aria-label="Seleccionar todos"></th>
                        <th style="padding:10px;text-align:left;">Ítems</th>
                        <th style="padding:10px;text-align:left;">C.I.</th>
                        <th style="padding:10px;text-align:left;">
                            <a href="{{ route('usuarios.index', array_merge(request()->query(), ['orden_nombre' => ($ordenNombre ?? 'asc') === 'asc' ? 'desc' : 'asc'])) }}"
                               style="color:#334155;text-decoration:none;display:inline-flex;align-items:center;gap:6px;"
                               title="Ordenar nombres alfabéticamente">
                                Nombre
                                <span style="font-size:15px;color:#2563eb;">
                                    {{ ($ordenNombre ?? 'asc') === 'asc' ? '↑ A-Z' : '↓ Z-A' }}
                                </span>
                            </a>
                        </th>
                        <th style="padding:10px;text-align:left;">Rol</th>
                        <th style="padding:10px;text-align:left;">Carrera</th>
                        <th style="padding:10px;text-align:center;">Estado</th>
                        <th style="padding:10px;text-align:center;">Puede Verificar</th>
                        <th style="padding:10px;text-align:center;width:200px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $u)
                        <tr style="border-bottom:1px solid #e2e8f0;">
                            <td style="padding:10px;text-align:center;"><input type="checkbox" class="usuario-checkbox" value="{{ $u->id_usuario }}"></td>
                            <td style="padding:10px;">
                                @forelse($u->items as $item)
                                    <span style="display:inline-block;background:#ecfeff;color:#155e75;border:1px solid #a5f3fc;border-radius:6px;padding:3px 7px;margin:2px;font-size:11px;font-weight:700;">
                                        {{ $item->numero_item }}
                                    </span>
                                @empty
                                    <span style="color:#94a3b8;font-size:12px;">Sin ítem asignado</span>
                                @endforelse
                            </td>
                            <td style="padding:10px;font-weight:600;">{{ $u->ci }}</td>
                            <td style="padding:10px;">
                                <div style="font-weight:700;color:#0f172a;">{{ $u->nombre_completo }}</div>
                                <small style="color:#64748b;">{{ $u->email }}</small>
                            </td>
                            <td style="padding:10px;">
                                @php
                                    $badges = [
                                        'ADMINISTRADOR' => '#2563eb',
                                        'INVENTARIADOR' => '#0f766e',
                                        'AYUDANTE'      => '#7c3aed',
                                        'DOCENTE_CUSTODIO' => '#be123c',
                                        'JEFE_CARRERA'  => '#b45200',
                                        'RECTOR'        => '#334155',
                                    ];
                                @endphp
                                <span style="font-size:12px;font-weight:700;color:{{ $badges[$u->rol] ?? '#555' }};">
                                    {{ $u->rol }}
                                </span>
                            </td>
                            <td style="padding:10px;">{{ $u->carrera->nombre ?? '—' }}</td>
                            <td style="padding:10px;text-align:center;">
                                @if($u->estado === 'ACTIVO')
                                    <span style="font-size:12px;color:#15803d;font-weight:700;">✓ Activo</span>
                                @else
                                    <span style="font-size:12px;color:#b91c1c;font-weight:700;">✗ Inactivo</span>
                                @endif
                            </td>

                            <td style="padding:10px;text-align:center;">
                                @if($u->esAdmin())
                                    <span style="background:#dcfce7;color:#166534;font-size:11px;font-weight:700;padding:4px 10px;border-radius:6px;">
                                        ✓ Siempre
                                    </span>
                                @else
                                    <form method="POST" action="{{ route('usuarios.toggle_verificar', $u->id_usuario) }}" style="display:inline;margin:0;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                style="background:{{ $u->puede_verificar ? '#dcfce7' : '#f1f5f9' }};
                                                       color:{{ $u->puede_verificar ? '#166534' : '#64748b' }};
                                                       border:1px solid {{ $u->puede_verificar ? '#86efac' : '#cbd5e1' }};
                                                       font-size:11px;font-weight:700;padding:5px 10px;border-radius:6px;cursor:pointer;">
                                            {{ $u->puede_verificar ? '✓ Habilitado' : '✗ Deshabilitado' }}
                                        </button>
                                    </form>
                                @endif
                            </td>

                            {{-- ACCIONES CON ICONOS SVG --}}
                            <td style="padding:10px;text-align:center;">
                                <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">

                                    {{-- ✏️ Editar --}}
                                    <a href="{{ route('usuarios.edit', $u->id_usuario) }}"
                                       class="btn-accion btn-editar"
                                       title="Editar usuario">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </a>

                                    {{-- 📧 Reenviar credenciales --}}
                                    <form method="POST"
                                          action="{{ route('usuarios.enviar_credenciales', $u->id_usuario) }}"
                                          class="form-reenviar"
                                          data-email="{{ $u->email }}"
                                          data-nombre="{{ $u->nombre_completo }}"
                                          style="display:inline;margin:0;">
                                        @csrf
                                        <button type="submit" class="btn-accion btn-email" title="Reenviar credenciales por email">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                                <polyline points="22,6 12,13 2,6"></polyline>
                                            </svg>
                                        </button>
                                    </form>

                                    {{-- 🔲 Ver QR --}}
                                    <a href="{{ route('usuarios.qr', $u->id_usuario) }}"
                                       target="_blank"
                                       class="btn-accion btn-qr"
                                       title="Ver QR de credencial">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                                            <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                                            <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                                            <path d="M14 14h3v3"></path>
                                            <path d="M21 14v3"></path>
                                            <path d="M14 21h3"></path>
                                            <path d="M21 21h-3v-3"></path>
                                        </svg>
                                    </a>

                                    {{-- 🗑️ Eliminar --}}
                                    @if($u->id_usuario !== auth()->id())
                                        <form method="POST"
                                              action="{{ route('usuarios.destroy', $u->id_usuario) }}"
                                              class="form-eliminar"
                                              data-nombre="{{ $u->nombre_completo }}"
                                              style="display:inline;margin:0;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-accion btn-eliminar" title="Eliminar usuario">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <span class="btn-accion btn-disabled" title="No puedes eliminarte a ti mismo">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                                            </svg>
                                        </span>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" style="padding:20px;text-align:center;color:#94a3b8;">No hay usuarios registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const seleccionarTodos = document.getElementById('seleccionar-todos-usuarios');
        const seleccionarUsuarios = () => Array.from(document.querySelectorAll('.usuario-checkbox:checked')).map(input => input.value);

        seleccionarTodos?.addEventListener('change', function() {
            document.querySelectorAll('.usuario-checkbox').forEach(input => {
                input.checked = this.checked;
            });
        });

        const enviarSeleccionados = document.getElementById('reenviar-seleccionados');
        const eliminarSeleccionados = document.getElementById('eliminar-seleccionados');

        const enviarFormularioMasivo = (formulario, titulo, texto, confirmButtonText) => {
            const ids = seleccionarUsuarios();
            if (ids.length === 0) {
                Swal.fire('Selecciona usuarios', 'Debes marcar al menos un usuario.', 'info');
                return;
            }

            const contenedor = formulario.querySelector('.bulk-usuarios-inputs');
            contenedor.innerHTML = '';
            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'usuarios[]';
                input.value = id;
                contenedor.appendChild(input);
            });

            Swal.fire({
                title: titulo,
                text: texto,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: formulario.id === 'form-eliminar-seleccionados' ? '#dc2626' : '#2563eb',
                cancelButtonColor: '#64748b',
                confirmButtonText,
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then(result => {
                if (result.isConfirmed) {
                    formulario.submit();
                }
            });
        };

        enviarSeleccionados.addEventListener('click', function() {
            enviarFormularioMasivo(
                document.getElementById('form-reenviar-seleccionados'),
                '¿Reenviar credenciales?',
                'Se generará una nueva contraseña temporal para cada usuario seleccionado y se enviará a su correo.',
                'Sí, reenviar'
            );
        });

        eliminarSeleccionados.addEventListener('click', function() {
            enviarFormularioMasivo(
                document.getElementById('form-eliminar-seleccionados'),
                '¿Eliminar usuarios seleccionados?',
                'No se eliminarán tu cuenta ni usuarios que todavía tengan activos asignados.',
                'Sí, eliminar'
            );
        });

        document.getElementById('form-reenviar-todos').addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: '¿Reenviar a todos?',
                text: 'Se generará una nueva contraseña temporal para todos los usuarios que tengan correo.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0f766e',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Sí, reenviar a todos',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then(result => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });

        // ⭐ Reenviar credenciales
        document.querySelectorAll('.form-reenviar').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const email  = this.dataset.email;
                const nombre = this.dataset.nombre;

                Swal.fire({
                    title: '¿Reenviar credenciales?',
                    html: `
                        <div style="text-align:left;background:#eff6ff;padding:12px;border-radius:8px;border:1px solid #bfdbfe;font-size:13px;">
                            <div><strong>Usuario:</strong> ${nombre}</div>
                            <div><strong>Email:</strong> ${email}</div>
                        </div>
                        <p style="margin-top:12px;color:#64748b;font-size:12px;">
                            Se generará una nueva contraseña temporal y se enviará al correo.
                        </p>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '📧 Sí, reenviar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then(result => {
                    if (result.isConfirmed) form.submit();
                });
            });
        });

        // ⭐ Eliminar usuario
        document.querySelectorAll('.form-eliminar').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const nombre = this.dataset.nombre;

                Swal.fire({
                    title: '¿Eliminar usuario?',
                    html: `
                        <div style="text-align:left;background:#fef2f2;padding:12px;border-radius:8px;border:1px solid #fecaca;font-size:13px;">
                            <div><strong>Usuario:</strong> ${nombre}</div>
                        </div>
                        <p style="margin-top:12px;color:#dc2626;font-size:12px;font-weight:600;">
                            ⚠️ Esta acción no se puede deshacer.
                        </p>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '🗑️ Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then(result => {
                    if (result.isConfirmed) form.submit();
                });
            });
        });

    });
</script>
@endsection