@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
<div class="container-fluid px-4 py-4">
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

    <header style="background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);padding:28px;border-radius:16px;color:white;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px;margin-bottom:24px;">
        <div>
            <span style="background:rgba(255,255,255,.2);padding:5px 12px;border-radius:20px;font-size:11px;font-weight:800;text-transform:uppercase;">Catálogo compartido</span>
            <h1 style="font-size:28px;font-weight:800;margin:8px 0 4px;">Gestión de categorías</h1>
            <p style="opacity:.9;font-size:14px;margin:0;">Selecciona una categoría por su ID al registrar un activo.</p>
        </div>
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);padding:14px 20px;border-radius:12px;text-align:center;min-width:110px;">
                <div style="font-size:24px;font-weight:800;">{{ $categorias->total() }}</div>
                <div style="font-size:11px;font-weight:700;opacity:.9;text-transform:uppercase;margin-top:4px;">Categorías</div>
            </div>
            @if (auth()->user()?->esAdmin())
                <a href="{{ route('categorias.create') }}" style="background:#fff;color:#1d4ed8;padding:12px 16px;border-radius:9px;font-weight:800;text-decoration:none;">＋ Crear categoría</a>
            @endif
        </div>
    </header>

    <section style="background:white;border-radius:16px;border:1px solid #e2e8f0;padding:24px;">
        <h2 style="font-size:18px;font-weight:800;color:#0f172a;margin:0 0 18px;">Categorías registradas</h2>

        @if ($categorias->isNotEmpty())
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
                @foreach ($categorias as $categoria)
                    <article style="border:1px solid #e2e8f0;border-radius:12px;padding:18px;display:flex;flex-direction:column;gap:12px;">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
                            <div>
                                <span style="display:inline-block;background:#eff6ff;color:#1d4ed8;padding:3px 8px;border-radius:6px;font-size:11px;font-weight:800;">ID {{ $categoria->id_categoria }}</span>
                                <h3 style="font-size:15px;font-weight:800;color:#0f172a;margin:8px 0 4px;">{{ $categoria->nombre }}</h3>
                            </div>
                            <span style="white-space:nowrap;background:#f1f5f9;color:#334155;padding:5px 8px;border-radius:6px;font-size:11px;font-weight:800;">{{ $categoria->activos_count }} activos</span>
                        </div>

                        @if (auth()->user()?->esAdmin())
                            <div style="display:flex;gap:8px;">
                                <a href="{{ route('categorias.edit', $categoria->id_categoria) }}" style="flex:1;text-align:center;background:#f59e0b;color:#111827;padding:9px 12px;border-radius:7px;font-size:12px;font-weight:800;text-decoration:none;">Editar</a>
                                <form method="POST" action="{{ route('categorias.destroy', $categoria->id_categoria) }}" onsubmit="return confirm('¿Eliminar esta categoría?');" style="flex:1;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" @disabled($categoria->activos_count > 0) style="width:100%;background:#dc2626;color:white;border:0;padding:9px 12px;border-radius:7px;font-size:12px;font-weight:800;cursor:{{ $categoria->activos_count > 0 ? 'not-allowed' : 'pointer' }};opacity:{{ $categoria->activos_count > 0 ? '.5' : '1' }};">
                                        {{ $categoria->activos_count > 0 ? 'Tiene activos' : 'Eliminar' }}
                                    </button>
                                </form>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>

            @if ($categorias->hasPages())
                <div style="margin-top:20px;">{{ $categorias->links() }}</div>
            @endif
        @else
            <p style="padding:40px 20px;text-align:center;color:#64748b;">No hay categorías registradas.</p>
        @endif
    </section>
</div>
@endsection
