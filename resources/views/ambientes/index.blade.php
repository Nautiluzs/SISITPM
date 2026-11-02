@extends('layouts.app')

@section('content')

@if(session('success'))
    <div style="background: #dcfce7; border: 1px solid #bbf7d0; color: #166534; padding: 14px 20px; border-radius: 12px; margin-bottom: 20px; font-weight: 700;">
        ✓ {{ session('success') }}
    </div>
@endif

<section class="hero-card" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); padding: 28px; border-radius: 18px; color: white; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 24px;">
    <div>
        <span style="display:inline-block;padding:5px 12px;border-radius:999px;background:rgba(255,255,255,0.2);font-size:11px;font-weight:800;text-transform:uppercase;">
            Ambientes
        </span>
        <h1 style="margin:10px 0 8px;font-size:26px;font-weight:800;">
            {{ isset($carrera) ? 'Ambientes de '.$carrera->nombre : 'Todos los ambientes' }}
        </h1>
        <p style="opacity:.9;font-size:14px;">Administra los espacios físicos donde se ubican los activos.</p>
    </div>
    <div style="display: flex; gap: 12px;">
        <div style="background: rgba(255,255,255,0.15); padding: 12px 20px; border-radius: 12px; text-align: center; min-width: 100px;">
            <div style="font-size: 22px; font-weight: 800;">{{ $ambientes->count() }}</div>
            <span style="font-size: 11px; font-weight: 700; opacity: .9; text-transform: uppercase;">Ambientes</span>
        </div>
        <div style="background: rgba(255,255,255,0.15); padding: 12px 20px; border-radius: 12px; text-align: center; min-width: 100px;">
            <div style="font-size: 22px; font-weight: 800;">{{ $ambientes->sum('activos_count') }}</div>
            <span style="font-size: 11px; font-weight: 700; opacity: .9; text-transform: uppercase;">Activos</span>
        </div>
    </div>
</section>

<section class="panel" style="background: white; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <div>
            <h2 style="margin:0; font-size: 18px; font-weight: 800; color: #0f172a;">Listado de ambientes</h2>
            <p style="margin:4px 0 0;color:#64748b;font-size:12px;">Usa los botones para abrir, editar o eliminar cada ambiente.</p>
        </div>
    </div>

    @if($ambientes->count())
        <div class="folders-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px;margin-top:18px;">
            @foreach($ambientes as $amb)
                <article class="folder-card" style="position:relative;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px;display:flex;flex-direction:column;justify-content:space-between;min-height:235px;transition:all .2s ease;">
                    <span style="position:absolute;top:12px;left:12px;color:#000;font-size:18px;font-weight:900;">{{ $amb->id_ambiente }}</span>
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding-top:22px;margin-bottom:12px;">
                            <span style="font-size:30px;">📂</span>
                            <span style="background:#dcfce7;color:#166534;font-size:10px;font-weight:800;padding:4px 8px;border-radius:6px;text-transform:uppercase;">Activa</span>
                        </div>
                        <span style="background:#eff6ff;color:#1d4ed8;padding:3px 7px;border-radius:5px;font-size:10px;font-weight:800;">{{ $amb->codigo ?? 'S/C' }}</span>
                        <h3 style="font-size:15px;font-weight:800;color:#0f172a;margin:9px 0 5px;">{{ $amb->nombre }}</h3>
                        <p style="font-size:11px;color:#64748b;margin:0 0 12px;">Bloque {{ $amb->bloque ?? 'N/A' }} · Piso {{ $amb->piso ?? 'N/D' }}</p>
                        <div style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:8px;padding:8px;font-size:11px;color:#475569;">
                            <strong>📦 Activos registrados:</strong> {{ $amb->activos_count ?? 0 }}
                        </div>
                    </div>
                    <div style="display:flex;gap:6px;border-top:1px solid #f1f5f9;padding-top:10px;margin-top:14px;">
                        <a href="{{ route('ambientes.show', ['id' => $amb->id_ambiente]) }}" style="flex:1;text-align:center;padding:8px 6px;border-radius:8px;background:#1e293b;color:#fff;text-decoration:none;font-size:11px;font-weight:800;">Ingresar</a>
                        @if(auth()->check() && auth()->user()->esAdmin())
                            <a href="{{ route('carreras.show', $carrera->id_carrera) }}?editar_ambiente={{ $amb->id_ambiente }}" style="flex:1;text-align:center;padding:8px 6px;border-radius:8px;background:#f59e0b;color:#111827;text-decoration:none;font-size:11px;font-weight:800;">Editar</a>
                            <form method="POST" action="{{ route('ambientes.destroy', [$carrera->id_carrera, $amb->id_ambiente]) }}" style="flex:1;margin:0;" onsubmit="return confirm('¿Eliminar este ambiente?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="width:100%;padding:8px 6px;border:0;border-radius:8px;background:#ef0040;color:#fff;font-size:11px;font-weight:800;cursor:pointer;">Eliminar</button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <p style="text-align:center; color:#94a3b8; padding: 30px; font-weight:600;">No hay ambientes registrados.</p>
    @endif
</section>
@endsection
