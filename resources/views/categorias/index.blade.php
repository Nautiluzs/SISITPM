@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">

    {{-- ============ MENSAJES ============ --}}
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

    {{-- ============ ENCABEZADO ============ --}}
    <div style="background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);padding:30px;border-radius:16px;color:white;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px;margin-bottom:25px;box-shadow:0 10px 15px -3px rgba(37,99,235,.2);">
        <div>
            <span style="background:rgba(255,255,255,.2);padding:5px 12px;border-radius:20px;font-size:11px;font-weight:800;text-transform:uppercase;">
                Categorías
            </span>
            <h1 style="font-size:28px;font-weight:800;margin:8px 0 4px 0;">
                {{ isset($ambiente) ? 'Categorías del Ambiente' : 'Todas las Categorías' }}
            </h1>
            <p style="opacity:.9;font-size:14px;margin:0;">
                {{ isset($ambiente) ? $ambiente->nombre : 'Administra las categorías del sistema' }}
            </p>
        </div>

        <div style="display:flex;gap:12px;">
            <div style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);padding:14px 20px;border-radius:12px;text-align:center;min-width:110px;">
                <div style="font-size:24px;font-weight:800;">{{ $categorias->total() }}</div>
                <div style="font-size:11px;font-weight:700;opacity:.9;text-transform:uppercase;margin-top:4px;">Categorías</div>
            </div>
            <div style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);padding:14px 20px;border-radius:12px;text-align:center;min-width:110px;">
                <div style="font-size:24px;font-weight:800;">{{ $categorias->sum('activos_count') }}</div>
                <div style="font-size:11px;font-weight:700;opacity:.9;text-transform:uppercase;margin-top:4px;">Activos</div>
            </div>
        </div>
    </div>

    {{-- ============ FILTROS ============ --}}
    <div style="background:white;border-radius:12px;border:1px solid #e2e8f0;padding:16px 20px;margin-bottom:20px;display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end;">
        <form method="GET" action="{{ route('categorias.index') }}" style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end;flex:1;">
            <div style="min-width:250px;">
                <label style="font-size:12px;font-weight:700;display:block;margin-bottom:4px;color:#334155;">Filtrar por Ambiente</label>
                <select name="id_ambiente" onchange="this.form.submit()" style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid #cbd5e1;background:#fff;">
                    <option value="">Todos los ambientes</option>
                    @foreach($ambientesFiltro as $amb)
                        <option value="{{ $amb->id_ambiente }}" {{ request('id_ambiente') == $amb->id_ambiente ? 'selected' : '' }}>
                            {{ $amb->nombre }} ({{ $amb->carrera->nombre ?? 'Sin carrera' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" style="background:#2563eb;color:white;border:none;padding:10px 20px;border-radius:8px;font-weight:700;cursor:pointer;">
                🔍 Filtrar
            </button>

            @if(request('id_ambiente'))
                <a href="{{ route('categorias.index') }}" style="background:#e2e8f0;color:#475569;padding:10px 16px;border-radius:8px;font-weight:700;text-decoration:none;font-size:13px;">
                    Limpiar
                </a>
            @endif
        </form>
    </div>

    {{-- ============ TARJETAS CUADRADAS ESTILO CARPETA ============ --}}
    <div style="background:white;border-radius:16px;border:1px solid #e2e8f0;padding:25px;box-shadow:0 4px 6px -1px rgba(0,0,0,.05);">
        <h3 style="font-size:18px;font-weight:800;color:#0f172a;margin:0 0 20px 0;">Listado de Categorías</h3>

        @if($categorias->count() > 0)
            <style>
                .folders-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                    gap: 16px;
                }
                .folder-card {
                    background: #ffffff;
                    border: 1px solid #e2e8f0;
                    border-radius: 14px;
                    padding: 20px;
                    text-align: left;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                }
                .folder-card:hover {
                    transform: translateY(-3px);
                    border-color: #2563eb;
                    box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.1);
                }
            </style>

            <div class="folders-grid">
                @foreach($categorias as $cat)
                    <div class="folder-card" style="position:relative;">
                        <span style="position:absolute;top:12px;left:12px;color:#000;font-size:18px;font-weight:900;z-index:2;">
                            {{ $cat->id_categoria }}
                        </span>
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-top: 24px;">
                                <span style="font-size: 32px;">📁</span>
                                <span style="background:#dcfce7;color:#166534;font-weight:800;font-size:11px;padding:3px 8px;border-radius:6px;">
                                    {{ $cat->activos_count ?? 0 }} activos
                                </span>
                            </div>

                            <div style="margin-bottom: 6px;">
                                <span style="background:#eff6ff;color:#2563eb;font-weight:700;font-size:10px;padding:2px 6px;border-radius:4px;">
                                    {{ $cat->codigo }}
                                </span>
                            </div>

                            <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0 0 6px 0;">{{ $cat->nombre }}</h3>
                            <p style="font-size: 12px; color: #475569; margin: 0 0 8px 0;">🏢 {{ $cat->ambiente->nombre ?? 'Sin ambiente' }}</p>

                            @if(!empty($cat->descripcion))
                                <p style="font-size: 11px; color: #64748b; font-style: italic; margin: 0 0 12px 0; background: #f8fafc; padding: 6px; border-radius: 6px; border: 1px solid #f1f5f9;">
                                    {{ \Illuminate\Support\Str::limit($cat->descripcion, 70) }}
                                </p>
                            @endif
                        </div>

                        {{-- Acciones --}}
                        <div style="display:flex; gap:6px; justify-content:flex-end; border-top:1px solid #f1f5f9; padding-top:10px;" onclick="event.stopPropagation();">
                            <a href="{{ route('categorias.show', $cat->id_categoria) }}"
                               style="background:#2563eb;color:#fff;padding:7px 12px;border-radius:8px;font-weight:700;text-decoration:none;font-size:12px;">
                                Ingresar
                            </a>
                            @if(auth()->check() && auth()->user()->esAdmin())
                                <a href="{{ route('ambientes.show', $cat->id_ambiente) }}?editar_categoria={{ $cat->id_categoria }}" title="Editar categoría"
                                   style="background:#f59e0b;color:#111827;padding:7px 10px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;font-size:11px;font-weight:800;">
                                    Editar
                                </a>

                                <form method="POST"
                                      action="{{ route('categorias.destroy', $cat->id_categoria) }}"
                                      onsubmit="return confirm('¿Eliminar la categoría {{ $cat->nombre }}?');"
                                      style="display:inline;margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Eliminar categoría"
                                            style="background:#ef0040;color:#fff;border:0;padding:7px 10px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:11px;font-weight:800;">
                                        Eliminar
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Paginación --}}
            @if($categorias->hasPages())
                <div style="margin-top:20px;">
                    {{ $categorias->links() }}
                </div>
            @endif
        @else
            <div style="text-align:center;padding:60px 20px;color:#94a3b8;font-weight:600;">
                <div style="font-size:48px;margin-bottom:12px;">📁</div>
                <h3 style="margin:0 0 6px 0;font-size:16px;color:#64748b;">No hay categorías registradas</h3>
                <p style="margin:0;font-size:13px;">Ve a un ambiente y crea categorías desde su panel.</p>
            </div>
        @endif
    </div>
</div>
@endsection
